@props([
    'slides' => [],               // 幻灯片：图片地址字符串，或数组 ['image', 'href', 'html']
    'swiperCss' => '',            // 附加到主 swiper 容器的 css 类
    'ratio' => null,              // 主 swiper 宽高比（如 "1/1"、"16/9"），设置后主 swiper 高度按比例自适应
    'rounded' => false,           // 圆角：true = rounded-xl；也可传自定义 class（如 "rounded-3xl"）
    'hasThumb' => true,           // 是否显示缩略图（仅 1 张幻灯片时自动隐藏）
    'thumbPosition' => 'bottom',  // 缩略图位置：left / right / top / bottom
    'thumbSize' => 80,            // 缩略条交叉轴尺寸 px（左右 = 条宽，上下 = 条高），单张缩略图沿条方向自适应
    'thumbCss' => '',             // 附加到缩略条容器的 css 类
    'imageFit' => 'cover',        // 主图填充方式：cover / contain
    'effect' => 'slide',          // 切换效果：slide / fade / cube / coverflow / flip / cards
    'loop' => true,               // 循环播放
    'navigation' => true,         // 显示左右切换按钮
    'pagination' => 'bullets',    // 分页指示器：null / bullets / fraction / progressbar
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
     * 两种用法：
     *
     * 1. 简单模式：传入 slides（图片地址字符串，或数组 ['image', 'href', 'html']），循环在组件内部：
     *        <x-sn-support::swiper :slides="$slides" />
     *
     * 2. 组合模式：不传 slides，循环在调用处，slot 中渲染 swiper.slide 子组件，
     *    覆盖内容直接读取循环 item 渲染，定位与样式完全由调用处控制：
     *        <x-sn-support::swiper :has-thumb="true">
     *            @foreach ($slides as $item)
     *                <x-sn-support::swiper.slide :item="$item">
     *                    <div class="absolute bottom-0 ...">{{ $item['label'] }}</div>
     *                </x-sn-support::swiper.slide>
     *            @endforeach
     *        </x-sn-support::swiper>
     */

    use Filament\Support\Facades\FilamentView;
    use Illuminate\Support\Arr;

    $slides = Arr::map($slides, function ($slide) {
        return is_string($slide) ? ['image' => $slide] : $slide;
    });

    $isVerticalThumb = in_array($thumbPosition, ['left', 'right']);

    // 是否为组合模式：slot 不为空 = 调用处自行循环渲染 swiper.slide 子组件（此时组件拿不到 $slides 数组）
    $hasSlotSlides = ! $slot->isEmpty();

    if ($hasSlotSlides) {
        // 组合模式下缩略图列表需从 slot 中"逆向"提取：
        // slide 子组件会把图片地址写在根元素的 data-slide-image 属性上（见 swiper/slide.blade.php），
        // 而 slot 到组件手里已是渲染完的 HTML 字符串，只能用正则按 DOM 顺序把地址抓出来。
        // 该匹配是可靠的：属性名由我们自己的子组件输出，且 Blade 转义会把值中的 " 变为 &quot;，不会越界。
        preg_match_all('/data-slide-image="([^"]*)"/', $slot->toHtml(), $thumbMatches);
        // 属性值经 {{ }} 转义（& -> &amp; 等），先 html_entity_decode 还原真实 URL；
        // array_filter 去掉未设置图片的项（如纯 html 内容的 slide），避免缩略条出现空块；
        // array_filter 会保留原 key 产生空洞，最后 array_values 重建索引
        $thumbImages = array_values(array_filter(array_map('html_entity_decode', $thumbMatches[1])));
    } else {
        // 简单模式：slides 数组就在组件内部，直接取出 image 列作为缩略图列表
        $thumbImages = array_column($slides, 'image');
    }

    // 仅 1 张幻灯片时缩略图没有意义，自动隐藏
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
     * swiper 自带样式给 .swiper 设置了水平 auto margin，在 flex-col 布局（缩略图在上下）中
     * 会禁用交叉轴 stretch，使主 swiper 变为内容自适应宽度：Swiper 按测量值写入 slide 宽度
     * 后内容随之变宽，ResizeObserver 再次测量再放大，宽度会指数级膨胀。
     * 这里用双类选择器提高优先级（x-load-css 注入的样式表在页面内联样式之后，同级会覆盖）。
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
            'gap-2 md:gap-4 lg:gap-6' => $hasThumb,
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
