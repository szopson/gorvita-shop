/**
 * Free shipping progress bar — block cart + block checkout.
 *
 * Subscribes to `wc/store/cart` and renders a hint into the cart sidebar.
 * Threshold (in major currency units, e.g. PLN) is injected as
 * `gorvitaShippingProgress.threshold` via wp_localize_script.
 */
(function () {
    'use strict';

    var cfg = window.gorvitaShippingProgress;
    if (!cfg || !cfg.threshold || cfg.threshold <= 0) {
        return;
    }

    var THRESHOLD = parseFloat(cfg.threshold);
    var CURRENCY = cfg.currency || 'zł';
    var I18N = cfg.i18n || {};
    var lastSubtotal = -1;

    function formatMoney(value) {
        return value.toFixed(2).replace('.', ',') + ' ' + CURRENCY;
    }

    function getSubtotal() {
        if (!window.wp || !window.wp.data) {
            return 0;
        }
        var store = window.wp.data.select('wc/store/cart');
        if (!store || typeof store.getCartData !== 'function') {
            return 0;
        }
        var data = store.getCartData();
        if (!data || !data.totals) {
            return 0;
        }
        var minorUnit = parseInt(data.totals.currency_minor_unit, 10);
        if (isNaN(minorUnit)) {
            minorUnit = 2;
        }
        var raw = parseInt(data.totals.total_items, 10);
        if (isNaN(raw)) {
            return 0;
        }
        return raw / Math.pow(10, minorUnit);
    }

    function findSidebar() {
        return (
            document.querySelector('.wp-block-woocommerce-cart-totals-block') ||
            document.querySelector('.wp-block-woocommerce-checkout-order-summary-block') ||
            document.querySelector('.wc-block-cart__sidebar') ||
            document.querySelector('.wc-block-checkout__sidebar')
        );
    }

    function findInsertionAnchor(sidebar) {
        return (
            sidebar.querySelector('.wp-block-woocommerce-proceed-to-checkout-block') ||
            sidebar.querySelector('.wp-block-woocommerce-cart-express-payment-block') ||
            sidebar.querySelector('.wp-block-woocommerce-checkout-actions-block')
        );
    }

    // Build the static structure with DOM APIs only (no innerHTML, no
    // string interpolation of user-influenced data — just element creation).
    function buildNode() {
        var node = document.createElement('div');
        node.className = 'gorvita-shipping-hint';
        node.setAttribute('aria-live', 'polite');

        var msg = document.createElement('div');
        msg.className = 'gorvita-shipping-hint__msg';
        node.appendChild(msg);

        var bar = document.createElement('div');
        bar.className = 'gorvita-shipping-hint__bar';
        bar.setAttribute('aria-hidden', 'true');
        var fill = document.createElement('div');
        fill.className = 'gorvita-shipping-hint__fill';
        bar.appendChild(fill);
        node.appendChild(bar);

        return node;
    }

    function ensureNode(sidebar) {
        var node = sidebar.querySelector('.gorvita-shipping-hint');
        if (node) {
            return node;
        }
        node = buildNode();
        var anchor = findInsertionAnchor(sidebar);
        if (anchor) {
            anchor.parentNode.insertBefore(node, anchor);
        } else {
            sidebar.appendChild(node);
        }
        return node;
    }

    // Render the localized message by splitting on the %s placeholder and
    // rebuilding the message as text + <strong> nodes — avoids innerHTML.
    function paintMessage(msgEl, template, valueText) {
        while (msgEl.firstChild) {
            msgEl.removeChild(msgEl.firstChild);
        }
        var parts = template.split('%s');
        msgEl.appendChild(document.createTextNode(parts[0] || ''));
        if (parts.length > 1) {
            var strong = document.createElement('strong');
            strong.textContent = valueText;
            msgEl.appendChild(strong);
            msgEl.appendChild(document.createTextNode(parts.slice(1).join('%s')));
        }
    }

    function render(subtotal) {
        var sidebar = findSidebar();
        if (!sidebar) {
            return;
        }
        var node = ensureNode(sidebar);
        var msg = node.querySelector('.gorvita-shipping-hint__msg');
        var fill = node.querySelector('.gorvita-shipping-hint__fill');
        var missing = THRESHOLD - subtotal;
        var pct = Math.min(100, Math.max(0, (subtotal / THRESHOLD) * 100));

        if (missing > 0.005) {
            var template = I18N.remaining || 'Dodaj %s więcej, aby otrzymać darmową wysyłkę';
            paintMessage(msg, template, formatMoney(missing));
            node.classList.remove('gorvita-shipping-hint--achieved');
        } else {
            msg.textContent = I18N.achieved || '✓ Darmowa wysyłka odblokowana';
            node.classList.add('gorvita-shipping-hint--achieved');
        }
        fill.style.width = pct + '%';
    }

    function tick() {
        var subtotal = getSubtotal();
        if (Math.abs(subtotal - lastSubtotal) < 0.005) {
            return;
        }
        lastSubtotal = subtotal;
        render(subtotal);
    }

    function start() {
        tick();
        if (window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function') {
            window.wp.data.subscribe(tick);
        } else {
            setInterval(tick, 2000);
        }
        var observer = new MutationObserver(function () {
            var sidebar = findSidebar();
            if (sidebar && !sidebar.querySelector('.gorvita-shipping-hint')) {
                render(lastSubtotal >= 0 ? lastSubtotal : getSubtotal());
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
