<?php

namespace Wsmallnews\Support\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum EditorType: string implements HasColor, HasLabel
{
    use EnumHelper;

    case Textarea = 'textarea';

    case RichEditor = 'rich_editor';

    case MarkdownEditor = 'markdown_editor';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Textarea => '纯文本',
            self::RichEditor => '富文本',
            self::MarkdownEditor => 'Markdown',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Textarea => 'gray',
            self::RichEditor => 'primary',
            self::MarkdownEditor => 'success',
        };
    }
}