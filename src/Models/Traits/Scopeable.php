<?php

namespace Wsmallnews\Support\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Livewire\Attributes\Locked;

trait Scopeable
{

    /**
     * 范围类型查询
     */
    public function scopeScopeType(Builder $query, $scope_type): Builder
    {
        return $query->where('scope_type', $scope_type);
    }

    /**
     * 范围值查询
     */
    public function scopeScopeId(Builder $query, $scope_id): Builder
    {
        return $query->where('scope_id', $scope_id);
    }

    /**
     * 范围查询
     */
    public function scopeScopeable(Builder $query, $scope_type, $scope_id = 0): Builder
    {
        return $query->scopeType($scope_type)->scopeId($scope_id);
    }

}
