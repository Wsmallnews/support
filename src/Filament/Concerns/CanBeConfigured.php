<?php

namespace Wsmallnews\Support\Filament\Concerns;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Resources\ResourceConfiguration;
use Illuminate\Support\Str;
use UnitEnum;

trait CanBeConfigured
{
    // ========================================================================
    // Navigation getters
    // ========================================================================

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::resolveConfiguredValue('navigationGroup') ?? parent::getNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return static::resolveConfiguredValue('navigationLabel') ?? parent::getNavigationLabel();
    }

    public static function getNavigationIcon(): string | BackedEnum | null
    {
        return static::resolveConfiguredValue('navigationIcon') ?? parent::getNavigationIcon();
    }

    public static function getActiveNavigationIcon(): string | BackedEnum | null
    {
        return static::resolveConfiguredValue('activeNavigationIcon') ?? (parent::getActiveNavigationIcon() ?? static::getNavigationIcon());
    }

    public static function getNavigationSort(): ?int
    {
        return static::resolveConfiguredValue('navigationSort') ?? parent::getNavigationSort();
    }

    public static function getNavigationParentItem(): ?string
    {
        return static::resolveConfiguredValue('navigationParentItem') ?? parent::getNavigationParentItem();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::resolveConfiguredValue('shouldRegisterNavigation') ?? parent::shouldRegisterNavigation();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::resolveConfiguredValue('navigationBadge') ?? parent::getNavigationBadge();
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return static::resolveConfiguredValue('navigationBadgeColor') ?? parent::getNavigationBadgeColor();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::resolveConfiguredValue('navigationBadgeTooltip') ?? parent::getNavigationBadgeTooltip();
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return static::resolveConfiguredValue('subNavigationPosition') ?? parent::getSubNavigationPosition();
    }

    // ========================================================================
    // Label getters
    // ========================================================================

    public static function getModelLabel(): string
    {
        return static::resolveConfiguredValue('modelLabel') ?? parent::getModelLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::resolveConfiguredValue('pluralModelLabel') ?? parent::getPluralModelLabel();
    }

    // ========================================================================
    // Global Search getters
    // ========================================================================

    public static function isGloballySearchable(): bool
    {
        return static::resolveConfiguredValue('isGloballySearchable') ?? parent::isGloballySearchable();
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        return static::resolveConfiguredValue('globalSearchResultsLimit') ?? parent::getGlobalSearchResultsLimit();
    }

    // ========================================================================
    // Tenant getters
    // ========================================================================

    public static function isScopedToTenant(): bool
    {
        return static::resolveConfiguredValue('isScopedToTenant') ?? parent::isScopedToTenant();
    }

    // ========================================================================
    // Parent resource getter
    // ========================================================================

    public static function getParentResource(): ?string
    {
        return static::resolveConfiguredValue('parentResource') ?? parent::getParentResource();
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

    /**
     * 优先获取 configuration ，其次获取 配置文件中的配置
     *
     * @param string $property
     * @return mixed
     */
    protected static function resolveConfiguredValue(string $property): mixed
    {
        $configValue = static::getConfigurationValue($property);
        if ($configValue !== null) {
            return $configValue;
        }

        $configValue = static::getConfigFileValue($property);
        if ($configValue !== null) {
            return $configValue;
        }

        return null;
    }


    protected static function getConfigurationValue(string $property): mixed
    {
        $value = null;
        $configuration = static::getSafeConfiguration();
        if ($configuration) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($configuration, $getter)) {
                $value = $configuration->{$getter}();
            }
        }

        return $value;
    }

    /**
     * 从配置文件中获取配置值
     *
     * @param string $property
     * @return mixed
     */
    protected static function getConfigFileValue(string $property): mixed
    {
        $current = static::class;
        $plugin = method_exists($current, 'getEssentialsPlugin') ? $current::getEssentialsPlugin() : null;

        if (! $plugin) {
            return null;
        }

        $type = is_subclass_of($current, Resource::class) ? 'resources' : 'pages';

        $configs = $plugin->getPanelRegister($type);
        $currentConfig = $configs[$current] ?? null;

        $snakeKey = Str::snake($property);

        if ($currentConfig && array_key_exists($snakeKey, $currentConfig)) {
            $value = $currentConfig[$snakeKey];

            return $value instanceof Closure ? $value() : $value;
        }

        return null;
    }

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
     *
     * @return Plugin|null
     */
    protected static function getCurrentPlugin(): ?Plugin
    {
        $current = static::class;

        return method_exists($current, 'getEssentialsPlugin') ? $current::getEssentialsPlugin() : null;
    }


    /**
     * 安全获取当前 config
     *
     * @return ResourceConfiguration|null
     */
    protected static function getSafeConfiguration(): ?ResourceConfiguration
    {
        try {
            $config = static::getConfiguration();   
        } catch (\Throwable) {
            $config = null;
        } 

        return $config;
    }
}
