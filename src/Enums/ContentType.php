<?php

namespace Wsmallnews\Support\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum ContentType: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Textarea = 'textarea';

    case Richtext = 'richtext';

    case Markdown = 'markdown';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Textarea => '纯文本',
            self::Richtext => '富文本',
            self::Markdown => 'Markdown',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Textarea => 'gray',
            self::Richtext => 'primary',
            self::Markdown => 'success',
        };
    }

    public function getIcon(): string | BackedEnum | null
    {
        return match ($this) {
            self::Textarea => Heroicon::OutlinedBars3BottomLeft,
            self::Richtext => Heroicon::OutlinedDocumentText,
            self::Markdown => Heroicon::OutlinedCodeBracket,
        };
    }
}
