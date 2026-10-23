<?php

namespace Wsmallnews\Support\Features\Feed;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Concerns\MatchesDomain;
use Wsmallnews\Support\Http\Controllers\FeedController;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/** Named RSS feed registry; see the Feed section of the README for registration options. */
class FeedRegistry
{
    use MatchesDomain;

    /**
     * @var Collection<string, array<string, mixed>> module name => options
     */
    protected Collection $configs;

    /**
     * @var Collection<string, array<string, mixed>> feed name => definition
     */
    protected Collection $feeds;

    /**
     * @var array<string, string> module => fully-prefixed route name (e.g. sn-cms.feed.show)
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

    /** Declare module options (merged shallowly, later calls win). Chainable. */
    public function config(string $module, array $config): static
    {
        $this->configs->put($module, array_merge($this->configs->get($module, []), $config));

        return $this;
    }

    /**
     * Register a named feed. The name is a URL segment and must be globally unique.
     *
     * @param  array{title?: Closure|string, label?: Closure|string, description?: Closure|string|null, link?: Closure|string, limit?: Closure|int, items: Closure}  $feed
     */
    public function register(string $module, string $name, array $feed): static
    {
        if (! preg_match('/^[a-z0-9\-]+$/', $name)) {
            throw new \InvalidArgumentException(__('sn-support::support.feed.name_invalid', ['name' => $name]));
        }

        if (! ($feed['items'] ?? null) instanceof Closure) {
            throw new \InvalidArgumentException(__('sn-support::support.feed.items_missing', ['module' => $module, 'name' => $name]));
        }

        if ($this->feeds->has($name)) {
            $owner = $this->feeds->get($name)['module'] ?? '?';

            throw new \InvalidArgumentException(__('sn-support::support.feed.name_duplicated', ['name' => $name, 'module' => $owner]));
        }

        $feed['module'] = $module;
        $this->feeds->put($name, $feed);

        return $this;
    }

    /** Remove a named feed. */
    public function forget(string $name): static
    {
        $this->feeds->forget($name);

        return $this;
    }

    /*
     * ---------- Module endpoint routes ----------
     */

    /**
     * Register module-scoped feed routes ({prefix}/feed and {prefix}/feed/{name}).
     *
     * Must be called inside the module route group, and before catch-all dynamic
     * segments such as navigation/{slug}, otherwise the static segment is swallowed.
     */
    public function routes(string $module): void
    {
        $uri = trim((string) SupportUtils::getFeedsConfig('uri', 'feed'), '/');

        Route::get($uri, [FeedController::class, 'moduleIndex'])
            ->defaults('feed_module', $module)
            ->name('feed');

        $show = Route::get($uri . '/{name}', [FeedController::class, 'moduleShow'])
            ->defaults('feed_module', $module)
            ->where('name', '[a-z0-9\-]+')
            ->name('feed.show');

        // Capture the group-prefixed route name for feedUrl() and autodiscoveryTags()
        $this->moduleRouteNames[$module] = (string) $show->getName();
    }

    /*
     * ---------- Reads ----------
     */

    /**
     * All feeds visible to the current request (site-wide view), keyed by feed name.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function available(): Collection
    {
        if (SupportUtils::getFeedsConfig('enabled', true) !== true) {
            return collect();
        }

        return $this->feeds->filter(fn (array $feed): bool => $this->moduleMatchesDomain($feed['module']));
    }

    /**
     * Feeds of a single module that are visible to the current request.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function moduleFeeds(string $module): Collection
    {
        if (SupportUtils::getFeedsConfig('enabled', true) !== true) {
            return collect();
        }

        return $this->feeds->filter(fn (array $feed): bool => $feed['module'] === $module && $this->moduleMatchesDomain($module));
    }

    /** Subscription URL: module endpoint when registered, site endpoint otherwise. */
    public function feedUrl(string $name): string
    {
        $feed = $this->feeds->get($name);

        $routeName = ($feed && isset($this->moduleRouteNames[$feed['module']]))
            ? $this->moduleRouteNames[$feed['module']]
            : 'sn-support.feed.show';

        return sn_route($routeName, ['name' => $name]);
    }

    /** RSS autodiscovery <link> tags for one module, one per feed. */
    public function autodiscoveryTags(string $module): HtmlString
    {
        $lines = [];

        foreach ($this->moduleFeeds($module) as $name => $feed) {
            $lines[] = sprintf(
                '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
                e((string) value($feed['title'] ?? null ?: config('app.name'))),
                e($this->feedUrl($name)),
            );
        }

        return new HtmlString(implode("\n", $lines));
    }

