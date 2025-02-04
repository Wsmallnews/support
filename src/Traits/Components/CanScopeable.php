<?php

namespace Wsmallnews\Support\Traits\Components;

use Livewire\Attributes\Locked;

trait CanScopeable
{
    #[Locked]
    public string $scope_type = 'default';

    #[Locked]
    public int $scope_id = 0;
}
