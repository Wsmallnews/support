<?php

namespace Wsmallnews\Support\Filament\Filters;

use Closure;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Wsmallnews\Support\Helpers\FilamentModelHelper;

class FilterComponents
{

    public static function morphFilter(
        string $type,
        string | Htmlable | Closure | null $label, 
        array | Arrayable | string | Closure | null $options = null,
        array $keywordSearchFields = [],
        string | Closure | null $morphKeywordPlaceholder = null,
        array $morphFields = [],
    )
    {
        return Tables\Filters\Filter::make($type)
            ->label($label)
            ->schema([
                Schemas\Components\FusedGroup::make([
                    Forms\Components\Select::make('morph_type')
                        ->options($options)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('morph_keyword')
                        ->placeholder($morphKeywordPlaceholder)
                        ->columnSpan(2),
                ])->label($label)->columns(3),
            ])
            ->query(function (Builder $query, array $data) use ($type, $morphFields, $keywordSearchFields): Builder {
                return $query
                    ->when(
                        $data['morph_keyword'],
                        function (Builder $query, $morph_keyword) use ($type, $data, $morphFields, $keywordSearchFields) {
                            $panel = Filament::getCurrentPanel();
                            $morphType = $data['morph_type'] ?? null;
                            $morphKeyword = $morph_keyword ?? ($data['morph_keyword'] ?? '');
                            $morphTypeFieldName = $morphFields['morph_type'] ?? $type . '_type';
                            $morphIdFieldName = $morphFields['morph_id'] ?? $type . '_id';

                            return $query->where($morphTypeFieldName, $morphType)
                                ->where(function ($query) use ($type, $panel, $morphIdFieldName, $morphKeyword, $keywordSearchFields) {
                                    $query->where($morphIdFieldName, $morphKeyword)
                                        ->orWhereHas($type, function ($query) use ($panel, $morphKeyword, $keywordSearchFields) {
                                            $query
                                                ->withoutGlobalScope($panel->getTenancyScopeName())       // 如果 panel 的auth model 是 user, 则需要去掉全局作用域 (不影响正常查询用户的日志)
                                                ->where(function ($query) use ($keywordSearchFields, $morphKeyword) {
                                                    foreach ($keywordSearchFields as $field) {
                                                        $query->orWhere($field, 'like', "%{$morphKeyword}%");
                                                    }
                                                });
                                        });
                                });
                        }
                    );
            })
            ->indicateUsing(function (array $data) use ($type): array {
                $indicators = [];

                if ($data['morph_type'] ?? null) {
                    $indicators[] = Indicator::make(Str::ucfirst($type . ' : ') . FilamentModelHelper::getTypeLabel($data['morph_type']))
                        ->removeField('morph_type');
                }
                if ($data['morph_keyword'] ?? null) {
                    $indicators[] = Indicator::make(Str::ucfirst($type . ' keyword: ') . $data['morph_keyword'])
                        ->removeField('morph_keyword');
                }
                return $indicators;
            });
    }


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
