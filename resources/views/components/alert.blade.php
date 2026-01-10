@props([
    'title' => null,
    'description' => null,
    'color' => 'success',
    'border' => true,
    'icon' => null,
    'iconVerticalAlignment' => 'center',
])

@php
    use function Filament\Support\get_color_css_variables;
    $colors = \Illuminate\Support\Arr::toCssStyles([
        get_color_css_variables($color, shades: [50, 100, 400, 500, 700, 800]),
    ]);

    if (!$icon) {
        match ($color) {
            'primary' => $icon = 'heroicon-o-check-circle',
            'danger' => $icon = 'heroicon-s-x-circle',
            'info' => $icon = 'heroicon-s-information-circle',
            'success' => $icon = 'heroicon-s-check-circle',
            'warning' => $icon = 'heroicon-s-exclamation-triangle',
            default => $icon = 'heroicon-o-check-circle',
        };
    }
@endphp

<div x-data="{}"
    {{ 
        $attributes->class([
            'sn-support-alert rounded-md bg-custom-50 p-4 dark:bg-custom-400/10',
            'ring-1 ring-custom-100 dark:ring-custom-500/70' => $border,
        ])
    }}
    style="{{ $colors }}">
    <div class="flex gap-3">
        @if($icon)
            <div @class([
                'flex-shrink-0',
                $iconVerticalAlignment === 'start' ? 'self-start' : 'self-center',
            ])>
                <x-filament::icon
                    :icon="$icon"
                    class="h-5 w-5 text-custom-400"
                />
            </div>
        @endif
        <div class="items-center flex-1 md:flex md:justify-between space-y-3 md:space-y-0 md:gap-3">
            @if($title || $description)
                <div class="space-y-0.5">
                    @if($title)
                        <p class="text-sm font-medium text-custom-800 dark:text-white">
                            {{ $title }}
                        </p>
                    @endif
                    @if($description)
                        <p class="text-sm text-custom-700 dark:text-white">
                            {{ $description }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