    /**
     * Resolve a visible feed by name; null when missing, domain-mismatched or owned by another module.
     *
     * @return array<string, mixed>|null
     */
    public function getFeed(string $name, ?string $module = null): ?array
    {
        $feed = $this->feeds->get($name);

        if (! $feed || ($module !== null && $feed['module'] !== $module)) {
            return null;
        }

        return $this->moduleMatchesDomain($feed['module']) ? $feed : null;
    }

    /*
     * ---------- Rendering ----------
     */

    /** Render RSS 2.0 for the site: aggregate stream when name is null, single feed otherwise. */
    public function render(?string $name = null): HtmlString
    {
        return $this->memoizedRender($name, null);
    }

    /** Render RSS 2.0 for one module: its aggregate stream when name is null, its named feed otherwise. */
    public function renderModule(string $module, ?string $name = null): HtmlString
    {
        return $this->memoizedRender($name, $module);
    }

    /** Drop the render cache of every endpoint (root and modules, aggregate and named). */
    public function flush(): static
    {
        $modules = $this->feeds->map(fn (array $feed): string => $feed['module'])->unique()->values()->all();
        $names = $this->feeds->keys()->all();

        foreach ($modules as $module) {
            // Module aggregate endpoint
            Cache::forget($this->cacheKey(null, $module));

            // Module named endpoints
            foreach ($names as $name) {
                Cache::forget($this->cacheKey($name, $module));
            }
        }

        // Root aggregate and root named endpoints
        foreach ($names as $name) {
            Cache::forget($this->cacheKey($name));
        }

        Cache::forget($this->cacheKey(null));

        return $this;
    }

    /*
     * ---------- Internals ----------
     */

    protected function memoizedRender(?string $name, ?string $module): HtmlString
    {
        $ttl = SupportUtils::getFeedsConfig('cache_ttl', 3600);
        $xml = ($ttl === null || $ttl <= 0)
            ? $this->compile($name, $module)
            : Cache::remember($this->cacheKey($name, $module), $ttl, fn (): string => $this->compile($name, $module));

        return new HtmlString($xml);
    }

    /** Domain filter switch (config sn-support.feeds.domain_filter, on by default). */
    protected function isDomainFilterEnabled(): bool
    {
        return SupportUtils::getFeedsConfig('domain_filter', true) === true;
    }

    /** Cache key carries tenant, host, module and feed, so entries never bleed across contexts. */
    protected function cacheKey(?string $name, ?string $module = null): string
    {
        $tenant = function_exists('has_tenancy') && has_tenancy() ? (current_tenant()?->id ?? 'none') : 'global';
        $host = request()->getHost();

        return 'sn-support:feed:' . $tenant . ':' . $host . ':' . ($module ?? '-') . ':' . ($name ?? '-aggregate');
    }

    /** Build the RSS 2.0 XML for one of the four endpoint shapes (site/module x aggregate/named). */
    protected function compile(?string $name = null, ?string $module = null): string
    {
        $items = [];

        if ($name === null && $module === null) {
            // Site aggregate: merge every visible feed
            foreach ($this->available() as $feed) {
                $items = array_merge($items, $this->resolveItems($feed));
            }

            $channel = [
                'title' => config('app.name'),
                'description' => null,
                'link' => url('/'),
            ];
        } elseif ($name === null) {
            // Module aggregate: merge this module's visible feeds only
            foreach ($this->moduleFeeds($module) as $feed) {
                $items = array_merge($items, $this->resolveItems($feed));
            }

            $config = $this->configs->get($module, []);

            $channel = [
                'title' => (string) (value($config['title'] ?? null) ?: config('app.name')),
                'description' => value($config['description'] ?? null),
                'link' => (string) (value($config['link'] ?? null) ?: url('/')),
            ];
        } else {
            // Named feed; getFeed() already validated ownership and domain
            $feed = $this->getFeed($name, $module) ?? [];
            $items = $this->resolveItems($feed);

            $channel = [
                'title' => (string) value($feed['title'] ?? null ?: config('app.name')),
                'description' => value($feed['description'] ?? null),
                'link' => (string) value($feed['link'] ?? null ?: url('/')),
            ];
        }

        // Aggregate streams sort by updated_at desc and truncate; named feeds keep their own order and limit
        if ($name === null) {
            usort($items, fn (array $a, array $b): int => $this->timestamp($b['updated_at'] ?? null) <=> $this->timestamp($a['updated_at'] ?? null));
            $items = array_slice($items, 0, (int) SupportUtils::getFeedsConfig('limit', 50));
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
     *
     * @param  array<string, mixed>  $feed
     * @return array<int, array<string, mixed>>
     */
    protected function resolveItems(array $feed): array
    {
        $items = ($feed['items'] ?? null) ? ($feed['items'])() : [];
        $limit = (int) value($feed['limit'] ?? 50);

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
