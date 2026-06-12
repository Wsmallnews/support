@props([
    'contained' => true,
])

<div
    {{ 
        $attributes->class([
            'sn-accordion w-full overflow-hidden',
            'sn-container' => $contained,
        ])
    }}
>
    <div class="sn-accordion-content divide-y divide-gray-200 dark:divide-white/10">
        {{ $slot }}
    </div>
</div>
