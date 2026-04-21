<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasAuth
{
    /**
     * 当前认证用户
     */
    public ?Model $user = null;         // 当前认证用户

    public function getAuthUser(): ?Model
    {
        return $this->user;
    }

    public function hasAuthUser(): bool
    {
        return $this->user !== null;
    }
}
