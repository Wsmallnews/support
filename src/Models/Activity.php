<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as ModelsActivity;
use Wsmallnews\Support\Enums\ActivityLogEvent;
use Wsmallnews\Support\Observers\ActivityLog\ActivityObserver;
use Wsmallnews\Support\Support\Utils;

#[ObservedBy([ActivityObserver::class])]
class Activity extends ModelsActivity
{
    /**
     * 搜索字段（用于全局搜索）。
     */
    public static array $keywordSearchFields = ['description'];

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
