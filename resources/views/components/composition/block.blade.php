@php
    // $block: ['component_name' => ..., 'extras' => [...]]；$blockKey: 渲染 key
    // 标题/描述由后台编排时设置（componentInfo 透传），为空则不渲染块头
    // 注意：$component 是 Blade 组件保留变量，这里用 $block 承载组件数据
@endphp

@props([
    'block' => [],
    'blockKey' => '',
])

@php($info = $block['extras']['componentInfo'] ?? [])

<div {{ $attributes->merge(['class' => 'w-full min-w-0 flex flex-col sn-gap']) }}>
    @if (filled($info['label'] ?? null) || filled($info['description'] ?? null))
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
