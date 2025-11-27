<?php

namespace Wsmallnews\Support\Filament\Resources\Tags\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class ListTags extends ListRecords
{
    use Scopeable;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
