<?php
/**
 * Replace WC default related products + cross-sells on PDP with the same
 * "[products tag=nowosc limit=4 columns=4]" pattern used on the homepage.
 *
 * Why: cross-sells on /product/<x>/ pulled WC Block Product Collection
 * which renders different markup/styling than the homepage Bestsellers
 * + Nowości rows. User wanted uniform card look across the entire shop.
 *
 * Removes:
 *   - woocommerce_output_related_products  (priority 20 on woocommerce_after_single_product_summary)
 *   - upsell_display                       (priority 15 on woocommerce_after_single_product_summary)
 *
 * Renders instead a heading + the canonical [products] shortcode at
 * priority 18, between the summary and the "Powiązane produkty" placeholder.
 *
 * @package GorvitaChild
 */

defined( 'ABSPATH' ) || exit;

function gorvita_pdp_replace_related_with_nowosci() {
	if ( function_exists( 'woocommerce_output_related_products' ) ) {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	}
	if ( function_exists( 'woocommerce_upsell_display' ) ) {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	}
}
add_action( 'init', 'gorvita_pdp_replace_related_with_nowosci', 20 );

function gorvita_pdp_render_nowosci() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$current_id = get_the_ID();
	echo '<section class="gorvita-pdp-nowosci">';
	echo '<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide" />';
	echo '<h2 class="wp-block-heading has-text-align-left gorvita-pdp-nowosci__heading">'
		. esc_html__( 'Może Cię zainteresować…', 'gorvita-child' )
		. '</h2>';
	// Same shortcode pattern as homepage Nowości row.
	echo do_shortcode( '[products tag="nowosc" limit="4" columns="4"]' );
	echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'gorvita_pdp_render_nowosci', 18 );
