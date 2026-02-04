<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;

trait Scopeable
{
    #[Locked]
    public string $scopeType = 'default';

    #[Locked]
    public int $scopeId = 0;

    public function getScopeable(): array
    {
        return ['scope_type' => $this->scopeType, 'scope_id' => $this->scopeId];
    }

    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }
}
