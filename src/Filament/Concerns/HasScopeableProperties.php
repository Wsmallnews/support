<?php

namespace Wsmallnews\Support\Filament\Concerns;

/**
 * Shared base trait for scopeable functionality in Filament components.
 * Provides scope_type and scope_id management for resource isolation.
 */
trait HasScopeableProperties
{
    protected static string $scopeType = 'default';

    protected static int $scopeId = 0;

    /**
     * Set the scope type for this resource/page.
     */
    public static function scopeType(?string $scope_type): void
    {
        static::$scopeType = $scope_type;
    }

    /**
     * Set the scope ID for this resource/page.
     */
    public static function scopeId(int $scope_id): void
    {
        static::$scopeId = $scope_id;
    }

    /**
     * Get the current scope type.
     */
    public static function getScopeType(): string
    {
        return static::$scopeType;
    }

    /**
     * Get the current scope ID.
     */
    public static function getScopeId(): int
    {
        return static::$scopeId;
    }

    /**
     * Get the scopeable array for database queries.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public static function getScopeable(): array
    {
        return [
            'scope_type' => static::getScopeType(),
            'scope_id' => static::getScopeId(),
        ];
    }
}
