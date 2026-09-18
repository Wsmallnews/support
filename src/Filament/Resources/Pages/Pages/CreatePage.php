<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;
use Wsmallnews\Support\Filament\Resources\Pages\PageResource;

class CreatePage extends CreateRecord
{
    use Scopeable;

    protected static string $resource = PageResource::class;

    /**
     * Mutate the form data before creating a record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 合并 scopeinfo 参数
        $data = array_merge($data, static::getScopeable());

        return parent::mutateFormDataBeforeCreate($data);
    }
}
