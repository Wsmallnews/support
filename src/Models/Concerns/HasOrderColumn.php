<?php

namespace Wsmallnews\Support\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * order_column 排序支持：创建时留空自动填充
 *
 * 取 max(order_column) + 1 填充，刻度与表格拖拽重排（1..N）一致。
 * 需要按租户等维度隔离序号的模型覆盖 modifyOrderColumnQuery() 自由拼接查询条件。
 */
trait HasOrderColumn
{
    public static function bootHasOrderColumn(): void
    {
        static::creating(function (Model $model) {
            if (! is_null($model->order_column)) {
                return;
            }

            $query = $model->modifyOrderColumnQuery(static::query());

            $model->order_column = ((int) $query->max('order_column')) + 1;
        });
    }

    /**
     * order_column 计算查询，默认全表；需要隔离序号的模型覆盖（如 where('team_id', $this->team_id)）
     */
    protected function modifyOrderColumnQuery(Builder $query): Builder
    {
        return $query;
    }
}
