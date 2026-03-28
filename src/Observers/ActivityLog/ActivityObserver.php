<?php

namespace Wsmallnews\Support\Observers\ActivityLog;

use Wsmallnews\Support\Models\Activity;

class ActivityObserver
{
    public function creating(Activity $activity): void
    {
        $activity->team_id = current_tenant()?->id;

        $activity->properties = $activity->properties->merge(array_filter([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));
    }
}
