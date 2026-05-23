<?php

namespace Wsmallnews\Support\Concerns;

trait UserIdentifiable
{
    public function getSnId(): int
    {
        return $this->id ?? 0;
    }

    public function getSnName(): ?string
    {
        return $this->name ?? null;
    }

    public function getSnAvatarUrl(): ?string
    {
        return $this->avatar_url ?? null;
    }

    public function getSnEmail(): ?string
    {
        return $this->email ?? null;
    }
}
