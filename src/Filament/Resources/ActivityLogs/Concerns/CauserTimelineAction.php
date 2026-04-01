<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;

class CauserTimelineAction extends Action
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema(fn(Schema $schema) => $schema
            ->schema([
                ViewField::make('activities')
                    ->label(__('filament-activity-log::activity.timeline'))
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

        $this->modalHeading(__('filament-activity-log::activity.action.timeline.label'));
        $this->label(__('filament-activity-log::activity.action.timeline.label'));
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

        // Get activities where the record is the subject
        if ($record instanceof Activity) {
            $causer = $record->causer;
            /** @phpstan-ignore-next-line */
            $activities = $causer ? $causer->actions()->with($with)->latest()->limit(50)->get() : collect();
        } elseif (method_exists($record, 'actions')) {
            $activities = $record->actions()->with($with)->latest()->limit(50)->get();
        } else {
            $activities = $record->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'causer')->with($with)->latest()->limit(50)->get();
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
        return parent::make($name ?? 'causerTimeline');
    }
}
