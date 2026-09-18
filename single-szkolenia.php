<?php
/**
 * Single szkolenie — site header/footer + ACF landing.
 *
 * @package akademiata
 */
get_header();
?>

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<?php get_template_part('template-parts/content/content-single-szkolenia'); ?>
	<?php endwhile; endif; ?>

<?php get_footer(); ?>
