<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum ScheduledTaskStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Pending = 'pending';

    case Executed = 'executed';

    case Cancelled = 'cancelled';

    case Failed = 'failed';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Pending => __('sn-support::scheduled_task.status.pending'),
            self::Executed => __('sn-support::scheduled_task.status.executed'),
            self::Cancelled => __('sn-support::scheduled_task.status.cancelled'),
            self::Failed => __('sn-support::scheduled_task.status.failed'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Executed => 'success',
            self::Cancelled => 'gray',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Executed => Heroicon::OutlinedCheckCircle,
            self::Cancelled => Heroicon::OutlinedXCircle,
            self::Failed => Heroicon::OutlinedExclamationCircle,
        };
    }
}
