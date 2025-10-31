<?php

namespace Wsmallnews\Support\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait Scopeable
{
    /**
     * 范围类型查询
     */
    public function scopeScopeType(Builder $query, string $scope_type): Builder
    {
        return $query->where('scope_type', $scope_type);
    }

    /**
     * 范围值查询
     */
    public function scopeScopeId(Builder $query, int | array $scope_id = 0): Builder
    {
        $scope_id = Arr::wrap($scope_id);

        return $query->whereIn('scope_id', $scope_id);
    }

    /**
     * 范围查询
     */
    public function scopeScopeable(Builder $query, string $scope_type, int | array $scope_id = 0): Builder
    {
        return $query->scopeType($scope_type)->scopeId($scope_id);
    }
}
