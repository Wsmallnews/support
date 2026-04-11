@php
    use Filament\Support\Icons\Heroicon;
    use Wsmallnews\Support\Enums\ActivityLogEvent;
    use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\ActivityLogFormat;

    $activities = $activities ?? $getState() ?? collect();
    if (!$activities instanceof \Illuminate\Support\Collection) {
        $activities = collect($activities);
    }
@endphp

<div class="sn-bg">
    @forelse ($activities as $key => $activity)
        @php
            $event = $activity->event ? ActivityLogEvent::tryFrom($activity->event) : null;
            $icon = $event?->getIcon() ?? Heroicon::Clock;
            $label = $event?->getLabel() ?? $activity->event;
            $color = $event?->getColor() ?? 'gray';

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
                @if (!$loop->last)
                    <div class="sn-gray-bg w-0.5 h-full"></div>
                @endif
            </div>

            <div class="sn-container flex flex-col gap-4 p-4 mb-4 flex-1 min-w-0">
                <div class="w-full flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <x-filament::badge :color="$color">
                            {{ $label }}
                        </x-filament::badge>

                        @if ($activity->subject)
                            <div class="sn-descript-text">
                                {{ ActivityLogFormat::getTypeLabel($activity->subject_type) }}
                            </div>

                            <x-filament::link :href="ActivityLogFormat::getUrl($activity->subject)">
                                #{{ $activity->subject_id }}
                            </x-filament::link>
                        @endif
                    </div>

                    <div class="sn-descript-text">
                        {{ ActivityLogFormat::getTitle($activity->subject) }}
                    </div>
                    <div class="sn-descript-text">
                        {{ $activity->description }}
                    </div>

                    @if (isset($activity->properties['user_agent'])) 
                        <div class="sn-tip-text">
                            {{ $activity->properties['user_agent'] ?? '' }}
                        </div>
                    @endif

                    <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500">
                        @if ($activity->causer)
                            <span class="flex items-center gap-1">
                                @if ($activity->causer->getFilamentAvatarUrl())
                                    <img src="{{ files_url($activity->causer->getFilamentAvatarUrl()) }}" alt="{{ $activity->causer->getFilamentName() }}" class="w-4 h-4 rounded-full" />
                                @else
                                    <x-filament::icon :icon="Heroicon::User" class="w-4 h-4" />
                                @endif
                                {{ $activity->causer->getFilamentName() }}
                            </span>
                        @endif

                        <div class="sn-tip-text flex items-center gap-1" title="{{ $activity->created_at }}">
                            <x-filament::icon :icon="Heroicon::Clock" class="w-4 h-4" />
                            {{ $activity->created_at->diffForHumans() }}
                        </div>
                        
                        <div class="sn-tip-text flex items-center gap-1">
                            <x-filament::icon :icon="Heroicon::MapPin" class="w-4 h-4" />
                            {{ $activity->properties['ip_address'] ?? '' }}
                        </div>
                    </div>
                </div>

                @php
                    $old = $activity->attribute_changes->get('old', []);
                    $attributes = $activity->attribute_changes->get('attributes', []);
                @endphp
                @if($old || $attributes)
                    <div x-data="{ open: false }" class="w-full flex flex-col gap-2">
                        <button @click="open = !open" type="button" class="flex justify-between items-center w-full">
                            <span class="sn-descript-text flex gap-2">
                                <x-filament::icon :icon="Heroicon::ArrowsRightLeft" class="w-4 h-4" />
                                {{ __('sn-support::activity.infolist.tab.changes') }}
                            </span>
                            <x-filament::icon :icon="Heroicon::ChevronDown" class="w-4 h-4 rotate-0 transform transition-transform duration-300"
                                x-bind:class="{ 'rotate-180': open }" />
                        </button>

                        <div class="w-full flex flex-col gap-2 @container" x-show="open" x-collapse>
                            <div class="w-full flex flex-col @2xl:flex-row gap-4">
                                @if($old)
                                    <div class="sn-rounded sn-danger-text w-full flex flex-col divide-y divide-danger-200 border bg-danger-50 border-danger-200">
                                        <div class="w-full p-2">
                                            {{ __('sn-support::activity.infolist.tab.old') }}
                                        </div>

                                        @if(is_array($old))
                                            @foreach($old as $key => $value)
                                                <div class="w-full flex items-center justify-between p-2">
                                                    <dt class="">{{ str($key)->title() }}</dt>
                                                    <dd class="">
                                                        {{ is_array($value) ? json_encode($value) : $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="w-full p-2">
                                                {{ $old }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
    
                                @if($attributes)
                                    <div class="sn-rounded sn-success-text w-full flex flex-col divide-y divide-success-200 border bg-success-50 border-success-200">
                                        <div class="w-full p-2">
                                            {{ __('sn-support::activity.infolist.tab.new') }}
                                        </div>
                                        
                                        @if(is_array($attributes))
                                            @foreach($attributes as $key => $value)
                                                <div class="w-full flex items-center justify-between p-2">
                                                    <dt class="">{{ str($key)->title() }}</dt>
                                                    <dd class="">
                                                        {{ is_array($value) ? json_encode($value) : $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="w-full p-2">
                                                {{ $attributes }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <x-filament::empty-state
            :contained="false"
            icon="heroicon-m-document-text"
            icon-color="gray"
        >
            <x-slot name="heading">
                暂无活动日志
            </x-slot>

            <x-slot name="description">
                暂无活动日志。
            </x-slot>
        </x-filament::empty-state>
    @endforelse
</div>
