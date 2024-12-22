@props([
    'tag' => 'div',
    'border' => false,
    'shouldOpenUrlInNewTab' => false,
    'shouldOpenUrlInSpaMode' => false,
])


@php
$classes = \Illuminate\Support\Arr::toCssClasses([
        'bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 text-gray-700 dark:text-gray-200',
    ...$border ? [
        is_bool($border) ? 'border' : $border,
        'border-gray-300 dark:border-gray-700 hover:border-primary-600 dark:hover:border-primary-400',
    ] : []
]);
@endphp

{{-- 支持暗黑的区块，默认是白色 --}}
<{{ $tag }}
    {{ $tag == 'a' ? href_format($attributes->get('href'), $shouldOpenUrlInNewTab, $shouldOpenUrlInSpaMode) : '' }}
    {{ $attributes->merge([
            'type' => $tag === 'button' ? $type : null,
        ], escape: false)
        ->class($classes)
    }}
>
    {{ $slot }}
</{{ $tag }}>