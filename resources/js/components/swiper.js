import Swiper from 'swiper';
import {
    Autoplay,
    EffectCards,
    EffectCoverflow,
    EffectCreative,
    EffectCube,
    EffectFade,
    EffectFlip,
    FreeMode,
    Navigation,
    Pagination,
    Thumbs,
} from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/free-mode';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/thumbs';
import 'swiper/css/effect-fade';
import 'swiper/css/effect-cube';
import 'swiper/css/effect-coverflow';
import 'swiper/css/effect-flip';
import 'swiper/css/effect-cards';

/**
 * 将 source 深度合并到 target（数组直接覆盖，不递归）
 */
function deepMerge(target, source) {
    Object.entries(source || {}).forEach(([key, value]) => {
        const isPlainObject = value !== null && typeof value === 'object' && !Array.isArray(value);
        const targetIsPlainObject = target[key] !== null && typeof target[key] === 'object' && !Array.isArray(target[key]);

        if (isPlainObject && targetIsPlainObject) {
            deepMerge(target[key], value);
        } else {
            target[key] = value;
        }
    });

    return target;
}

export default function supportSwiper({
    effect = 'slide',
    loop = true,
    navigation = true,
    pagination = null,
    autoplay = false,
    autoplayDelay = 3000,
    thumbDirection = 'horizontal',
    options = {},
}) {
    return {
        swiper: null,
        thumbSwiper: null,

        init: function () {
            // 主 swiper / 缩略 swiper 都通过 ref 定位，天然支持同页多实例
            const mainEl = this.$refs.main;

            if (this.$refs.thumbs) {
                this.thumbSwiper = new Swiper(this.$refs.thumbs, {
                    modules: [FreeMode, Thumbs],
                    direction: thumbDirection,
                    slidesPerView: 'auto',      // 缩略图尺寸由 css 控制，沿条方向自适应
                    spaceBetween: 8,
                    freeMode: true,
                    watchSlidesProgress: true,
                });
            }

            const swiperOptions = {
                modules: [Autoplay, EffectCards, EffectCoverflow, EffectCreative, EffectCube, EffectFade, EffectFlip, FreeMode, Navigation, Pagination, Thumbs],
                loop: loop,
                effect: effect,
                spaceBetween: 10,           // 滑动时两个幻灯片之间的距离 px
                slidesPerView: 1,           // 可视区域可见幻灯片数量
                navigation: navigation ? {
                    nextEl: mainEl.querySelector('.swiper-button-next'),
                    prevEl: mainEl.querySelector('.swiper-button-prev'),
                } : false,
                pagination: pagination ? {
                    el: mainEl.querySelector('.swiper-pagination'),
                    type: pagination,
                    clickable: true,
                } : false,
                autoplay: autoplay ? {
                    delay: autoplayDelay,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                } : false,
            };

            if (this.thumbSwiper) {
                swiperOptions.thumbs = {
                    swiper: this.thumbSwiper,
                };
            }

            // options 透传 Swiper 原生配置，优先级最高
            this.swiper = new Swiper(mainEl, deepMerge(swiperOptions, options));
        },

        destroy: function () {
            this.swiper?.destroy(true, true);
            this.thumbSwiper?.destroy(true, true);
        },

        jumpToUrl: function (url, spaMode) {
            if (spaMode) {
                Livewire.navigate(url);
            } else {
                window.location.href = url;
            }
        },
    };
}
