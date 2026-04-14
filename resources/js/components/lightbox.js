import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

export default function supportLightbox({ bindKey }) {
    return {
        bindKey,
        lightbox: null,
        init: function() {
            this.lightbox = GLightbox({
                touchNavigation: true,       // 启用触摸导航
                loop: false,                 // 循环播放
                autoplayVideos: true,        // 自动播放视频
                selector: '.' + this.bindKey,     // 自定义选择器
                openEffect: 'zoom',         // 打开效果
                closeEffect: 'fade',        // 关闭效果
                slideEffect: 'slide',       // 切换效果
                // moreLength: 60,             // 标题省略长度
                // moreText: '查看更多',       // 省略文本
                // lessText: '收起',          // 展开文本
                closeOnOutsideClick: true,  // 点击外部关闭
                keyboardNavigation: true,   // 键盘导航
                touchFollowAxis: 'y',       // 触摸跟随轴
            });
        }
    }
}