<?php

namespace Wsmallnews\Support\Concerns;

use Illuminate\Support\HtmlString;

trait UserIdentifiable
{
    public function getSnId(): int
    {
        return $this->id ?? 0;
    }

    public function getSnName(): string | HtmlString | null
    {
        return $this->name ?? null;
    }

    public function getSnAvatarUrl(): string | HtmlString | null
    {
        return $this->avatar_url ?? null;
    }

    public function getSnEmail(): string | HtmlString | null
    {
        return $this->email ?? null;
    }

    public function getSnHrefUrl(): string | HtmlString | null
    {
        return null;
    }
}
