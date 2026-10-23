<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Enums\ContentType;

trait HasContentType
{
    public ContentType $contentType = ContentType::Textarea;

    /** Whether the content type is a rich editor. */
    public function isFormattedContent(): bool
    {
        return in_array($this->contentType, [ContentType::Richtext, ContentType::Markdown]);
    }
}
