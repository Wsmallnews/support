<?php

namespace Wsmallnews\Support\Filament\Resources\Concerns\Pages;

/** Convenience access to the scope configured on the resource. */
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
