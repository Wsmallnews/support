<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Pages;

use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\ActivityLogResource;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}
