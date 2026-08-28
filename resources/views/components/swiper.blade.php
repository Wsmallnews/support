@props([
    'slides' => [],               // 幻灯片：图片地址字符串，或数组 ['image', 'url', 'label', 'label_class', 'html']
    'swiperCss' => '',            // 附加到主 swiper 容器的 css 类
    'ratio' => null,              // 主 swiper 宽高比（如 "1/1"、"16/9"），设置后主 swiper 高度按比例自适应
    'hasThumb' => true,           // 是否显示缩略图（仅 1 张幻灯片时自动隐藏）
    'thumbPosition' => 'bottom',  // 缩略图位置：left / right / top / bottom
    'thumbSize' => 80,            // 缩略条交叉轴尺寸 px（左右 = 条宽，上下 = 条高），单张缩略图沿条方向自适应
    'thumbCss' => '',             // 附加到缩略条容器的 css 类
    'labelClass' => null,         // 说明条全局样式，可被单张幻灯片的 label_class 覆盖
    'imageFit' => 'contain',      // 主图填充方式：contain / cover
    'effect' => 'slide',          // 切换效果：slide / fade / cube / coverflow / flip / cards
    'loop' => true,               // 循环播放
    'navigation' => true,         // 显示左右切换按钮
    'pagination' => null,         // 分页指示器：null / bullets / fraction / progressbar
    'autoplay' => false,          // 自动播放
    'autoplayDelay' => 3000,      // 自动播放间隔 ms
    'options' => [],              // Swiper 原生配置（与默认配置深度合并，优先级最高）：https://swiperjs.com/swiper-api
])

@php
    /**
     * 外层容器宽高完全由调用处控制（class / style 直接透传到最外层）：
     *   - 外层设置固定尺寸（h-xxx、aspect-video 等）：主 swiper 自动占满剩余空间（缩略条除外）
     *   - 外层不设高度：可设置 ratio 让主 swiper 按宽高比自适应高度
     *
     * 缩略图：
     *   - thumbSize 控制缩略条的交叉轴尺寸（左右 = 条宽，上下 = 条高）
     *   - 单张缩略图在条内沿条方向自适应（左右 = 宽 100% 高自适应，上下 = 高 100% 宽自适应）
     *
     * 幻灯片说明条样式：labelClass 全局设置，单张幻灯片可用 label_class 覆盖；
     * 需要完全自定义幻灯片内容时，给单张幻灯片设置 html 键（忽略 image/label 默认渲染）
     */

    use Filament\Support\Facades\FilamentView;
    use Illuminate\Support\Arr;

    $slides = Arr::map($slides, function ($slide) {
        return is_string($slide) ? ['image' => $slide] : $slide;
    });

    $isVerticalThumb = in_array($thumbPosition, ['left', 'right']);

    // 仅 1 张幻灯片时缩略图没有意义，自动隐藏
    $hasThumb = $hasThumb && count($slides) > 1;

    $labelClass = $labelClass ?: 'absolute bottom-0 left-0 right-0 z-10 text-left text-sm leading-6 text-white bg-black/50 px-2 py-1 line-clamp-1';

    $imageFitClass = $imageFit === 'cover' ? 'object-cover' : 'object-contain';

    $spaMode = FilamentView::hasSpaMode();
@endphp

@assets
<style>
    .sn-swiper {
        --swiper-navigation-color: #fff;
        --swiper-pagination-color: #fff
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

    /* 缩略图自适应：左右 = 宽 100% 高自适应，上下 = 高 100% 宽自适应 */
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
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('sn-support-components-swiper', 'wsmallnews/support') }}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('sn-support-components-swiper', 'wsmallnews/support'))]"
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
        x-ref="main"
        @class([
            'swiper sn-swiper flex-1 min-w-0 min-h-0',
            $swiperCss,
        ])
        @if ($ratio)
            style="aspect-ratio: {{ $ratio }}"
        @endif
        x-cloak
    >
        <div class="swiper-wrapper">
            @foreach($slides as $slide)
                @php
                    $url = $slide['url'] ?? null;
                    $label = $slide['label'] ?? null;
                    $slideLabelClass = $slide['label_class'] ?? $labelClass;
                @endphp
                <div
                    @class([
                        'swiper-slide',
                        'cursor-pointer' => $url,
                    ])
                    @if ($url)
                        @click="jumpToUrl('{{ $url }}', @js($spaMode))"
                    @endif
                >
                    @if (! empty($slide['html']))
                        {!! $slide['html'] !!}
                    @else
                        <img src="{{ $slide['image'] }}" loading="lazy" class="w-full h-full {{ $imageFitClass }}" />
                        @if ($label)
                            <div class="swiper-slide-label {{ $slideLabelClass }}">{{ $label }}</div>
                        @endif
                    @endif
                </div>
            @endforeach
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
                $isVerticalThumb ? 'is-vertical' : 'is-horizontal',
                $thumbCss,
            ])
            style="{{ $isVerticalThumb ? 'width: ' . $thumbSize . 'px' : 'height: ' . $thumbSize . 'px' }}"
        >
            <div class="swiper-wrapper">
                @foreach($slides as $slide)
                    <div class="swiper-slide">
                        @if (! empty($slide['image']))
                            <img src="{{ $slide['image'] }}" loading="lazy" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
