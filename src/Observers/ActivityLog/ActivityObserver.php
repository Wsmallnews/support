<?php

namespace Wsmallnews\Support\Observers\ActivityLog;

use Filament\Facades\Filament;
use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    public function creating(Activity $activity): void
    {
        $activity->team_id = current_tenant()?->id;

        $channel = 'frontend';
        if ($panel = Filament::getCurrentPanel()) {
            // 当前在后台面板
            $channel = 'panel-' . $panel->getId();
        }

        $activity->properties = $activity->properties->merge(array_filter([
            'channel' => $channel,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));
    }
}
