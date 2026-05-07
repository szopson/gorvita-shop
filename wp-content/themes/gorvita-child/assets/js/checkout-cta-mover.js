/**
 * Move the WC Block Checkout "Kupuję i płacę" CTA visually under the order
 * summary card (right sidebar) on desktop. On narrow viewports we leave the
 * native flow alone — sidebar already stacks under main on mobile.
 *
 * Strategy:
 *   1. Wait for `.wc-block-checkout__actions_row` (place-order container)
 *      to be present in the DOM.
 *   2. On viewports >= 900px, append it into a wrapper that sits directly
 *      after `.wc-block-checkout__sidebar`.
 *   3. Observe DOM mutations (React rerenders) and re-anchor the wrapper
 *      if the actions row gets re-inserted in its native location.
 */

(function () {
  'use strict';

  var WRAPPER_ID = 'gorvita-checkout-cta-mount';
  var DESKTOP_QUERY = window.matchMedia('(min-width: 900px)');
  var moved = false;

  function ensureWrapper(sidebar) {
    var existing = document.getElementById(WRAPPER_ID);
    if (existing) return existing;
    var wrap = document.createElement('div');
    wrap.id = WRAPPER_ID;
    wrap.className = 'gorvita-checkout-cta-mount';
    // Append INSIDE sidebar so the mount sits at the bottom of the right
    // column. Inserting as a sibling makes it a third flex column in the
    // .wc-block-components-sidebar-layout, which renders it BETWEEN main
    // and sidebar instead of under the sidebar.
    sidebar.appendChild(wrap);
    return wrap;
  }

  function maybeMove() {
    if (!DESKTOP_QUERY.matches) {
      // On mobile the sidebar already collapses under main; let WC native flow stay.
      return;
    }
    var actionsRow = document.querySelector('.wc-block-checkout__actions_row');
    var sidebar = document.querySelector('.wc-block-checkout__sidebar');
    if (!actionsRow || !sidebar) return;

    var wrap = ensureWrapper(sidebar);
    if (actionsRow.parentNode === wrap) return; // already moved
    wrap.appendChild(actionsRow);
    moved = true;
  }

  function maybeRestore() {
    // When viewport drops below desktop, return the actions row to its
    // native parent so WC's native flow is intact on mobile.
    if (DESKTOP_QUERY.matches || !moved) return;
    var wrap = document.getElementById(WRAPPER_ID);
    if (!wrap) return;
    var actionsRow = wrap.querySelector('.wc-block-checkout__actions_row');
    if (!actionsRow) return;
    var form = document.querySelector('form.wc-block-checkout__form');
    var actionsBlock = form && form.querySelector('.wc-block-checkout__actions');
    if (actionsBlock) {
      actionsBlock.appendChild(actionsRow);
      moved = false;
    }
  }

  function init() {
    maybeMove();
    var observer = new MutationObserver(function () {
      // React may re-attach the actions row to its native location on any
      // cart-state mutation; nudge it back if we were already in desktop mode.
      maybeMove();
    });
    observer.observe(document.body, { childList: true, subtree: true });

    DESKTOP_QUERY.addEventListener('change', function () {
      if (DESKTOP_QUERY.matches) {
        maybeMove();
      } else {
        maybeRestore();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  // Belt-and-suspenders for slow block hydration paths.
  setTimeout(maybeMove, 800);
  setTimeout(maybeMove, 2000);
})();
