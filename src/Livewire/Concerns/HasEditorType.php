<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Wsmallnews\Support\Enums\EditorType;

trait HasEditorType
{
    public EditorType $editorType = EditorType::Textarea;
}
