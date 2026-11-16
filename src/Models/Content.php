<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Support\Utils;

class Content extends SupportModel
{
    protected $table = 'sn_contents';

    protected $casts = [
        'content_type' => ContentType::class,
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

    public function contentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }
}
