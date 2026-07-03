<?php
/**
 * SEO round 2 fixes (audit 94/100 → target 97+). Idempotent, slug-based —
 * safe to run on staging and production.
 *
 * 1. ALT: site-wide decorative icon (block 424 + Home) gets descriptive ALT
 *    (attr JSON + rendered HTML), shop-hero attachment gets ALT meta.
 * 2. /zamowienie/ → rank_math_robots noindex (302s out of sitemap).
 * 3. Pagination meta descriptions: %page% in category template + shop page.
 * 4. Twin products (CBD oils, Erotic, Rabka Spa) → unique meta descriptions.
 * 5. Over-long titles (>65 chars) shortened; too-short/duplicate titles fixed.
 * 6. Multiple H1: /dostawa/ loses in-content H1; landing pages disable the
 *    Blocksy page-title so their designed H1 is the only one.
 *
 * Usage: wp eval-file /var/scripts/seo-round2-fixes.php
 */

$icon_alt = 'Ikona — tradycja marki Gorvita';

/* -- 1a. Greenshift icon blocks: set ALT in block JSON + rendered HTML -- */
foreach ( get_posts( [
	'post_type'      => [ 'ct_content_block', 'page' ],
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	's'              => 'icon-marka-tradycja',
] ) as $p ) {
	$c = $p->post_content;
	$c = preg_replace(
		'/("mediaurl":"[^"]*icon-marka-tradycja\.svg","mediaid":\d+,"alt":)""/',
		'$1"' . $icon_alt . '"',
		$c
	);
	$c = str_replace(
		'icon-marka-tradycja.svg" data-src="" alt=""',
		'icon-marka-tradycja.svg" data-src="" alt="' . $icon_alt . '"',
		$c
	);
	if ( $c !== $p->post_content ) {
		wp_update_post( [ 'ID' => $p->ID, 'post_content' => $c ] );
		WP_CLI::log( "icon ALT set in post {$p->ID} ({$p->post_title})" );
	} else {
		WP_CLI::log( "post {$p->ID}: icon already has ALT / no match" );
	}
}

/* -- 1b. Attachment ALT meta: icon + shop hero (lookup by filename) -- */
$att_alts = [
	'icon-marka-tradycja.svg' => $icon_alt,
	'gorvita_hero.webp'       => 'Sklep Gorvita — naturalne suplementy i kosmetyki ziołowe',
];
foreach ( $att_alts as $file => $alt ) {
	$ids = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 5,
		'fields'         => 'ids',
		'meta_query'     => [ [ 'key' => '_wp_attached_file', 'value' => $file, 'compare' => 'LIKE' ] ],
	] );
	foreach ( $ids as $id ) {
		if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			WP_CLI::log( "att {$id} ({$file}): ALT set" );
		}
	}
}

/* -- 2. /zamowienie/ noindex (checkout 302s when the cart is empty) -- */
$zam = get_page_by_path( 'zamowienie' );
if ( $zam ) {
	update_post_meta( $zam->ID, 'rank_math_robots', [ 'noindex' ] );
	WP_CLI::log( "zamowienie ({$zam->ID}): noindex" );
}

/* -- 3. Pagination meta descriptions: add %page% -- */
$t = get_option( 'rank-math-options-titles', [] );
if ( isset( $t['tax_product_cat_description'] ) && false === strpos( $t['tax_product_cat_description'], '%page%' ) ) {
	$t['tax_product_cat_description'] .= ' %page%';
	update_option( 'rank-math-options-titles', $t );
	WP_CLI::log( 'tax_product_cat_description: %page% appended' );
}
$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
$sd      = get_post_meta( $shop_id, 'rank_math_description', true );
if ( $sd && false === strpos( $sd, '%page%' ) ) {
	update_post_meta( $shop_id, 'rank_math_description', $sd . ' %page%' );
	WP_CLI::log( "shop page ({$shop_id}) description: %page% appended" );
}
// Terms with a custom description meta bypass the template — append there too.
foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] ) as $ct ) {
	$td = get_term_meta( $ct->term_id, 'rank_math_description', true );
	if ( $td && false === strpos( $td, '%page%' ) ) {
		update_term_meta( $ct->term_id, 'rank_math_description', $td . ' %page%' );
		WP_CLI::log( "term {$ct->slug} description: %page% appended" );
	}
}

