<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Features\Site\SiteContext;

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

    /** Get the authenticated user: explicit props win, otherwise the bound site decides. */
    public function getAuthUser(): ?HasSnIdentifiable
    {
        if ($this->authUser !== null) {
            return $this->authUser;
        }

        $user = app(SiteContext::class)->getAuthUser();

        return $user instanceof HasSnIdentifiable ? $user : null;
    }

    /** Whether a user is authenticated. */
    public function hasAuthUser(): bool
    {
        return $this->getAuthUser() !== null;
    }
}
