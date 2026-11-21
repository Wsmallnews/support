@php
    use Filament\Support\Facades\FilamentAsset;
@endphp

@props([
    'slides' => [],               // Slides: image URLs, or arrays of [image, href, html]
    'swiperCss' => '',            // Extra classes on the main swiper container
    'ratio' => null,              // Aspect ratio of the main swiper, e.g. 1/1 or 16/9
    'rounded' => false,           // Rounding: true for rounded-xl, or a custom class
    'hasThumb' => true,           // Thumbnails, hidden automatically for a single slide
    'thumbPosition' => 'bottom',  // Thumbnail position: left, right, top or bottom
    'thumbSize' => 80,            // Thumbnail bar cross-axis size in px
    'thumbCss' => '',             // Extra classes on the thumbnail bar
    'imageFit' => 'cover',        // Main image fit: cover or contain
    'effect' => 'slide',          // Effect: slide, fade, cube, coverflow, flip or cards
    'loop' => true,               // Loop
    'navigation' => true,         // Previous and next buttons
    'pagination' => 'bullets',    // Pagination: null, bullets, fraction or progressbar
    'autoplay' => false,          // Autoplay
    'autoplayDelay' => 3000,      // Autoplay delay in ms
    'options' => [],              // Native Swiper options, merged last and winning over the defaults
])

@php
    /** Outer sizing is the caller's job: a fixed height makes the main swiper fill the rest,
     * or set ratio for an aspect-ratio height. Pass slides for the simple mode, or leave them
     * out and loop swiper.slide in the slot for full control over each slide. */

    use Filament\Support\Facades\FilamentView;
    use Illuminate\Support\Arr;

    $slides = Arr::map($slides, function ($slide) {
        return is_string($slide) ? ['image' => $slide] : $slide;
    });

    $isVerticalThumb = in_array($thumbPosition, ['left', 'right']);

    // Composed mode: a non-empty slot means the caller loops swiper.slide itself
    $hasSlotSlides = ! $slot->isEmpty();

    if ($hasSlotSlides) {
        // Thumbnails are recovered from the rendered slot: each slide writes its image to
        // data-slide-image, and the slot is already an HTML string by now.
        preg_match_all('/data-slide-image="([^"]*)"/', $slot->toHtml(), $thumbMatches);
        // Decode the escaped attribute values, drop slides without an image, then rebuild keys.
        $thumbImages = array_values(array_filter(array_map('html_entity_decode', $thumbMatches[1])));
    } else {
        // Simple mode: the slides array is available here, so read the image column directly
        $thumbImages = array_column($slides, 'image');
    }

    // A single slide needs no thumbnails
    $hasThumb = $hasThumb && count($thumbImages) > 1;

    $roundedClass = $rounded === true ? 'rounded-xl' : (is_string($rounded) && $rounded !== '' ? $rounded : '');

    $imageFitClass = $imageFit === 'cover' ? 'object-cover' : 'object-contain';

    $spaMode = FilamentView::hasSpaMode();
@endphp

@assets
<style>
    .sn-swiper {
        --swiper-navigation-color: #fff;
        --swiper-pagination-color: #fff
    }

    /*
     Swiper's own margin:auto on .swiper disables cross-axis stretch in flex-col layouts,
     so the main swiper shrinks to content width and then grows exponentially as Swiper
     measures and rewrites slide widths. A double class selector outranks the x-load CSS.
     */
    .swiper.sn-swiper,
    .swiper.sn-swiper-thumbs {
        margin-left: 0;
        margin-right: 0;
    }

    .sn-swiper .swiper-slide {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #fff;
        text-align: center;
    }

    .sn-swiper-thumbs {
        box-sizing: border-box;
    }

    .sn-swiper-thumbs .swiper-slide {
        opacity: 0.4;
        overflow: hidden;
        transition: opacity 0.2s;
    }

    .sn-swiper-thumbs .swiper-slide-thumb-active {
        opacity: 1;
    }

    /* Thumbnail fitting: horizontal bars grow in height, vertical bars grow in width */
    .sn-swiper-thumbs .swiper-slide {
        width: auto;
        height: auto;
    }

    .sn-swiper-thumbs img {
        display: block;
    }

    .sn-swiper-thumbs.is-vertical .swiper-slide {
        width: 100%;
        height: auto;
    }

    .sn-swiper-thumbs.is-vertical img {
        width: 100%;
        height: auto;
    }

    .sn-swiper-thumbs.is-horizontal .swiper-slide {
        width: auto;
        height: 100%;
    }

    .sn-swiper-thumbs.is-horizontal img {
        width: auto;
        height: 100%;
    }
</style>
@endassets

<div
    @if (FilamentView::hasSpaMode())
        x-load="visible || event (ax-modal-opened)"
    @else
        x-load
    @endif
    wire:ignore
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('sn-support-components-swiper', 'wsmallnews/support') }}"
    x-load-css="[@js(FilamentAsset::getStyleHref('sn-support-components-swiper', 'wsmallnews/support'))]"
    x-data="supportSwiper({
        effect: @js($effect),
        loop: @js($loop),
        navigation: @js($navigation),
        pagination: @js($pagination),
        autoplay: @js($autoplay),
        autoplayDelay: @js($autoplayDelay),
        thumbDirection: @js($isVerticalThumb && $hasThumb ? 'vertical' : 'horizontal'),
        options: @js((object) $options),
    })"
    {{ $attributes
        ->class([
            'flex overflow-hidden',
            'gap-2 @3xl:gap-4 @5xl:gap-6' => $hasThumb,
            $hasThumb ? match ($thumbPosition) {
                'left' => 'flex-row-reverse',
                'right' => 'flex-row',
                'top' => 'flex-col-reverse',
                'bottom' => 'flex-col'
            } : ''
        ])
    }}
>
    <div
        x-ref="main"
        @class([
            'swiper sn-swiper flex-1 min-w-0 min-h-0',
            $roundedClass,
            $swiperCss,
        ])
        @if ($ratio)
            style="aspect-ratio: {{ $ratio }}"
        @endif
        x-cloak
    >
        <div class="swiper-wrapper">
            @if ($hasSlotSlides)
                {{ $slot }}
            @else
                @foreach($slides as $slide)
                    @php
                        $href = $slide['href'] ?? null;
                    @endphp
                    <div
                        @class([
                            'swiper-slide',
                            'cursor-pointer' => $href,
                        ])
                        @if ($href)
                            @click="jumpToUrl('{{ $href }}', @js($spaMode))"
                        @endif
                    >
                        @if (! empty($slide['html']))
                            {!! $slide['html'] !!}
                        @else
                            <img src="{{ $slide['image'] }}" loading="lazy" class="w-full h-full {{ $imageFitClass }}" />
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
        @if ($navigation)
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        @endif
        @if ($pagination)
            <div class="swiper-pagination"></div>
        @endif
    </div>

    @if($hasThumb)
        <div
            x-ref="thumbs"
            @class([
                'swiper sn-swiper-thumbs shrink-0',
                $roundedClass,
                $isVerticalThumb ? 'is-vertical' : 'is-horizontal',
                $thumbCss,
            ])
            style="{{ $isVerticalThumb ? 'width: ' . $thumbSize . 'px' : 'height: ' . $thumbSize . 'px' }}"
            x-cloak
        >
            <div class="swiper-wrapper">
                @foreach($thumbImages as $thumbImage)
                    <div class="swiper-slide">
                        @if (! empty($thumbImage))
                            <img src="{{ $thumbImage }}" loading="lazy" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
