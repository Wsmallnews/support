@php
    // $block: ['component_name' => ..., 'extras' => [...]]; $blockKey: render key.
    // Header is rendered only when componentInfo carries a label or description.
    // $block avoids shadowing Blade's reserved $component variable.
@endphp

@props([
    'block' => [],
    'blockKey' => '',
])

@php($info = $block['extras']['componentInfo'] ?? [])

<div {{ $attributes->merge(['class' => 'w-full min-w-0 flex flex-col sn-gap @container']) }}>
    @if (($info['show_header'] ?? true) && (filled($info['label'] ?? null) || filled($info['description'] ?? null)))
        <div class="flex flex-col gap-1">
            @if (filled($info['label'] ?? null))
                <h3 class="sn-content-text text-base font-semibold">{{ $info['label'] }}</h3>
            @endif

            @if (filled($info['description'] ?? null))
                <p class="sn-descript-text text-sm">{{ $info['description'] }}</p>
            @endif
        </div>
    @endif

    @livewire($block['component_name'], $block['extras'], key($blockKey))
</div>
