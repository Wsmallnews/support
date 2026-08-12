<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Wsmallnews\Support\Enums\ScheduledTaskStatus;
use Wsmallnews\Support\Filament\Actions\ActionComponents;
use Wsmallnews\Support\Filament\Filters\FilterComponents;
use Wsmallnews\Support\Filament\Tables\ColumnComponents;
use Wsmallnews\Support\Helpers\FilamentModelHelper;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class ScheduledTaskTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                static::IDColumn(),
                static::schedulableColumn(),
                static::actionColumn(),
                static::scheduledAtColumn(),
                static::statusColumn(),
                static::executedAtColumn(),
                static::createdAtColumn(),
                static::updatedAtColumn(),
            ])
            ->defaultSort('scheduled_at', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with(['schedulable']))
            ->searchPlaceholder(__('sn-support::scheduled_task.table.filter.search_placeholder'))
            ->filtersFormWidth(Width::Medium)
            ->filters([
                static::statusFilter(),
                static::schedulableFilter(),
                FilterComponents::dateTimeRangeFilter('scheduled_at', __('sn-support::scheduled_task.table.filter.scheduled_at')),
            ])
            ->recordActions([
                ...ActionComponents::recordActions([
                    ViewAction::make(),
                    static::deleteAction(),
                ]),
            ])
            ->toolbarActions([
                ...ActionComponents::toolbarActions([
                    static::deleteBulkAction(),
                ]),
            ]);
    }

    // ---------------------------- Columns --------------------------------

    protected static function IDColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('id')
            ->label('ID')
            ->searchable()
            ->sortable()
            ->alignCenter()
            ->toggleable();
    }

    protected static function schedulableColumn()
    {
        return ColumnComponents::morphColumn(
            'schedulable_type',
            __('sn-support::scheduled_task.table.column.schedulable'),
            fn ($record) => $record->schedulable,
            fn ($record) => $record->schedulable_type,
            fn ($record) => $record->schedulable_id,
        );
    }

    protected static function actionColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('action')
            ->label(__('sn-support::scheduled_task.table.column.action'))
            ->badge()
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    protected static function scheduledAtColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('scheduled_at')
            ->label(__('sn-support::scheduled_task.table.column.scheduled_at'))
            ->sortable()
            ->toggleable();
    }

    protected static function statusColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('status')
            ->label(__('sn-support::scheduled_task.table.column.status'))
            ->badge()
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    protected static function executedAtColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('executed_at')
            ->label(__('sn-support::scheduled_task.table.column.executed_at'))
            ->placeholder(__('sn-support::scheduled_task.table.column.no_executed_placeholder'))
            ->sortable()
            ->toggleable();
    }

    protected static function createdAtColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('created_at')
            ->label(__('sn-support::scheduled_task.table.column.created_at'))
            ->sortable()
            ->toggleable();
    }

    protected static function updatedAtColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('updated_at')
            ->label(__('sn-support::scheduled_task.table.column.updated_at'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    // ---------------------------- Filters --------------------------------

    protected static function statusFilter()
    {
        return \Filament\Tables\Filters\SelectFilter::make('status')
            ->label(__('sn-support::scheduled_task.table.filter.status_label'))
            ->options(ScheduledTaskStatus::class);
    }

    protected static function actionFilter()
    {
        return \Filament\Tables\Filters\Filter::make('action')
            ->label(__('sn-support::scheduled_task.table.column.action'))
            ->form([
                \Filament\Forms\Components\TextInput::make('value')
                    ->label(__('sn-support::scheduled_task.table.filter.action_placeholder')),
            ])
            ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($q, $v) => $q->where('action', $v)));
    }

    protected static function schedulableFilter()
    {
        return FilterComponents::morphFilter(
            type: 'schedulable',
            label: __('sn-support::scheduled_task.table.column.schedulable'),
            options: function () {
                $modelClass = SupportUtils::getScheduledTaskModel();

                $types = $modelClass::query()
                    ->distinct()
                    ->whereNotNull('schedulable_type')
                    ->pluck('schedulable_type', 'schedulable_type');

                return FilamentModelHelper::getTypeOptions($types);
            },
            morphKeywordPlaceholder: __('sn-support::scheduled_task.table.filter.schedulable_keyword_placeholder'),
        );
    }

    // ---------------------------- Actions --------------------------------

    protected static function deleteAction()
    {
        return DeleteAction::make()
            ->modalHeading(__('sn-support::scheduled_task.action.delete.heading'))
            ->modalDescription(__('sn-support::scheduled_task.action.delete.confirmation'));
    }

    protected static function deleteBulkAction()
    {
        return DeleteBulkAction::make()
            ->modalDescription(__('sn-support::scheduled_task.action.bulk.delete.confirmation'));
    }
}
