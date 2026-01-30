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
     *
     * @param Table $table
     * @return Table|null
     */
    public static function getCustomTable(Table $table): ?Table
    {
        return self::getCustomProperty('table') instanceof Closure ?
            self::getCustomProperty('table')($table, self::class)
            : null;
    }

    /**
     * 快捷获取自定义的 form schema
     *
     * @param Schema $schema
     * @return Schema|null
     */
    public static function getCustomForm(Schema $schema): ?Schema
    {
        return self::getCustomProperty('form') instanceof Closure ?
            self::getCustomProperty('form')($schema, self::class)
            : null;
    }

    /**
     * 快捷获取自定义的 form array 数组 (比如：filament-nestedset 插件)
     *
     * @param Array $arguments
     * @return Array|null
     */
    public static function getCustomFormArray(Array $arguments): ?Array
    {
        return self::getCustomProperty('form_array') instanceof Closure ?
            self::getCustomProperty('form_array')($arguments, self::class)
            : null;
    }


    /**
     * 快捷获取自定义的 infolist schema
     *
     * @param Schema $schema
     * @return Schema|null
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
     * @param Array $arguments
     * @return Array|null
     */
    public static function getCustomInfolistArray(): ?array
    {
        return self::getCustomProperty('infolist_array') instanceof Closure ?
            self::getCustomProperty('infolist_array')(self::class)
            : null;
    }
}
