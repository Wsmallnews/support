<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;
use Wsmallnews\Support\Filament\Resources\Pages\PageResource;

class EditPage extends EditRecord
{
    use Scopeable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
