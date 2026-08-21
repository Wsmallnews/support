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

    case Images = 'images';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Textarea => __('sn-support::support.content_type.textarea'),
            self::Richtext => __('sn-support::support.content_type.richtext'),
            self::Markdown => __('sn-support::support.content_type.markdown'),
            self::Images => __('sn-support::support.content_type.images'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Textarea => 'gray',
            self::Richtext => 'primary',
            self::Markdown => 'success',
            self::Images => 'info',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Textarea => Heroicon::OutlinedBars3BottomLeft,
            self::Richtext => Heroicon::OutlinedDocumentText,
            self::Markdown => Heroicon::OutlinedCodeBracket,
            self::Images => Heroicon::OutlinedPhoto,
        };
    }
}
