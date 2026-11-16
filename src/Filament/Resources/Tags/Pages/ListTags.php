<?php

namespace Wsmallnews\Support\Filament\Resources\Tags\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\ForwardsResourceScope;

class ListTags extends ListRecords
{
    use ForwardsResourceScope;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
