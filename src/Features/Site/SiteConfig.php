<?php

namespace Wsmallnews\Support\Features\Site;

use InvalidArgumentException;

/**
 * Mutable builder passed to plugin site() closures.
 *
 * The plugin fills pluginId/panelId/key and resolves the default mode from the
 * panel; callers configure the data space, the route group and the site's module
 * options, then the plugin calls toSite() to freeze it into the registry.
 */
final class SiteConfig
{
    private ?SiteMode $mode = null;

    private ?string $scopeType = null;

    private int $scopeId = 0;

    private ?string $tenantType = null;

    private ?string $domain = null;

    private string $prefix = '';

    /** @var array<string> */
    private array $middleware = ['web'];

    private ?string $routeNamePrefix = null;

    private bool $routable = true;

    private ?string $sourceSiteKey = null;

    private ?string $label = null;

    /** @var array<string, mixed> */
    private array $options = [];

    /**
     * Admin component defaults for this site, same keys as
     * panel_register.global_default. Merged between the config file defaults
     * and each entry's own overrides.
     *
     * @var array<string, mixed>
     */
    private array $panelDefaults = [];

    /** @var array<string, array{scope_type: string, scope_id: int}> suffix => pair */
    private array $scopes = [];

    public function __construct(
        private readonly string $pluginId,
        private string $panelId,
        private readonly string $key,
    ) {}

    /** Filled by the plugin when the panel is known (site() runs before register()). */
    public function panel(string $panelId): static
    {
        $this->panelId = $panelId;

        return $this;
    }

    public function getMode(): ?SiteMode
    {
        return $this->mode;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getScopeType(): ?string
    {
        return $this->scopeType;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }

    /** @return array{scope_type: string, scope_id: int}|null */
    public function getScopeable(): ?array
    {
        return blank($this->scopeType)
            ? null
            : ['scope_type' => $this->scopeType, 'scope_id' => $this->scopeId];
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getRouteNamePrefix(): ?string
    {
        return $this->routeNamePrefix;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Sub-scope pairs resolved from registered components (suffix => pair).
     * Filled by the site registrar, not by hand.
     *
     * @param  array<string, array{scope_type: string, scope_id: int}>  $scopes
     */
    public function scopes(array $scopes): static
    {
        $this->scopes = $this->scopes + $scopes;

        return $this;
    }

    /** @param  array{scope_type: string, scope_id: int}  $pair */
    public function scope(string $suffix, array $pair): static
    {
        $this->scopes[$suffix] = $pair;

        return $this;
    }

    /** @return array<string, array{scope_type: string, scope_id: int}> */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /** Human label, used as the navigation group when a panel hosts several sites. */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** Set one module option, e.g. option('search.display', 'page'). */
    public function option(string $key, mixed $value): static
    {
        data_set($this->options, $key, $value);

        return $this;
    }

    /** Merge a tree of module options; later values win. */
    public function options(array $options): static
    {
        $this->options = array_replace_recursive($this->options, $options);

        return $this;
    }

    /**
     * Admin component defaults for this site, applied on top of the module's
     * panel_register.global_default before each entry's own overrides.
     *
     * These only affect how admin components are registered (navigation,
     * labels, custom properties); module runtime options belong to options().
     *
     * @param  array<string, mixed>  $defaults
     */
    public function panelDefaults(array $defaults): static
    {
        $this->panelDefaults = array_merge($this->panelDefaults, $defaults);

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPanelDefaults(): array
    {
        return $this->panelDefaults;
    }

    public function mode(SiteMode | string $mode): static
    {
        $this->mode = is_string($mode) ? SiteMode::from($mode) : $mode;

        return $this;
    }

    /** scope_type + scope_id are one unit and are always passed together. */
    public function scopeable(string $scopeType, int $scopeId = 0): static
    {
        // Each plugin owns its data space: the base scope must be the plugin id
        // or a suffix of it, so two plugins can never claim the same rows.
        if ($scopeType !== $this->pluginId && ! str_starts_with($scopeType, "{$this->pluginId}-")) {
            throw new InvalidArgumentException(
                "Site [{$this->pluginId}:{$this->key}] scope type [{$scopeType}] must be [{$this->pluginId}] "
                . "or start with [{$this->pluginId}-]."
            );
        }

        if (str_contains($scopeType, ':')) {
            throw new InvalidArgumentException("Site [{$this->pluginId}:{$this->key}] scope type [{$scopeType}] must not contain ':'.");
        }

        $this->scopeType = $scopeType;
        $this->scopeId = $scopeId;

        return $this;
    }

    public function tenantType(?string $tenantType): static
    {
        $this->tenantType = $tenantType;

        return $this;
    }

    public function domain(?string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = trim($prefix, '/');

        return $this;
    }

    /**
     * @param  array<string>|string  $middleware
     */
    public function middleware(array | string $middleware): static
    {
        $this->middleware = array_values((array) $middleware);

        return $this;
    }

    public function routeNamePrefix(?string $routeNamePrefix): static
    {
        $this->routeNamePrefix = $routeNamePrefix;

        return $this;
    }

    /** Whether this site exposes a frontend route group (default true). */
    public function routable(bool $enabled = true): static
    {
        $this->routable = $enabled;

        return $this;
    }

    /**
     * Aggregate sites reference a site declared on the same plugin instance by
     * its key and inherit its scopeable at runtime. References are resolved
     * and validated while the plugin registers its sites, so declaration order
     * does not matter.
     */
    public function aggregateOf(string $siteKey): static
    {
        if (trim($siteKey) === '') {
            throw new InvalidArgumentException("Site [{$this->pluginId}:{$this->key}] must declare a non-empty aggregate source key.");
        }

        if ($siteKey === $this->key) {
            throw new InvalidArgumentException("Site [{$this->pluginId}:{$this->key}] cannot aggregate itself.");
        }

        $this->sourceSiteKey = $siteKey;

        return $this;
    }

    public function getSourceSiteKey(): ?string
    {
        return $this->sourceSiteKey;
    }

    public function toSite(): Site
    {
        if (trim($this->panelId) === '') {
            throw new InvalidArgumentException("Site [{$this->pluginId}:{$this->key}] has no panel id resolved.");
        }

        if ($this->mode === null) {
            throw new InvalidArgumentException("Site [{$this->pluginId}:{$this->key}] has no mode resolved.");
        }

        $routeNamePrefix = $this->routeNamePrefix !== null
            ? rtrim($this->routeNamePrefix, '.') . '.'
            : "{$this->pluginId}.{$this->key}.";

        return new Site(
            pluginId: $this->pluginId,
            panelId: $this->panelId,
            key: $this->key,
            mode: $this->mode,
            scopeType: $this->scopeType,
            scopeId: $this->scopeId,
            tenantType: $this->tenantType,
            domain: $this->domain,
            prefix: $this->prefix,
            middleware: $this->middleware,
            routeNamePrefix: $routeNamePrefix,
            routable: $this->routable,
            sourceSiteKey: $this->sourceSiteKey,
            label: $this->label,
            options: $this->options,
            panelDefaults: $this->panelDefaults,
            scopes: $this->scopes,
        );
    }
}
