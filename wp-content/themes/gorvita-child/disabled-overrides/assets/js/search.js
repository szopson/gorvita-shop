/**
 * Gorvita live search overlay.
 * Opens on: Blocksy search icon click | Ctrl+K / Cmd+K | custom trigger.
 * Closes on: Escape | backdrop click | "Anuluj" button.
 */
(function () {
  'use strict';

  var cfg     = window.gorvitaSearchCfg || {};
  var overlay = null;
  var input   = null;
  var results = null;
  var suggestions = null;
  var empty   = null;
  var loader  = null;
  var clearBtn = null;
  var debounceTimer = null;
  var lastQuery = '';

  /* ---------------------------------------------------------------
     Init
  --------------------------------------------------------------- */

  document.addEventListener('DOMContentLoaded', function () {
    overlay     = document.getElementById('gorvitaSearch');
    input       = document.getElementById('gorvitaSearchInput');
    results     = document.getElementById('gorvitaSearchResults');
    suggestions = document.getElementById('gorvitaSearchSuggestions');
    empty       = document.getElementById('gorvitaSearchEmpty');
    loader      = document.getElementById('gorvitaSearchLoader');
    clearBtn    = document.getElementById('gorvitaSearchClear');

    if (!overlay || !input) return;

    // Set placeholder from i18n
    if (cfg.i18n && cfg.i18n.placeholder) {
      input.placeholder = cfg.i18n.placeholder;
    }

    setupTriggers();
    setupOverlay();
    setupInput();
    setupKeyboard();
  });

  /* ---------------------------------------------------------------
     Triggers: Blocksy search button + fallback inject
  --------------------------------------------------------------- */

  function setupTriggers() {
    // Intercept Blocksy's built-in search button
    var blocksySearch = document.querySelector(
      '.ct-search-block button, [data-id="search"] button, .ct-header .ct-search button'
    );
    if (blocksySearch) {
      blocksySearch.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openSearch();
      });
    } else {
      // No Blocksy search found — inject our own trigger into the header
      injectTrigger();
    }

    // Also wire the custom trigger if it exists in DOM
    var customTrigger = document.getElementById('gorvitaSearchTrigger');
    if (customTrigger) {
      customTrigger.addEventListener('click', openSearch);
    }
  }

  function injectTrigger() {
    // Find Blocksy header or fall back to site-header
    var header = document.querySelector('.ct-header-content, .ct-header, header.site-header');
    if (!header) return;

    var btn = document.createElement('button');
    btn.className   = 'gorvita-search-trigger';
    btn.id          = 'gorvitaSearchTrigger';
    btn.setAttribute('aria-label', 'Szukaj');
    btn.setAttribute('aria-expanded', 'false');
    btn.innerHTML   = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" '
      + 'stroke="currentColor" stroke-width="2" aria-hidden="true">'
      + '<circle cx="11" cy="11" r="7"/>'
      + '<line x1="16.5" y1="16.5" x2="22" y2="22"/>'
      + '</svg>';
    btn.addEventListener('click', openSearch);

    // Append near the end of header
    var lastChild = header.lastElementChild;
    if (lastChild) {
      header.insertBefore(btn, lastChild.nextSibling);
    } else {
      header.appendChild(btn);
    }
  }

  /* ---------------------------------------------------------------
     Overlay open / close
  --------------------------------------------------------------- */

  function openSearch() {
    if (!overlay) return;
    overlay.removeAttribute('hidden');
    document.body.style.overflow = 'hidden';

    // Trigger animation on next frame
    requestAnimationFrame(function () {
      overlay.classList.add('is-open');
    });

    // Focus input after panel slides in
    setTimeout(function () { input && input.focus(); }, 280);

    // Update aria on any trigger
    var trigger = document.getElementById('gorvitaSearchTrigger');
    if (trigger) trigger.setAttribute('aria-expanded', 'true');
  }

  function closeSearch() {
    if (!overlay) return;
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';

    var trigger = document.getElementById('gorvitaSearchTrigger');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');

    // Wait for slide-out animation then hide
    setTimeout(function () {
      overlay.setAttribute('hidden', '');
      clearResults();
      input.value = '';
      input.blur();
    }, 300);
  }

  function setupOverlay() {
    var backdrop = document.getElementById('gorvitaSearchBackdrop');
    var closeBtn = document.getElementById('gorvitaSearchClose');

    if (backdrop) backdrop.addEventListener('click', closeSearch);
    if (closeBtn) closeBtn.addEventListener('click', closeSearch);
  }

  /* ---------------------------------------------------------------
     Input handling
  --------------------------------------------------------------- */

  function setupInput() {
    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearBtn && (clearBtn.hidden = q.length === 0);

      clearTimeout(debounceTimer);
      if (q.length < 2) {
        clearResults();
        return;
      }
      debounceTimer = setTimeout(function () { fetchResults(q); }, 320);
    });

    clearBtn && clearBtn.addEventListener('click', function () {
      input.value = '';
      clearBtn.hidden = true;
      clearResults();
      input.focus();
    });
  }

  function clearResults() {
    lastQuery = '';
    showState('suggestions');
  }

  /* ---------------------------------------------------------------
     AJAX product fetch
  --------------------------------------------------------------- */

  function fetchResults(q) {
    if (q === lastQuery) return;
    lastQuery = q;

    showState('loader');

    var url = cfg.ajaxUrl
      + '?action=gorvita_search'
      + '&nonce=' + encodeURIComponent(cfg.nonce)
      + '&q=' + encodeURIComponent(q)
      + (cfg.context ? '&context=' + encodeURIComponent(cfg.context) : '');

    fetch(url, { method: 'GET', credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          renderResults(data.data.products, q);
        } else {
          showState('empty');
        }
      })
      .catch(function () { showState('empty'); });
  }

  /* ---------------------------------------------------------------
     Render results
  --------------------------------------------------------------- */

  function renderResults(products, q) {
    if (!products || products.length === 0) {
      showState('empty');
      return;
    }

    var html = '';
    products.forEach(function (p) {
      var badge = p.badge ? renderBadge(p.badge) : '';
      var img   = p.image
        ? '<img class="gorvita-search__result-img" src="' + p.image + '" alt="" loading="lazy" width="64" height="64">'
        : '<div class="gorvita-search__result-img"></div>';

      html += '<a class="gorvita-search__result" href="' + p.url + '">'
        + img
        + '<div class="gorvita-search__result-meta">'
        + (p.category ? '<p class="gorvita-search__result-cat">' + p.category + '</p>' : '')
        + '<p class="gorvita-search__result-name">' + escHtml(p.name) + '</p>'
        + '<div class="gorvita-search__result-bottom">'
        + '<span class="gorvita-search__result-price">' + p.price + '</span>'
        + badge
        + '</div>'
        + '</div>'
        + '</a>';
    });

    // "View all" link
    var searchUrl = '/sklep/?s=' + encodeURIComponent(q);
    html += '<a class="gorvita-search__view-all" href="' + searchUrl + '">'
      + (cfg.i18n && cfg.i18n.viewAll ? escHtml(cfg.i18n.viewAll) : 'Zobacz wszystkie wyniki')
      + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>'
      + '</a>';

    results.innerHTML = html;
    showState('results');
  }

  function renderBadge(badge) {
    var map = { sale: 'Promocja', best: 'Bestseller', new: 'Nowość', cbd: 'CBD Gold' };
    var label = map[badge] || '';
    if (!label) return '';
    return '<span class="gorvita-search__result-badge gorvita-search__result-badge--' + badge + '">' + label + '</span>';
  }

  /* ---------------------------------------------------------------
     State management: show one section at a time
  --------------------------------------------------------------- */

  function showState(state) {
    suggestions && (suggestions.hidden = state !== 'suggestions');
    results     && (results.hidden     = state !== 'results');
    empty       && (empty.hidden       = state !== 'empty');
    loader      && (loader.hidden      = state !== 'loader');
  }

  /* ---------------------------------------------------------------
     Keyboard navigation
  --------------------------------------------------------------- */

  function setupKeyboard() {
    // Ctrl+K / Cmd+K to open
    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        openSearch();
        return;
      }
      if (e.key === 'Escape') {
        closeSearch();
      }
    });
  }

  /* ---------------------------------------------------------------
     Helpers
  --------------------------------------------------------------- */

  function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

})();
