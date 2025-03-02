<?php

namespace Wsmallnews\Support\Traits\Components;

use Livewire\Attributes\Locked;

trait CanScopeable
{
    #[Locked]
    public string $scope_type = 'default';

    #[Locked]
    public int $scope_id = 0;


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
}
