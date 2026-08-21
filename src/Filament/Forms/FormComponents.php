<?php

namespace Wsmallnews\Support\Filament\Forms;

use Wsmallnews\Support\Filament\Forms\Concerns\HasContentTypeGroup;
use Wsmallnews\Support\Filament\Forms\Concerns\HasEditorComponents;
use Wsmallnews\Support\Filament\Forms\Concerns\HasUploadComponents;

/**
 * 通用表单组件工厂
 *
 * 提供快捷组件，统一应用 sn-support.form_components 配置：
 */
class FormComponents
{
    use HasContentTypeGroup;
    use HasEditorComponents;
    use HasUploadComponents;
}
