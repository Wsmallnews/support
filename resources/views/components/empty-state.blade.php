@props([
    'compact' => false,
    'contained' => true,
    'description' => null,
    'footer' => null,
    'heading' => null,
    'headingTag' => 'h2',
    'icon' => null,
    'iconColor' => 'gray',
    'iconSize' => 'lg',
    'actions' => null,
])

<section @class([
    'sn-empty',
    'sn-compact' => $compact,
    'sn-container' => $contained,
])>
    @if ($icon)
        <div @class([
            'sn-empty-icon-bg',
            'sn-empty-icon-' . $iconSize => $iconSize,
            'sn-empty-icon-primary' => $iconColor === 'primary',
            'sn-empty-icon-danger' => $iconColor === 'danger',
            'sn-empty-icon-success' => $iconColor === 'success',
            'sn-empty-icon-info' => $iconColor === 'info',
            'sn-empty-icon-warning' => $iconColor === 'warning',
        ])>
            <x-filament::icon
                :icon="$icon"
                @class([
                    'size-5' => $iconSize === 'sm',
                    'size-6' => $iconSize === 'md',
                    'size-8' => $iconSize === 'lg',
                    'size-10' => $iconSize === 'xl',
                    'size-12' => $iconSize === '2xl',
                ])
            />
        </div>
    @else
        <div class="sn-empty-icon">
            @if (isset($iconSlot))
                {{ $iconSlot }}
            @endif
        </div>
    @endif

    <div>
        @if ($heading)
            <{{ $headingTag }} class="sn-empty-title">{{ $heading }}</{{ $headingTag }}>
        @endif

        @if ($description)
            <p class="sn-empty-description">{{ $description }}</p>
        @endif

        @if ($footer)
            <footer class="sn-empty-footer">{{ $footer }}</footer>
        @endif

        @if ($actions)
            <div class="sn-empty-actions">{{ $actions }}</div>
        @endif
    </div>
</section>
