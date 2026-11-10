<?php

namespace Wsmallnews\Support\Features\Sitemap;

use Carbon\CarbonInterface;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Http\Controllers\SitemapController;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Sitemap and robots registry: modules register URL sources and disallow rules, support
 * exposes the endpoints. See the Sitemap section of the README.
 */
class SitemapRegistry
{
    /**
     * @var Collection<string, array<string, mixed>> module => robots rules
     */
    protected Collection $robotsRules;

    /**
     * @var Collection<string, Collection<string, array<string, mixed>>> module => siteId => options
     */
    protected Collection $configs;

    /**
     * @var Collection<string, Collection<string, Collection<int, array<string, mixed>>>> module => siteId => sources
     */
    protected Collection $sources;

    public function __construct()
    {
        $this->robotsRules = collect();
        $this->configs = collect();
        $this->sources = collect();
    }

    /*
     * ---------- Registration ----------
     */

    /** Declare options for one site (merged shallowly, later calls win). Chainable. */
    public function config(string $module, array $config, Site $site): static
    {
        $this->assertOwnership($module, $site);

        $sites = $this->configs->get($module, collect());
        $sites->put($site->id(), array_merge($sites->get($site->id(), []), $config));
        $this->configs->put($module, $sites);

        return $this;
    }

    /** Register one URL source for a site. Chainable. */
    public function register(string $module, array $source, Site $site): static
    {
        $this->assertOwnership($module, $site);
        $this->assertSource($module, $source);

        $sites = $this->sources->get($module, collect());
        $sites->put($site->id(), ($sites->get($site->id(), collect()))->push($source));
        $this->sources->put($module, $sites);

        return $this;
    }

    /** Register several URL sources for a site at once. */
    public function registers(string $module, array $sources, Site $site): static
    {
        foreach ($sources as $source) {
            $this->register($module, $source, $site);
        }

        return $this;
    }

    /** Register robots rules for a module (disallow is a list of paths). */
    public function robots(string $module, array $rules): static
    {
        $this->robotsRules->put($module, array_merge($this->robotsRules->get($module, []), $rules));

        return $this;
    }

    /** Only the module owning the site may register on it. */
    protected function assertOwnership(string $module, Site $site): void
    {
        if ($module !== $site->pluginId) {
            throw new InvalidArgumentException(
                "Sitemap module [{$module}] cannot register on site [{$site->id()}] owned by [{$site->pluginId}]."
            );
        }
    }

    /** Register the site sitemap endpoint. Must be called inside the site route group. */
    public function routes(string $module, Site $site): void
    {
        $this->assertOwnership($module, $site);

        Route::get('sitemap.xml', [SitemapController::class, 'sitemap'])
            ->defaults('sitemap_module', $module)
            ->defaults('sitemap_site', $site->id())
            ->name('sitemap');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getSources(string $module, Site $site): Collection
    {
        return $this->sources->get($module, collect())->get($site->id(), collect());
    }

    /** Read module options of one site. */
    public function getConfig(string $module, Site $site, ?string $key = null, mixed $default = null): mixed
    {
        $config = (array) $this->configs->get($module, collect())->get($site->id(), []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /** Resolve in order: module declaration, global sn-support.sitemap.*, then $default. */
    public function resolveConfig(string $module, string $key, Site $site, mixed $default = null): mixed
    {
        return $this->getConfig($module, $site, $key)
            ?? SupportUtils::getSitemapConfig($key, $default);
    }

    /*
     * ---------- Rendering ----------
     */

    /** Render the sitemap XML of one module on one site (cached as a whole). */
    public function render(string $module, Site $site): HtmlString
    {
        $this->assertOwnership($module, $site);

        // TTL 解析链：模块声明（站点 sitemap.* 透传）> sn-support.sitemap.cache_ttl
        $ttl = (int) $this->resolveConfig($module, 'cache_ttl', $site, 3600);
        $xml = $ttl <= 0
            ? $this->compile($module, $site)
            : Cache::remember($this->cacheKey($module, $site), $ttl, fn (): string => $this->compile($module, $site));

        return new HtmlString($xml);
    }

    /** Render robots.txt: panel paths, module disallow rules and the Sitemap line. */
    public function renderRobots(): string
    {
        $lines = ['User-agent: *'];

        // Panel paths are discovered automatically; modules never register them
        foreach (Filament::getPanels() as $panel) {
            if (filled($panel->getPath())) {
                $lines[] = 'Disallow: /' . trim($panel->getPath(), '/');
            }
        }

        foreach ($this->robotsRules as $rules) {
            foreach ($rules['disallow'] ?? [] as $path) {
                if (filled($path)) {
                    $lines[] = 'Disallow: /' . trim($path, '/');
                }
            }
        }

        $lines[] = '';

        // One Sitemap line per registered frontend site (there is no root sitemap).
        foreach (app(SiteRegistry::class)->all() as $site) {
            if (! $site->routable) {
                continue;
            }

            $routeName = $site->routeNamePrefix . 'sitemap';
            $route = app('router')->getRoutes()->getByName($routeName);

            // Path-tenant sites need a concrete tenant in the URL; root robots cannot
            // know it, so only parameterless sitemap routes are advertised here.
            if ($route && $route->parameterNames() === []) {
                $lines[] = 'Sitemap: ' . sn_route($routeName);
            }
        }

        return implode("\n", array_values(array_unique($lines))) . "\n";
    }

    /** Drop the cached sitemap of one module on one site. */
    public function flush(string $module, Site $site): static
    {
        Cache::forget($this->cacheKey($module, $site));

        return $this;
    }

    /*
     * ---------- Internals ----------
     */

    /** Cache key carries tenant, site, host and module, so entries never bleed across contexts. */
    protected function cacheKey(string $module, Site $site): string
    {
        $tenant = current_tenant()?->getKey() ?? 'global';
        $host = request()->getHost();

        return 'sn-support:sitemap:' . $tenant . ':' . $site->id() . ':' . $module . ':' . $host;
    }

    /** Build the XML from the module's registered sources (uncached, runs every urls closure). */
    protected function compile(string $module, Site $site): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->getSources($module, $site) as $source) {
            foreach (($source['urls'])() as $url) {
                $loc = e($url['loc'] ?? '');
                if (blank($loc)) {
                    continue;
                }

                $lastmod = $this->formatLastmod($url['lastmod'] ?? null);
                $lines[] = '    <url><loc>' . $loc . '</loc>' . ($lastmod ? '<lastmod>' . $lastmod . '</lastmod>' : '') . '</url>';
            }
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    /** @param  array<string, mixed>  $source */
    protected function assertSource(string $module, array $source): void
    {
        if (blank($source['key'] ?? null) || ! ($source['urls'] ?? null) instanceof Closure) {
            throw new \InvalidArgumentException(sprintf('Sitemap 来源必须包含 key 与 urls 闭包（模块 %s）', $module));
        }
    }

    /** Normalise lastmod to a W3C date; drop the field when unparsable. */
    protected function formatLastmod(mixed $lastmod): ?string
    {
        if (blank($lastmod)) {
            return null;
        }

        if ($lastmod instanceof CarbonInterface) {
            return $lastmod->toDateString();
        }

        try {
            return Carbon::parse($lastmod)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
