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
 * Deep merge source into target; arrays are replaced, not merged.
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
            // Both swipers are located by ref, so several instances can share a page
            const mainEl = this.$refs.main;

            if (this.$refs.thumbs) {
                this.thumbSwiper = new Swiper(this.$refs.thumbs, {
                    modules: [FreeMode, Thumbs],
                    direction: thumbDirection,
                    slidesPerView: 'auto',      // Thumb size comes from CSS; slides adapt along the bar
                    spaceBetween: 8,
                    freeMode: true,
                    watchSlidesProgress: true,
                });
            }

            const swiperOptions = {
                modules: [Autoplay, EffectCards, EffectCoverflow, EffectCreative, EffectCube, EffectFade, EffectFlip, FreeMode, Navigation, Pagination, Thumbs],
                loop: loop,
                effect: effect,
                spaceBetween: 10,           // Gap between slides in px
                slidesPerView: 1,           // Slides visible at once
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
                    slideToClickedSlide: true,  // Clicking a thumbnail switches the main swiper
                };
            }

            // options pass straight through to Swiper and win over every other default
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
