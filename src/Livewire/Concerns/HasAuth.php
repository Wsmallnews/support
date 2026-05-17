<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait HasAuth
{
    /**
     * 当前认证用户
     */
    public Model | Authenticatable | null $user = null;         // 当前认证用户

    /**
     * 设置当前认证用户
     */
    public function authUser(Model | Authenticatable | null $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * 获取当前认证用户
     */
    public function getAuthUser(): Model | Authenticatable | null
    {
        return $this->user;
    }

    /**
     * 是否存在认证用户
     */
    public function hasAuthUser(): bool
    {
        return $this->user !== null;
    }
}
