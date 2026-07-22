<?php
/**
 * GORVITA — straznik promocji: czy promocja B2C nie schodzi ponizej ceny B2B.
 *
 * Uruchamiac PRZY KAZDYM imporcie cennika i przy kazdej zmianie promocji:
 *   docker compose exec -T wordpress wp eval-file /tmp/straznik-promocji.php --allow-root
 *
 * Co sprawdza. Dla kazdego produktu z aktywnym _sale_price:
 *
 *     sale_netto  >  regular_netto x (1 - rabat_B2B)
 *
 * Jesli warunek nie zachodzi, kontrahent B2B placi WIECEJ niz gosc z ulicy,
 * a faktura pokazuje mu rabat, ktorego faktycznie nie ma — bo rabat B2BKing
 * "everywhere" liczy sie od ceny REGULARNEJ i ignoruje promocje.
 *
 * Dlaczego to nie jest teoretyczne: _sale_price jest kwota ZAMROZONA i nie idzie
 * za cena regularna. Kazdy import cennika obniza regularna, promocja zostaje —
 * i przy dostatecznie duzej obnizce przekracza prog rabatu B2B.
 *
 * WYLACZNIE ODCZYT. Kod wyjscia 1 przy jakimkolwiek naruszeniu.
 *
 * @package gorvita-child
 */

defined( 'ABSPATH' ) || exit;

/** Rabat B2B w procentach — z reguly, nie zaszyty na sztywno. */
$gorvita_rabat = 18.0;
foreach ( get_posts( array(
	'post_type'   => 'b2bking_rule',
	'post_status' => 'publish',
	'numberposts' => -1,
) ) as $gorvita_regula ) {
	if ( 'discount_percentage' !== get_post_meta( $gorvita_regula->ID, 'b2bking_rule_what', true ) ) {
		continue;
	}
	if ( 'everyone_registered_b2b' !== get_post_meta( $gorvita_regula->ID, 'b2bking_rule_who', true ) ) {
		continue;
	}
	$gorvita_ile = (float) get_post_meta( $gorvita_regula->ID, 'b2bking_rule_howmuch', true );
	if ( $gorvita_ile > 0 ) {
		$gorvita_rabat = $gorvita_ile;
	}
}
$gorvita_mnoznik = ( 100.0 - $gorvita_rabat ) / 100.0;

global $wpdb;
$gorvita_promocje = $wpdb->get_col(
	"SELECT post_id FROM {$wpdb->postmeta}
	  WHERE meta_key = '_sale_price' AND meta_value <> ''
	  ORDER BY post_id"
);

printf( "STRAZNIK PROMOCJI — rabat B2B %.2f%% (mnoznik %.4f)\n", $gorvita_rabat, $gorvita_mnoznik );
printf( "produktow z aktywna _sale_price: %d\n\n", count( $gorvita_promocje ) );

printf( "%-8s %-34s %10s %10s %10s %10s  %s\n",
	'ID', 'produkt', 'regular', 'sale', 'prog B2B', 'zapas', 'wynik' );

$gorvita_naruszen = 0;
foreach ( $gorvita_promocje as $gorvita_pid ) {
	$gorvita_produkt = wc_get_product( $gorvita_pid );
	if ( ! $gorvita_produkt ) {
		continue;
	}
	// Surowe postmeta — B2BKing filtruje gettery ceny na priorytecie 9999.
	$gorvita_reg  = (float) get_post_meta( $gorvita_pid, '_regular_price', true );
	$gorvita_sale = (float) get_post_meta( $gorvita_pid, '_sale_price', true );
	if ( $gorvita_reg <= 0 ) {
		continue;
	}

	$gorvita_prog  = round( $gorvita_reg * $gorvita_mnoznik, 2 );
	$gorvita_zapas = round( $gorvita_sale - $gorvita_prog, 2 );
	$gorvita_ok    = ( $gorvita_sale > $gorvita_prog );
	if ( ! $gorvita_ok ) {
		$gorvita_naruszen++;
	}

	printf( "%-8s %-34s %10.2f %10.2f %10.2f %+10.2f  %s\n",
		$gorvita_pid,
		mb_substr( $gorvita_produkt->get_name(), 0, 34 ),
		$gorvita_reg, $gorvita_sale, $gorvita_prog, $gorvita_zapas,
		$gorvita_ok ? 'OK' : 'NARUSZENIE — B2B placi wiecej niz gosc'
	);
}

printf( "\nnaruszen: %d\n", $gorvita_naruszen );
if ( $gorvita_naruszen > 0 ) {
	WP_CLI::error( sprintf(
		'%d promocji schodzi do lub ponizej ceny B2B — kontrahent zaplaci wiecej niz gosc, a faktura pokaze mu rabat, ktorego nie ma.',
		$gorvita_naruszen
	) );
}
WP_CLI::success( 'wszystkie promocje pozostaja powyzej ceny B2B' );
