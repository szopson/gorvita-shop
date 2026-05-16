<?php
/**
 * Top announcement bar pointing guests to the B2B registration funnel.
 *
 * Gorvita carries a hidden B2B funnel (page 1761, /rejestracja-b2b/) with
 * dedicated pricing tiers for pharmacies, wholesalers and distributors.
 * Pre-v6.21 the page had no entry point — guests could only land on it via
 * direct URL or a single mention in the /o-marce/ FAQ schema. This bar makes
 * B2B discoverable from any front-of-site page.
 *
 * Visibility rules:
 *   - Hidden for any logged-in user (B2C customers don't need it post-login;
 *     B2BKing-approved B2B users live on their own dashboard). B2BKing does
 *     not assign a dedicated WP role — it gates B2B via user meta — so the
 *     "is logged in" heuristic is both safer and simpler than role sniffing.
 *   - Hidden on the registration page itself (no-op CTA noise).
 *   - Dismissable: an X button writes `gorvita_b2b_bar_dismissed=1` to
 *     localStorage; the inline boot script then keeps the bar hidden on
 *     subsequent loads in that browser. The bar renders with
 *     `style="display:none"` and is unhidden in JS only when the flag is
 *     absent — that way dismissed users never see a flash of the bar.
 *
 * Hooked at `wp_body_open` priority 20 so the markup follows the GTM
 * `<noscript>` iframe (priority 10) for a cleaner HTML source order.
 *
 * Styling lives in docs/customizer-additional-css-v6.3.css under the v6.21
 * section; that file is the source-of-truth pasted into WP Admin → Wygląd →
 * Dostosuj → Dodatkowy CSS.
 *
 * @package GorvitaChild
 */

defined( 'ABSPATH' ) || exit;

function gorvita_b2b_announcement_bar() {
    if ( is_user_logged_in() ) {
        return;
    }
    if ( is_page( 1761 ) ) {
        return;
    }
    ?>
<div class="gorvita-b2b-bar" role="region" aria-label="Oferta hurtowa B2B" style="display:none">
    <span class="gorvita-b2b-bar__text">Aptekom, hurtowniom i&nbsp;dystrybutorom &mdash; sprawdź warunki współpracy B2B</span>
    <a class="gorvita-b2b-bar__btn" href="<?php echo esc_url( home_url( '/rejestracja-b2b/' ) ); ?>">Sprawdź ofertę B2B &rarr;</a>
    <button type="button" class="gorvita-b2b-bar__close" aria-label="Zamknij baner B2B">&times;</button>
</div>
<script>
(function(){
    var KEY = 'gorvita_b2b_bar_dismissed';
    var bar = document.currentScript && document.currentScript.previousElementSibling;
    if ( ! bar || ! bar.classList.contains('gorvita-b2b-bar') ) { return; }
    try {
        if ( window.localStorage && localStorage.getItem(KEY) === '1' ) { return; }
    } catch (e) { /* private mode etc. — fall through and show the bar */ }
    bar.style.display = 'flex';
    var close = bar.querySelector('.gorvita-b2b-bar__close');
    if ( close ) {
        close.addEventListener('click', function(){
            bar.style.display = 'none';
            try { localStorage.setItem(KEY, '1'); } catch (e) {}
        });
    }
})();
</script>
    <?php
}
add_action( 'wp_body_open', 'gorvita_b2b_announcement_bar', 20 );
