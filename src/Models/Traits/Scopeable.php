<?php

namespace Wsmallnews\Support\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Scopeable
{
    
    /**
     * @sn todo 需要有个地方设置，scope_type 类型
     *
     * @return array
     */
    public function scopeTypeList()
    {
        return [
            'shop' => '商城',
            'store' => '店铺'
        ];
    }


    /**
     * 范围类型查询
     * @param Builder $query
     * @return Builder
     */
    public function scopeScopeType(Builder $query, $scope_type): Builder
    {
        return $query->where('scope_type', $scope_type);
    }


    /**
     * 范围值查询
     * @param Builder $query
     * @return Builder
     */
    public function scopeScopeId(Builder $query, $scope_id): Builder
    {
        return $query->where('scope_id', $scope_id);
    }


    /**
     * 范围查询
     * @param Builder $query
     * @return Builder
     */
    public function scopeScopeInfo(Builder $query, $scope_type, $scope_id = 0): Builder
    {
        return $query->scopeType($scope_type)->scopeId($scope_id);
    }


    /**
     * 范围类型获取器
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getScopeTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['scope_type'] ?? null);

        $list = $this->scopeTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    /**
     * 获取范围类型文字
     */
    protected function scopeTypeText(): Attribute
    {
        return Attribute::make(
            get: fn(string $value, array $attributes) => $this->scopeTypeList()[$attributes['scope_type'] ?? ''] ?? '',
        );
    }
}
