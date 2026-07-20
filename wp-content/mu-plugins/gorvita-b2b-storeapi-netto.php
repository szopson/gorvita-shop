<?php
/**
 * Plugin Name: Gorvita — B2B netto prices in Store API products
 * Description: The WooCommerce Store API /products endpoint does not bootstrap
 *   WC()->customer, so B2BKing tax-exemption (netto / "bez VAT") bails on its
 *   is_a( WC_Customer ) guard and returns gross ("z VAT") for B2B users — even
 *   though cart, checkout and product pages already show netto. This shim
 *   establishes WC()->customer for Store API *products* requests only, so
 *   B2BKing OWN rule decides the display mode (no hardcoded group or VAT rate),
 *   keeping the search dropdown consistent with the cart. No-op when B2BKing is
 *   inactive, for guests, or when the customer is already initialised.
 * Version: 1.0.0
 */
defined( "ABSPATH" ) || exit;

add_filter( "rest_request_before_callbacks", "gorvita_storeapi_products_init_customer", 10, 3 );

function gorvita_storeapi_products_init_customer( $response, $handler, $request ) {
	if ( ! ( $request instanceof WP_REST_Request ) || ! function_exists( "WC" ) ) {
		return $response;
	}
	if ( ! preg_match( "#/wc/store/(v\\d+/)?products#", (string) $request->get_route() ) ) {
		return $response;
	}
	if ( ! is_user_logged_in() ) {
		return $response;
	}
	if ( ! function_exists( "b2bking" ) ) {
		return $response;
	}
	$wc = WC();
	if ( isset( $wc->customer ) && $wc->customer instanceof WC_Customer ) {
		return $response;
	}
	if ( is_null( $wc->session ) && method_exists( $wc, "initialize_session" ) ) {
		$wc->initialize_session();
	}
	$wc->customer = new WC_Customer( get_current_user_id(), true );

	return $response;
}
