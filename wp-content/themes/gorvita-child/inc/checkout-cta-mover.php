<?php
/**
 * Move the Block Checkout "Kupuję i płacę" CTA visually under the order
 * summary card (right sidebar) on desktop, mirroring how /koszyk/'s
 * `.wc-block-cart__submit-container` sits below `.wc-block-cart__sidebar`.
 *
 * WC Blocks renders the place-order button inside `.wc-block-checkout__main`
 * (`form.wc-block-checkout__form > .wc-block-checkout__actions_row`), so
 * pure CSS cannot reposition it across the two-column flex layout. The
 * script below relocates `.wc-block-checkout__actions_row` into a wrapper
 * appended after `.wc-block-checkout__sidebar` once the block has hydrated,
 * and re-runs on cart state changes (since React re-renders parts of the
 * subtree). Click handling and form submission survive because the button
 * stays inside the same `<form>` via the JS-injected wrapper that gets
 * `.wc-block-checkout__form`-relative positioning.
 *
 * @package GorvitaChild
 */

defined( 'ABSPATH' ) || exit;

function gorvita_checkout_cta_mover_assets() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }

    $rel = '/assets/js/checkout-cta-mover.js';
    $abs = get_stylesheet_directory() . $rel;
    if ( ! file_exists( $abs ) ) {
        return;
    }

    wp_enqueue_script(
        'gorvita-checkout-cta-mover',
        get_stylesheet_directory_uri() . $rel,
        array(),
        filemtime( $abs ),
        true
    );
}
add_action( 'wp_enqueue_scripts', 'gorvita_checkout_cta_mover_assets', 30 );
