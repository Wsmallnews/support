<?php

namespace Wsmallnews\Support\Contracts;

interface HasSnSubject
{
    public function getSnSubjectId(): int;

    public function getSnSubjectTitle(): ?string;

    public function getSnSubjectDescription(): ?string;

    public function getSnSubjectCoverUrl(): ?string;
}
