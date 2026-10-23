<?php

namespace Wsmallnews\Support\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class CreateTag extends CreateRecord
{
    use Scopeable;

    /**
     * Mutate the form data before creating a record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Merge the scope parameters
        $data = array_merge($data, static::getResource()::getScopeable());

        // Merge the tag type
        $data['type'] = static::getResource()::getTagType();

        return parent::mutateFormDataBeforeCreate($data);
    }
}
