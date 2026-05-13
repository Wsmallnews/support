<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;
use Wsmallnews\Support\Enums\ActivityLogEvent;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\ActivityLogFormat;

class ActivityLogExporter extends Exporter
{
    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        return ActivitylogConfig::activityModel();
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('event')
                ->label(__('sn-support::activity.table.column.event'))
                ->formatStateUsing(fn ($state) => ActivityLogEvent::tryFrom($state)?->getLabel() ?? ucfirst((string) $state)),
            ExportColumn::make('subject_type')
                ->label(__('sn-support::activity.table.column.subject_type'))
                ->formatStateUsing(fn ($state, $record) => ActivityLogFormat::getTypeLabel($record->subject_type)),
            ExportColumn::make('subject_title')
                ->label(__('sn-support::activity.table.column.subject_title'))
                ->formatStateUsing(fn ($state, $record) => ActivityLogFormat::getTitle($record->subject)),
            ExportColumn::make('subject_id')
                ->label(__('sn-support::activity.table.column.subject_id')),
            ExportColumn::make('description')
                ->label(__('sn-support::activity.table.column.description')),
            ExportColumn::make('causer_type')
                ->label(__('sn-support::activity.table.column.causer_type'))
                ->formatStateUsing(fn ($state, $record) => ActivityLogFormat::getTypeLabel($record->causer_type)),
            ExportColumn::make('causer.name')
                ->label(__('sn-support::activity.table.column.causer_name'))
                ->formatStateUsing(fn ($state, $record) => $record->causer?->name ?? ''),
            ExportColumn::make('causer_id')
                ->label(__('sn-support::activity.table.column.causer_id')),
            ExportColumn::make('properties.ip_address')
                ->label(__('sn-support::activity.table.column.ip_address')),
            ExportColumn::make('properties.user_agent')
                ->label(__('sn-support::activity.table.column.browser')),
            ExportColumn::make('created_at')
                ->label(__('sn-support::activity.table.column.created_at')),
        ];
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public function getFileName(Export $export): string
    {
        return 'activity-log-' . date('YmdHis') . "-{$export->getKey()}";
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('sn-support::activity.action.export.completed_body', [
            'count' => $export->successful_rows,
            'unit' => str('row')->plural($export->successful_rows),
        ]);
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= __('sn-support::activity.action.export.failed_body', [
                'fail_count' => $failedRowsCount,
                'fail_unit' => str('row')->plural($failedRowsCount),
            ]);
        }

        return $body;
    }
}
