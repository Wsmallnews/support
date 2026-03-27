<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction as FilamentExportAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Wsmallnews\Support\Enums\ActivityLogEvent;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\ActivityLogCauser;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\ActivityLogFormat;
use Wsmallnews\Support\Helpers\FilamentHelper;
use Wsmallnews\Support\Models\Activity;

class ActivityLogTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                static::eventColumn(),
                static::subjectTypeColumn(),
                static::causerColumn(),
                static::ipAddressColumn(),
                static::userAgentColumn(),
                static::descriptionColumn(),
                static::createdAtColumn(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                static::eventFilter(),
                static::causerFilter(),
                static::subjectTypeFilter(),
                FilamentHelper::dateTimeRangeFilter('created_at', '发生'),

                // Tables\Filters\Filter::make('batch_uuid')
                //     ->label('Batch UUID')
                //     ->query(fn (Builder $query, array $data): Builder => $query->when(
                //         $data['value'] ?? null,
                //         fn (Builder $query, $uuid): Builder => $query->where('batch_uuid', $uuid)
                //     )),
            ])
            ->headerActions([
                // FilamentExportAction::make()
                //     ->exporter(ActivityLogExporter::class)
                //     ->icon('heroicon-m-arrow-down-tray')
                //     ->color('gray'),
            ])
            ->recordActions([
                ActionGroup::make([
                //     // ActivityLogTimelineTableAction::make()
                //     //     ->visible(config('filament-activity-log.table.actions.timeline', true)),
                    ViewAction::make(),

                //     // Action::make('view_batch')
                //     //     ->label(__('filament-activity-log::activity.action.batch.label'))
                //     //     ->icon('heroicon-m-rectangle-stack')
                //     //     ->color('gray')
                //     //     ->visible(fn ($record) => $record->batch_uuid &&
                //     //         (config('filament-activity-log.permissions.enabled') === false || Gate::allows('view', $record))
                //     //     )
                //     //     ->url(fn ($record) => request()->url().'?tableFilters[batch_uuid][value]='.$record->batch_uuid),

                //     static::revertAction(),

                //     // Action::make('restore')
                //     //     ->label(__('filament-activity-log::activity.action.restore.label'))
                //     //     ->icon('heroicon-m-arrow-path')
                //     //     ->color('success')
                //     //     ->requiresConfirmation()
                //     //     ->modalHeading(__('filament-activity-log::activity.action.restore.heading'))
                //     //     ->action(function ($record) {
                //     //         $modelClass = $record->subject_type;
                //     //         if (! $modelClass || ! class_exists($modelClass)) {
                //     //             return;
                //     //         }

                //     //         $attributes = $record->properties['old'] ?? $record->properties['attributes'] ?? [];
                //     //         if (empty($attributes)) {
                //     //             return;
                //     //         }

                //     //         $modelClass::create($attributes);
                //     //         Notification::make()->success()->title(__('filament-activity-log::activity.action.restore.success'))->send();
                //     //     })
                //     //     ->visible(fn ($record) => config('filament-activity-log.table.actions.restore', true) &&
                //     //         $record->event === 'deleted' &&
                //     //         $record->subject === null &&
                //     //         (config('filament-activity-log.permissions.enabled') === false || Gate::allows('restore', $record))
                //     //     ),
                //     // DeleteAction::make()
                //     //     ->requiresConfirmation()
                //     //     ->modalHeading(__('filament-activity-log::activity.action.delete.heading'))
                //     //     ->modalDescription(__('filament-activity-log::activity.action.delete.confirmation'))
                //     //     ->modalSubmitActionLabel(__('filament-activity-log::activity.action.delete.button'))
                //     //     ->visible(fn ($record) => config('filament-activity-log.table.actions.delete', true) &&
                //     //         (config('filament-activity-log.permissions.enabled') === false || Gate::allows('delete', $record))
                //     //     ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription(__('filament-activity-log::activity.action.bulk.delete.confirmation'))
                        ->visible(config('filament-activity-log.table.bulk_actions.delete', true)),

                    BulkAction::make('restore_selected')
                        ->label(__('filament-activity-log::activity.action.bulk.restore.label'))
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('filament-activity-log::activity.action.bulk.restore.label'))
                        ->modalDescription(__('filament-activity-log::activity.action.bulk.restore.confirmation'))
                        ->action(function ($records) {
                            $restoredCount = 0;

                            foreach ($records as $record) {
                                if ($record->event !== 'deleted' || $record->subject !== null) {
                                    continue;
                                }

                                $modelClass = $record->subject_type;
                                if (! $modelClass || ! class_exists($modelClass)) {
                                    continue;
                                }

                                $attributes = $record->properties['old'] ?? $record->properties['attributes'] ?? [];
                                if (empty($attributes)) {
                                    continue;
                                }

                                $modelClass::create($attributes);
                                $restoredCount++;
                            }

                            if ($restoredCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title(__('filament-activity-log::activity.action.bulk.restore.success', ['count' => $restoredCount]))
                                    ->send();
                            }
                        })
                        ->visible(fn () => config('filament-activity-log.table.actions.restore', true)),

                    BulkAction::make('revert_selected')
                        ->label(__('filament-activity-log::activity.action.bulk.revert.label'))
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('filament-activity-log::activity.action.bulk.revert.label'))
                        ->modalDescription(__('filament-activity-log::activity.action.bulk.revert.confirmation'))
                        ->action(function ($records) {
                            $revertedCount = 0;

                            foreach ($records as $record) {
                                if ($record->event !== 'updated' || ! $record->properties->has('old') || ! $record->subject) {
                                    continue;
                                }

                                $record->subject->update($record->properties['old']);
                                $revertedCount++;
                            }

                            if ($revertedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title(__('filament-activity-log::activity.action.bulk.revert.success', ['count' => $revertedCount]))
                                    ->send();
                            }
                        })
                        ->visible(fn () => config('filament-activity-log.table.actions.revert', true)),
                ]),
            ]);
    }

    // ---------------------------- Columns --------------------------------

    protected static function eventColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('event')
            ->label('Event')
            ->badge()
            ->formatStateUsing(fn ($state) => ActivityLogEvent::tryFrom($state)?->getLabel() ?? ucfirst((string) $state))
            ->color(fn ($state) => ActivityLogEvent::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn ($state) => ActivityLogEvent::tryFrom($state)?->getIcon())
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    protected static function subjectTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('subject_type')
            ->label('Subject Type')
            ->formatStateUsing(fn ($state, $record) => ActivityLogFormat::getTitle($record->subject))
            ->description(fn ($record) => $record->subject_type)
            ->url(function ($record) {
                return ActivityLogFormat::getUrl($record->subject);
            })
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    protected static function causerColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('causer.name')
            ->label('Causer')
            ->description(fn ($record) => $record->causer?->email)
            ->url(function ($record) {
                return ActivityLogFormat::getUrl($record->causer);
            })
            ->searchable()
            ->sortable()
            ->toggleable();
    }


    protected static function ipAddressColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('properties.ip_address')
            ->label('IP Address')
            ->searchable()
            ->toggleable();
    }

    protected static function userAgentColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('properties.user_agent')
            ->label('Browser')
            ->limit(50)
            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                $state = $column->getState();
                if (strlen($state) <= 50) {
                    return null;
                }

                return $state;
            })
            ->searchable()
            ->toggleable();
    }


    protected static function descriptionColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('description')
            ->label('Description')
            ->limit(50)
            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                $state = $column->getState();
                if (strlen($state) <= 50) {
                    return null;
                }

                return $state;
            })
            ->searchable()
            ->toggleable();
    }


    protected static function createdAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label('Created At')
            ->searchable()
            ->sortable()
            ->toggleable();
    }


    // ---------------------------- Filters --------------------------------

    protected static function eventFilter()
    {
        return Tables\Filters\SelectFilter::make('event')
            ->label('Event')
            ->options(ActivityLogEvent::class);
    }


    protected static function causerFilter()
    {
        return Tables\Filters\SelectFilter::make('causer_id')
            ->label('Causer')
            ->options(function () {
                $causerClass = ActivityLogCauser::resolveModelClass();
                if (! $causerClass || ! class_exists($causerClass)) {
                    return [];
                }

                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = $causerClass::query();

                return $query
                    ->whereIn(
                        'id',
                        Activity::query()
                            ->distinct()
                            ->whereNotNull('causer_id')
                            ->pluck('causer_id')
                    )
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->searchable();
    }

    protected static function subjectTypeFilter()
    {
        return Tables\Filters\SelectFilter::make('subject_type')
            ->label('Subject Type')
            ->options(function () {
                $subjectTypes = Activity::query()
                    ->distinct()
                    ->whereNotNull('subject_type')
                    ->pluck('subject_type', 'subject_type');

                return ActivityLogFormat::getSubjectTypeOptions($subjectTypes);
            });
    }


    protected static function revertAction()
    {
        Action::make('revert')
            ->label(__('filament-activity-log::activity.action.revert.label'))
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('warning')
            ->schema(function ($record) {
                $old = $record->properties['old'] ?? [];
                $attributes = $record->properties['attributes'] ?? [];

                $fields = [];
                foreach ($old as $key => $value) {
                    $currentValue = data_get($attributes, $key);
                    $fields[] = Checkbox::make("revert_attributes.{$key}")
                        ->label($key)
                        ->helperText(__('filament-activity-log::activity.action.revert.helper_text', [
                            'old' => $value,
                            'new' => $currentValue,
                        ]));
                }

                return $fields;
            })
            ->action(function ($record, array $data) {
                $subject = $record->subject;
                if (! $subject) {
                    Notification::make()->danger()->title(__('filament-activity-log::activity.action.revert.subject_not_found'))->send();

                    return;
                }

                $revertData = [];
                $old = $record->properties['old'] ?? [];
                foreach ($data['revert_attributes'] ?? [] as $key => $shouldRevert) {
                    if ($shouldRevert && isset($old[$key])) {
                        $revertData[$key] = $old[$key];
                    }
                }

                if (empty($revertData)) {
                    Notification::make()->warning()->title(__('filament-activity-log::activity.action.revert.nothing_selected'))->send();

                    return;
                }

                $subject->update($revertData);
                Notification::make()->success()->title(__('filament-activity-log::activity.action.revert.success'))->send();
            })
            ->visible(fn ($record) => config('filament-activity-log.table.actions.revert', true) &&
                $record->event === 'updated' &&
                $record->properties->has('old') &&
                $record->subject !== null &&
                (config('filament-activity-log.permissions.enabled') === false || Gate::allows('update', $record))
            );
    }
}
