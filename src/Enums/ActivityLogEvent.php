<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum ActivityLogEvent: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Created = 'created';

    case Updated = 'updated';

    case Deleted = 'deleted';

    case Restored = 'restored';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Created => __('sn-support::activity.event.created'),
            self::Updated => __('sn-support::activity.event.updated'),
            self::Deleted => __('sn-support::activity.event.deleted'),
            self::Restored => __('sn-support::activity.event.restored'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Created => 'success',
            self::Updated => 'warning',
            self::Deleted => 'danger',
            self::Restored => 'gray',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Created => Heroicon::Plus->getIconForSize(IconSize::Small),
            self::Updated => Heroicon::Pencil->getIconForSize(IconSize::Small),
            self::Deleted => Heroicon::Trash,
            self::Restored => Heroicon::ArrowUturnLeft,
        };
    }
}
