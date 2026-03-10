<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Tags\Tag as BaseTagModel;
use Wsmallnews\Support\Models\Concerns\Scopeable;

class Tag extends BaseTagModel
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
}
