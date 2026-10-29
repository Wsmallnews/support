<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Models\Concerns;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Wsmallnews\Support\Enums\ActivityLogEvent;

/**
 * Activity logging built on LogsActivity. Override getActivityTitleAttribute(),
 * getActivityIgnoreAttributes() or $recordEvents in the model to customise it.
 */
trait HasActivityLog
{
    use LogsActivity {
        LogsActivity::getActivitylogOptions as spatieGetActivitylogOptions;
    }

    /** Log title column; override in the model. */
    protected function getActivityTitleAttribute(): string
    {
        return 'name';
    }

    /** Attributes excluded from the log; override in the model. */
    protected function getActivityIgnoreAttributes(): array
    {
        return ['order_column', 'updated_at'];
    }

    /** Extra attributes to log; override in the model. */
    protected function getActivityOthersAttributes(): array
    {
        return [];
    }

    /**
     * Activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*', ...$this->getActivityOthersAttributes()])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly($this->getActivityIgnoreAttributes())
            ->setDescriptionForEvent(fn (string $eventName) => $this->getActivityDescription($eventName));
    }

    /**
     * Build the log description.
     *
     * @param  string  $eventName  Event name
     * @return string Description
     */
    protected function getActivityDescription(string $eventName): string
    {
        $label = ActivityLogEvent::tryFrom($eventName)?->getLabel() ?? Str::studly($eventName);
        $title = $this->{$this->getActivityTitleAttribute()} ?? $this->getKey();

        return "{$label}: {$title}";
    }
}
