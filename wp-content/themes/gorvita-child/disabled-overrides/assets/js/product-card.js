/**
 * product-card.js — LQIP lazy loader for .gv-product-thumb.lazy images.
 * Wishlist, cart count, and bottom-nav are handled by wishlist.js / mobile-ux.php.
 */
(function () {
    'use strict';

    function loadImage(img) {
        if (!img.dataset.src) return;
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
        img.addEventListener('load', function () {
            img.classList.add('loaded');
        }, { once: true });
        // If already cached (complete before load fires)
        if (img.complete) {
            img.classList.add('loaded');
        }
    }

    function initLqip() {
        var imgs = document.querySelectorAll('img.lazy[data-src]');
        if (!imgs.length) return;

        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        loadImage(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '400px 0px' });

            imgs.forEach(function (img) { io.observe(img); });
        } else {
            // Fallback: load all immediately
            imgs.forEach(loadImage);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLqip);
    } else {
        initLqip();
    }
})();
