<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;

class SubjectTimelineAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema(fn (Schema $schema) => $schema
            ->schema([
                ViewField::make('activities')
                    ->label(__('sn-support::activity.timeline'))
                    ->hiddenLabel()
                    /** @phpstan-ignore-next-line */
                    ->view('sn-support::filament.forms.timeline')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component) {
                        /** @var Model|null $record */
                        $record = $component->getRecord();

                        $component->state($this->getActivities($record));
                    }),
            ]));

        $this->modalHeading(__('sn-support::activity.action.timeline.label'));
        $this->label(__('sn-support::activity.action.timeline.label'));
        $this->color('gray');
        $this->icon(Heroicon::Clock);
        $this->modalSubmitAction(false);
        $this->modalCancelAction(false);
        $this->slideOver();
    }

    /**
     * Retrieve activities for the given record.
     *
     * Fetches activities where the record is the subject or the causer.
     */
    protected function getActivities(?Model $record): Collection
    {
        if (! $record) {
            return collect();
        }

        $with = ['causer', 'subject'];

        // Get activitiesAsSubject where the record is the subject
        if ($record instanceof Activity) {
            $subject = $record->subject;
            /** @phpstan-ignore-next-line */
            $activities = $subject ? $subject->activitiesAsSubject()->with($with)->latest()->limit(50)->get() : collect();
        } elseif (method_exists($record, 'activitiesAsSubject')) {
            $activities = $record->activitiesAsSubject()->with($with)->latest()->limit(50)->get();
        } else {
            $activities = $record->morphMany(ActivitylogConfig::activityModel(), 'subject')->with($with)->latest()->limit(50)->get();
        }

        $activities = $activities ?? collect();

        return $activities;
    }

    /**
     * Create a new timeline action instance.
     *
     * @param  string|null  $name  The action name (defaults to 'timeline')
     * @return static The action instance
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'subjectTimeline');
    }
}
