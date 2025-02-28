<?php

namespace Wsmallnews\Support\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Scopeable
{
    /**
     * 范围类型查询
     *
     * @param  string  $scope_type
     */
    public function scopeScopeType(Builder $query, $scope_type): Builder
    {
        return $query->where('scope_type', $scope_type);
    }

    /**
     * 范围值查询
     *
     * @param  int  $scope_id
     */
    public function scopeScopeId(Builder $query, $scope_id): Builder
    {
        return $query->where('scope_id', $scope_id);
    }

    /**
     * 范围查询
     *
     * @param  string  $scope_type
     * @param  int  $scope_id
     */
    public function scopeScopeable(Builder $query, $scope_type, $scope_id = 0): Builder
    {
        return $query->scopeType($scope_type)->scopeId($scope_id);
    }
}
