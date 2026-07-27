<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;

class CauserTimelineAction extends Action
{
    protected ?Closure $modifyQueryUsing = null;

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
     * 自定义查询条件
     */
    public function modifyQueryUsing(Closure $modifyQueryUsing): static
    {
        $this->modifyQueryUsing = $modifyQueryUsing;

        return $this;
    }

    /**
     * 查询活动记录
     */
    protected function getActivities(?Model $record): Collection
    {
        if (! $record) {
            return collect();
        }

        // Get activitiesAsCauser where the record is the subject
        if ($record instanceof Activity) {
            $causer = $record->causer;
            $query = $causer?->activitiesAsCauser();
        } elseif (method_exists($record, 'activitiesAsCauser')) {
            $query = $record->activitiesAsCauser();
        } else {
            $query = $record->morphMany(ActivitylogConfig::activityModel(), 'causer');
        }

        if (blank($query)) {
            return collect();
        }

        if ($this->modifyQueryUsing) {
            $query = $this->evaluate($this->modifyQueryUsing, [
                'query' => $query,
            ]) ?? $query;
        }

        $activities = $query->with([
            'causer',
            'subject',
        ])->latest()->limit(50)->get();

        return $activities;
    }

    /**
     * Create a new timeline action instance.
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'causerTimeline');
    }
}
