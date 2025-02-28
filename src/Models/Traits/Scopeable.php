<?php

namespace Wsmallnews\Support\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Scopeable
{
    /**
     * 范围类型查询
     * 
     * @param Builder $query
     * @param string $scope_type
     * @return Builder
     */
    public function scopeScopeType(Builder $query, $scope_type): Builder
    {
        return $query->where('scope_type', $scope_type);
    }

    /**
     * 范围值查询
     * 
     * @param Builder $query
     * @param int $scope_id
     * @return Builder
     */
    public function scopeScopeId(Builder $query, $scope_id): Builder
    {
        return $query->where('scope_id', $scope_id);
    }

    /**
     * 范围查询
     * 
     * @param Builder $query
     * @param string $scope_type
     * @param int $scope_id
     * @return Builder
     */
    public function scopeScopeable(Builder $query, $scope_type, $scope_id = 0): Builder
    {
        return $query->scopeType($scope_type)->scopeId($scope_id);
    }
}
