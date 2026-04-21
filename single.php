<?php
/**
 * The template for displaying single posts
 *
 * @package akademiata theme
 */

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php if (have_posts()) : while (have_posts()) : the_post();

        global $post;

        // Ensure we have a valid post object
        if (empty($post) || !is_a($post, 'WP_Post')) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part('template-parts/content', 'none');
            get_footer();
            exit;
        }

        // Detect if post is in 'aktualnosci' category (with WPML support)
        $show_aktualnosci_template = false;

        if (function_exists('wpml_get_default_language')) {
            // Get 'aktualnosci' term in any language
            $raw_term = get_term_by('slug', 'aktualnosci', 'category');

            if ($raw_term) {
                // Get ID of this term in default language
                $default_term_id = apply_filters('wpml_object_id', $raw_term->term_id, 'category', true, wpml_get_default_language());

                // Get translated ID in current language
                $translated_term_id = apply_filters('wpml_object_id', $default_term_id, 'category', true);

                if (!empty($translated_term_id) && has_category($translated_term_id, $post)) {
                    $show_aktualnosci_template = true;
                }
            }
        } else {
            if (has_category('aktualnosci', $post)) {
                $show_aktualnosci_template = true;
            }
        }

        // Load appropriate single template
        if ($show_aktualnosci_template) {
            get_template_part('template-parts/content/content-single-aktualnosci');
        } else {
            get_template_part('template-parts/content/content-single');
        }

        // Load ACF Flexible Content Sections
        $sections = get_field('qorp_post_sections');
        if (!empty($sections) && is_array($sections)) {
            foreach ($sections as $section) {
                if (!empty($section['acf_fc_layout'])) {
                    $template = str_replace('_', '-', $section['acf_fc_layout']);
                    get_template_part('flexible-content/sections/' . esc_attr($template), '', $section);
                }
            }
        }

    endwhile; else :
        get_template_part('template-parts/content', 'none');
    endif;
    ?>
</article>

<?php get_footer(); ?>
