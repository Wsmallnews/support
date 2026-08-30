<?php

namespace Wsmallnews\Support\Contracts;

use Illuminate\Support\HtmlString;

interface HasSnIdentifiable
{
    public function getSnId(): int;

    public function getSnName(): string | HtmlString | null;

    public function getSnAvatarUrl(): string | HtmlString | null;

    public function getSnEmail(): string | HtmlString | null;
}
