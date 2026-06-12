@props([
    'expanded' => false,
    'icon' => null,
])

<div
    {{ 
        $attributes->class([
            'sn-accordion-item',
        ])
    }}
    x-data="{ open: {{ $expanded ? 'true' : 'false' }} }"
>
    {{-- 标题区域 --}}
    <button
        type="button"
        class="sn-accordion-item-header flex w-full items-center justify-between gap-3 px-5 py-3.5 text-left sn-gray-bg sn-hover"
        @click="open = !open"
        :aria-expanded="open"
    >
        <div class="flex items-center gap-2.5 min-w-0">
            @if($icon)
                <x-filament::icon :icon="$icon" class="size-5 text-gray-400 dark:text-gray-500 shrink-0" />
            @endif
            {{ $heading }}
        </div>
        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="size-4 text-gray-400 shrink-0 transition-transform duration-200"
            ::class="open ? 'rotate-180' : ''"
        />
    </button>

    {{-- 内容区域 --}}
    <div
        x-show="open"
        x-collapse
        x-cloak
        class="sn-accordion-item-content px-5 py-4"
    >
        {{ $slot }}
    </div>
</div>
