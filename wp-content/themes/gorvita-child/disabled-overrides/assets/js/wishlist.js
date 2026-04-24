/**
 * Wishlist — toggle heart, call AJAX, update UI.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.gorvita-wishlist-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        if (!window.gorvitaWishlist || !window.gorvitaWishlist.isLoggedIn) {
            // Redirect guest to login
            window.location.href = window.gorvitaWishlist.loginUrl;
            return;
        }

        const productId = btn.getAttribute('data-product-id');
        if (!productId) return;

        btn.setAttribute('disabled', 'disabled');

        const form = new FormData();
        form.append('action', 'gorvita_wishlist_toggle');
        form.append('product_id', productId);
        form.append('nonce', window.gorvitaWishlist.nonce);

        fetch(window.gorvitaWishlist.ajaxUrl, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.removeAttribute('disabled');
                if (data && data.success) {
                    btn.classList.toggle('is-active', data.data.in_list);
                    btn.setAttribute('aria-pressed', data.data.in_list ? 'true' : 'false');
                    // Update any wishlist-count badges on page
                    document.querySelectorAll('.gorvita-wishlist-count').forEach(function (el) {
                        el.textContent = data.data.count;
                    });
                }
            })
            .catch(function () {
                btn.removeAttribute('disabled');
            });
    });
})();
