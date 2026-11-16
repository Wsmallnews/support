<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Features\Site\Site;

/**
 * Defaults for a Filament component that only works when it was registered with
 * a configuration (a site's resources()/pages() entry, or a panel registration).
 *
 * The real implementation lives in CanBeConfigured, which reads the injected
 * configuration and overrides every method here. A consumer-built component that
 * forgets to use it hits these defaults and fails with a readable exception
 * instead of an undefined-method fatal — and never with a made-up scope that
 * would silently read another data space.
 */
trait RequiresConfiguration
{
    /**
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException
     */
    public static function getScopeable(): array
    {
        throw InvalidScopeException::missingSiteBinding(static::class);
    }

    public static function getScopeType(): string
    {
        return static::getScopeable()['scope_type'];
    }

    public static function getScopeId(): int
    {
        return static::getScopeable()['scope_id'];
    }

    /** @return array<string, string> */
    public static function getRelatedScopeSuffixes(): array
    {
        return [];
    }

    /**
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException
     */
    public static function getRelatedScopeable(string $related): array
    {
        throw InvalidScopeException::missingSiteBinding(static::class);
    }

    /** No sub-scope by default; a component or its registration may declare one. */
    public static function getScopeSuffix(): ?string
    {
        return null;
    }

    /**
     * Module owning this component, used to address module-level registries.
     *
     * @throws InvalidScopeException
     */
    public static function getModuleId(): ?string
    {
        throw InvalidScopeException::missingComponentConfiguration(static::class, 'module id');
    }

    /** Null for components registered directly on a panel (they belong to no site). */
    public static function getSite(): ?Site
    {
        return null;
    }
}
