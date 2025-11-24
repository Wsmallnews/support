<?php

namespace Wsmallnews\Support\Filament\Pages\Concerns;

trait Scopeable
{
    protected static string $scopeType = 'default';

    protected static int $scopeId = 0;

    public static function scopeType(?string $scope_type): void
    {
        static::$scopeType = $scope_type;
    }

    public static function scopeId(int $scope_id): void
    {
        static::$scopeId = $scope_id;
    }

    public static function getScopeType(): string
    {
        return static::$scopeType;
    }

    public static function getScopeId(): int
    {
        return static::$scopeId;
    }

    public static function getScopeable(): array
    {
        return [
            'scope_type' => static::getScopeType(),
            'scope_id' => static::getScopeId(),
        ];
    }
}
