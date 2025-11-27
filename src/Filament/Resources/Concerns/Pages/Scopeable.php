<?php

namespace Wsmallnews\Support\Filament\Resources\Concerns\Pages;

/**
 * 可以方便的读取 resource 中设置的 scope 信息
 */
trait Scopeable
{
    public static function getScopeType(): string
    {
        return static::getResource()::getScopeType();
    }

    public static function getScopeId(): int
    {
        return static::getResource()::getScopeId();
    }

    public static function getScopeable(): array
    {
        return static::getResource()::getScopeable();
    }
}
