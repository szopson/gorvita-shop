<?php
/**
 * Single product layout — 2-column: gallery + info panel.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 */
defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'gorvita-product-page', $product ); ?>>
	<div class="container">

		<?php woocommerce_breadcrumb(); ?>

		<div class="gorvita-product__layout">

			<!-- Left column: product gallery -->
			<div class="gorvita-product__gallery-col">
				<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
			</div>

			<!-- Right column: title, price, add-to-cart, meta -->
			<div class="gorvita-product__info-col">
				<?php
				// Category eyebrow link
				$terms = get_the_terms( get_the_ID(), 'product_cat' );
				if ( $terms && ! is_wp_error( $terms ) ) :
					$term      = reset( $terms );
					$term_link = get_term_link( $term );
					if ( ! is_wp_error( $term_link ) ) :
					?>
						<a class="gorvita-product__category" href="<?php echo esc_url( $term_link ); ?>">
							<?php echo esc_html( $term->name ); ?>
						</a>
					<?php
					endif;
				endif;
				?>

				<?php do_action( 'woocommerce_single_product_summary' ); ?>

				<div class="gorvita-product__trust" aria-label="<?php esc_attr_e( 'Zalety produktu', 'gorvita-child' ); ?>">
					<span class="gorvita-product__trust-item">🌿 <?php esc_html_e( 'Naturalny skład', 'gorvita-child' ); ?></span>
					<span class="gorvita-product__trust-item">🚚 <?php esc_html_e( 'Dostawa 1–3 dni', 'gorvita-child' ); ?></span>
					<span class="gorvita-product__trust-item">↩️ <?php esc_html_e( '14 dni na zwrot', 'gorvita-child' ); ?></span>
				</div>
			</div>

		</div><!-- /.gorvita-product__layout -->

		<!-- Below fold: WC tabs, related products, upsells -->
		<div class="gorvita-product__below-fold">
			<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
		</div>

	</div><!-- /.container -->
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
