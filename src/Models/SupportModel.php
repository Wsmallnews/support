<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Models\Concerns\Scopeable;

/**
 * Base model for all support package models.
 * Provides scopeable and tenant-aware functionality.
 */
class SupportModel extends Model
{
    use Scopeable;

    /**
     * Scope query to current tenant.
     */
    public function scopeScopeTenant(Builder $query): Builder
    {
        if (has_tenancy()) {
            return $query->where('team_id', current_tenant()->id);
        }

        return $query->whereNull('team_id');
    }

    /**
     * Combined scope query: scopeable + tenant.
     * This is a convenience method for common filtering patterns.
     */
    public function scopeSnScope(Builder $query, string $scope_type, int | array $scope_id = 0): Builder
    {
        return $query->scopeable($scope_type, $scope_id)->scopeTenant();
    }

    /**
     * Boot the model and apply default scope attributes.
     */
    // protected static function boot(): void
    // {
    //     parent::boot();

    //     // Auto-fill team_id on creation if tenancy is enabled
    //     static::creating(function ($model) {
    //         if (has_tenancy() && ! isset($model->team_id)) {
    //             $model->team_id = current_tenant()?->id;
    //         }
    //     });
    // }
}
