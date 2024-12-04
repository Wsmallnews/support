@php
    use Filament\Support\Enums\IconPosition;
@endphp

@props([
    'active' => false,
    'alpineActive' => null,
    'labelClass' => null,               // 自定义文字样式
    'activeLabelHasUnderline' => true,  // 文字选中是否带下划线
    'badge' => null,
    'badgeColor' => null,
    'badgeTooltip' => null,
    'badgeIcon' => null,
    'badgeIconPosition' => IconPosition::Before,
    'href' => null,
    'icon' => null,
    'iconColor' => 'gray',
    'iconPosition' => IconPosition::Before,
    'spaMode' => null,
    'tag' => 'button',
    'target' => null,
    'type' => 'button',
])

@php
    if (! $iconPosition instanceof IconPosition) {
        $iconPosition = filled($iconPosition) ? (IconPosition::tryFrom($iconPosition) ?? $iconPosition) : null;
    }

    $hasAlpineActiveClasses = filled($alpineActive);

    // 外层 元素 鼠标经过时候的样式
    $inactiveItemClasses = '';

    // 外层 元素 选中的样式
    $activeItemClasses = 'fi-active';

    // 自定义文字样式
    $labelClasses = $labelClass ?? '';

    // 文字未选中时候的样式
    $inactiveLabelClasses = 'text-gray-500 group-hover:text-primary-600 group-focus-visible:text-primary-600 dark:text-gray-400 dark:group-hover:text-primary-400 dark:group-focus-visible:text-primary-400';

    // 文字选中时候的样式
    $activeLabelClasses = 'font-bold text-primary-600 dark:text-primary-400';

    // 文字选中时候是否带下划线
    $activeLabelHasUnderlineClasses = $activeLabelHasUnderline ? 'after:content-[""] after:absolute after:inset-x-0 after:bottom-0 after:h-[2px] after:bg-primary-600 dark:after:bg-primary-400' : '';

    // icon 默认 class
    $iconClasses = 'fi-tabs-item-icon h-5 w-5 shrink-0 transition duration-75';

    // icon 未选中时候的样式
    $inactiveIconClasses = 'text-gray-400 dark:text-gray-500';

    // icon 选中时候的样式
    $activeIconClasses = 'text-primary-600 dark:text-primary-400';
@endphp

<{{ $tag }}
    @if ($tag === 'button')
        type="{{ $type }}"
    @elseif ($tag === 'a')
        {{ \Filament\Support\generate_href_html($href, $target === '_blank', $spaMode) }}
    @endif
    @if ($hasAlpineActiveClasses)
        x-bind:class="{
            @js($inactiveItemClasses): {{-- format-ignore-start --}} ! ({{ $alpineActive }}) {{-- format-ignore-end --}},
            @js($activeItemClasses): {{ $alpineActive }},
        }"
    @endif
    {{
        $attributes
            ->merge([
                'aria-selected' => $active,
                'role' => 'tab',
            ])
            ->class([
                'fi-tabs-item group relative flex items-center gap-x-2 rounded-lg py-2 text-base font-medium outline-none transition duration-75 mr-4 last:mr-0',
                $inactiveItemClasses => (! $hasAlpineActiveClasses) && (! $active),
                $activeItemClasses => (! $hasAlpineActiveClasses) && $active,
            ])
    }}
>
    @if ($icon && $iconPosition === IconPosition::Before)
        <x-filament::icon
            :icon="$icon"
            :x-bind:class="$hasAlpineActiveClasses ? '{ ' . \Illuminate\Support\Js::from($inactiveIconClasses) . ': ! (' . $alpineActive . '), ' . \Illuminate\Support\Js::from($activeIconClasses) . ': ' . $alpineActive . ' }' : null"
            @class([
                $iconClasses,
                $inactiveIconClasses => (! $hasAlpineActiveClasses) && (! $active),
                $activeIconClasses => (! $hasAlpineActiveClasses) && $active,
            ])
        />
    @endif

    <span
        @if ($hasAlpineActiveClasses)
            x-bind:class="{
                @js($inactiveLabelClasses): {{-- format-ignore-start --}} ! ({{ $alpineActive }}) {{-- format-ignore-end --}},
                @js($activeLabelClasses): {{ $alpineActive }},
                @js($activeLabelHasUnderlineClasses): {{ $alpineActive }},
            }"
        @endif
        @class([
            'fi-tabs-item-label transition duration-75',
            $labelClasses,
            $inactiveLabelClasses => (! $hasAlpineActiveClasses) && (! $active),
            $activeLabelClasses => (! $hasAlpineActiveClasses) && $active,
            $activeLabelHasUnderlineClasses => (! $hasAlpineActiveClasses) && $active,
        ])
    >
        {{ $slot }}
    </span>

    @if ($icon && $iconPosition === IconPosition::After)
        <x-filament::icon
            :icon="$icon"
            :x-bind:class="$hasAlpineActiveClasses ? '{ ' . \Illuminate\Support\Js::from($inactiveIconClasses) . ': ! (' . $alpineActive . '), ' . \Illuminate\Support\Js::from($activeIconClasses) . ': ' . $alpineActive . ' }' : null"
            @class([
                $iconClasses,
                $inactiveIconClasses => (! $hasAlpineActiveClasses) && (! $active),
                $activeIconClasses => (! $hasAlpineActiveClasses) && $active,
            ])
        />
    @endif

    @if (filled($badge))
        <x-filament::badge
            :color="$badgeColor"
            :icon="$badgeIcon"
            :icon-position="$badgeIconPosition"
            size="sm"
            :tooltip="$badgeTooltip"
            class="w-max"
        >
            {{ $badge }}
        </x-filament::badge>
    @endif
</{{ $tag }}>
