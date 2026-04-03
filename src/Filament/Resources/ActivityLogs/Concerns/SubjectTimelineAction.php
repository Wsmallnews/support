<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;

class SubjectTimelineAction extends CauserTimelineAction
{

    /**
     * 查询活动记录
     * 
     * @param Model|null $record
     * @return Collection
     */
    protected function getActivities(?Model $record): Collection
    {
        if (! $record) {
            return collect();
        }

        // Get activitiesAsSubject where the record is the subject
        if ($record instanceof Activity) {
            $subject = $record->subject;
            $query = $subject?->activitiesAsSubject();
        } elseif (method_exists($record, 'activitiesAsSubject')) {
            $query = $record->activitiesAsSubject();
        } else {
            $query = $record->morphMany(ActivitylogConfig::activityModel(), 'subject');
        }

        if (blank($query)) {
            return collect();
        }

        if ($this->modifyQueryUsing) {
            $query = $this->evaluate($this->modifyQueryUsing, [
                'query' => $query,
            ]) ?? $query;
        }

        $activities = $query->with(['causer', 'subject'])->latest()->limit(50)->get();

        return $activities;
    }

    /**
     * Create a new timeline action instance.
     *
     * @param  string|null  $name
     * @return static
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'subjectTimeline');
    }
}
