@props([
    'key' => null,
    'galleries' => [],                // Slides
    'lightboxCss' => '',          // Extra classes on the outer container
    'thumbClass' => '',          // Thumbnail classes
])

{{-- gallery item: ['title' => ..., 'image' => ..., 'thumb' => optional, defaults to image] --}}

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