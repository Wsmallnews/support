<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wsmallnews\Support\Enums\ScheduledTaskStatus;
use Wsmallnews\Support\Support\Utils;

class ScheduledTask extends SupportModel
{
    protected $table = 'sn_scheduled_tasks';

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'status' => ScheduledTaskStatus::class,
    ];

    /** Boot the model and apply default scope attributes. */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-fill team_id from the current context (null on platform surfaces)
        static::creating(function ($model) {
            if (! isset($model->team_id)) {
                $model->team_id = current_tenant()?->getKey();
            }
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', ScheduledTaskStatus::Pending);
    }

    public function scopeExecuted($query)
    {
        return $query->where('status', ScheduledTaskStatus::Executed);
    }

    public function scopeDue($query)
    {
        return $query->pending()->where('scheduled_at', '<=', now());
    }

    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }
}
