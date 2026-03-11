<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Concerns\Resource;

use BezhanSalleh\PluginEssentials\Concerns\Resource\DelegatesToPlugin;
use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

trait HasCustomProperties
{
    use DelegatesToPlugin;

    public static function getCustomProperties(): ?array
    {
        $pluginResult = static::delegateToPlugin('HasCustomProperties', 'getCustomProperties');

        if (! static::isNoPluginResult($pluginResult) && $pluginResult !== null) {
            return $pluginResult;
        }

        return static::getParentResult('getCustomProperties');
    }

    public static function getCustomProperty(string $key): mixed
    {
        return static::getCustomProperties()[$key] ?? null;
    }

    /**
     * 快捷获取自定义的 table
     */
    public static function getCustomTable(Table $table): ?Table
    {
        return self::getCustomProperty('table') instanceof Closure ?
            self::getCustomProperty('table')($table, self::class)
            : null;
    }

    /**
     * 快捷获取自定义的 form schema
     */
    public static function getCustomForm(Schema $schema): ?Schema
    {
        return self::getCustomProperty('form') instanceof Closure ?
            self::getCustomProperty('form')($schema, self::class)
            : null;
    }

    /**
     * 快捷获取自定义的 form array 数组 (比如：filament-nestedset 插件)
     */
    public static function getCustomFormArray(array $arguments): ?array
    {
        return self::getCustomProperty('form_array') instanceof Closure ?
            self::getCustomProperty('form_array')($arguments, self::class)
            : null;
    }

    /**
     * 快捷获取自定义的 infolist schema
     */
    public static function getCustomInfolist(Schema $schema): ?Schema
    {
        return self::getCustomProperty('infolist') instanceof Closure ?
            self::getCustomProperty('infolist')($schema, self::class)
            : null;
    }

    /**
     * 快捷获取自定义的 infolist array 数组 (比如：filament-nestedset 插件)
     *
     * @param  array  $arguments
     */
    public static function getCustomInfolistArray(): ?array
    {
        return self::getCustomProperty('infolist_array') instanceof Closure ?
            self::getCustomProperty('infolist_array')(self::class)
            : null;
    }

    /**
     * Get scopeable array (legacy method for backward compatibility).
     *
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws CmsException
     */
    public static function getCustomScopeable(): ?array
    {
        return self::getCustomProperty('scopeable');
    }

    /**
     * Get scope type.
     *
     *
     * @throws CmsException
     */
    public static function getCustomScopeType(): ?string
    {
        return self::getCustomScopeable()['scopeType'] ?? null;
    }

    /**
     * Get custom scope ID.
     *
     *
     * @throws CmsException
     */
    public static function getCustomScopeId(): ?int
    {
        return self::getCustomScopeable()['scopeId'] ?? null;
    }
}
