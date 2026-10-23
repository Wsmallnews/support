<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Contracts\HasSnIdentifiable;

trait HasAuth
{
    /** Current authenticated user. */
    public ?HasSnIdentifiable $authUser = null;         // Current authenticated user

    /** Set the authenticated user. */
    public function authUser(?HasSnIdentifiable $authUser): static
    {
        $this->authUser = $authUser;

        return $this;
    }

    /** Get the authenticated user. */
    public function getAuthUser(): ?HasSnIdentifiable
    {
        return $this->authUser;
    }

    /** Whether a user is authenticated. */
    public function hasAuthUser(): bool
    {
        return $this->authUser !== null;
    }
}
