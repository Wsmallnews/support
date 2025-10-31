<?php

namespace Wsmallnews\Support\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Scopeable
{
    protected static string $scopeType = 'default';

    protected static int $scopeId = 0;


    public static function getScopeType(): string
    {
        return static::$scopeType;
    }


    public static function scopeType(?string $scope_type): void
    {
        static::$scopeType = $scope_type;
    }


    public static function getScopeId(): int
    {
        return static::$scopeId;
    }


    public static function scopeId(int $scope_id): void
    {
        static::$scopeId = $scope_id;
    }

    public static function getScopeInfo(): array
    {
        return [
            'scope_type' => static::$scopeType,
            'scope_id' => static::$scopeId,
        ];
    }


    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()->scopeable(self::$scope_type, self::$scope_id);
    // }
}
