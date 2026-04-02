<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
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
                ->label(__('filament-activity-log::activity.table.column.id')),
            ExportColumn::make('event')
                ->label(__('filament-activity-log::activity.table.column.event'))
                ->formatStateUsing(fn ($state) => ActivityLogEvent::tryFrom($state)?->getLabel() ?? ucfirst((string) $state)),
            ExportColumn::make('subject_label')
                ->label(__('filament-activity-log::activity.table.column.subject_type'))
                ->formatStateUsing(fn ($state, $record) => ActivityLogFormat::getTypeLabel($record->subject_type)),
            ExportColumn::make('subject_type')
                ->label(__('filament-activity-log::activity.table.column.subject_type'))
                ->formatStateUsing(fn ($state, $record) => ActivityLogFormat::getTitle($record->subject)),
            ExportColumn::make('subject_id')
                ->label(__('filament-activity-log::activity.table.column.subject_id')),
            ExportColumn::make('description')
                ->label(__('filament-activity-log::activity.table.column.description')),
            ExportColumn::make('causer_type')
                ->label(__('filament-activity-log::activity.table.column.causer_type')),
            ExportColumn::make('causer_id')
                ->label(__('filament-activity-log::activity.table.column.causer_id')),
            ExportColumn::make('properties.ip_address')
                ->label(__('filament-activity-log::activity.table.column.ip_address')),
            ExportColumn::make('properties.user_agent')
                ->label(__('filament-activity-log::activity.table.column.browser')),
            ExportColumn::make('created_at')
                ->label(__('filament-activity-log::activity.table.column.created_at')),
        ];
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public function getFileName(Export $export): string
    {
        return "activity-log-" . date('YmdHis') . "-{$export->getKey()}";
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your activity log export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
