@props([
    'item' => [],      // 快捷传参：['image', 'href', 'html']
    'image' => null,   // 图片地址，优先级高于 item
    'href' => null,    // 点击跳转地址，优先级高于 item
    'html' => null,    // 完全自定义幻灯片内容（忽略 image 默认渲染），优先级高于 item
])

@aware([
    'imageFit' => 'cover',  // 从父级 swiper 继承主图填充方式
])

@php
    use Filament\Support\Facades\FilamentView;

    $image = $image ?? ($item['image'] ?? null);
    $href = $href ?? ($item['href'] ?? null);
    $html = $html ?: ($item['html'] ?? null);

    $spaMode = FilamentView::hasSpaMode();
@endphp

<div
    {{ $attributes->class([
        'swiper-slide',
        'cursor-pointer' => $href,
    ]) }}
    data-slide-image="{{ $image }}"
    @if ($href)
        @click="jumpToUrl('{{ $href }}', @js($spaMode))"
    @endif
>
    @if ($html)
        {!! $html !!}
    @else
        <img src="{{ $image }}" loading="lazy" class="w-full h-full {{ $imageFit === 'cover' ? 'object-cover' : 'object-contain' }}" />
    @endif

    {{-- 覆盖内容：直接读取循环 item 渲染，定位与样式由调用处控制 --}}
    {{ $slot }}
</div>