/* -- 4. Twin products: unique meta descriptions -- */
$twin_desc = [
	'olej-z-konopii-cbd-510ml'  => 'Olej konopny CBD 5% w buteleczce 10 ml — łagodne stężenie na start. Ekstrakt z certyfikowanych konopi siewnych, produkcja GMP w Polsce.',
	'olej-z-konopii-cbd-520ml'  => 'Olej konopny CBD 5% w ekonomicznej buteleczce 20 ml — łagodne stężenie do codziennego stosowania. Certyfikowane konopie siewne, polska produkcja.',
	'olej-z-konopii-cbd-1010ml' => 'Olej konopny CBD 10% w buteleczce 10 ml — wyższe stężenie dla znających działanie kannabidiolu. Certyfikowane uprawy, produkcja GMP.',
	'olej-z-konopii-cbd-1020ml' => 'Olej konopny CBD 10% w ekonomicznej buteleczce 20 ml — mocniejsze wsparcie przy regularnym stosowaniu. Polska produkcja z Gorców.',
	'erotic-dla-mezczyzn-afrodyzjak-20-kapsulek' => 'Erotic dla mężczyzn — ziołowy afrodyzjak w kapsułkach z damianą i żeń-szeniem. Naturalne wsparcie witalności i energii. 20 kapsułek.',
	'erotic-dla-kobiet-afrodyzjak-20-kapsulek'   => 'Erotic dla kobiet — ziołowy afrodyzjak w kapsułkach z damianą. Naturalne wsparcie libido i witalności kobiet. 20 kapsułek.',
	'rabka-spa-minerale-spray-200-ml' => 'RABKA SPA MINERALE w sprayu 200 ml — lecznicza woda mineralna z Rabki z lawendą, do szybkiej i wygodnej aplikacji na ciało.',
	'rabka-spa-minerale-zel-200-ml'   => 'RABKA SPA MINERALE żel 200 ml — kojący żel z leczniczą wodą mineralną z Rabki i lawendą, do masażu i pielęgnacji ciała.',
];
foreach ( $twin_desc as $slug => $desc ) {
	$p = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $p ) {
		update_post_meta( $p->ID, 'rank_math_description', $desc );
		WP_CLI::log( "desc: {$slug}" );
	} else {
		WP_CLI::warning( "desc: {$slug} NOT FOUND" );
	}
}

/* -- 5. Titles: shorten >65 chars, fix short/duplicate ones -- */
$titles = [
	'page:leksykon-skladnikow' => 'Leksykon składników — zioła, witaminy i produkty | Gorvita',
	'page:cbd'                 => 'CBD i konopie — oleje 5% i 10%, maści, kapsułki | Gorvita',
	'page:dostawa'             => 'Dostawa i płatność — koszty, terminy, przewoźnicy | Gorvita',
	'page:platnosc'            => 'Płatność — dostępne metody płatności w sklepie | Gorvita',
	'product:aloevera-zel-150-ml'                    => 'AloeVera Żel 150ml — żel z aloesu i wody z Rabki | Gorvita',
	'product:silicum-h-krzem-biotyna-30-tabletek'    => 'Silicum H — krzem i biotyna na włosy i skórę | Gorvita',
	'product:witamina-c-500mg-vegan'                 => 'Witamina C 500mg VEGAN — naturalna z aceroli | Gorvita',
	'product:colafit-slim-z-chitosanem-60-kapsulek'  => 'Colafit SLIM z chitosanem — kolagen na linię | Gorvita',
];
foreach ( $titles as $key => $title ) {
	list( $type, $slug ) = explode( ':', $key, 2 );
	$p = get_page_by_path( $slug, OBJECT, $type );
	if ( $p ) {
		update_post_meta( $p->ID, 'rank_math_title', $title );
		WP_CLI::log( "title: {$slug} (" . mb_strlen( $title ) . ' zn.)' );
	} else {
		WP_CLI::warning( "title: {$slug} NOT FOUND" );
	}
}
$term = get_term_by( 'slug', 'skora-cialo', 'product_cat' );
if ( $term ) {
	update_term_meta( $term->term_id, 'rank_math_title', 'Kosmetyki ziołowe Gorvita — skóra i ciało %page%' );
	WP_CLI::log( 'term skora-cialo: title shortened' );
}

/* -- 6a. /dostawa/: drop the in-content H1 (Blocksy page-title is the H1) -- */
$d = get_page_by_path( 'dostawa' );
if ( $d ) {
	$c = preg_replace(
		'/<!-- wp:heading( \{"level":1\})? -->\s*<h1[^>]*>Dostawa i płatność<\/h1>\s*<!-- \/wp:heading -->\s*/',
		'',
		$d->post_content
	);
	if ( $c !== $d->post_content ) {
		wp_update_post( [ 'ID' => $d->ID, 'post_content' => $c ] );
		WP_CLI::log( "dostawa ({$d->ID}): in-content H1 removed" );
	} else {
		WP_CLI::log( 'dostawa: H1 pattern not found (already fixed?)' );
	}
}

/* -- 6b. Landing pages with own designed H1: hide the Blocksy page-title -- */
foreach ( [ 'b2b', 'promocje', 'nowosci', 'newsletter-potwierdzony' ] as $slug ) {
	$p = get_page_by_path( $slug );
	if ( $p ) {
		update_post_meta( $p->ID, 'blocksy_post_meta_options', [ 'has_hero_section' => 'disabled' ] );
		WP_CLI::log( "{$slug} ({$p->ID}): Blocksy page-title disabled" );
	}
}

WP_CLI::success( 'SEO round 2 fixes applied.' );
