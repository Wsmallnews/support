@props([
    'images' => [],             // 图片数组
    'swiperCss' => '',          // 给 swiper 最外层容器附加 css
    'hasThumb' => true,         // 是否有缩略图 swiper
    'thumbCss' => '',           // 给 thumb swiper 最外层容器附加 css
    'thumbScale' => 20,         // 缩略图 swiper 所占比例 20%
    'thumbNum' => 6,            // 默认缩略图 swiper 显示的幻灯片数量
    'thumbPosition' => 'bottom'   // 缩略图所在位置 left, right, top, bottom
])

@php
    use Filament\Support\Facades\FilamentView;
    $thumbSwiperScale = (100 / $thumbNum) . '% !important';

    if (!$hasThumb) {
        $thumbScale = 0;       // 比例改为 0
    }

    $swiperScale = (100 - $thumbScale) . '%';
    $thumbScale = $thumbScale . '%';
@endphp

@assets
<style>
    .swiper-container {
        position: relative;
        font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
        font-size: 14px;
        margin: 0;
        padding: 0;
    }
    
    .detail-swiper {
        --swiper-navigation-color: #fff;
        --swiper-pagination-color: #fff
    }

    .detail-swiper-thumbs {
        box-sizing: border-box;
        padding: 10px 0;
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

    .swiper-slide img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .detail-swiper-thumbs .swiper-slide {
        opacity: 0.4;
    }
    
    .detail-swiper-thumbs .swiper-slide-thumb-active {
        opacity: 1;
    }

    .detail-swiper-thumbs.directionx {
        padding: 0 10px !important;
    }

    .detail-swiper-thumbs.directionx .swiper-slide {
        margin-right: 0px;
        margin-bottom: 10px;
        width: 100% !important;
        height: {{$thumbSwiperScale}};
    }
</style>
@endassets

<div 
    @if (FilamentView::hasSpaMode())
            ax-load="visible || event (ax-modal-opened)"
        @else
            ax-load
        @endif
        wire:ignore
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('components-swiper', 'wsmallnews/support') }}"
        x-data="supportSwiper({
            hasThumb: @js($hasThumb)
        })"
    {{
        $attributes
            ->class([
                'swiper-container', 'flex',
                match ($thumbPosition) {
                    'left' => 'flex-row-reverse',
                    'right' => 'flex-row',
                    'top' => 'flex-col-reverse',
                    'bottom' => 'flex-col'
                }
            ])
    }}
    >

    <div class="swiper detail-swiper {{$swiperCss}}
        {{
            match ($thumbPosition) {
                'left', 'right' => 'w-[' . $swiperScale . '] h-full',
                'top', 'bottom' => 'h-[' . $swiperScale . '] w-full',
            }
        }}
    ">
        <div class="swiper-wrapper">
            @foreach($images as $image)
                <div class="swiper-slide">
                    <img src="https://unit.smallnews.top/storage/{{$image}}" />
                </div>
            @endforeach
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>

    @if($hasThumb)
        <div class="swiper detail-swiper-thumbs {{$thumbCss}}
            {{
                match ($thumbPosition) {
                    'left', 'right' => 'w-[' . $thumbScale . '] h-full directionx',
                    'top', 'bottom' => 'h-[' . $thumbScale . '] w-full',
                }
            }}
        " thumbsSlider="">
            <div class="
                swiper-wrapper flex
                {{
                    match ($thumbPosition) {
                        'left', 'right' => 'flex-col',
                        'top', 'bottom' => 'flex-row',
                    }
                }}
            ">
                @foreach($images as $image)
                    <div class="swiper-slide">
                        <img src="https://unit.smallnews.top/storage/{{$image}}" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>