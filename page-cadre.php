<?php
/**
 * Template Name: Cadre Page
 */
get_header(); ?>


<?php
if (have_rows('page_sections')) :
    while (have_rows('page_sections')) : the_row();

        if (get_row_layout() === 'cadre_section') {
            get_template_part('flexible-content/sections/section', 'cadre');
        }

    endwhile;
endif; ?>


<?php get_footer(); ?>


<?php
