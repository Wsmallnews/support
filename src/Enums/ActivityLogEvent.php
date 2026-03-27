<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Enums\IconSize;
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
            self::Created => '创建',
            self::Updated => '更新',
            self::Deleted => '删除',
            self::Restored => '恢复',
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

    public function getIcon(): string | BackedEnum | null
    {
        return match ($this) {
            self::Created => Heroicon::Plus->getIconForSize(IconSize::Small),
            self::Updated => Heroicon::Pencil->getIconForSize(IconSize::Small),
            self::Deleted => Heroicon::Trash,
            self::Restored => Heroicon::ArrowUturnLeft,
        };
    }
}
