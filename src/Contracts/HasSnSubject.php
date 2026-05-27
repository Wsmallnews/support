<?php

namespace Wsmallnews\Support\Contracts;

use Illuminate\Support\HtmlString;

interface HasSnSubject
{
    public function getSnSubjectId(): int;

    public function getSnSubjectTitle(): string | HtmlString | null;

    public function getSnSubjectDescription(): string | HtmlString | null;

    public function getSnSubjectCoverUrl(): string | HtmlString | null;

    public function getSnSubjectHrefUrl(): string | HtmlString | null;
}
