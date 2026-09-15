<?php

namespace Wsmallnews\Support\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum CompositionStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Draft = 'draft';

    case Published = 'published';

    case Hidden = 'hidden';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Draft => __('sn-support::composition.status.draft'),
            self::Published => __('sn-support::composition.status.published'),
            self::Hidden => __('sn-support::composition.status.hidden'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'primary',
            self::Hidden => 'gray',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedClipboardDocumentList,
            self::Published => Heroicon::OutlinedEye,
            self::Hidden => Heroicon::OutlinedEyeSlash,
        };
    }
}
