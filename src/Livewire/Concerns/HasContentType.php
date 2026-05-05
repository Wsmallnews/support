<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Enums\ContentType;

trait HasContentType
{
    public ContentType $contentType = ContentType::Textarea;

    /**
     * 是否是 格式化编辑器
     */
    public function isFormattedContent(): bool
    {
        return in_array($this->contentType, [ContentType::Richtext, ContentType::Markdown]);
    }
}
