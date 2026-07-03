<?php
/**
 * Fill missing ALT text on product images (featured + gallery).
 *
 * Featured image ALT = product title; gallery image ALT = "{title} – zdjęcie N".
 * Skips attachments that already have a non-empty ALT, so the script is
 * idempotent and safe to re-run (staging and production).
 *
 * Usage:
 *   wp eval-file /var/scripts/seo-fill-product-image-alts.php dry-run   # preview only
 *   wp eval-file /var/scripts/seo-fill-product-image-alts.php          # apply
 */

$dry_run = in_array( 'dry-run', $args ?? array(), true );

$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$updated = 0;
$skipped = 0;

$maybe_set_alt = function ( $attachment_id, $alt ) use ( $dry_run, &$updated, &$skipped ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return;
	}
	$current = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	if ( '' !== $current ) {
		$skipped++;
		return;
	}
	if ( ! $dry_run ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}
	$updated++;
	WP_CLI::log( ( $dry_run ? '[dry-run] ' : '' ) . "att {$attachment_id}: \"{$alt}\"" );
};

foreach ( $product_ids as $product_id ) {
	$title = get_the_title( $product_id );
	if ( '' === $title ) {
		continue;
	}

	$maybe_set_alt( get_post_thumbnail_id( $product_id ), $title );

	$gallery = get_post_meta( $product_id, '_product_image_gallery', true );
	if ( $gallery ) {
		$n = 2; // featured image is picture no. 1
		foreach ( array_filter( array_map( 'trim', explode( ',', $gallery ) ) ) as $gallery_id ) {
			$maybe_set_alt( $gallery_id, "{$title} – zdjęcie {$n}" );
			$n++;
		}
	}
}

WP_CLI::success(
	sprintf(
		'%s: %d ALT set, %d already had ALT (products scanned: %d)',
		$dry_run ? 'DRY-RUN' : 'DONE',
		$updated,
		$skipped,
		count( $product_ids )
	)
);
