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
     * @var Collection<string, Collection<string, array>> morphType => (action => info)
     */
    protected ?Collection $scopes;

    public function __construct()
    {
        $this->scopes = collect();
    }

    /**
     * Register one action.
     *
     * @param  string  $morphType  Entity morph alias (e.g. sn_product), which isolates modules
     * @param  array  $actionInfo  {action, label, forms, handler, visible}
     */
    public function register(string $morphType, array $actionInfo): static
    {
        $actions = $this->getActions($morphType);
        $action = $actionInfo['action'];

        $this->scopes->put($morphType, $actions->put($action, $actionInfo));

        return $this;
    }

    /**
     * Register several actions.
     *
     * @param  string  $morphType  Entity morph alias
     * @param  array  $actionInfos  Action definitions
     */
    public function registers(string $morphType, array $actionInfos): static
    {
        foreach ($actionInfos as $actionInfo) {
            $this->register($morphType, $actionInfo);
        }

        return $this;
    }

    /**
     * All actions registered for a morph type.
     *
     * @return Collection<string, array>
     */
    public function getActions(string $morphType): Collection
    {
        return $this->scopes->get($morphType, collect());
    }

    /**
     * action => label map for select options.
     *
     * @return array<string, string>
     */
    public function getActionsOptions(string $morphType): array
    {
        return $this->getActions($morphType)->mapWithKeys(function (array $actionInfo) {
            return [$actionInfo['action'] => $actionInfo['label']];
        })->toArray();
    }

    /** Custom form fields of an action (closure or array). */
    public function getActionForms(string $morphType, string $action): array
    {
        $actionInfo = $this->getActions($morphType)->get($action);

        $forms = $actionInfo['forms'] ?? [];

        return $forms instanceof Closure ? app()->call($forms) : (array) $forms;
    }

    /** Whether an action declares extra fields; the payload fieldset is hidden when it does not. */
    public function hasActionForms(string $morphType, string $action): bool
    {
        $forms = $this->getActionForms($morphType, $action);

        return $forms && count($forms) > 0;
    }

    /**
     * Execution handler of an action.
     *
     * @return Closure|null function(ScheduledTask $task, ?array $payload): bool
     */
    public function getHandler(string $morphType, string $action): ?Closure
    {
        $actionInfo = $this->getActions($morphType)->get($action);

        return $actionInfo['handler'] ?? null;
    }

    /**
     * Visibility condition of an action, used to filter mutually exclusive states.
     *
     * @return Closure|null function(Get $get): bool
     */
    public function getActionVisible(string $morphType, string $action): ?Closure
    {
        $actionInfo = $this->getActions($morphType)->get($action);

        return $actionInfo['visible'] ?? null;
    }

    /**
     * Build the task repeater over sn_scheduled_tasks. Only pending tasks are loaded, because
     * executed, failed and cancelled rows are audited from their own columns.
     *
     * @param  string  $morphType  Entity morph alias, used to read the action options
     * @param  string  $relationship  Relation method, defaults to scheduledTasks
     */
    public function scheduleRepeater(string $morphType, string $relationship = 'scheduledTasks'): Repeater
    {
        return Repeater::make($relationship)
            ->label(__('sn-support::support.scheduled_task.label'))
            // Explicit empty default: hydration must not invent a blank task
            ->default([])
            ->relationship(modifyQueryUsing: fn ($query) => $query->where('status', ScheduledTaskStatus::Pending))
            ->schema(fn (): array => $this->repeaterSchema($morphType))
            ->columns(2);
    }

    /** Item schema: shared fields plus the fields of the selected action. */
    protected function repeaterSchema(string $morphType): array
    {
        $uuid = (string) Str::uuid();

        return [
            // 1. Action, filtered by the current status
            Select::make('action')
                ->options(fn (Get $get): array => $this->filterVisibleActions($morphType, $get))
                ->required()
                ->live()
                ->afterStateUpdated(function (Select $component, $state) use ($uuid) {
                    // Re-seed the dynamic fields when the action changes
                    return $state && $component
                        ->getContainer()
                        ->getComponent('payloadFields_' . $uuid)
                        ?->getChildSchema()
                        ->fill();
                }),

            // 2. Shared: scheduled time
            DateTimePicker::make('scheduled_at')
                ->required()
                ->displayFormat('Y-m-d H:i:s')
                ->native(false),

            // 3. Shared: status, hidden, defaults to pending
            Hidden::make('status')
                ->default(ScheduledTaskStatus::Pending->value),

            // 4. Dynamic: payload fields registered for the action
            Fieldset::make('payload')
                ->schema(function (Get $get) use ($morphType): array {
                    $action = $get('action');

                    return filled($action)
                        ? $this->getActionForms($morphType, $action)
                        : [];
                })
                ->visible(function (Get $get) use ($morphType): bool {
                    $action = $get('action');

                    // Only when an action is selected and it has custom fields
                    return filled($action) && $this->hasActionForms($morphType, $action);
                })
                ->statePath('payload')
                ->key('payloadFields_' . $uuid),
        ];
    }

    /**
     * Filter the available actions through their visible closures. Each closure receives the
     * inner repeater Get and decides which field to read; actions without one always show.
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

            // No visible closure means always visible; otherwise let the closure pick the field
            return $visible === null || $visible($get);
        })->toArray();
    }
}
