<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 定时任务小部件。
 *
 * 用于在 Post / Product 等实体的详情页中，
 * 通过 footer/header widget 展示该记录的定时调度任务。
 */
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
            // 优先使用模型上定义的 scheduledTasks 关联
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
