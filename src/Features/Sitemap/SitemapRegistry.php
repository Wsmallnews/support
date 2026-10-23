<?php

namespace Wsmallnews\Support\Features\Sitemap;

use Carbon\CarbonInterface;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Concerns\MatchesDomain;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Sitemap and robots registry: modules register URL sources and disallow rules, support
 * exposes the endpoints. See the Sitemap section of the README.
 */
class SitemapRegistry
{
    use MatchesDomain;

    /**
     * @var Collection<string, array<string, mixed>> module => options
     */
    protected Collection $configs;

    /**
     * @var Collection<string, Collection<int, array<string, mixed>>> module => sources
     */
    protected Collection $modules;

    /**
     * @var Collection<string, array<string, mixed>> module => robots rules
     */
    protected Collection $robotsRules;

    public function __construct()
    {
        $this->configs = collect();
        $this->modules = collect();
        $this->robotsRules = collect();
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

    /** Register one URL source for a module. Chainable. */
    public function register(string $module, array $source): static
    {
        if (blank($source['key'] ?? null) || ! ($source['urls'] ?? null) instanceof Closure) {
            throw new \InvalidArgumentException(sprintf('Sitemap 来源必须包含 key 与 urls 闭包（模块 %s）', $module));
        }

        $this->modules->put($module, ($this->modules->get($module, collect()))->push($source));

        return $this;
    }

    /** Register several URL sources at once. */
    public function registers(string $module, array $sources): static
    {
        foreach ($sources as $source) {
            $this->register($module, $source);
        }

        return $this;
    }

    /** Register robots rules for a module (disallow is a list of paths). */
    public function robots(string $module, array $rules): static
    {
        $this->robotsRules->put($module, array_merge($this->robotsRules->get($module, []), $rules));

        return $this;
    }

    /** Remove every source, option and rule of a module. */
    public function forget(string $module): static
    {
        $this->configs->forget($module);
        $this->modules->forget($module);
        $this->robotsRules->forget($module);

        return $this;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getSources(string $module): Collection
    {
        return $this->modules->get($module, collect());
    }

    /**
     * @return array<int, string> modules that registered at least one source
     */
    public function getModules(): array
    {
        return $this->modules->keys()->values()->all();
    }

    /*
     * ---------- Rendering ----------
     */

    /** Render the sitemap XML for sources matching the current domain (cached as a whole). */
    public function render(): HtmlString
    {
        $ttl = SupportUtils::getSitemapConfig('cache_ttl', 3600);
        $xml = ($ttl === null || $ttl <= 0)
            ? $this->compile()
            : Cache::remember($this->cacheKey(), $ttl, fn (): string => $this->compile());

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

        // Module rules also respect the domain filter
        foreach ($this->robotsRules as $module => $rules) {
            if (! $this->moduleMatchesDomain($module)) {
                continue;
            }

            foreach ($rules['disallow'] ?? [] as $path) {
                if (filled($path)) {
                    $lines[] = 'Disallow: /' . trim($path, '/');
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . route('sn-support.sitemap');

        return implode("\n", array_values(array_unique($lines))) . "\n";
    }

    /** Drop the cached sitemap, for callers to invoke after content changes. */
    public function flush(): static
    {
        Cache::forget($this->cacheKey());

        return $this;
    }

    /*
     * ---------- Internals ----------
     */

    /** Cache key carries tenant, host and module set, so entries never bleed across contexts. */
    protected function cacheKey(): string
    {
        $tenant = function_exists('has_tenancy') && has_tenancy() ? (current_tenant()?->id ?? 'none') : 'global';
        $host = request()->getHost();

        return 'sn-support:sitemap:' . $tenant . ':' . $host . ':' . md5(implode(',', $this->getModules()));
    }

    /** Aggregate domain-matching sources and build the XML (uncached, runs every urls closure). */
    protected function compile(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->modules as $module => $sources) {
            if (! $this->moduleMatchesDomain($module)) {
                continue;
            }

            foreach ($sources as $source) {
                foreach (($source['urls'])() as $url) {
                    $loc = e($url['loc'] ?? '');
                    if (blank($loc)) {
                        continue;
                    }

                    $lastmod = $this->formatLastmod($url['lastmod'] ?? null);
                    $lines[] = '    <url><loc>' . $loc . '</loc>' . ($lastmod ? '<lastmod>' . $lastmod . '</lastmod>' : '') . '</url>';
                }
            }
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    /** Domain filter switch (config sn-support.sitemap.domain_filter, on by default). */
    protected function isDomainFilterEnabled(): bool
    {
        return SupportUtils::getSitemapConfig('domain_filter', true) === true;
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
