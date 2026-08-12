<?php

namespace Wsmallnews\Support;

use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Wsmallnews\Support\Enums\ScheduledTaskStatus;

class ScheduledTaskRegistry
{
    /**
     * 按 morphType 分组存储的调度动作集合。
     * 结构: [morphType => [action => actionInfo]]
     *
     * @var Collection<string, Collection<string, array>>
     */
    protected ?Collection $scopes;

    public function __construct()
    {
        $this->scopes = collect();
    }

    /**
     * 注册一个调度动作。
     *
     * @param  string  $morphType  实体 morph 别名（如 'sn_product'），用于模块隔离
     * @param  array  $actionInfo  注册项：{action, label, forms, handler, visible}
     */
    public function register(string $morphType, array $actionInfo): static
    {
        $actions = $this->getActions($morphType);
        $action = $actionInfo['action'];

        $this->scopes->put($morphType, $actions->put($action, $actionInfo));

        return $this;
    }

    /**
     * 注册多个调度动作。
     *
     * @param  string  $morphType  实体 morph 别名
     * @param  array  $actionInfos  注册项数组
     */
    public function registers(string $morphType, array $actionInfos): static
    {
        foreach ($actionInfos as $actionInfo) {
            $this->register($morphType, $actionInfo);
        }

        return $this;
    }

    /**
     * 获取指定 morphType 下已注册的所有动作。
     *
     * @return Collection<string, array>
     */
    public function getActions(string $morphType): Collection
    {
        return $this->scopes->get($morphType, collect());
    }

    /**
     * 获取 action => label 映射（用于 Select 的 options）。
     *
     * @return array<string, string>
     */
    public function getActionsOptions(string $morphType): array
    {
        return $this->getActions($morphType)->mapWithKeys(function (array $actionInfo) {
            return [$actionInfo['action'] => $actionInfo['label']];
        })->toArray();
    }

    /**
     * 获取指定动作的自定义表单字段（闭包或数组）。
     */
    public function getActionForms(string $morphType, string $action): array
    {
        $actionInfo = $this->getActions($morphType)->get($action);

        $forms = $actionInfo['forms'] ?? [];

        return $forms instanceof Closure ? app()->call($forms) : (array) $forms;
    }

    /**
     * 检查指定动作是否有自定义表单字段。
     *
     * 用于控制 Repeater 中 payload Fieldset 的显隐：没有额外字段的 action
     * （如 publish/unpublish）不显示 Fieldset；有字段的（如 price_change）才显示。
     *
     * @return bool
     */
    public function hasActionForms(string $morphType, string $action): bool
    {
        $forms = $this->getActionForms($morphType, $action);

        return $forms && count($forms) > 0;
    }

    /**
     * 获取指定动作的执行处理器。
     *
     * @return Closure|null function(ScheduledTask $task, ?array $payload): bool
     */
    public function getHandler(string $morphType, string $action): ?Closure
    {
        $actionInfo = $this->getActions($morphType)->get($action);

        return $actionInfo['handler'] ?? null;
    }

    /**
     * 获取指定动作的 visible 条件（用于状态互斥过滤）。
     *
     * @return Closure|null function(Get $get): bool
     */
    public function getActionVisible(string $morphType, string $action): ?Closure
    {
        $actionInfo = $this->getActions($morphType)->get($action);

        return $actionInfo['visible'] ?? null;
    }

    /**
     * 生成定时任务 Repeater（直接关联 sn_scheduled_tasks 表）。
     *
     * 只加载 pending（未执行）的任务用于编辑；已执行/失败/取消的记录不在表单中显示
     * （它们在数据库中有 executed_at 等记录可审计）。
     *
     * @param  string  $morphType  实体 morph 别名（从 registry 读 action 选项）
     * @param  string  $relationship  关联方法名，默认 'scheduledTasks'
     */
    public function scheduleRepeater(string $morphType, string $relationship = 'scheduledTasks'): Repeater
    {
        return Repeater::make($relationship)
            ->label(__('sn-support::support.scheduled_task.label'))
            ->relationship(modifyQueryUsing: fn ($query) => $query->where('status', ScheduledTaskStatus::Pending))
            ->schema(fn (): array => $this->repeaterSchema($morphType))
            ->columns(2);
    }

    /**
     * Repeater item 的 schema（通用字段 + 动态加载 action 特有字段）。
     */
    protected function repeaterSchema(string $morphType): array
    {
        $uuid = (string) Str::uuid();

        return [
            // 1. action 选择（options 从 registry 读，按 visible 过滤当前 status）
            Select::make('action')
                ->options(fn (Get $get): array => $this->filterVisibleActions($morphType, $get))
                ->required()
                ->live()
                ->afterStateUpdated(function (Select $component, $state) use ($uuid) {
                    // 切换 action 时重新 fill 动态字段区
                    return $state && $component
                        ->getContainer()
                        ->getComponent('payloadFields_' . $uuid)
                        ?->getChildSchema()
                        ->fill();
                }),

            // 2. 通用：计划执行时间
            DateTimePicker::make('scheduled_at')
                ->required()
                ->displayFormat('Y-m-d H:i:s')
                ->native(false),

            // 3. 通用：任务状态（hidden，默认 pending）
            Hidden::make('status')
                ->default(ScheduledTaskStatus::Pending->value),

            // 4. 动态：action 特有的 payload 字段（从 registry 读 forms）
            Fieldset::make('payload')
                ->schema(function (Get $get) use ($morphType): array {
                    $action = $get('action');

                    return filled($action)
                        ? $this->getActionForms($morphType, $action)
                        : [];
                })
                ->visible(function (Get $get) use ($morphType): bool {
                    $action = $get('action');

                    // 选了 action，并且该 action 有自定义字段时才显示
                    return filled($action) && $this->hasActionForms($morphType, $action);
                })
                ->statePath('payload')
                ->key('payloadFields_' . $uuid),
        ];
    }

    /**
     * 根据 registry 的 visible 条件过滤当前可用的 actions。
     *
     * visible 闭包接收 Repeater item 内层的 Get，调用方自行决定取哪个字段
     * （例如 `$get('../../status')` 取实体层 status，或其他业务字段）。
     * 不设 visible 的 action 始终显示。
     *
     * @return array<string, string>
     */
    protected function filterVisibleActions(string $morphType, Get $get): array
    {
        $allOptions = $this->getActionsOptions($morphType);
        $actions = $this->getActions($morphType);

        return collect($allOptions)->filter(function (string $label, string $action) use ($actions, $get): bool {
            $actionInfo = $actions->get($action);
            $visible = $actionInfo['visible'] ?? null;

            // 没设 visible 的始终显示；设了的把 Get 传入，由闭包自行决定读取哪个字段
            return $visible === null || $visible($get);
        })->toArray();
    }
}
