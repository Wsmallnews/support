@php
    use Filament\Support\Icons\Heroicon;
    use Wsmallnews\Support\Enums\ScheduledTaskStatus;
    use Wsmallnews\Support\Helpers\FilamentModelHelper;

    $tasks = $tasks ?? collect();
@endphp

<x-filament-widgets::widget @class([
    'sn-container px-4 py-8' => $contained,
    'w-full flex flex-col gap-4',
])>
    <div class="flex items-center gap-2">
        <h3 class="sn-h3-text flex items-center gap-2">
            <x-filament::icon :icon="Heroicon::Clock" class="w-5 h-5" />
            {{ __('sn-support::scheduled_task.widget.heading') }}
        </h3>
    </div>

    <div class="sn-bg">
        @forelse ($tasks as $task)
            @php
                $status = $task->status;
                $icon = $status?->getIcon() ?? Heroicon::Clock;
                $label = $status?->getLabel() ?? $task->status;
                $color = $status?->getColor() ?? 'gray';

                $iconColorClass = null;
                if (in_array($color, ['danger', 'gray', 'info', 'primary', 'success', 'warning'])) {
                    $iconColorClass = 'fi-color fi-bg-color-400 dark:fi-bg-color-600';
                    $iconColorClass .= ' bg-(--bg) dark:bg-(--dark-bg)';
                    $iconColorClass .= ' fi-color-' . $color;
                }
            @endphp
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div @class([
                        'flex flex-row grow-0 shrink-0 items-center justify-center w-8 h-8 rounded-full shadow-sm',
                        $iconColorClass,
                    ])>
                        <x-filament::icon :icon="$icon" class="w-4 h-4" />
                    </div>
                    @if (! $loop->last)
                        <div class="sn-gray-bg w-0.5 h-full"></div>
                    @endif
                </div>

                <div class="sn-container flex flex-col gap-2 p-4 mb-4 flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <x-filament::badge :color="$color">
                            {{ $label }}
                        </x-filament::badge>

                        <div class="sn-descript-text font-medium">
                            {{ __('sn-support::scheduled_task.timeline.action') }}: <x-filament::badge color="primary">{{ $task->action }}</x-filament::badge>
                        </div>
                    </div>

                    <div class="sn-descript-text flex items-center gap-1">
                        <x-filament::icon :icon="Heroicon::Clock" class="w-4 h-4" />
                        {{ __('sn-support::scheduled_task.timeline.scheduled_at') }}: {{ $task->scheduled_at }}
                    </div>

                    <div class="sn-tip-text flex items-center gap-1">
                        @if ($task->executed_at)
                            <x-filament::icon :icon="Heroicon::CheckCircle" class="w-4 h-4" />
                            {{ __('sn-support::scheduled_task.timeline.executed_at') }}: {{ $task->executed_at?->format('Y-m-d H:i:s') }}
                        @else
                            {{ __('sn-support::scheduled_task.timeline.no_executed') }}
                        @endif
                    </div>

                    @if (filled($task->result))
                        <div class="sn-tip-text">
                            {{ __('sn-support::scheduled_task.timeline.result') }}: {{ $task->result }}
                        </div>
                    @endif

                    <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500">
                        <div class="sn-tip-text flex items-center gap-1" title="{{ $task->created_at }}">
                            <x-filament::icon :icon="Heroicon::Clock" class="w-4 h-4" />
                            {{ $task->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <x-filament::empty-state
                :contained="false"
                icon="heroicon-m-clock"
                icon-color="gray"
            >
                <x-slot name="heading">
                    {{ __('sn-support::scheduled_task.no_tasks') }}
                </x-slot>

                <x-slot name="description">
                    {{ __('sn-support::scheduled_task.no_tasks_desc') }}
                </x-slot>
            </x-filament::empty-state>
        @endforelse
    </div>
</x-filament-widgets::widget>
