<?php
/**
 * Template for displaying single postgraduate custom post type
 *
 * @package akademiata
 */

get_header();

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-postgraduate'); ?> style="overflow: hidden;">

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

        <?php get_template_part('template-parts/content/content-single-pg-mba'); ?>

    <?php endwhile; endif; ?>
</article>

<?php get_footer(); ?>
