<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Enums\EditorType;

trait HasEditorType
{

    public EditorType $editorType = EditorType::Textarea;


    /**
     * 是否是 格式化编辑器
     */
    public function isFormattedEditor(): bool
    {
        return in_array($this->editorType, [EditorType::RichEditor, EditorType::MarkdownEditor]);
    }

}
