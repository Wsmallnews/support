<?php

namespace Wsmallnews\Support\Traits\Resources\Pages;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Livewire\Attributes\Locked;

trait CanScopeable
{

    #[Locked]
    public string $scope_type = 'default';

    #[Locked]
    public int $scope_id = 0;


    public function mountCanScopeable()
    {
        static::getResource()::setAttribute('scope_type', $this->scope_type);
        static::getResource()::setAttribute('scope_id', $this->scope_id);
    }

    
    public function getScopeInfo()
    {
        return ['scope_type' => $this->scope_type, 'scope_id' => $this->scope_id];
    }


    public function fillScopeable($data)
    {
        return array_merge($data, [
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
        ]);
    }
}
