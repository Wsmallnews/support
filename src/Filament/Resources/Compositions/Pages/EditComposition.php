<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Support\Filament\Resources\Compositions\CompositionResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\ForwardsResourceScope;

class EditComposition extends EditRecord
{
    use ForwardsResourceScope;

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
