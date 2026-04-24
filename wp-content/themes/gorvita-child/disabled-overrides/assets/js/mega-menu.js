/**
 * Gorvita mega menu.
 * Finds the desktop "Sklep" nav link and shows the pre-rendered mega panel
 * below the header on hover. Never activates on mobile (≤860px).
 */
(function () {
  'use strict';

  var panel      = null;
  var shopLink   = null;
  var closeTimer = null;
  var headerH    = 0;

  document.addEventListener('DOMContentLoaded', function () {
    panel = document.getElementById('gorvitaMega');
    if (!panel) return;

    // Mobile: skip entirely
    if (window.innerWidth <= 860) return;

    shopLink = findShopLink();
    if (!shopLink) return;

    measureHeader();
    bindEvents();
  });

  /* ---------------------------------------------------------------
     Find the desktop "Sklep" nav link (not inside .ct-panel.ct-header)
  --------------------------------------------------------------- */

  function findShopLink() {
    // Try by URL fragment
    var candidates = document.querySelectorAll(
      '.ct-header a.ct-menu-link, .ct-nav a.ct-menu-link, .site-header a'
    );
    for (var i = 0; i < candidates.length; i++) {
      var el = candidates[i];
      // Skip mobile panel links
      if (el.closest('.ct-panel')) continue;
      var href = el.getAttribute('href') || '';
      var text = (el.textContent || '').trim().toLowerCase();
      if (href.indexOf('sklep') !== -1 || text === 'sklep' || text === 'shop' || text === 'sklep') {
        return el;
      }
    }
    // Fallback: find any top-level nav link whose text contains "sklep"
    var all = document.querySelectorAll('a.ct-menu-link, nav a');
    for (var j = 0; j < all.length; j++) {
      if (all[j].closest('.ct-panel')) continue;
      var t = (all[j].textContent || '').trim().toLowerCase();
      if (t.indexOf('sklep') !== -1) return all[j];
    }
    return null;
  }

  /* ---------------------------------------------------------------
     Measure header height so panel starts below it
  --------------------------------------------------------------- */

  function measureHeader() {
    var header = document.querySelector('.ct-header, header.site-header');
    headerH = header ? header.getBoundingClientRect().bottom : 70;
    panel.style.top = Math.round(headerH) + 'px';
  }

  /* ---------------------------------------------------------------
     Hover events (desktop only)
  --------------------------------------------------------------- */

  function bindEvents() {
    var li = shopLink.parentElement; // the <li>

    li.addEventListener('mouseenter', showPanel);
    li.addEventListener('mouseleave', startCloseTimer);
    panel.addEventListener('mouseenter', cancelCloseTimer);
    panel.addEventListener('mouseleave', startCloseTimer);

    // Re-measure on resize (e.g. sticky header changes height)
    window.addEventListener('resize', function () {
      if (window.innerWidth <= 860) { hidePanel(); return; }
      measureHeader();
      panel.style.top = Math.round(headerH) + 'px';
    }, { passive: true });

    // Escape key closes
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') hidePanel();
    });
  }

  function showPanel() {
    if (window.innerWidth <= 860) return;
    cancelCloseTimer();
    measureHeader();
    panel.style.top = Math.round(headerH) + 'px';
    panel.removeAttribute('hidden');
    panel.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(function () {
      panel.classList.add('is-visible');
    });
  }

  function hidePanel() {
    panel.classList.remove('is-visible');
    panel.setAttribute('aria-hidden', 'true');
    setTimeout(function () { panel.setAttribute('hidden', ''); }, 220);
  }

  function startCloseTimer() {
    closeTimer = setTimeout(hidePanel, 160);
  }

  function cancelCloseTimer() {
    clearTimeout(closeTimer);
  }

})();
