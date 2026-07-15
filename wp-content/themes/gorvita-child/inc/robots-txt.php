<?php
/**
 * Virtual robots.txt additions (WooCommerce cart crawler-trap hygiene).
 *
 * meta-externalagent (Meta's AI training crawler) looped through infinite
 * cart URL permutations (/koszyk/?remove_item=...&add-to-cart=...) and
 * generated ~94% of the server's outbound traffic. It is also hard-blocked
 * with 403 at the Traefik layer (docker-compose.yml labels); robots.txt is
 * left reachable for it so it can learn to stop crawling entirely.
 * The generic block protects against any other crawler hitting the same trap
 * (cart/checkout/account pages are noindex anyway — no SEO impact).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'robots_txt', 'gorvita_robots_txt_bot_rules', 20 );

function gorvita_robots_txt_bot_rules( $output ) {
	$output .= "\nUser-agent: meta-externalagent\n";
	$output .= "Disallow: /\n";
	$output .= "\nUser-agent: *\n";
	$output .= "Disallow: /koszyk/\n";
	$output .= "Disallow: /zamowienie/\n";
	$output .= "Disallow: /moje-konto/\n";
	$output .= "Disallow: /*?remove_item=\n";
	$output .= "Disallow: /*add-to-cart=*\n";

	return $output;
}
