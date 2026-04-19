/**
 * Gorvita homepage interactions.
 * Pure vanilla JS. No dependencies. Respects prefers-reduced-motion.
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------- Reveal-on-scroll ----------
  function initReveal() {
    var els = document.querySelectorAll('.gorvita-reveal');
    if (!els.length) return;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -60px 0px' });
    els.forEach(function (el) { io.observe(el); });
  }

  // ---------- Ingredient tab switcher ----------
  function initIngredients() {
    var chips = document.querySelectorAll('.gorvita-chip[data-ingr]');
    var panels = document.querySelectorAll('.gorvita-ingr-panel[data-ingr-panel]');
    if (!chips.length || !panels.length) return;

    function activate(key) {
      chips.forEach(function (c) {
        var on = c.dataset.ingr === key;
        c.classList.toggle('is-active', on);
        c.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (p) {
        p.classList.toggle('is-active', p.dataset.ingrPanel === key);
      });
    }

    chips.forEach(function (c) {
      c.addEventListener('click', function () { activate(c.dataset.ingr); });
      c.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activate(c.dataset.ingr);
        }
      });
    });
  }

  // ---------- Quick add feedback ----------
  // Works with WooCommerce AJAX add-to-cart when `.gorvita-prod__quickadd` has data-product_id.
  function initQuickadd() {
    var buttons = document.querySelectorAll('.gorvita-prod__quickadd[data-product_id]');
    if (!buttons.length) return;

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        // If WooCommerce AJAX add-to-cart is present, let it handle the request
        // but swap the button label optimistically.
        var hasWooAjax = typeof window.jQuery !== 'undefined' && typeof window.wc_add_to_cart_params !== 'undefined';
        if (!hasWooAjax) return; // plain link fallback
        e.preventDefault();

        btn.classList.add('is-added');
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg> Dodano';

        window.jQuery(document.body).trigger('adding_to_cart', [window.jQuery(btn), { product_id: btn.dataset.product_id, quantity: 1 }]);
        window.jQuery.post(
          window.wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
          { product_id: btn.dataset.product_id, quantity: 1 },
          function (response) {
            if (!response) return;
            if (response.error && response.product_url) {
              window.location = response.product_url;
              return;
            }
            window.jQuery(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, window.jQuery(btn)]);
            setTimeout(function () {
              btn.classList.remove('is-added');
              btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Do koszyka';
            }, 1600);
          }
        );
      });
    });
  }

  // ---------- Wish (local-storage bookmarklist) ----------
  function initWishlist() {
    var buttons = document.querySelectorAll('.gorvita-prod__wish[data-wish]');
    if (!buttons.length) return;

    var STORAGE_KEY = 'gorvitaWishlist';
    var list = [];
    try { list = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch (e) { list = []; }

    function sync() {
      buttons.forEach(function (b) {
        b.classList.toggle('is-active', list.indexOf(String(b.dataset.wish)) !== -1);
      });
    }
    sync();

    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        var id = String(b.dataset.wish);
        var i = list.indexOf(id);
        if (i === -1) list.push(id); else list.splice(i, 1);
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list)); } catch (e) { /* quota full */ }
        sync();
      });
    });
  }

  // ---------- Header scroll state ----------
  function initHeaderScroll() {
    var header = document.querySelector('header, .site-header, #masthead');
    if (!header) return;
    var last = 0;
    window.addEventListener('scroll', function () {
      var cur = window.scrollY;
      header.classList.toggle('scrolled', cur > 80);
      last = cur;
    }, { passive: true });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initReveal();
    initIngredients();
    initQuickadd();
    initWishlist();
    initHeaderScroll();
  });
})();
