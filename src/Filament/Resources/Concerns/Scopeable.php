<?php

namespace Wsmallnews\Support\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Support\Filament\Concerns\HasScopeableProperties;

/**
 * Scopeable trait for Filament Resources.
 * Automatically applies scope filters to Eloquent queries.
 */
trait Scopeable
{
    use HasScopeableProperties;

    /**
     * Apply scopeable filter to the Eloquent query.
     * Override this method to customize scope application.
     */
    protected static function applyScopeableToQuery(Builder $query): Builder
    {
        return $query->scopeable(static::getScopeType(), static::getScopeId());
    }
}
