@props([
    'compact' => false,
    'contained' => true,
    'description' => null,
    'footer' => null,
    'heading' => null,
    'headingTag' => 'h3',
    'icon' => null,
    'iconColor' => 'gray',
    'iconSize' => 'lg',
    'iconSlot' => null,
    'actions' => null,
])

{{-- 前台空状态统一版式（sn-empty 令牌族）：icon 或 iconSlot 二选一，都缺省时不渲染图标位（不留占位空白）。
    contained=true 自带卡片（独立空态页）；嵌在列表卡内时传 :contained="false" --}}
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
    @elseif ($iconSlot)
        <div class="sn-empty-icon">
            {{ $iconSlot }}
        </div>
    @endif

    @if ($heading || $description || $footer || $actions)
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
    @endif
</section>
