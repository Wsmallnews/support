<?php

namespace Wsmallnews\Support\Features\Seo;

use Closure;
use Illuminate\Support\HtmlString;
use Stringable;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;

/**
 * SEO renderer: per-module site defaults plus the current page context (rebuilt by init()).
 * See the Seo section of the README.
 *
 * @method SeoPage title(?string $title)
 * @method SeoPage description(?string $description)
 * @method SeoPage image(?string $image)
 * @method SeoPage type(string $type)
 * @method SeoPage url(?string $url)
 * @method SeoPage robots(?string $robots)
 * @method SeoPage jsonLd(array $schema)
 * @method SeoPage article(array $extra = [])
 * @method SeoPage website()
 */
class Seo implements Stringable
{
    /**
     * @var array<string, array<string, array|Closure>> module => siteId => defaults, closures resolved lazily
     */
    protected array $configs = [];

    /** Module the current page belongs to; null falls back to global defaults. */
    protected ?string $forModule = null;

    /**
     * Resolved defaults cache, invalidated by init() and config().
     *
     *
     * @var ?array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    protected ?array $resolvedDefaults = null;

    /** Cache marker: defaults are per site + module, so switching either invalidates them. */
    protected ?string $resolvedFor = null;

    /** Page-level SEO value object. */
    protected SeoPage $page;

    public function __construct()
    {
        $this->page = new SeoPage;
    }

    /*
     * ---------- Module registration and resolution ----------
     */

    /** Declare defaults for one site; repeated calls merge. */
    public function config(string $module, array | Closure $config, Site $site): static
    {
        $current = $this->configs[$module][$site->id()] ?? null;

        $this->configs[$module][$site->id()] = $current === null
            ? $config                                   // stored as-is so closures stay lazy
            : array_merge($this->resolveConfig($current), $this->resolveConfig($config));

        $this->resolvedDefaults = null;

        return $this;
    }

    /** Start a page: rebuild the page context and bind its module. Called by the sn-resolve-seo middleware. */
    public function init(?string $module = null): static
    {
        $this->forModule = $module;
        $this->resolvedDefaults = null;
        $this->resolvedFor = null;
        $this->page = new SeoPage;

        return $this;
    }

    /** Module the current page belongs to. */
    public function getFor(): ?string
    {
        return $this->forModule;
    }

    /**
     * Site defaults: module config (closures resolved now) with a per-key global fallback.
     *
     *
     * @return array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    public function defaults(): array
    {
        $site = app(SiteContext::class)->site();
        $marker = ($site?->id() ?? '-') . '|' . ($this->forModule ?? '-');

        if ($this->resolvedDefaults === null || $this->resolvedFor !== $marker) {
            $this->resolvedDefaults = $this->resolveDefaults();
            $this->resolvedFor = $marker;
        }

        return $this->resolvedDefaults;
    }

    /**
     * @return array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    protected function resolveDefaults(): array
    {
        $module = $this->forModule;
        $site = app(SiteContext::class)->site();

        $config = [];

        if ($module !== null && $site !== null) {
            $stored = $this->configs[$module][$site->id()] ?? null;

            $config = $stored !== null ? $this->resolveConfig($stored) : [];
        }

        return [
            'site_name' => $config['site_name'] ?? config('app.name'),
            'description' => $config['description'] ?? null,
            'image' => $config['image'] ?? null,
            'favicon' => $config['favicon'] ?? null,
            'analytics_code' => $config['analytics_code'] ?? null,
        ];
    }

    protected function resolveConfig(array | Closure $config): array
    {
        return $config instanceof Closure ? $config() : $config;
    }

    /*
     * ---------- Page context ----------
     */

    /** Current page context. */
    public function page(): SeoPage
    {
        return $this->page;
    }

    /*
     * ---------- Page data declaration (forwarded to the current SeoPage via __call) ----------
     */

    /**
     * Forward page declarations to SeoPage, so new page methods need no change here.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists($this->page, $method)) {
            return $this->page->{$method}(...$parameters);
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }

    /*
     * ---------- Rendering ----------
     */

    /** Render every SEO tag for <head>: page declarations with module defaults as fallback. */
    public function render(): HtmlString
    {
        return $this->page->render($this->defaults());
    }

    /** Render the analytics snippet, for use before </body>. */
    public function renderAnalytics(): HtmlString
    {
        $analytics = $this->defaults()['analytics_code'];

        return new HtmlString(filled($analytics) ? $analytics : '');
    }

    public function __toString(): string
    {
        return $this->render()->toHtml();
    }
}
