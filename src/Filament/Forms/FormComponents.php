<?php

namespace Wsmallnews\Support\Filament\Forms;

use Wsmallnews\Support\Filament\Forms\Concerns\HasCommonFormComponents;
use Wsmallnews\Support\Filament\Forms\Concerns\HasContentTypeGroup;
use Wsmallnews\Support\Filament\Forms\Concerns\HasEditorComponents;
use Wsmallnews\Support\Filament\Forms\Concerns\HasUploadComponents;

/** Form component factories applying the sn-support.form_components config. */
class FormComponents
{
    use HasCommonFormComponents;
    use HasContentTypeGroup;
    use HasEditorComponents;
    use HasUploadComponents;
}
