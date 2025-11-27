<?php

namespace Wsmallnews\Support\Filament\Resources\Tags\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class EditTag extends EditRecord
{
    use Scopeable;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
