<?php

namespace Wsmallnews\Support\Filament\Concerns;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\PageConfiguration;
use Filament\Resources\ResourceConfiguration;
use Illuminate\Support\Str;
use UnitEnum;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Helpers\FilamentModelHelper;

trait CanBeConfigured
{
    /**
     * Sub-scope this component belongs to, e.g. "footer". Null means the site's
     * main scope. Declared by overriding this method on the class (a property
     * cannot be redeclared over a trait); a registration entry may override it
     * with `'scope_suffix' => '...'`.
     */
    public static function getScopeSuffix(): ?string
    {
        return null;
    }

    // ========================================================================
    // Navigation getters
    // ========================================================================

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::getConfigurationValue('navigationGroup') ?? parent::getNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return static::getConfigurationValue('navigationLabel') ?? parent::getNavigationLabel();
    }

    public static function getNavigationIcon(): string | BackedEnum | null
    {
        return static::getConfigurationValue('navigationIcon') ?? parent::getNavigationIcon();
    }

    public static function getActiveNavigationIcon(): string | BackedEnum | null
    {
        return static::getConfigurationValue('activeNavigationIcon') ?? (parent::getActiveNavigationIcon() ?? static::getNavigationIcon());
    }

    public static function getNavigationSort(): ?int
    {
        return static::getConfigurationValue('navigationSort') ?? parent::getNavigationSort();
    }

    public static function getNavigationParentItem(): ?string
    {
        return static::getConfigurationValue('navigationParentItem') ?? parent::getNavigationParentItem();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::getConfigurationValue('shouldRegisterNavigation') ?? parent::shouldRegisterNavigation();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getConfigurationValue('navigationBadge') ?? parent::getNavigationBadge();
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return static::getConfigurationValue('navigationBadgeColor') ?? parent::getNavigationBadgeColor();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getConfigurationValue('navigationBadgeTooltip') ?? parent::getNavigationBadgeTooltip();
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return static::getConfigurationValue('subNavigationPosition') ?? parent::getSubNavigationPosition();
    }

    // ========================================================================
    // Label getters
    // ========================================================================

    public static function getModelLabel(): string
    {
        return static::getConfigurationValue('modelLabel') ?? parent::getModelLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::getConfigurationValue('pluralModelLabel') ?? parent::getPluralModelLabel();
    }

    // ========================================================================
    // Global Search getters
    // ========================================================================

    public static function isGloballySearchable(): bool
    {
        return static::getConfigurationValue('isGloballySearchable') ?? parent::isGloballySearchable();
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        return static::getConfigurationValue('globalSearchResultsLimit') ?? parent::getGlobalSearchResultsLimit();
    }

    public static function getGloballySearchableAttributes(): array
    {
        // Plugin configuration wins
        $configValue = static::getConfigurationValue('globallySearchableAttributes');
        if ($configValue !== null) {
            return $configValue;
        }

        // Fall back to the model convention
        $model = static::getModel();
        if (class_exists($model)) {
            $fields = FilamentModelHelper::resolveKeywordSearchFields($model);

            if ([(new $model)->getKeyName()] == $fields) {
                // When resolveKeywordSearchFields() only returns the fallback key, merge Filament's attributes
                $fields = array_merge($fields, parent::getGloballySearchableAttributes());
            }
        }

        // Filament default (returns [$recordTitleAttribute])
        return parent::getGloballySearchableAttributes();
    }

    // ========================================================================
    // Tenant getters
    // ========================================================================

    public static function isScopedToTenant(): bool
    {
        return static::getConfigurationValue('isScopedToTenant') ?? parent::isScopedToTenant();
    }

    // ========================================================================
    // Parent resource getter
    // ========================================================================

    public static function getParentResource(): ?string
    {
        return static::getConfigurationValue('parentResource') ?? parent::getParentResource();
    }

    // ========================================================================
    // Scopeable
    // ========================================================================

    /**
     * Resolve the scope for this resource or page: the pair injected at
     * registration. A component registered directly on a panel without one
     * throws — a broken scope must fail instead of silently falling back.
     *
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException
     */
    public static function getScopeable(): array
    {
        $scopeType = static::getConfigurationValue('scopeType');

        if (filled($scopeType)) {
            return [
                'scope_type' => $scopeType,
                'scope_id' => static::getConfigurationValue('scopeId') ?? 0,
            ];
        }

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

    /**
     * Default related-scope map declared on the class. A registration entry may
     * override it with 'related_scope_suffix'.
     *
     * @return array<string, string>
     */
    public static function getRelatedScopeSuffixes(): array
    {
        return [];
    }

    /** Scope of the site itself, without this component's own sub-scope suffix. */
    public static function getBaseScopeable(): array
    {
        $scopeable = static::getScopeable();
        $separator = strpos($scopeable['scope_type'], '::');

        if ($separator === false) {
            return $scopeable;
        }

        return [
            'scope_type' => substr($scopeable['scope_type'], 0, $separator),
            'scope_id' => $scopeable['scope_id'],
        ];
    }

    /** Suffix declared for one related key, if any. */
    public static function getRelatedScopeSuffix(string $related): ?string
    {
        $suffixes = static::getConfigurationValue('relatedScopeSuffix') ?? [];
        $suffix = $suffixes[$related] ?? null;

        return filled($suffix) ? (string) $suffix : null;
    }

    /**
     * Data space for reading one related resource's rows: the site's base scope
     * plus the declared suffix, or the base scope when none is declared. The
     * suffix is not validated here — a suffix that names no registered sub-scope
     * simply matches no rows.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public static function getRelatedScopeable(string $related): array
    {
        $base = static::getBaseScopeable();
        $suffix = static::getRelatedScopeSuffix($related);

        return $suffix === null
            ? $base
            : ['scope_type' => "{$base['scope_type']}::{$suffix}", 'scope_id' => $base['scope_id']];
    }

    /**
     * Site this component was registered for; null for components registered directly
     * on the panel. Backend code reads the site from here, never from SiteContext.
     */
    public static function getSite(): ?Site
    {
        $siteId = static::getConfigurationValue('siteId');

        return $siteId === null ? null : app(SiteRegistry::class)->get($siteId);
    }

    /**
     * Module that owns this resource, used to address module-level registries.
     * Injected by the site registrar (RegistersSites) at registration; null outside that context.
     */
    public static function getModuleId(): ?string
    {
        return static::getConfigurationValue('moduleId');
    }

    // ========================================================================
    // Core resolution
    // ========================================================================

    /** Safely read a property from the Configuration object. */
    protected static function getConfigurationValue(string $property): mixed
    {
        $configuration = static::getSafeConfiguration();
        if (! $configuration) {
            return null;
        }

        $getter = 'get' . Str::studly($property);
        if (method_exists($configuration, $getter)) {
            return $configuration->{$getter}();
        }

        return null;
    }

    /** Read a custom property from the Configuration object. */
    protected static function resolveCustomProperty(string $key, mixed $default = null): mixed
    {
        $configuration = static::getSafeConfiguration();
        if ($configuration && method_exists($configuration, 'getCustomProperty')) {
            return $configuration->getCustomProperty($key, $default);
        }

        return $default;
    }

    /** Read the configuration for the current route; null when no key is bound. */
    protected static function getSafeConfiguration(): PageConfiguration | ResourceConfiguration | null
    {
        try {
            return static::getConfiguration();
        } catch (\Throwable) {
            return null;
        }
    }
}
