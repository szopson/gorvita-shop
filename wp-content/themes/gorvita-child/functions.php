<?php
// Gorvita Child — naked mode. All enqueues and modules disabled for Blocksy configuration.

function gorvita_theme_setup() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'gorvita_theme_setup' );
