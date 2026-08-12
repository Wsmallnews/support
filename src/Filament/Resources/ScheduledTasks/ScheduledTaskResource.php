<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks;

use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Pages\ListScheduledTasks;
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Pages\ViewScheduledTask;
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class ScheduledTaskResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getPages(): array
    {
        return [
            'index' => ListScheduledTasks::route('/'),
            'view' => ViewScheduledTask::route('/{record}'),
        ];
    }
}
