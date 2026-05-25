<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction as FilamentExportAction;
use Filament\Actions\ExportBulkAction as FilamentExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;
use Wsmallnews\Support\Enums\ActivityLogEvent;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\ActivityLogFormat;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\SubjectTimelineAction;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Exports\ActivityLogExporter;
use Wsmallnews\Support\Filament\Filters\FilterComponents;

class ActivityLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                static::IDColumn(),
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
                static::onlyPanelFilter(),
                static::eventFilter(),
                static::causerFilter(),
                static::subjectTypeFilter(),
                FilterComponents::dateTimeRangeFilter('created_at', __('sn-support::activity.table.column.created_filter_label')),
            ])
            ->headerActions([
                static::exportHeaderAction(),
            ])
            ->recordActions([
                ActionGroup::make([
                    SubjectTimelineAction::make()->color('info'),
                    ViewAction::make(),
                    static::revertAction(),
                    static::deleteAction(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::exportBulkAction(),
                    static::deleteBulkAction(),
                    static::revertBulkAction(),
                ]),
            ]);
    }

    // ---------------------------- Columns --------------------------------

    protected static function IDColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('id')
            ->label('ID')
            ->searchable()
            ->sortable()
            ->alignCenter()
            ->toggleable();
    }

    protected static function eventColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('event')
            ->label(__('sn-support::activity.table.column.event'))
            ->badge()
            ->formatStateUsing(fn($state) => ActivityLogEvent::tryFrom($state)?->getLabel() ?? ucfirst((string) $state))
            ->color(fn($state) => ActivityLogEvent::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn($state) => ActivityLogEvent::tryFrom($state)?->getIcon())
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    protected static function subjectTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('subject_type')
            ->label(__('sn-support::activity.table.column.subject_info'))
            ->formatStateUsing(fn($state, $record) => new HtmlString('<span class="sn-primary-text">#' . $record->subject_id . '</span> ' . (ActivityLogFormat::getTitle($record->subject))))
            ->description(fn($record) => ActivityLogFormat::getTypeLabel($record->subject_type))
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
            ->label(__('sn-support::activity.table.column.causer_info'))
            ->formatStateUsing(fn($state, $record) => new HtmlString('<span class="sn-primary-text">#' . $record->causer_id . '</span> ' . ($record->causer?->name ?? '')))
            ->description(fn($record) => $record->causer?->email)
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
            ->label(__('sn-support::activity.table.column.ip_address'))
            ->searchable()
            ->toggleable();
    }

    protected static function userAgentColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('properties.user_agent')
            ->label(__('sn-support::activity.table.column.browser'))
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
            ->label(__('sn-support::activity.table.column.description'))
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
            ->label(__('sn-support::activity.table.column.created_at'))
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    // ---------------------------- Filters --------------------------------

    protected static function onlyPanelFilter()
    {
        return Tables\Filters\Filter::make('only_panel')
            ->label(__('sn-support::activity.table.filter.only_panel'))
            ->query(function (Builder $query): Builder {
                $panel = Filament::getCurrentPanel();
                $channel = 'panel-' . $panel->getId();

                return $query->where('properties->channel', $channel);
            });
    }

    protected static function eventFilter()
    {
        return Tables\Filters\SelectFilter::make('event')
            ->label(__('sn-support::activity.table.filter.event'))
            ->options(ActivityLogEvent::class);
    }

    protected static function causerFilter()
    {
        return Tables\Filters\Filter::make('causer')
            ->label(__('sn-support::activity.table.filter.causer'))
            ->schema([
                Schemas\Components\FusedGroup::make([
                    Forms\Components\Select::make('causer_type')
                        ->options(function () {
                            $causerTypes = ActivitylogConfig::activityModel()::query()
                                ->distinct()
                                ->whereNotNull('causer_type')
                                ->pluck('causer_type', 'causer_type');

                            $options = ActivityLogFormat::getTypeOptions($causerTypes);

                            return $options;
                        })
                        ->selectablePlaceholder(false)      // 禁用空选项，默认选择第一个
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('causer_keyword')
                        ->placeholder(__('sn-support::activity.table.filter.causer_keyword.placeholder'))
                        ->columnSpan(2),
                ])->columns(3),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['causer_keyword'],
                        function (Builder $query, $causer_keyword) use ($data) {
                            $panel = Filament::getCurrentPanel();
                            $causer_type = $data['causer_type'] ?? 'user';

                            return $query->where('causer_type', $causer_type)
                                ->where(function ($query) use ($panel, $causer_keyword) {
                                    $query->where('causer_id', $causer_keyword)
                                        ->orWhereHas('causer', function ($query) use ($panel, $causer_keyword) {
                                            $query->withoutGlobalScope($panel->getTenancyScopeName())       // 如果 panel 的auth model 是 user, 则需要去掉全局作用域 (不影响正常查询用户的日志)
                                                ->where(function ($query) use ($causer_keyword) {
                                                    $query->where('name', 'like', "%{$causer_keyword}%")
                                                        ->orWhere('email', 'like', "%{$causer_keyword}%");
                                                });
                                        });
                                });
                        }
                    );
            });
    }

    protected static function subjectTypeFilter()
    {
        return Tables\Filters\SelectFilter::make('subject_type')
            ->label(__('sn-support::activity.table.filter.subject_type'))
            ->options(function () {
                $subjectTypes = ActivitylogConfig::activityModel()::query()
                    ->distinct()
                    ->whereNotNull('subject_type')
                    ->pluck('subject_type', 'subject_type');

                return ActivityLogFormat::getTypeOptions($subjectTypes);
            });
    }

    // ---------------------------- Actions --------------------------------

    protected static function exportHeaderAction()
    {
        return FilamentExportAction::make()
            ->exporter(ActivityLogExporter::class)
            ->icon(Heroicon::ArrowDownTray)
            ->color('gray');
    }

    protected static function revertAction()
    {
        return Action::make('revert')
            ->label(__('sn-support::activity.action.revert.label'))
            ->icon(Heroicon::ArrowUturnLeft)
            ->color('warning')
            ->schema(function ($record) {
                $old = $record->attribute_changes['old'] ?? [];
                $attributes = $record->attribute_changes['attributes'] ?? [];

                $fields = [];
                foreach ($old as $key => $value) {
                    $currentValue = data_get($attributes, $key);
                    $fields[] = Forms\Components\Checkbox::make("revert_attributes.{$key}")
                        ->label($key)
                        ->helperText(__('sn-support::activity.action.revert.helper_text', [
                            'old' => $value,
                            'new' => $currentValue,
                        ]));
                }

                return $fields;
            })
            ->action(function ($record, array $data) {
                $subject = $record->subject;
                if (! $subject) {
                    Notification::make()->danger()->title(__('sn-support::activity.action.revert.subject_not_found'))->send();

                    return;
                }

                $revertData = [];
                $old = $record->attribute_changes['old'] ?? [];
                foreach ($data['revert_attributes'] ?? [] as $key => $shouldRevert) {
                    if ($shouldRevert && isset($old[$key])) {
                        $revertData[$key] = $old[$key];
                    }
                }

                if (empty($revertData)) {
                    Notification::make()->warning()->title(__('sn-support::activity.action.revert.nothing_selected'))->send();

                    return;
                }

                $subject->update($revertData);
                Notification::make()->success()->title(__('sn-support::activity.action.revert.success'))->send();
            })
            ->visible(
                fn($record) => $record->event === 'updated' &&
                    $record->attribute_changes->has('old') &&
                    $record->subject !== null
                // &&
                // (config('filament-activity-log.permissions.enabled') === false || Gate::allows('update', $record))
            );
    }

    protected static function deleteAction()
    {
        return DeleteAction::make()
            ->requiresConfirmation()
            ->modalHeading(__('sn-support::activity.action.delete.heading'))
            ->modalDescription(__('sn-support::activity.action.delete.confirmation'))
            ->modalSubmitActionLabel(__('sn-support::activity.action.delete.button'));
        // ->visible(fn ($record) => Gate::allows('delete', $record))
    }

    protected static function deleteBulkAction()
    {
        return DeleteBulkAction::make()
            ->modalDescription(__('sn-support::activity.action.bulk.delete.confirmation'));
        // ->visible(config('filament-activity-log.table.bulk_actions.delete', true))
    }

    protected static function revertBulkAction()
    {
        return BulkAction::make('revert_selected')
            ->label(__('sn-support::activity.action.bulk.revert.label'))
            ->icon(Heroicon::ArrowUturnLeft)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('sn-support::activity.action.bulk.revert.label'))
            ->modalDescription(__('sn-support::activity.action.bulk.revert.confirmation'))
            ->action(function ($records) {
                $revertedCount = 0;

                foreach ($records as $record) {
                    if ($record->event !== 'updated' || ! $record->attribute_changes->has('old') || ! $record->subject) {
                        continue;
                    }

                    $record->subject->update($record->attribute_changes['old']);
                    $revertedCount++;
                }

                if ($revertedCount > 0) {
                    Notification::make()
                        ->success()
                        ->title(__('sn-support::activity.action.bulk.revert.success', ['count' => $revertedCount]))
                        ->send();
                }
            });
    }

    protected static function exportBulkAction()
    {
        return FilamentExportBulkAction::make()
            ->exporter(ActivityLogExporter::class)
            ->icon(Heroicon::ArrowDownTray)
            ->color('gray');
    }
}
