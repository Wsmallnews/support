<?php

namespace Wsmallnews\Support\Features\Feed;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Wsmallnews\Support\Features\Site\Concerns\AssertsSiteOwnership;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Http\Controllers\FeedController;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Named RSS feed registry.
 *
 * Storage stays keyed by module then site. A site's feed space belongs to exactly
 * one module (the plugin that owns the site), so writes keep the module argument
 * (ownership is asserted) while reads take only the site and derive the module.
 */
class FeedRegistry
{
    use AssertsSiteOwnership;

    /**
     * @var Collection<string, Collection<string, array<string, mixed>>> module => siteId => options
     */
    protected Collection $configs;

    /**
     * @var Collection<string, Collection<string, Collection<string, array<string, mixed>>>> module => siteId => name => feed
     */
    protected Collection $feeds;

    /**
     * @var array<string, array<string, string>> module => siteId => fully-prefixed route name
     */
    protected array $feedRouteNames = [];

    public function __construct()
    {
        $this->configs = collect();
        $this->feeds = collect();
    }

    /*
     * ---------- Registration ----------
     */

    /** Declare feed options for one site (merged shallowly, later calls win). */
    public function config(string $module, array $config, Site $site): static
    {
        $this->assertOwnership($module, $site);

        $sites = $this->configs->get($module, collect());
        $sites->put($site->id(), array_merge($sites->get($site->id(), []), $config));
        $this->configs->put($module, $sites);

        return $this;
    }

    /**
     * Batch of named feeds: one ownership check, then the same write as register()
     * per entry (a repeated name overrides, an empty array registers nothing).
     *
     * @param  array<string, array<string, mixed>>  $feeds  name => definition
     */
    public function registers(string $module, array $feeds, Site $site): static
    {
        $this->assertOwnership($module, $site);

        foreach ($feeds as $name => $feed) {
            $this->putFeed($module, $site, (string) $name, $feed);
        }

        return $this;
    }

    /**
     * Register a named feed, replacing the previous definition when the name repeats.
     *
     * @param  array{title?: Closure|string, label?: Closure|string, description?: Closure|string|null, link?: Closure|string, limit?: Closure|int, items: Closure}  $feed
     */
    public function register(string $module, string $name, array $feed, Site $site): static
    {
        $this->assertOwnership($module, $site);

        $this->putFeed($module, $site, $name, $feed);

        return $this;
    }

    /**
     * A feed occupies the site's URL space ({prefix}/feed/{name}) and its route name
     * comes from the site group, so a second module on the same site would shadow the
     * first. Only the module owning the site may register feeds on it.
     */
    public function routes(string $module, Site $site): void
    {
        $this->assertOwnership($module, $site);

        // Site declaration wins, then sn-support.feeds.
        $uri = trim((string) $this->resolveConfig($site, 'uri', 'feed'), '/');

        Route::get($uri, [FeedController::class, 'moduleIndex'])
            ->defaults('feed_site', $site->id())
            ->name('feed');

        $show = Route::get($uri . '/{name}', [FeedController::class, 'moduleShow'])
            ->defaults('feed_site', $site->id())
            ->where('name', '[a-z0-9\-]+')
            ->name('feed.show');

        // One route per module and site; feedUrl() looks it up directly.
        $this->feedRouteNames[$module][$site->id()] = (string) $show->getName();
    }

    /*
     * ---------- Reads ----------
     */

