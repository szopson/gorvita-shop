<?php
/**
 * Template Name: Strona B2B
 * Template Post Type: page
 *
 * B2B landing page — medium hero with CTAs + wide Gutenberg content.
 * Assign this template to the "Współpraca B2B" page in WP Admin
 * (or via WP-CLI: wp post meta update 120 _wp_page_template 'templates/page-b2b.php')
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="gorvita-b2b-main" class="gorvita-page gorvita-page--b2b" role="main">
	<?php while ( have_posts() ) : the_post(); ?>

		<?php
		get_template_part( 'template-parts/page-header', null, [
			'size'     => 'md',
			'title'    => 'Współpraca hurtowa z Gorvita',
			'subtitle' => 'Bezpośrednio od producenta. Ceny hurtowe, opiekun handlowy, płatności NET 14/30.',
			'ctas'     => [
				[
					'label'   => 'Zarejestruj firmę',
					'url'     => '/b2b-rejestracja/',
					'variant' => 'primary',
				],
				[
					'label'   => 'Skontaktuj się',
					'url'     => '/kontakt/',
					'variant' => 'outline',
				],
			],
		] );
		?>

		<div class="gorvita-page__content gorvita-page__content--wide">
			<?php the_content(); ?>
		</div>

	<?php endwhile; ?>
</main>
<?php
get_footer();
