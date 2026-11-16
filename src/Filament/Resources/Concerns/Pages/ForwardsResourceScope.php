<?php

namespace Wsmallnews\Support\Filament\Resources\Concerns\Pages;

/**
 * Forwards the scope declared on the resource to its pages (create / edit / list).
 *
 * Resource pages are not registered components themselves, so the scope lives
 * on the resource class and is read through here.
 */
trait ForwardsResourceScope
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

    public static function getBaseScopeable(): array
    {
        return static::getResource()::getBaseScopeable();
    }

    public static function getRelatedScopeable(string $related): array
    {
        return static::getResource()::getRelatedScopeable($related);
    }
}
