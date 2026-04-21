<?php
/**
 * Template for displaying Custom Categories (taxonomy: program)
 */

get_header(); ?>

<div id="site-content" class="py-1" style="background-color: #F5F5F5;">
    <div class="container">
        <header class="taxonomy-header">
            <h1 class="taxonomy-title my-5">
                <?php _e('Kierunek studiów', 'akademiata'); ?> - <?php single_term_title(); ?>
            </h1>
            <div class="taxonomy-description">
                <?php echo term_description(); ?>
            </div>
        </header>

        <?php
        $term = get_queried_object();
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        // Query for Bachelor
        $bachelor_query = new WP_Query(array(
            'post_type' => 'bachelor',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ),
            ),
        ));

        // Query for Master
        $master_query = new WP_Query(array(
            'post_type' => 'master',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ),
            ),
        ));
        ?>

        <?php if ($bachelor_query->have_posts()) : ?>
            <h2 class="mt-5"><?php _e('Studia I stopnia', 'akademiata'); ?></h2>
            <div class="taxonomy-posts">
                <div class="row">
                    <?php while ($bachelor_query->have_posts()) : $bachelor_query->the_post(); ?>
                        <?php get_template_part('./partials/card_post'); ?>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($master_query->have_posts()) : ?>
            <h2 class="mt-5"><?php _e('Studia II stopnia', 'akademiata'); ?></h2>
            <div class="taxonomy-posts">
                <div class="row">
                    <?php while ($master_query->have_posts()) : $master_query->the_post(); ?>
                        <?php get_template_part('./partials/card_post'); ?>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$bachelor_query->have_posts() && !$master_query->have_posts()) : ?>
            <p><?php esc_html_e('No programs found for this category.', 'akademiata'); ?></p>
        <?php endif; ?>

        <?php
        wp_reset_postdata();
        ?>
    </div>
</div>

<?php get_footer(); ?>
