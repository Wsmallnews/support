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
        // 合并 scopeinfo 参数
        $data = array_merge($data, static::getResource()::getScopeable());

        // 合并 tag type 参数
        $data['type'] = static::getResource()::getTagType();

        return parent::mutateFormDataBeforeCreate($data);
    }
}
