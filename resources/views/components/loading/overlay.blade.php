@props([
    'delay' => true,
    'spinnerClass' => 'size-8 text-primary-500',
])

<div
    @if ($delay)
        wire:loading.delay.class="opacity-100 pointer-events-auto"
    @else
        wire:loading.class="opacity-100 pointer-events-auto"
    @endif
    {{ 
        $attributes->class([
            'sn-transition-opacity sn-rounded opacity-0 pointer-events-none absolute inset-0 z-10',
            'flex items-center justify-center',
            'bg-white/60 dark:bg-gray-900/60 '
        ]);
    }}
>
    <x-filament::loading-indicator :class="$spinnerClass" />
</div>
