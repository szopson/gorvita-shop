<?php
/**
 * Generic CMS page template.
 * Used for: O marce, Kontakt, Dostawa, Regulamin, Płatność
 * and any other standard pages without a custom template.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="gorvita-page-main" class="gorvita-page" role="main">
	<?php while ( have_posts() ) : the_post(); ?>

		<?php
		get_template_part( 'template-parts/page-header', null, [
			'size'  => 'sm',
			'title' => get_the_title(),
		] );
		?>

		<div class="gorvita-page__content">
			<?php the_content(); ?>
		</div>

	<?php endwhile; ?>
</main>
<?php
get_footer();
