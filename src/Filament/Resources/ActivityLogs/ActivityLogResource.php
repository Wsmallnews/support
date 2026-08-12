<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs;

use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class ActivityLogResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }
}
