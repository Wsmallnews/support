<?php

namespace Wsmallnews\Support\Traits\Resources\Pages;

use Livewire\Attributes\Locked;

trait CanScopeable
{
    #[Locked]
    public string $scope_type = 'default';

    #[Locked]
    public int $scope_id = 0;

    public function bootCanScopeable()
    {
        static::getResource()::setAttribute('scope_type', $this->scope_type);
        static::getResource()::setAttribute('scope_id', $this->scope_id);
    }

    public function getScopeInfo(): array
    {
        return ['scope_type' => $this->scope_type, 'scope_id' => $this->scope_id];
    }

    public function fillScopeable($data): array
    {
        return array_merge($data, [
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
        ]);
    }

    /**
     * Mutate the form data before creating a record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->fillScopeable($data);

        return parent::mutateFormDataBeforeCreate($data);
    }
}
