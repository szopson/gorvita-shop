<?php
/**
 * Force WooCommerce store open — disables "Coming Soon" mode at the
 * earliest possible point (muplugins_loaded), before WooCommerce reads
 * its options and wires the template_include hook.
 *
 * Must-use plugins load before regular plugins and the active theme,
 * so pre_option filters registered here always fire first.
 */
defined('ABSPATH') || exit;

// Short-circuit option reads so WC never sees 'yes'
add_filter('pre_option_woocommerce_coming_soon',       '__return_empty_string');
add_filter('pre_option_woocommerce_store_pages_only',  '__return_empty_string');

// Belt-and-suspenders: WC 9.x filter on the helper method
add_filter('woocommerce_coming_soon_is_active', '__return_false');
add_filter('woocommerce_is_coming_soon',        '__return_false');

// Last resort: if the coming-soon template somehow still loads, swap it out
add_filter('template_include', function (string $tpl): string {
    if (false !== strpos($tpl, 'coming-soon')) {
        // Fall through to the normal WooCommerce shop archive
        remove_filter('template_include', $GLOBALS['_wc_coming_soon_cb'] ?? null);
        return locate_template(['archive.php', 'index.php']);
    }
    return $tpl;
}, PHP_INT_MAX);
