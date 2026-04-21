<?php
/**
 * Template for displaying single course
 *
 * @package akademiata
 */
get_header();
?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('single-courses'); ?> style="overflow: hidden;">

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

            <?php get_template_part('template-parts/content/content-single-courses'); ?>

        <?php endwhile; endif; ?>
    </article>

<?php get_footer(); ?>