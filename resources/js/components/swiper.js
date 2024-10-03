import Swiper from 'swiper';
import { FreeMode, Navigation, Thumbs } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/free-mode';
import 'swiper/css/navigation';
import 'swiper/css/thumbs';

export default function supportSwiper({ hasThumb }) {
    return {
        swiper: null,
        thumbSwiper: null,
        hasThumb,
        init: function() {
            let swiperOptions = {
                modules: [FreeMode, Navigation, Thumbs],
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
                    modules: [FreeMode, Navigation, Thumbs],
                    loop: true,
                    spaceBetween: 10,       // 滑动时两个幻灯片之间的距离 px
                    slidesPerView: 6,       // 可视区域可见幻灯片数量
                    freeMode: true,
                    watchSlidesProgress: true,      // 启用此功能以计算每个幻灯片的进度和可见性(视口中的幻灯片将有额外的可见类
                });

                swiperOptions['thumbs'] = {
                    swiper: this.thumbSwiper,
                }
            }

            this.swiper = new Swiper(".detail-swiper", swiperOptions);
        }
        
    }
}
