@props([
    'key' => null,
    'galleries' => [],                // 幻灯片内容
    'lightboxCss' => '',          // 外层容器 class`
    'thumbClass' => '',          // 缩略图 class
])

{{-- gallery: {
    title: '预览标题',
    image: '预览图片',
    thumb: '缩略图（可选，默认使用 image）',
} --}}

@php
    use Filament\Support\Facades\FilamentView;
    use Illuminate\Support\Arr;

    $bindKey = $key ? $key . '-container' : 'lightbox-container-' . rand(10000, 99999);

    $galleries = Arr::map($galleries, function ($gallery) {
        return is_string($gallery) ? ['image' => $gallery] : $gallery;
    });
@endphp

<div 
    @if (FilamentView::hasSpaMode())
        x-load="visible || event (ax-modal-opened)"
    @else
        x-load
    @endif
    wire:ignore
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('sn-support-components-lightbox', 'wsmallnews/support') }}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('sn-support-components-lightbox', 'wsmallnews/support'))]"
    x-data="supportLightbox({
        bindKey: @js($bindKey),
    })"
    {{ $attributes
        ->class([
            'lightbox-container flex overflow-hidden',
        ])
    }}
>
    <div 
        @class([
            'flex flex-wrap gap-2',
            $lightboxCss,
        ])
        x-cloak
    >
        @foreach($galleries as $gallery)
            <a @class([
                    'inline-block',
                    $bindKey,
                    $thumbClass,
                ]) 
                href="{{$gallery['image']}}" 
                data-gallery="{{'gallery-' . $bindKey}}"
                data-title="{{$gallery['title'] ?? ''}}"
                target="_blank"
            >
                <img class="w-full h-full object-cover"
                    src="{{$gallery['thumb'] ?? $gallery['image']}}" 
                    alt="{{$gallery['title'] ?? ''}}" 
                />
            </a>
        @endforeach
    </div>
</div>