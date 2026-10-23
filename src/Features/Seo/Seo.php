<?php

namespace Wsmallnews\Support\Features\Seo;

use Closure;
use Illuminate\Support\HtmlString;
use Stringable;

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
     * @var array<string, array|Closure> module => defaults, closures resolved lazily
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

    /** Page-level SEO value object. */
    protected SeoPage $page;

    public function __construct()
    {
        $this->page = new SeoPage;
    }

    /*
     * ---------- Module registration and resolution ----------
     */

    /** Declare module defaults; repeated calls merge. Call from packageBooted(). */
    public function config(string $module, array | Closure $config): static
    {
        if (! isset($this->configs[$module])) {
            $this->configs[$module] = $config;      // stored as-is so closures stay lazy
        } else {
            $this->configs[$module] = array_merge($this->resolveConfig($this->configs[$module]), $this->resolveConfig($config));
        }

        $this->resolvedDefaults = null;

        return $this;
    }

    /** Start a page: rebuild the page context and bind its module. Called by the seo-init middleware. */
    public function init(?string $module = null): static
    {
        $this->forModule = $module;
        $this->resolvedDefaults = null;
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
        return $this->resolvedDefaults ??= $this->resolveDefaults();
    }

    /**
     * @return array{site_name: string, description: ?string, image: ?string, favicon: ?string, analytics_code: ?string}
     */
    protected function resolveDefaults(): array
    {
        $module = $this->forModule;
        $config = ($module !== null && isset($this->configs[$module])) ? $this->resolveConfig($this->configs[$module]) : [];

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
