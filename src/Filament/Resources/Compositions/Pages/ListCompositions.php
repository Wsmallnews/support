<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wsmallnews\Support\Filament\Resources\Compositions\CompositionResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class ListCompositions extends ListRecords
{
    use Scopeable;

    protected static string $resource = CompositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
