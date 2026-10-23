<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Support\Filament\Resources\Compositions\CompositionResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class CreateComposition extends CreateRecord
{
    use Scopeable;

    protected static string $resource = CompositionResource::class;

    /**
     * Mutate the form data before creating a record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Merge the scope parameters
        $data = array_merge($data, static::getScopeable());

        return parent::mutateFormDataBeforeCreate($data);
    }
}
