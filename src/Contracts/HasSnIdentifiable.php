<?php

namespace Wsmallnews\Support\Contracts;

interface HasSnIdentifiable
{
    public function getSnId(): int;
    public function getSnName(): ?string;
    public function getSnAvatarUrl(): ?string;
    public function getSnEmail(): ?string;
}
