<?php

namespace Wsmallnews\Support\Filament\Concerns;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\PageConfiguration;
use Filament\Resources\ResourceConfiguration;
use Illuminate\Support\Str;
use UnitEnum;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Helpers\FilamentModelHelper;
use Wsmallnews\Support\Support\Utils as SupportUtils;

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

    public static function getGloballySearchableAttributes(): array
    {
        // 优先从插件配置覆盖
        $configValue = static::getConfigurationValue('globallySearchableAttributes');
        if ($configValue !== null) {
            return $configValue;
        }

        // 从模型约定获取
        $model = static::getModel();
        if (class_exists($model)) {
            $fields = FilamentModelHelper::resolveKeywordSearchFields($model);

            if ([(new $model)->getKeyName()] == $fields) {
                // 如果 resolveKeywordSearchFields 只获取到了 兜底 的 [(new $model)->getKeyName()]，则合并默认的 getGloballySearchableAttributes
                $fields = array_merge($fields, parent::getGloballySearchableAttributes());
            }
        }

        // Filament 默认行为（返回 [$recordTitleAttribute]）
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
     * 解析当前资源/页面的 scopeable（scope_type + scope_id）
     *
     * 优先级：配置实例键（scopeable，经模块 scopeables 解析）→ 显式 pair（scope_type/scope_id）
     * → 模块 main 实例；module_id 与显式 pair 均缺失时抛异常，
     * 不再回落 'default'，scope 配置错误必须在开发期暴露。
     *
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException
     */
    public static function getScopeable(): array
    {
        $instanceKey = static::getConfigurationValue('scopeable');

        if (filled($instanceKey)) {
            return static::resolveScopeableInstance($instanceKey);
        }

        $scopeType = static::getConfigurationValue('scopeType');

        if (filled($scopeType)) {
            return [
                'scope_type' => $scopeType,
                'scope_id' => static::getConfigurationValue('scopeId') ?? 0,
            ];
        }

        // 未声明实例键 = 使用模块 main 默认实例
        return static::resolveScopeableInstance('main');
    }

    /**
     * 经模块语境（moduleId，即 config root）解析 scopeable 实例
     *
     * @throws InvalidScopeException
     */
    protected static function resolveScopeableInstance(string $key): array
    {
        $configRoot = static::getConfigurationValue('moduleId');

        if (blank($configRoot)) {
            throw InvalidScopeException::unresolvableScope(static::class, $key);
        }

        return SupportUtils::getScopeFromInstances("{$configRoot}.scopeables", $key)->toArray();
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
     * 资源归属的消费模块标识（插件 id）：用于模块级注册表（如 CompositionRegistry）寻址。
     * 注册时由 RegistersConfigurable 自动注入（注册即归属），未注册语境为 null
     */
    public static function getModuleId(): ?string
    {
        return static::getConfigurationValue('moduleId');
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
     * 安全获取当前 config
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
