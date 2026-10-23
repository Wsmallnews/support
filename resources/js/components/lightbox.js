import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

export default function supportLightbox({ bindKey }) {
    return {
        bindKey,
        lightbox: null,
        init: function() {
            this.lightbox = GLightbox({
                touchNavigation: true,       // Touch navigation
                loop: false,                 // Loop
                autoplayVideos: true,        // Autoplay videos
                selector: '.' + this.bindKey,     // Custom selector
                openEffect: 'zoom',         // Open effect
                closeEffect: 'fade',        // Close effect
                slideEffect: 'slide',       // Slide effect
                closeOnOutsideClick: true,  // Close on outside click
                keyboardNavigation: true,   // Keyboard navigation
                touchFollowAxis: 'y',       // Touch follow axis
            });
        }
    }
}