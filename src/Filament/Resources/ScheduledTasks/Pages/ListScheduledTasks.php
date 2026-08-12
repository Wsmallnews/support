<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks\Pages;

use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\ScheduledTaskResource;

class ListScheduledTasks extends ListRecords
{
    protected static string $resource = ScheduledTaskResource::class;
}
