@props([
    'tag' => 'a',
    'active' => false,
    'border' => false,
    'bg' => false,
    'type' => 'button',
    'disabled' => false,
    'shouldOpenUrlInNewTab' => false,
    'shouldOpenUrlInSpaMode' => false,
])

@php
$classes = \Illuminate\Support\Arr::toCssClasses([
    'font-bold focus:outline-none transition duration-150 ease-in-out',
    match ($active) {
        true => 'text-primary-600 dark:text-primary-400',
        false => 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:text-gray-700 dark:focus:text-gray-200',
    },
    match ($bg) {
        true => 'bg-primary-50 dark:bg-primary-900/50',
        false => '',
    },
    ...$border ? [
        is_bool($border) ? 'border' : $border,
        match ($active) {
            true => 'border-primary-600 dark:border-primary-400 focus:border-primary-700',
            false => 'border-transparent hover:border-gray-700 dark:hover:border-gray-200 focus:border-gray-700 dark:focus:border-gray-200',
        },
    ] : [],
]);

@endphp

<{{ $tag }}
    {{ $tag == 'a' ? href_format($attributes->get('href'), $shouldOpenUrlInNewTab, $shouldOpenUrlInSpaMode) : '' }}
    {{ $attributes->merge([
            'disabled' => $disabled,
            'type' => $tag === 'button' ? $type : null,
        ], escape: false)
        ->class($classes)
    }}
>
    {{ $slot }}
</{{ $tag }}>