    /** Read the site's feed options (owning module derived from the site). */
    public function getConfig(Site $site, ?string $key = null, mixed $default = null): mixed
    {
        $config = (array) $this->configs->get($this->siteModule($site), collect())->get($site->id(), []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /** Resolve in order: site declaration, global sn-support.feeds.*, then $default. */
    public function resolveConfig(Site $site, string $key, mixed $default = null): mixed
    {
        return $this->getConfig($site, $key)
            ?? SupportUtils::getFeedsConfig($key, $default);
    }

    /**
     * Feeds of one site, keyed by feed name.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function getFeeds(Site $site): Collection
    {
        return $this->feeds->get($this->siteModule($site), collect())->get($site->id(), collect());
    }

    /**
     * Resolve a feed of the given site by name; null when missing.
     *
     * @return array<string, mixed>|null
     */
    public function getFeed(string $name, Site $site): ?array
    {
        return $this->getFeeds($site)->get($name);
    }

    /** RSS autodiscovery <link> tags for the site, one per feed. */
    public function autodiscoveryTags(Site $site): HtmlString
    {
        $lines = [];

        foreach ($this->getFeeds($site) as $name => $feed) {
            $lines[] = sprintf(
                '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
                e((string) value($feed['title'] ?? null ?: config('app.name'))),
                e($this->feedUrl($name, $site)),
            );
        }

        return new HtmlString(implode("\n", $lines));
    }

    /** Subscription URL for one feed of the given site. */
    public function feedUrl(string $name, Site $site): string
    {
        $routeName = $this->feedRouteNames[$this->siteModule($site)][$site->id()] ?? null;

        return $routeName ? sn_route($routeName, ['name' => $name]) : '';
    }

    /*
     * ---------- Rendering ----------
     */

    /** Render RSS 2.0: the site's aggregate stream when name is null, its named feed otherwise. */
    public function render(Site $site, ?string $name = null): HtmlString
    {
        $ttl = (int) $this->resolveConfig($site, 'cache_ttl', 3600);
        $xml = $ttl <= 0
            ? $this->compile($site, $name)
            : Cache::remember($this->cacheKey($site, $name), $ttl, fn (): string => $this->compile($site, $name));

        return new HtmlString($xml);
    }

    /** Drop the render cache of every registered module and site. */
    public function flush(): static
    {
        $host = request()->getHost();
        $tenant = current_tenant()?->getKey() ?? 'global';
        $names = $this->allFeedNames();

        foreach ($this->flushScopes() as [$module, $siteId]) {
            $this->flushScope($tenant, $module, $siteId, $host, $names);
        }

        return $this;
    }

    /*
     * ---------- Internals ----------
     */

    /** @param  array<string, mixed>  $feed */
    protected function putFeed(string $module, Site $site, string $name, array $feed): void
    {
        $sites = $this->feeds->get($module, collect());
        $feeds = $sites->get($site->id(), collect());
        $feeds->put($name, $this->prepare($module, $site, $name, $feed));
        $sites->put($site->id(), $feeds);
        $this->feeds->put($module, $sites);
    }

    /** @param  array<string, mixed>  $feed */
    protected function prepare(string $module, Site $site, string $name, array $feed): array
    {
        if (! preg_match('/^[a-z0-9\-]+$/', $name)) {
            throw new InvalidArgumentException(__('sn-support::support.feed.name_invalid', ['name' => $name]));
        }

        if (! ($feed['items'] ?? null) instanceof Closure) {
            throw new InvalidArgumentException(__('sn-support::support.feed.items_missing', [
                'module' => $module,
                'name' => $name,
            ]));
        }

        return ['module' => $module, 'site' => $site->id()] + $feed;
    }

    /** Cache key carries tenant, module, site, host and feed, so entries never bleed across contexts. */
    protected function cacheKey(Site $site, ?string $name): string
    {
        $tenant = current_tenant()?->getKey() ?? 'global';
        $host = request()->getHost();

        return 'sn-support:feed:' . $tenant . ':' . $site->id() . ':' . $host
            . ':' . $this->siteModule($site) . ':' . ($name ?? '-aggregate');
    }

    /** Forget every cached shape of one module on one site. */
    protected function flushScope(string $tenant, string $module, string $siteId, string $host, array $names): void
    {
        $prefix = 'sn-support:feed:' . $tenant . ':' . $siteId . ':' . $host . ':' . $module . ':';

        Cache::forget($prefix . '-aggregate');

        foreach ($names as $name) {
            Cache::forget($prefix . $name);
        }
    }

    /** @return list<string> */
    protected function allFeedNames(): array
    {
        $names = [];

        foreach ($this->feeds as $sites) {
            foreach ($sites as $feeds) {
                $names = array_merge($names, $feeds->keys()->all());
            }
        }

        return array_values(array_unique($names));
    }

    /** @return list<array{0: string, 1: string}> module/site pairs holding config or feeds */
    protected function flushScopes(): array
    {
        $pairs = [];

        foreach ([$this->configs, $this->feeds] as $registry) {
            foreach ($registry as $module => $sites) {
                foreach ($sites->keys() as $siteId) {
                    $pairs[(string) $module . '|' . $siteId] = [(string) $module, (string) $siteId];
                }
            }
        }

        return array_values($pairs);
    }

    /** Build the RSS 2.0 XML: the site's aggregate stream, or one named feed. */
    protected function compile(Site $site, ?string $name): string
    {
        if ($name !== null) {
            $feed = $this->getFeed($name, $site) ?? [];
            $items = $this->resolveItems($feed, $site);
            $channel = [
                'title' => (string) value($feed['title'] ?? null ?: config('app.name')),
                'description' => value($feed['description'] ?? null),
                'link' => (string) value($feed['link'] ?? null ?: url('/')),
            ];
        } else {
            $items = [];

            foreach ($this->getFeeds($site) as $feed) {
                $items = array_merge($items, $this->resolveItems($feed, $site));
            }

            $config = $this->getConfig($site);
            $channel = [
                'title' => (string) (value($config['title'] ?? null) ?: config('app.name')),
                'description' => value($config['description'] ?? null),
                'link' => (string) (value($config['link'] ?? null) ?: url('/')),
            ];

            // Aggregate streams sort by updated_at desc and truncate by the override chain.
            usort($items, fn (array $a, array $b): int => $this->timestamp($b['updated_at'] ?? null) <=> $this->timestamp($a['updated_at'] ?? null));
            $limit = (int) $this->resolveConfig($site, 'aggregate_limit', 50);
            $items = array_slice($items, 0, $limit);
        }

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0">',
            '    <channel>',
            '        <title>' . e($channel['title']) . '</title>',
            '        <link>' . e($channel['link']) . '</link>',
            '        <description>' . e((string) $channel['description']) . '</description>',
            '        <language>' . str_replace('_', '-', app()->getLocale()) . '</language>',
            '        <lastBuildDate>' . now()->toRfc2822String() . '</lastBuildDate>',
        ];

        foreach ($items as $item) {
            $url = (string) ($item['url'] ?? '');
            $pubDate = $this->formatPubDate($item['updated_at'] ?? null);

            $lines[] = '        <item>';
            $lines[] = '            <title>' . e((string) ($item['title'] ?? '')) . '</title>';
            $lines[] = '            <link>' . e($url) . '</link>';
            $lines[] = '            <guid>' . e($url) . '</guid>';

            if (filled($item['description'] ?? null)) {
                $lines[] = '            <description>' . e((string) $item['description']) . '</description>';
            }

            if ($pubDate !== null) {
                $lines[] = '            <pubDate>' . $pubDate . '</pubDate>';
            }

            $lines[] = '        </item>';
        }

        $lines[] = '    </channel>';
        $lines[] = '</rss>';

        return implode("\n", $lines);
    }

    /**
     * Run the items closure, drop entries without a URL, then apply the feed limit.
     * Cap chain: the definition's own limit > site declaration (feed.limit) >
     * sn-support.feeds.limit.
     *
     * @param  array<string, mixed>  $feed
     * @return array<int, array<string, mixed>>
     */
    protected function resolveItems(array $feed, Site $site): array
    {
        $items = ($feed['items'] ?? null) ? ($feed['items'])() : [];
        $limit = (int) value($feed['limit'] ?? $this->resolveConfig($site, 'limit', 50));

        $resolved = [];

        foreach ($items as $item) {
            if (blank($item['url'] ?? null)) {
                continue;
            }

            $resolved[] = $item;
        }

        return $limit > 0 ? array_slice($resolved, 0, $limit) : $resolved;
    }

    /** Timestamp used for aggregate sorting; unparsable dates count as 0. */
    protected function timestamp(mixed $date): int
    {
        try {
            return filled($date) ? Carbon::parse($date)->getTimestamp() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Normalise pubDate to RFC 2822; drop the field when unparsable. */
    protected function formatPubDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->toRfc2822String();
        } catch (\Throwable) {
            return null;
        }
    }
}
