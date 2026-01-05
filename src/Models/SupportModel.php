<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Models\Concerns\Scopeable;

class SupportModel extends Model
{
    use Scopeable;

    public function scopeScopeTenant(Builder $query): Builder
    {
        if (has_tenancy()) {
            return $query->where('team_id', current_tenant()->id);
        } else {
            return $query->whereNull('team_id');
        }
    }

    /**
     * 范围查询 和 租户查询合并
     */
    public function scopeSnScope(Builder $query, string $scope_type, int | array $scope_id = 0): Builder
    {
        return $query->scopeable($scope_type, $scope_id)->scopeTenant();
    }
}
