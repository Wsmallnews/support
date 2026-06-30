<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as ModelsActivity;
use Wsmallnews\Support\Observers\ActivityLog\ActivityObserver;
use Wsmallnews\Support\Support\Utils;
use Wsmallnews\Support\Enums\ActivityLogEvent;

#[ObservedBy([ActivityObserver::class])]
class Activity extends ModelsActivity
{

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'event' => ActivityLogEvent::class,
        ]);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }
}
