<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Support\Filament\Resources\Compositions\CompositionResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class EditComposition extends EditRecord
{
    use Scopeable;

    protected static string $resource = CompositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
