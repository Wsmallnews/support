<?php

namespace Wsmallnews\Support\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * order_column support: fills max(order_column) + 1 on create, matching the 1..N scale used
 * by table reordering. Models needing per-tenant sequences override modifyOrderColumnQuery().
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

    /** Query used to compute the next value; override to scope the sequence (e.g. by tenant). */
    protected function modifyOrderColumnQuery(Builder $query): Builder
    {
        return $query;
    }
}
