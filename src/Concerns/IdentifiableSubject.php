<?php

namespace Wsmallnews\Support\Concerns;

use Illuminate\Support\HtmlString;

/** Lets an identity act as a subject too: the subject contract answers with the identity's own values. */
trait IdentifiableSubject
{
    use UserIdentifiable;

    public function getSnSubjectId(): int
    {
        return $this->getSnId();
    }

    public function getSnSubjectTitle(): string | HtmlString | null
    {
        return $this->getSnName();
    }

    public function getSnSubjectDescription(): string | HtmlString | null
    {
        return $this->getSnEmail();
    }

    public function getSnSubjectCoverUrl(): string | HtmlString | null
    {
        return $this->getSnAvatarUrl();
    }
}
