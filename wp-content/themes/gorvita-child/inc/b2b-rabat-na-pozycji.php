<?php
/**
 * GORVITA — record the B2B discount basis on each order line item.
 *
 * Why this exists: under B2BKing "wariant B" (discount_show_everywhere = 1) the
 * discount is baked into the unit price at checkout and the order records nothing
 * about it — line subtotal equals line total, the item carries no meta, and the
 * order meta holds only b2bking_is_b2b_order / b2bking_b2b_group. The invoice
 * therefore cannot show the contractual "-18%" unless we persist it ourselves,
 * at the moment the order is created. Reading it back from the live rules later
 * would be wrong: rules and prices change, invoices must not.
 *
 * Hook choice — woocommerce_new_order_item:
 *   Store API (block checkout, the production path) calls
 *   wc()->checkout->create_order_line_items(), classic checkout calls the same,
 *   and both end in the order-item data store. Admin-created orders and REST
 *   (/wc/v3/orders) never touch WC_Checkout at all. The data store's create()
 *   fires woocommerce_new_order_item for every path, so it is the only single
 *   hook covering all three. (woocommerce_checkout_create_order_line_item would
 *   miss admin and REST.)
 *
 * @package gorvita-child
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_new_order_item', 'gorvita_zapisz_podstawe_rabatu', 10, 3 );

/**
 * @param int           $item_id  Order item ID.
 * @param WC_Order_Item $item     Order item object.
 * @param int           $order_id Order ID.
 */
function gorvita_zapisz_podstawe_rabatu( $item_id, $item, $order_id ) {
	// Only product lines — the hook also fires for shipping, fee, tax and coupon items.
	if ( ! $item instanceof WC_Order_Item_Product ) {
		return;
	}
	if ( ! class_exists( 'B2bking_Globalhelper' ) ) {
		return;
	}

	$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
	if ( ! $product_id ) {
		return;
	}

	// The percentage is resolved for the ORDER'S CUSTOMER, not for whoever is
	// executing the request. They coincide on the Store API path but diverge when
	// an admin creates an order on a customer's behalf — there the current user is
	// the admin, whose discount is 0. Switch, read, switch back immediately.
	$order       = wc_get_order( $order_id );
	$customer_id = $order ? (int) $order->get_customer_id() : 0;
	$biezacy     = get_current_user_id();
	$przelaczony = ( $customer_id > 0 && $customer_id !== $biezacy );

	if ( $przelaczony ) {
		wp_set_current_user( $customer_id );
	}
	$procent = (float) B2bking_Globalhelper::get_discount_everywhere_percentage( $product_id );
	if ( $przelaczony ) {
		wp_set_current_user( $biezacy );
	}

	// No discount (B2C, guest, or a product the rule does not cover) — write nothing.
	// A B2C invoice must not merely hide the column; the meta must not exist at all,
	// so the template can tell "no discount" from "discount unknown".
	if ( $procent <= 0 ) {
		return;
	}

	// Raw postmeta, deliberately not $product->get_regular_price(): B2BKing filters
	// the price getters at priority 9999 and we need the untouched catalogue value.
	$katalogowa = get_post_meta( $product_id, '_regular_price', true );
	if ( '' === $katalogowa || null === $katalogowa ) {
		return;
	}

	$item->add_meta_data( '_gorvita_cena_katalogowa_netto', wc_format_decimal( $katalogowa, 2 ), true );
	$item->add_meta_data( '_gorvita_rabat_procent', wc_format_decimal( $procent, 2 ), true );
	$item->save_meta_data();
}

/**
 * Keep both keys out of the customer-facing item meta list (order emails, thank-you
 * page, My Account). They are bookkeeping for the invoice, not product options.
 * The leading underscore already hides them in core, but WCPDF and some themes
 * render hidden meta when explicitly asked, so state the intent.
 */
add_filter( 'woocommerce_hidden_order_itemmeta', 'gorvita_ukryj_meta_rabatu' );
function gorvita_ukryj_meta_rabatu( $ukryte ) {
	$ukryte[] = '_gorvita_cena_katalogowa_netto';
	$ukryte[] = '_gorvita_rabat_procent';
	return $ukryte;
}
