<?php
/**
 * WooCommerce product archive — shop + category pages.
 * Replaces WooCommerce default archive-product.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$term  = is_product_category() ? get_queried_object() : null;
$title = $term
	? $term->name
	: apply_filters( 'woocommerce_product_archive_title', __( 'Produkty', 'gorvita-child' ) );
$desc  = $term ? $term->description : '';
?>
<main id="gorvita-archive-main" class="gorvita-archive" role="main">

	<div class="gorvita-archive__header">
		<div class="container">
			<?php woocommerce_breadcrumb(); ?>
			<h1 class="gorvita-archive__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $desc ) : ?>
				<p class="gorvita-archive__desc"><?php echo wp_kses_post( $desc ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="gorvita-archive__body">
		<div class="container">
			<?php if ( woocommerce_product_loop() ) : ?>

				<div class="gorvita-archive__toolbar">
					<?php woocommerce_result_count(); ?>
					<?php woocommerce_catalog_ordering(); ?>
				</div>

				<?php woocommerce_product_loop_start(); ?>

				<?php while ( have_posts() ) : the_post(); ?>
					<?php wc_get_template_part( 'content', 'product' ); ?>
				<?php endwhile; ?>

				<?php woocommerce_product_loop_end(); ?>

				<?php woocommerce_pagination(); ?>

			<?php else : ?>
				<?php do_action( 'woocommerce_no_products_found' ); ?>
			<?php endif; ?>
		</div>
	</div>

</main>

<?php
get_footer( 'shop' );
