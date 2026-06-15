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
 *   - Shown to guests and logged-in B2C customers (they're the audience
 *     that still needs to discover the hurt/B2B funnel).
 *   - Hidden only for B2BKing-approved B2B users — detected via
 *     user meta `b2bking_b2buser=yes`, which is the canonical flag
 *     B2BKing uses internally (see class-b2bking-global-helper.php:817).
 *     B2BKing does not assign a dedicated WP role for B2B users, so
 *     role sniffing is not an option.
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

function gorvita_b2b_announcement_bar_should_render() {
    if ( is_page( 1761 ) ) {
        return false;
    }
    if ( is_user_logged_in() ) {
        $is_b2b = get_user_meta( get_current_user_id(), 'b2bking_b2buser', true );
        if ( 'yes' === $is_b2b ) {
            return false;
        }
    }
    return true;
}

function gorvita_b2b_announcement_bar_assets() {
    if ( ! gorvita_b2b_announcement_bar_should_render() ) {
        return;
    }

    $rel = '/assets/css/b2b-announcement-bar.css';
    $abs = get_stylesheet_directory() . $rel;
    if ( ! file_exists( $abs ) ) {
        return;
    }

    wp_enqueue_style(
        'gorvita-b2b-announcement-bar',
        get_stylesheet_directory_uri() . $rel,
        array(),
        filemtime( $abs )
    );
}
add_action( 'wp_enqueue_scripts', 'gorvita_b2b_announcement_bar_assets', 30 );

function gorvita_b2b_announcement_bar() {
    if ( ! gorvita_b2b_announcement_bar_should_render() ) {
        return;
    }
    ?>
<div class="gorvita-b2b-bar" role="region" aria-label="Oferta hurtowa B2B" style="display:none">
    <span class="gorvita-b2b-bar__text">Dla Aptek, Hurtowników i&nbsp;Dystrybutorów mamy specjalną ofertę B2B</span>
    <a class="gorvita-b2b-bar__btn" href="<?php echo esc_url( home_url( '/b2b/' ) ); ?>">Sprawdź ofertę B2B &rarr;</a>
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
