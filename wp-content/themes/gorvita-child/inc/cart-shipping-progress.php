<?php
/**
 * Free shipping progress bar for block cart + block checkout.
 *
 * Reads the threshold from any enabled `free_shipping` method
 * (`woocommerce_free_shipping_*_settings.min_amount`) and exposes it to
 * the JS client, which subscribes to `wc/store/cart` and renders the
 * "Dodaj X zł więcej, aby otrzymać darmową wysyłkę" hint into the cart
 * sidebar. Rebuilds the feature originally in
 * `disabled-overrides/inc/mobile-ux.php::gorvita_render_shipping_hint`,
 * which was hooked to `woocommerce_before_cart_table` and never fired
 * once page 7 became a `wp:woocommerce/cart` block.
 *
 * @package GorvitaChild
 */

defined( 'ABSPATH' ) || exit;

function gorvita_get_free_shipping_threshold() {
    static $cached = null;
    if ( $cached !== null ) {
        return $cached;
    }

    $threshold = 0.0;

    // Iterate over every free_shipping zone method. Pick the lowest enabled
    // min_amount so customers see an achievable goal regardless of which
    // zone they end up matching at checkout.
    global $wpdb;
    $rows = $wpdb->get_col(
        "SELECT option_value FROM {$wpdb->options}
         WHERE option_name LIKE 'woocommerce_free_shipping_%_settings'"
    );
    foreach ( $rows as $serialized ) {
        $opt = maybe_unserialize( $serialized );
        if ( ! is_array( $opt ) || empty( $opt['min_amount'] ) ) {
            continue;
        }
        $val = (float) $opt['min_amount'];
        if ( $val <= 0 ) {
            continue;
        }
        if ( $threshold === 0.0 || $val < $threshold ) {
            $threshold = $val;
        }
    }

    $cached = $threshold;
    return $threshold;
}

function gorvita_cart_shipping_progress_assets() {
    if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
        return;
    }
    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }

    $threshold = gorvita_get_free_shipping_threshold();
    if ( $threshold <= 0 ) {
        return;
    }

    $rel = '/assets/js/cart-shipping-progress.js';
    $abs = get_stylesheet_directory() . $rel;
    if ( ! file_exists( $abs ) ) {
        return;
    }

    $handle = 'gorvita-cart-shipping-progress';
    wp_enqueue_script(
        $handle,
        get_stylesheet_directory_uri() . $rel,
        array( 'wp-data', 'wc-blocks-data-store' ),
        filemtime( $abs ),
        true
    );

    wp_localize_script(
        $handle,
        'gorvitaShippingProgress',
        array(
            'threshold' => (float) $threshold,
            'currency'  => function_exists( 'get_woocommerce_currency_symbol' )
                ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' )
                : 'zł',
            'i18n'      => array(
                'remaining' => __( 'Dodaj %s więcej, aby otrzymać darmową wysyłkę', 'gorvita-child' ),
                'achieved'  => __( '✓ Darmowa wysyłka odblokowana', 'gorvita-child' ),
            ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'gorvita_cart_shipping_progress_assets', 30 );
