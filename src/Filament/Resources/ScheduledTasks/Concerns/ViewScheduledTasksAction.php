<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/** Shows the scheduled tasks of one record in a slide-over, for row actions or detail pages. */
class ViewScheduledTasksAction extends Action
{
    protected ?Closure $modifyQueryUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema(fn (Schema $schema) => $schema
            ->schema([
                ViewField::make('scheduledTasks')
                    ->hiddenLabel()
                    ->view('sn-support::filament.forms.scheduled-tasks')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component) {
                        /** @var Model|null $record */
                        $record = $component->getRecord();

                        $component->state($this->getScheduledTasks($record));
                    }),
            ]));

        $this->label(__('sn-support::scheduled_task.action.view_tasks.label'));
        $this->modalHeading(__('sn-support::scheduled_task.action.view_tasks.label'));
        $this->color('gray');
        $this->icon(Heroicon::Clock);
        $this->modalSubmitAction(false);
        $this->modalCancelAction(false);
        $this->slideOver();
    }

    /** Custom query constraints. */
    public function modifyQueryUsing(Closure $modifyQueryUsing): static
    {
        $this->modifyQueryUsing = $modifyQueryUsing;

        return $this;
    }

    /** Query the scheduled tasks of the current record. */
    protected function getScheduledTasks(?Model $record): Collection
    {
        if (! $record) {
            return collect();
        }

        // Prefer the model's own scheduledTasks relation
        if (method_exists($record, 'scheduledTasks')) {
            $query = $record->scheduledTasks();
        } else {
            $query = $record->morphMany(SupportUtils::getScheduledTaskModel(), 'schedulable');
        }

        if ($this->modifyQueryUsing) {
            $query = $this->evaluate($this->modifyQueryUsing, [
                'query' => $query,
            ]) ?? $query;
        }

        return $query->orderBy('scheduled_at', 'asc')->limit(50)->get();
    }

    /** Create a new action instance. */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'viewScheduledTasks');
    }
}
