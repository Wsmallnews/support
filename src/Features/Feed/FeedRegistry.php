<?php

namespace Wsmallnews\Support\Features\Feed;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Http\Controllers\FeedController;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Named RSS feed registry.
 *
 * A site's feed space belongs to exactly one module (the plugin that owns the
 * site), so every write and read pairs a module with its own site. Reads take
 * the site explicitly — there is no ambient fallback.
 */
class FeedRegistry
{
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
    protected array $moduleRouteNames = [];

    public function __construct()
    {
        $this->configs = collect();
        $this->feeds = collect();
    }

    /*
     * ---------- Registration ----------
     */

    /** Declare module options for one site (merged shallowly, later calls win). */
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
            $this->putFeed($module, (string) $name, $feed, $site);
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

        $this->putFeed($module, $name, $feed, $site);

        return $this;
    }

    /**
     * A feed occupies the site's URL space ({prefix}/feed/{name}) and its route name
     * comes from the site group, so a second module on the same site would shadow the
     * first. Only the module owning the site may register feeds on it.
     */
    protected function assertOwnership(string $module, Site $site): void
    {
        if ($module !== $site->pluginId) {
            throw new InvalidArgumentException(
                "Feed module [{$module}] cannot register on site [{$site->id()}] owned by [{$site->pluginId}]."
            );
        }
    }

    /** @param  array<string, mixed>  $feed */
    protected function putFeed(string $module, string $name, array $feed, Site $site): void
    {
        $sites = $this->feeds->get($module, collect());
        $moduleFeeds = $sites->get($site->id(), collect());
        $moduleFeeds->put($name, $this->prepare($module, $name, $feed, $site));
        $sites->put($site->id(), $moduleFeeds);
        $this->feeds->put($module, $sites);
    }

    /*
     * ---------- Module endpoint routes ----------
     */

    /**
     * Register this module's feed routes inside one site group ({prefix}/feed and
     * {prefix}/feed/{name}), each tagged with feed_module and feed_site defaults.
     *
     * Must be called inside the site route group, and before catch-all dynamic
     * segments such as navigation/{slug}, otherwise the static segment is swallowed.
     */
    public function routes(string $module, Site $site): void
    {
        $this->assertOwnership($module, $site);

        // Module declaration (the site's feed.* section passed through) wins, then sn-support.feeds.
        $uri = trim((string) $this->resolveConfig($module, 'uri', $site, 'feed'), '/');

        Route::get($uri, [FeedController::class, 'moduleIndex'])
            ->defaults('feed_module', $module)
            ->defaults('feed_site', $site->id())
            ->name('feed');

        $show = Route::get($uri . '/{name}', [FeedController::class, 'moduleShow'])
            ->defaults('feed_module', $module)
            ->defaults('feed_site', $site->id())
            ->where('name', '[a-z0-9\-]+')
            ->name('feed.show');

        // One route per module and site; feedUrl() looks it up directly.
        $this->moduleRouteNames[$module][$site->id()] = (string) $show->getName();
    }

    /*
     * ---------- Reads ----------
     */

    /** Read module options of one site. */
    public function getConfig(string $module, Site $site, ?string $key = null, mixed $default = null): mixed
    {
        $config = (array) $this->configs->get($module, collect())->get($site->id(), []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /** Resolve in order: module declaration, global sn-support.feeds.*, then $default. */
    public function resolveConfig(string $module, string $key, Site $site, mixed $default = null): mixed
    {
        return $this->getConfig($module, $site, $key)
            ?? SupportUtils::getFeedsConfig($key, $default);
    }

    /**
     * Feeds of one module on one site, keyed by feed name.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function moduleFeeds(string $module, Site $site): Collection
    {
        return $this->feeds->get($module, collect())->get($site->id(), collect());
    }

    /** Subscription URL for one feed of the given site. */
    public function feedUrl(string $name, Site $site): string
    {
        $routeName = $this->resolveFeedRouteName($site->pluginId, $site);

        return $routeName ? sn_route($routeName, ['name' => $name]) : '';
    }

    /** RSS autodiscovery <link> tags for one module, one per feed. */
    public function autodiscoveryTags(string $module, Site $site): HtmlString
    {
        $lines = [];

        foreach ($this->moduleFeeds($module, $site) as $name => $feed) {
            $lines[] = sprintf(
                '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
                e((string) value($feed['title'] ?? null ?: config('app.name'))),
                e($this->feedUrl($name, $site)),
            );
        }

        return new HtmlString(implode("\n", $lines));
    }

    /**
     * Resolve a feed of the given site by name; null when missing.
     *
     * @return array<string, mixed>|null
     */
    public function getFeed(string $name, string $module, Site $site): ?array
    {
        return $this->moduleFeeds($module, $site)->get($name);
    }

    /*
     * ---------- Rendering ----------
     */

    /** Render RSS 2.0: the module's aggregate stream when name is null, its named feed otherwise. */
    public function render(string $module, Site $site, ?string $name = null): HtmlString
    {
        $ttl = (int) $this->resolveConfig($module, 'cache_ttl', $site, 3600);
        $xml = ($ttl === null || $ttl <= 0)
            ? $this->compile($module, $site, $name)
            : Cache::remember($this->cacheKey($module, $site, $name), $ttl, fn (): string => $this->compile($module, $site, $name));

        return new HtmlString($xml);
    }

    /** Drop the render cache of every registered site. */
    public function flush(): static
    {
        $siteIds = [];

        foreach ($this->feeds as $sites) {
            $siteIds = array_merge($siteIds, $sites->keys()->all());
        }

        $host = request()->getHost();
        $tenant = current_tenant()?->getKey() ?? 'global';

        foreach (array_unique($siteIds) as $siteId) {
            $this->flushScope($tenant, (string) $siteId, $host);
        }

        $this->flushScope($tenant, 'global', $host);

        return $this;
    }

    /*
     * ---------- Internals ----------
     */

    /** @param  array<string, mixed>  $feed */
    protected function prepare(string $module, string $name, array $feed, Site $site): array
    {
        if (! preg_match('/^[a-z0-9\-]+$/', $name)) {
            throw new InvalidArgumentException(__('sn-support::support.feed.name_invalid', ['name' => $name]));
        }

        if (! ($feed['items'] ?? null) instanceof Closure) {
            throw new InvalidArgumentException(__('sn-support::support.feed.items_missing', ['module' => $module, 'name' => $name]));
        }

        return ['module' => $module, 'site' => $site->id()] + $feed;
    }

    /** Route name of this module on the given site; null when the site has no feed route. */
    protected function resolveFeedRouteName(string $module, Site $site): ?string
    {
        return $this->moduleRouteNames[$module][$site->id()] ?? null;
    }

    /** Cache key carries tenant, site, host, module and feed, so entries never bleed across contexts. */
    protected function cacheKey(string $module, Site $site, ?string $name): string
    {
        $tenant = current_tenant()?->getKey() ?? 'global';
        $host = request()->getHost();

        return 'sn-support:feed:' . $tenant . ':' . $site->id() . ':' . $host . ':' . $module . ':' . ($name ?? '-aggregate');
    }

    /** Forget every cached shape of one scope. */
    protected function flushScope(string $tenant, string $siteId, string $host): void
    {
        $prefix = 'sn-support:feed:' . $tenant . ':' . $siteId . ':' . $host . ':';

        foreach ($this->moduleNames() as $module) {
            Cache::forget($prefix . $module . ':-aggregate');

            foreach ($this->allFeedNames() as $name) {
                Cache::forget($prefix . $module . ':' . $name);
            }
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

    /** @return list<string> */
    protected function moduleNames(): array
    {
        return array_values(array_unique([
            ...$this->configs->keys()->all(),
            ...$this->feeds->keys()->all(),
        ]));
    }

    /** Build the RSS 2.0 XML: the module's aggregate stream, or one named feed. */
    protected function compile(string $module, Site $site, ?string $name): string
    {
        if ($name !== null) {
            $feed = $this->getFeed($name, $module, $site) ?? [];
            $items = $this->resolveItems($module, $feed, $site);
            $channel = [
                'title' => (string) value($feed['title'] ?? null ?: config('app.name')),
                'description' => value($feed['description'] ?? null),
                'link' => (string) value($feed['link'] ?? null ?: url('/')),
            ];
        } else {
            $items = [];

            foreach ($this->moduleFeeds($module, $site) as $feed) {
                $items = array_merge($items, $this->resolveItems($module, $feed, $site));
            }

            $config = $this->getConfig($module, $site);
            $channel = [
                'title' => (string) (value($config['title'] ?? null) ?: config('app.name')),
                'description' => value($config['description'] ?? null),
                'link' => (string) (value($config['link'] ?? null) ?: url('/')),
            ];

            // Aggregate streams sort by updated_at desc and truncate by the override chain.
            usort($items, fn (array $a, array $b): int => $this->timestamp($b['updated_at'] ?? null) <=> $this->timestamp($a['updated_at'] ?? null));
            $limit = (int) $this->resolveConfig($module, 'aggregate_limit', $site, 50);
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
     * Cap chain: the definition's own limit > module declaration (feed.limit) >
     * sn-support.feeds.limit.
     *
     * @param  array<string, mixed>  $feed
     * @return array<int, array<string, mixed>>
     */
    protected function resolveItems(string $module, array $feed, Site $site): array
    {
        $items = ($feed['items'] ?? null) ? ($feed['items'])() : [];
        $limit = (int) value($feed['limit'] ?? $this->resolveConfig($module, 'limit', $site, 50));

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
