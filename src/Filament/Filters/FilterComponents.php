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

                            // Keyword search needs a selected morph type
                            if (! $morphType) {
                                return;
                            }

                            // Resolve the model class from the selected morph type
                            $modelClass = FilamentModelHelper::getModelClassName($morphType);
                            if (! $modelClass || ! class_exists($modelClass)) {
                                // Search morph_id by default
                                $query->where($morphIdFieldName, $keyword);

                                return;
                            }

                            // Keyword searchable fields of the model
                            $searchFields = FilamentModelHelper::resolveKeywordSearchFields($modelClass);
                            $keyName = (new $modelClass)->getKeyName();

                            // Split plain fields from relation paths
                            $plainFields = [];
                            $relationFields = [];

                            foreach ($searchFields as $field) {
                                if (str_contains($field, '.')) {
                                    $parts = explode('.', $field);
                                    $column = array_pop($parts);           // Last segment is the column
                                    $relation = implode('.', $parts);      // The rest is the relation
                                    $relationFields[$relation][] = $column;
                                } else {
                                    $plainFields[] = $field;
                                }
                            }

                            $query->whereHasMorph($type, $modelClass, function ($q) use ($keyword, $plainFields, $relationFields, $keyName) {
                                $q->where(function ($q) use ($keyword, $plainFields, $relationFields, $keyName) {
                                    // Exact id match, since a keyword may be a numeric id
                                    $q->where($keyName, $keyword);

                                    // LIKE on this table's own columns
                                    foreach ($plainFields as $field) {
                                        if ($field !== $keyName) {
                                            $q->orWhere($field, 'like', "%{$keyword}%");
                                        }
                                    }

                                    // Relation fields: one whereHas per relation
                                    foreach ($relationFields as $relation => $columns) {
                                        $q->orWhereHas($relation, function ($relQ) use ($keyword, $columns) {
                                            $relQ->where(function ($innerQ) use ($keyword, $columns) {
                                                foreach ($columns as $column) {
                                                    $innerQ->orWhere($column, 'like', "%{$keyword}%");
                                                }
                                            });
                                        });
                                    }
                                });
                            });
                        }
                    );
            })
            ->indicateUsing(function (array $data) use ($label): array {
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
     * Status filter driven by an enum.
     *
     * @param  class-string<\BackedEnum>  $enumClass  Status enum
     * @param  string  $field  Column name
     * @param  string | null  $label  Filter label, defaults to the translation
     */
    public static function statusFilter(string $enumClass, string $field = 'status', ?string $label = null): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make($field)
            ->label($label ?? __('sn-support::support.status_filter.label'))
            ->options($enumClass);
    }

    /** Created-at and updated-at range filter. */
    public static function createUpdateRangeFilter(): array
    {
        return [
            static::dateTimeRangeFilter('created_at', __('sn-support::support.date_filter.created')),
            static::dateTimeRangeFilter('updated_at', __('sn-support::support.date_filter.updated')),
        ];
    }

    /**
     * Date range filter for one column.
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
