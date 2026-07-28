<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Contracts\HasSnIdentifiable;

trait HasAuth
{
    /**
     * 当前认证用户
     */
    public ?HasSnIdentifiable $authUser = null;         // 当前认证用户

    /**
     * 设置当前认证用户
     */
    public function authUser(?HasSnIdentifiable $authUser): static
    {
        $this->authUser = $authUser;

        return $this;
    }

    /**
     * 获取当前认证用户
     */
    public function getAuthUser(): ?HasSnIdentifiable
    {
        return $this->authUser;
    }

    /**
     * 是否存在认证用户
     */
    public function hasAuthUser(): bool
    {
        return $this->authUser !== null;
    }
}
