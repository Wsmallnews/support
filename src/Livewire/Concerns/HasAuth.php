<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasAuth
{
    
    /**
     * 当前认证用户
     */
    public ?Model $user = null;         // 当前认证用户

    
    public function getUser(): ?Model
    {
        return $this->user;
    }


    public function hasUser(): bool
    {
        return $this->user !== null;
    }

}
