<?php
/**
 * Single szkolenie — bare layout, pixel mockup (no theme header/footer).
 *
 * @package akademiata
 */
get_header('szkolenia');
?>

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<?php get_template_part('template-parts/content/content-single-szkolenia'); ?>
	<?php endwhile; endif; ?>

<?php get_footer('szkolenia'); ?>
