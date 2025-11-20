@props([
    'images' => [],                // 图片数组
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
     *  正方形：一版设置宽度 100，高度等于 宽度
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

    $thumbSwiperScale = (100 / $thumbNum) . '% !important';

    if ($hasThumb && in_array($thumbPosition, ['left', 'right'])) {
        // 有缩略图，并且是放到左右
        $swiperWidth = (100 - $thumbScale) . '%';
        $thumbWidth = $thumbScale . '%';
    }




    // if (!$hasThumb) {
    //     $thumbScale = 0;       // 比例改为 0
    // }

    // $swiperScaleFmt = (100 - $thumbScale) . '%';
    // $thumbScaleFmt = $thumbScale . '%';
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

    .detail-swiper-thumbs .swiper-slide {
        opacity: 0.4;
    }
    
    .detail-swiper-thumbs .swiper-slide-thumb-active {
        opacity: 1;
    }

    /* .detail-swiper-thumbs.directionl {
        padding-right: 10px !important;
    }
    .detail-swiper-thumbs.directionr {
        padding-left: 10px !important;
    }
    .detail-swiper-thumbs.directiont {
        padding-bottom: 10px !important;
    }
    .detail-swiper-thumbs.directionb {
        padding-top: 10px !important;
    }

    .detail-swiper-thumbs.directionx .swiper-slide {
        margin-right: 0px;
        margin-bottom: 10px;
        width: 100% !important;
        height: {{$thumbSwiperScale}};
    } */
</style>
@endassets

<div 
    wire:ignore
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('components-swiper', 'wsmallnews/support'))]"
    x-data="supportSwiper({
        isSquare: @js($isSquare),
        hasThumb: @js($hasThumb),
        thumbNum: @js($thumbNum),
        thumbPosition: @js($thumbPosition),
        {{-- thumbScale: @js($thumbScale), --}}
    })"
    x-cloak
    x-resize="setSwiperHeight"
    :style="{ height: swiperHeight + 'px' }"
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

    <div 
        @class([
            'swiper detail-swiper {{$swiperCss}}',
            'w-[' . $swiperWidth . ']' => $hasThumb && in_array($thumbPosition, ['left', 'right']),
        ])
    >
        <div class="swiper-wrapper">
            @foreach($images as $image)
                <div class="swiper-slide">
                    <img src="{{$image}}" class="w-full h-full object-contain" />
                </div>
            @endforeach
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>

    @if($hasThumb)
        <div @class([
                'swiper detail-swiper-thumbs {{$thumbCss}}',
                'w-[' . $thumbWidth . ']' => $hasThumb && in_array($thumbPosition, ['left', 'right']),
            ]) 
        >
            <div @class([
                    'swiper-wrapper flex',
                    match ($thumbPosition) {
                        'left', 'right' => 'flex-col',
                        'top', 'bottom' => 'flex-row',
                    }
                ])
            >
                @foreach($images as $image)
                    <div class="swiper-slide">
                        <img src="{{$image}}" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="w-[20%] h-[20%] hidden"></div>
    <div class="w-[80%] h-[80%] hidden"></div>
</div>

@assets
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"
/>

<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>

<script>
    

    function supportSwiper({ isSquare, hasThumb, thumbNum, thumbPosition, thumbScale }) {
        return {
            swiper: null,
            thumbSwiper: null,
            isSquare,
            hasThumb,
            thumbNum,
            thumbPosition,
            thumbScale,
            swiperHeight: null,
            init: function() {
                let swiperOptions = {
                    // modules: [FreeMode, Navigation, Thumbs],
                    loop: true,
                    spaceBetween: 10,       // 滑动时两个幻灯片之间的距离 px
                    slidesPerView: 1,       // 可视区域可见幻灯片数量
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    }
                }

                if (this.hasThumb) {        // 包含缩略 swiper
                    this.thumbSwiper = new Swiper(".detail-swiper-thumbs", {
                        // modules: [FreeMode, Navigation, Thumbs],
                        loop: true,
                        spaceBetween: 10,       // 滑动时两个幻灯片之间的距离 px
                        slidesPerView: Number(this.thumbNum),       // 可视区域可见幻灯片数量
                        // freeMode: true,
                        watchSlidesProgress: true,      // 启用此功能以计算每个幻灯片的进度和可见性(视口中的幻灯片将有额外的可见类
                    });

                    swiperOptions['thumbs'] = {
                        swiper: this.thumbSwiper,
                    }
                }

                this.swiper = new Swiper(".detail-swiper", swiperOptions);
            },
            setSwiperHeight: function () {
                if (this.isSquare) {
                    if (this.hasThumb) {
                        if (['left', 'right'].includes(this.thumbPosition)) {
                            this.swiperHeight = ((this.$width * (100 - this.thumbScale)) / 100).toFixed(2);
                        } else {
                            this.swiperHeight = (this.$width / ((100 - this.thumbScale) / 100)).toFixed(2);
                        }
                    } else {
                        // 没有缩略图的正方形，高度等于宽度
                        this.swiperHeight = this.$width;
                    }
                } else {
                    // 非正方形，高度还是等于当前容器高度
                    this.swiperHeight = this.$height
                }
            }
        }
    }

</script>

@endassets