<?php

namespace Wsmallnews\Support\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum TeamStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Enabled = 'enabled';

    case Disabled = 'disabled';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Enabled => __('sn-support::support.team_status.enabled'),
            self::Disabled => __('sn-support::support.team_status.disabled'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Enabled => 'primary',
            self::Disabled => 'danger',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Enabled => Heroicon::OutlinedCheckCircle,
            self::Disabled => Heroicon::OutlinedNoSymbol,
        };
    }
}
