<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as ModelsActivity;
use Wsmallnews\Support\Observers\ActivityLog\ActivityObserver;
use Wsmallnews\Support\Support\Utils;

#[ObservedBy([ActivityObserver::class])]
class Activity extends ModelsActivity
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }
}