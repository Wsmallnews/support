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

/**
 * 查看某条记录的定时任务列表的 Action。
 *
 * 用于在 Post / Product 等实体的表格行操作或详情页中，
 * 以 slide-over 弹层展示该记录的所有定时调度任务。
 */
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

    /**
     * 自定义查询条件
     */
    public function modifyQueryUsing(Closure $modifyQueryUsing): static
    {
        $this->modifyQueryUsing = $modifyQueryUsing;

        return $this;
    }

    /**
     * 查询当前记录的定时任务
     */
    protected function getScheduledTasks(?Model $record): Collection
    {
        if (! $record) {
            return collect();
        }

        // 优先使用模型上定义的 scheduledTasks 关联
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

    /**
     * Create a new action instance.
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'viewScheduledTasks');
    }
}
