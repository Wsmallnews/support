<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;
use Wsmallnews\Support\Filament\Resources\Pages\PageResource;

class ListPages extends ListRecords
{
    use Scopeable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
