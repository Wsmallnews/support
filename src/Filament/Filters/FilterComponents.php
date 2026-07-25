<?php

namespace Wsmallnews\Support\Filament\Filters;

use Closure;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
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
        string | Closure | null $morphKeywordPlaceholder = null,
        array $morphFields = [],
    ) {
        return Tables\Filters\Filter::make($type)
            ->label($label)
            ->schema([
                Schemas\Components\FusedGroup::make([
                    Forms\Components\Select::make('morph_type')
                        ->options($options)
                        ->live()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('morph_keyword')
                        ->placeholder($morphKeywordPlaceholder)
                        ->disabled(fn (Get $get): bool => blank($get('morph_type')))
                        ->columnSpan(2),
                ])->label($label)->columns(3),
            ])
            ->query(function (Builder $query, array $data) use ($type, $morphFields): Builder {
                $morphTypeFieldName = $morphFields['morph_type'] ?? $type . '_type';
                $morphIdFieldName = $morphFields['morph_id'] ?? $type . '_id';

                return $query
                    ->when(
                        $data['morph_type'] ?? null,
                        fn (Builder $query, $morphType) => $query->where($morphTypeFieldName, $morphType)
                    )
                    ->when(
                        $data['morph_keyword'] ?? null,
                        function (Builder $query, $keyword) use ($data, $type, $morphIdFieldName) {
                            $morphType = $data['morph_type'] ?? null;

                            // 未选择 morphType，则不支持关键字搜索
                            if (! $morphType) {
                                return;
                            }

                            // 根据所选的 morphType，解析出对应的模型类
                            $modelClass = FilamentModelHelper::getModelClassName($morphType);
                            if (! $modelClass || ! class_exists($modelClass)) {
                                // 默认搜索 morph_id 字段
                                $query->where($morphIdFieldName, $keyword);
                                return;
                            }

                            // 获取模型要 keyword search 的字段（关键词搜索用）
                            $searchFields = FilamentModelHelper::resolveKeywordSearchFields($modelClass);
                            $keyName = (new $modelClass)->getKeyName();

                            // 过滤 dot-notation（whereHasMorph 已限定关联表，不需要跨表字段）
                            $plainFields = array_values(array_filter(
                                $searchFields,
                                fn ($f) => ! str_contains($f, '.')  // 移除所有带 . 的字段
                            ));

                            $query->whereHasMorph($type, $modelClass, function ($q) use ($keyword, $plainFields, $keyName) {
                                $q->where(function ($q) use ($keyword, $plainFields, $keyName) {
                                    // 精确 ID 匹配（keyword 可能是数字 ID）
                                    $q->where($keyName, $keyword);

                                    // LIKE 搜索
                                    foreach ($plainFields as $field) {
                                        if ($field !== $keyName) {
                                            $q->orWhere($field, 'like', "%{$keyword}%");
                                        }
                                    }
                                });
                            });
                        }
                    );
            })
            ->indicateUsing(function (array $data) use ($type, $label): array {
                $indicators = [];

                if ($data['morph_type'] ?? null) {
                    $indicators[] = Indicator::make(__('sn-support::support.morph_filter.type_indicator', [
                        'type' => $label,
                        'value' => FilamentModelHelper::getTypeLabel($data['morph_type']),
                    ]))
                        ->removeField('morph_type');
                }
                if ($data['morph_keyword'] ?? null) {
                    $indicators[] = Indicator::make(__('sn-support::support.morph_filter.keyword_indicator', [
                        'type' => $label,
                        'value' => $data['morph_keyword'],
                    ]))
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
