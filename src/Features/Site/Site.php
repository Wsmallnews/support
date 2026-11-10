<?php

namespace Wsmallnews\Support\Features\Site;

use InvalidArgumentException;

/**
 * Immutable site definition: one data space and one frontend route group.
 *
 * Ids are "{pluginId}:{key}" and keys are unique per plugin across every panel,
 * so an id always resolves to exactly one site. Panel ownership stays on
 * {@see self::$panelId} and only drives the admin namespace.
 */
final class Site
{
    /**
     * @param  array<string>  $middleware
     * @param  array<string, mixed>  $options  Explicit module options for this site
     * @param  array<string, mixed>  $panelDefaults  Admin component defaults for this site
     */
    public function __construct(
        public readonly string $pluginId,
        public readonly string $panelId,
        public readonly string $key,
        public readonly SiteMode $mode,
        public readonly ?string $scopeType,
        public readonly int $scopeId,
        public readonly ?string $tenantType,
        public readonly ?string $domain,
        public readonly string $prefix,
        public readonly array $middleware,
        public readonly string $routeNamePrefix,
        public readonly bool $routable,
        public readonly ?string $sourceSiteKey,
        public readonly ?string $label = null,
        public readonly array $options = [],
        public readonly array $panelDefaults = [],
        /** @var array<string, array{scope_type: string, scope_id: int}> suffix => pair */
        public readonly array $scopes = [],
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException("Site [{$pluginId}] must declare a key.");
        }

        if (trim($prefix) === '') {
            throw new InvalidArgumentException("Site [{$pluginId}:{$key}] must declare a route prefix.");
        }

        // Sub-scopes are derived as "{scopeType}::suffix"; a colon in the base
        // scope would make them ambiguous against other sites' derived scopes.
        if ($scopeType !== null && str_contains($scopeType, ':')) {
            throw new InvalidArgumentException("Site [{$pluginId}:{$key}] scope type [{$scopeType}] must not contain ':'.");
        }

        if ($mode === SiteMode::Aggregate) {
            if (blank($sourceSiteKey)) {
                throw new InvalidArgumentException("Aggregate site [{$pluginId}:{$key}] must reference a source site via aggregateOf().");
            }

            if (filled($scopeType)) {
                throw new InvalidArgumentException("Aggregate site [{$pluginId}:{$key}] inherits its scope from the source site and must not declare one.");
            }
        } elseif (blank($scopeType)) {
            throw new InvalidArgumentException("Site [{$pluginId}:{$key}] must declare a scopeable in [{$mode->value}] mode.");
        }
    }

    /** Unique site id: "{pluginId}:{key}". */
    public function id(): string
    {
        return "{$this->pluginId}:{$this->key}";
    }

    /**
     * Own scopeable; aggregate sites inherit the source site's value at runtime.
     *
     * @return array{scope_type: string, scope_id: int}|null
     */
    public function scopeable(): ?array
    {
        if (blank($this->scopeType)) {
            return null;
        }

        return [
            'scope_type' => $this->scopeType,
            'scope_id' => $this->scopeId,
        ];
    }

    public function isAggregate(): bool
    {
        return $this->mode === SiteMode::Aggregate;
    }

    /**
     * Resolved sub-scope pair for a suffix declared by a registered component,
     * e.g. "footer". Built during registration so the frontend never re-derives
     * a pair that an explicit override may have changed.
     *
     * @return array{scope_type: string, scope_id: int}|null
     */
    public function getScope(string $suffix): ?array
    {
        return $this->scopes[$suffix] ?? null;
    }

    /** @return array<string, array{scope_type: string, scope_id: int}> */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /** Aggregate sites read across tenants and must never be written through. */
    public function isReadOnly(): bool
    {
        return $this->isAggregate();
    }

    /**
     * Effective option: this site's own options override the module's
     * config("{$this->pluginId}.options.*") defaults. Top-level module config
     * never participates — it cannot be overridden per site by definition.
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        $value = data_get($this->options, $name);

        return $value !== null ? $value : config("{$this->pluginId}.options.{$name}", $default);
    }

    /**
     * All effective options: the module's config("{$this->pluginId}.options.*")
     * defaults, recursively overridden by this site's own options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return array_replace_recursive(
            (array) config("{$this->pluginId}.options", []),
            $this->options,
        );
    }

    /** Full route name for this site's route key, e.g. "posts.show" => "sn-cms.main.posts.show". */
    public function routeName(string $name): string
    {
        return $this->routeNamePrefix . $name;
    }

    /** Generate one of this site's URLs; tenant parameters come from the current context. */
    public function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return sn_route($this->routeName($name), $parameters, $absolute);
    }
}
