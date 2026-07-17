<?php

namespace Wsmallnews\Support\Filament\Concerns;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\ResourceConfiguration;
use Filament\Pages\PageConfiguration;
use Illuminate\Support\Str;
use UnitEnum;

trait CanBeConfigured
{
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

    public static function getScopeType(): string
    {
        // 默认 读取 config 中设置的 scopeable 信息
        return static::getConfigurationValue('scopeType') ?? static::getCurrentPlugin()->getScopeType();
    }

    public static function getScopeId(): int
    {
        // 默认 读取 config 中设置的 scopeable 信息
        return static::getConfigurationValue('scopeId') ?? static::getCurrentPlugin()->getScopeId();
    }

    // ========================================================================
    // Core resolution
    // ========================================================================


    /**
     * 从 Configuration 对象安全获取属性值
     */
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


    /**
     * 从 Configuration 对象获取自定义属性
     */
    protected static function resolveCustomProperty(string $key, mixed $default = null): mixed
    {
        $configuration = static::getSafeConfiguration();
        if ($configuration && method_exists($configuration, 'getCustomProperty')) {
            return $configuration->getCustomProperty($key, $default);
        }

        return $default;
    }

    /**
     * 获取当前资源所属的插件
     */
    protected static function getCurrentPlugin(): ?Plugin
    {
        $current = static::class;

        return method_exists($current, 'getEssentialsPlugin') ? $current::getEssentialsPlugin() : null;
    }

    /**
     * 安全获取当前 config
     *
     * @return ResourceConfiguration|PageConfiguration|null
     */
    protected static function getSafeConfiguration(): PageConfiguration | ResourceConfiguration | null
    {
        try {
            $config = static::getConfiguration();
        } catch (\Throwable) {
            $config = null;
        }

        return $config;
    }
}
