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
        c.setAttribute('tabindex', on ? '0' : '-1');
      });
      panels.forEach(function (p) {
        p.classList.toggle('is-active', p.dataset.ingrPanel === key);
      });
    }

    var chipArr = Array.from(chips);
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        activate(c.dataset.ingr);
        c.focus();
      });
      c.addEventListener('keydown', function (e) {
        var idx = chipArr.indexOf(c);
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activate(c.dataset.ingr);
        } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
          e.preventDefault();
          var next = chipArr[(idx + 1) % chipArr.length];
          activate(next.dataset.ingr);
          next.focus();
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
          e.preventDefault();
          var prev = chipArr[(idx - 1 + chipArr.length) % chipArr.length];
          activate(prev.dataset.ingr);
          prev.focus();
        } else if (e.key === 'Home') {
          e.preventDefault();
          activate(chipArr[0].dataset.ingr);
          chipArr[0].focus();
        } else if (e.key === 'End') {
          e.preventDefault();
          var last = chipArr[chipArr.length - 1];
          activate(last.dataset.ingr);
          last.focus();
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

  // ---------- Product carousel (CSS scroll-snap) ----------
  function initCarousel() {
    var wraps = document.querySelectorAll('.gorvita-carousel-wrap[data-carousel]');
    if (!wraps.length) return;

    wraps.forEach(function (wrap) {
      var track = wrap.querySelector('.gorvita-prod-grid');
      var dotsEl = wrap.querySelector('.gorvita-carousel__dots');
      var btnPrev = wrap.querySelector('.gorvita-carousel__btn--prev');
      var btnNext = wrap.querySelector('.gorvita-carousel__btn--next');
      if (!track) return;

      var cards = Array.from(track.children);
      if (!cards.length) return;

      // Build dots
      if (dotsEl) {
        cards.forEach(function (_, i) {
          var dot = document.createElement('button');
          dot.className = 'gorvita-carousel__dot' + (i === 0 ? ' is-active' : '');
          dot.setAttribute('role', 'tab');
          dot.setAttribute('aria-label', 'Produkt ' + (i + 1));
          dot.addEventListener('click', function () {
            track.scrollTo({ left: cards[i].offsetLeft - track.offsetLeft, behavior: 'smooth' });
          });
          dotsEl.appendChild(dot);
        });
      }

      function getActiveIndex() {
        var trackLeft = track.getBoundingClientRect().left;
        var mid = track.clientWidth / 2;
        var best = 0, bestDist = Infinity;
        cards.forEach(function (c, i) {
          var dist = Math.abs(c.getBoundingClientRect().left - trackLeft - mid + c.offsetWidth / 2);
          if (dist < bestDist) { bestDist = dist; best = i; }
        });
        return best;
      }

      function updateDots() {
        if (!dotsEl) return;
        var idx = getActiveIndex();
        Array.from(dotsEl.children).forEach(function (d, i) {
          d.classList.toggle('is-active', i === idx);
        });
      }

      track.addEventListener('scroll', updateDots, { passive: true });

      if (btnPrev) {
        btnPrev.addEventListener('click', function () {
          var idx = getActiveIndex();
          var target = cards[Math.max(0, idx - 1)];
          track.scrollTo({ left: target.offsetLeft - track.offsetLeft, behavior: 'smooth' });
        });
      }
      if (btnNext) {
        btnNext.addEventListener('click', function () {
          var idx = getActiveIndex();
          var target = cards[Math.min(cards.length - 1, idx + 1)];
          track.scrollTo({ left: target.offsetLeft - track.offsetLeft, behavior: 'smooth' });
        });
      }
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

  // ---------- Footer accordion (mobile only) ----------
  function initFooterAccordion() {
    if (window.innerWidth > 768) return;
    document.querySelectorAll('.gorvita-footer__section[open]').forEach(function (el) {
      el.removeAttribute('open');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initReveal();
    initIngredients();
    initQuickadd();
    initWishlist();
    initHeaderScroll();
    initCarousel();
    initFooterAccordion();
  });
})();
