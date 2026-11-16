<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\ForwardsResourceScope;
use Wsmallnews\Support\Filament\Resources\Pages\PageResource;

class ListPages extends ListRecords
{
    use ForwardsResourceScope;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
