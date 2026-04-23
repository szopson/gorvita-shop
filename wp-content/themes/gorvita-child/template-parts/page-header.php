<?php
/**
 * Reusable page header component.
 *
 * Args (passed via get_template_part's $args parameter):
 *   size       string  'sm' | 'md' | 'lg'  — controls height/padding
 *   title      string  Page headline (default: get_the_title())
 *   subtitle   string  Optional lead paragraph
 *   ctas       array   Optional buttons: [['label'=>'','url'=>'','variant'=>'primary|outline']]
 *   bg_image   string  Optional full URL for background-image CSS
 *   tag        string  Wrapper element, default 'header'
 */
defined( 'ABSPATH' ) || exit;

$size     = $args['size']     ?? 'sm';
$title    = $args['title']    ?? get_the_title();
$subtitle = $args['subtitle'] ?? '';
$ctas     = $args['ctas']     ?? [];
$bg_image = $args['bg_image'] ?? '';
$tag      = $args['tag']      ?? 'header';

$allowed_sizes = [ 'sm', 'md', 'lg' ];
if ( ! in_array( $size, $allowed_sizes, true ) ) {
	$size = 'sm';
}
$style_attr = $bg_image ? ' style="background-image:url(\'' . esc_url( $bg_image ) . '\')"' : '';
?>
<<?php echo esc_html( $tag ); ?> class="gorvita-page-header gorvita-page-header--<?php echo esc_attr( $size ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="container">
		<?php if ( ! is_front_page() ) : ?>
			<?php
			if ( function_exists( 'woocommerce_breadcrumb' ) ) {
				woocommerce_breadcrumb( [
					'wrap_before' => '<nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Nawigacja okruszkowa', 'gorvita-child' ) . '">',
					'wrap_after'  => '</nav>',
				] );
			}
			?>
		<?php endif; ?>

		<h1 class="gorvita-page-header__title"><?php echo wp_kses_post( $title ); ?></h1>

		<?php if ( $subtitle ) : ?>
			<p class="gorvita-page-header__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( $ctas ) : ?>
			<div class="gorvita-page-header__ctas">
				<?php foreach ( $ctas as $cta ) : ?>
					<a href="<?php echo esc_url( $cta['url'] ); ?>"
					   class="gorvita-btn gorvita-btn--<?php echo esc_attr( $cta['variant'] ?? 'primary' ); ?>">
						<?php echo esc_html( $cta['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</<?php echo esc_html( $tag ); ?>>
