@props([
    'key' => null,
    'slides' => [],                // 幻灯片内容
    'swiperCss' => '',          // 给 swiper 最外层容器附加 css
    'isSquare' => false,            // 主 swiper 是否是正方形
    'hasThumb' => true,            // 是否有缩略图 swiper
    'thumbCss' => '',           // 给 thumb swiper 最外层容器附加 css
    'thumbScale' => 20,         // 缩略图 swiper 所占比例 20%
    'thumbNum' => 6,            // 默认缩略图 swiper 显示的幻灯片数量
    'thumbPosition' => 'bottom'   // 缩略图所在位置 left, right, top, bottom
])

@php
    /**
     *  引用 swiper 组件如果非正方形，不要设置高度
     *  正方形：一般设置宽度 100，高度等于 宽度
     *     有缩略图：
     *          如果 thumbPosition 为 left 或 right，则 thumb swiper 会占用宽度，swiper 宽度会变小，比如 80%，
     *          如果 thumbPosition 为 上下，则 thumb swiper 会占用 额外的高度，swiper 依然是  100% 宽度
     * 
     *  非正方形，一般设置宽度 100%，高度根据内容自适应
     *      有缩略图
     *          如果 thumbPosition 为 left 或 right，则 thumb swiper 会占用宽度，swiper 宽度会变小，比如 80%，
     *          如果 thumbPosition 为 上下，则 thumb swiper 会占用 额外的高度，swiper 依然是  100% 宽度
     */

    use Filament\Support\Facades\FilamentView;
    use Illuminate\Support\Arr;

    $bindMainKey = $key ? $key . '-main' : 'swiper-main-' . rand(10000, 99999);
    $bindThumbKey = $key ? $key . '-thumb' : 'swiper-thumb-' . rand(10000, 99999);

    // 如果有缩略图，并且是放到左右，则 swiper 宽度会变小，比如 80%
    $shouldSetWidth = $hasThumb && in_array($thumbPosition, ['left', 'right']);

    $swiperWidth = 'w-full';
    $thumbWidth = 'w-full';
    if ($shouldSetWidth) {
        // 有缩略图，并且是放到左右
        $swiperWidth = 'w-[' . (100 - $thumbScale) . '%]';
        $thumbWidth = 'w-[' . $thumbScale . '%]';
    }

    $slides = Arr::map($slides, function ($slide) {
        return is_string($slide) ? ['image' => $slide] : $slide;
    });

    $spaModel = FilamentView::hasSpaMode();
@endphp

@assets
<style>
    .detail-swiper {
        --swiper-navigation-color: #fff;
        --swiper-pagination-color: #fff
    }

    .swiper-slide {
        text-align: center;
        font-size: 18px;
        background: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        background-size: cover;
        background-position: center;
    }

    .detail-swiper-thumbs {
        box-sizing: border-box;
    }

    .detail-swiper-thumbs .swiper-slide {
        opacity: 0.4;
    }
    
    .detail-swiper-thumbs .swiper-slide-thumb-active {
        opacity: 1;
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
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('sn-support-components-swiper', 'wsmallnews/support') }}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('sn-support-components-swiper', 'wsmallnews/support'))]"
    x-data="supportSwiper({
        bindMainKey: @js($bindMainKey),
        bindThumbKey: @js($bindThumbKey),
        isSquare: @js($isSquare),
        hasThumb: @js($hasThumb),
        thumbNum: @js($thumbNum),
        thumbPosition: @js($thumbPosition),
        thumbScale: @js($thumbScale),
    })"
    {{ $attributes
        ->class([
            'swiper-container flex overflow-hidden',
            'gap-2 md:gap-4' => $hasThumb,
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
        @class([
            'swiper detail-swiper',
            $bindMainKey,
            $swiperCss,
            $swiperWidth,
        ])
        @if ($isSquare)
            x-resize="setSwiperHeight"
            :style="{ height: swiperHeight + 'px' }"
        @endif
        x-cloak
    >
        <div class="swiper-wrapper" >
            @foreach($slides as $slide)
                @php
                    $url = (isset($slide['url']) && $slide['url']) ? $slide['url'] : null;
                    $label = (isset($slide['label']) && $slide['label']) ? $slide['label'] : null;
                @endphp
                <div
                    @class([
                        'swiper-slide',
                        'cursor-pointer' => $url,
                    ])
                    @if ($url)
                        @click="jumpToUrl('{{ $url }}', @js($spaModel))"
                    @endif
                >
                    <img src="{{$slide['image']}}" class="w-full h-full object-contain" />
                    @if (isset($slide['label']) && $slide['label'])
                        <div class="swiper-slide-label absolute bottom-0 left-0 right-0 text-base text-left text-white leading-7 bg-black/50 px-2 py-1 line-clamp-1">{{ $slide['label'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>

    @if($hasThumb)
        <div @class([
                'swiper detail-swiper-thumbs',
                $bindThumbKey,
                $thumbCss, 
                $thumbWidth,
            ]) 
        >
            <div @class([
                    'swiper-wrapper flex',
                ])
            >
                @foreach($slides as $slide)
                    <div class="swiper-slide">
                        <img src="{{$slide['image']}}" class="w-full h-full object-contain" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>