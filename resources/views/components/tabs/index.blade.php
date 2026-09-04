@props([
    'contained' => false,
    'label' => null,
])

<nav
    {{
        $attributes
            ->merge([
                'aria-label' => $label,
                'role' => 'tablist',
            ])
            ->class([
                'fi-tabs flex max-w-full gap-x-1 overflow-x-auto',
                'fi-contained sn-bg border-b border-gray-200 py-2.5 dark:border-gray-800' => $contained,
                'sn-container p-2' => ! $contained,
            ])
    }}
>
    {{ $slot }}
</nav>
