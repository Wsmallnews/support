<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/** Shows the scheduled tasks of a record, for footer or header widgets on detail pages. */
class ScheduledTasks extends Widget
{
    use CanBeContained;

    #[Reactive]
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'sn-support::filament.resources.scheduled-tasks.widgets.scheduled-tasks';

    public function getViewData(): array
    {
        $tasks = collect();

        if ($this->record && method_exists($this->record, 'scheduledTasks')) {
            // Prefer the model's own scheduledTasks relation
            if (method_exists($this->record, 'scheduledTasks')) {
                $query = $this->record->scheduledTasks();
            } else {
                $query = $this->record->morphMany(SupportUtils::getScheduledTaskModel(), 'schedulable');
            }
            $tasks = $query->orderBy('scheduled_at', 'asc')->limit(50)->get();
        }

        return [
            'tasks' => $tasks,
        ];
    }
}
