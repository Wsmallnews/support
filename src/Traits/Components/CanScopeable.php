<?php

namespace Wsmallnews\Support\Traits\Components;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Livewire\Attributes\Locked;

trait CanScopeable
{

    #[Locked]
    public string $scope_type = 'default';

    #[Locked]
    public int $scope_id = 0;

}
