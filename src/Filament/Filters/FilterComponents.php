<?php

namespace Wsmallnews\Support\Filament\Filters;

use Filament\Forms;
use Filament\Schemas;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class FilterComponents
{
    /**
     * 创建时间，更新时间 筛选
     */
    public static function createUpdateRangeFilter(): array
    {
        return [
            static::dateTimeRangeFilter('created_at', __('sn-support::support.date_filter.created')),
            static::dateTimeRangeFilter('updated_at', __('sn-support::support.date_filter.updated')),
        ];
    }

    /**
     * 时间区间筛选
     *
     * @param  string  $field_name
     * @param  string | null  $label
     */
    public static function dateTimeRangeFilter($field_name, $label = null): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make($field_name)
            ->schema([
                Schemas\Components\Group::make()->schema([
                    Forms\Components\DatePicker::make($field_name . '_from')->label(($label ?? '') . __('sn-support::support.date_filter.from'))->columnSpan(1),
                    Forms\Components\DatePicker::make($field_name . '_until')->label(($label ?? '') . __('sn-support::support.date_filter.until'))->columnSpan(1),
                ])->columns(2),
            ])
            ->query(function (Builder $query, array $data) use ($field_name): Builder {
                return $query
                    ->when(
                        $data[$field_name . '_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate($field_name, '>=', $date),
                    )
                    ->when(
                        $data[$field_name . '_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate($field_name, '<=', $date),
                    );
            });
    }
}
