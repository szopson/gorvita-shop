<?php
/**
 * Free shipping equalizer — make EVERY carrier free above the threshold.
 *
 * Decision (2026-06-15): orders >= 250 zl ship free on ANY method; the customer
 * still picks the carrier. InPost (easypack_*) already zeroes itself via its
 * built-in `free_shipping_cost`. WooCommerce flat_rate (FedEx, Poczta Polska)
 * has NO native amount threshold, so we zero it here.
 *
 * The threshold signal is WooCommerce's OWN free_shipping availability: when the
 * standalone `free_shipping` method appears in the package rates, WC has already
 * decided the cart meets `min_amount` (250) — using the exact same net/gross +
 * ignore_discounts basis as the method. We piggyback on that to avoid any
 * net-vs-gross drift, then hide the redundant standalone "Darmowa wysylka" rate.
 *
 * @package gorvita-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zero flat_rate carriers (and drop the standalone free_shipping option) once the
 * cart qualifies for free shipping.
 *
 * @param array $rates   WC_Shipping_Rate[] keyed by rate id.
 * @param array $package Shipping package (unused — eligibility is read from $rates).
 * @return array
 */
function gorvita_equalize_free_shipping_rates( $rates, $package ) {
	// Does WC consider the cart eligible for free shipping in this package?
	$free_available = false;
	foreach ( $rates as $rate ) {
		if ( 'free_shipping' === $rate->get_method_id() ) {
			$free_available = true;
			break;
		}
	}

	// Below threshold — every method keeps its normal cost.
	if ( ! $free_available ) {
		return $rates;
	}

	foreach ( $rates as $rate_id => $rate ) {
		$method = $rate->get_method_id();

		if ( 'flat_rate' === $method || 0 === strpos( $method, 'easypack' ) ) {
			// FedEx / Poczta Polska (flat_rate, no native threshold) AND InPost
			// (easypack_*, whose own free_shipping_cost check reads the NET
			// displayed subtotal for B2B) — zero them once the cart qualifies.
			$rate->set_cost( 0 );
			$taxes = $rate->get_taxes();
			if ( is_array( $taxes ) ) {
				$rate->set_taxes( array_fill_keys( array_keys( $taxes ), 0 ) );
			}
		} elseif ( 'free_shipping' === $method ) {
			// Redundant generic option — carriers already display 0, hide it.
			unset( $rates[ $rate_id ] );
		}
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'gorvita_equalize_free_shipping_rates', 10, 2 );
