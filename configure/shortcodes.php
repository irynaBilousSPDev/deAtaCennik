<?php
function akademiata_cpt_tag_filter_shortcode($atts)
{
    $atts = shortcode_atts([
        'tag'  => '',
        'city' => '',
    ], $atts, 'cpt_tag_filter');

    $post_types = ['bachelor', 'master'];

    $tag_slugs = is_array($atts['tag'])
        ? array_filter(array_map('sanitize_title', $atts['tag']))
        : array_filter(array_map('sanitize_title', array_map('trim', explode(',', $atts['tag']))));

    $city_slugs = is_array($atts['city'])
        ? array_filter(array_map('sanitize_title', $atts['city']))
        : array_filter(array_map('sanitize_title', array_map('trim', explode(',', $atts['city']))));

    $args = [
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'order'          => 'ASC',
        'orderby'        => 'title',
        'lang'           => apply_filters('wpml_current_language', null),
    ];

    $tax_query = ['relation' => 'AND'];

    if (!empty($tag_slugs)) {
        $tax_query[] = [
            'taxonomy' => 'post_tag',
            'field'    => 'slug',
            'terms'    => $tag_slugs,
            'operator' => 'IN',
        ];
    }

    if (!empty($city_slugs)) {
        $tax_query[] = [
            'taxonomy' => 'city',
            'field'    => 'slug',
            'terms'    => $city_slugs,
            'operator' => 'IN',
        ];
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        echo '<div class="row">';

        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('partials/card_post');
        }

        echo '</div>';
    } else {
        echo '<div id="no-results">' . esc_html__('Nie znaleziono żadnych wyników', 'akademiata') . '</div>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('cpt_tag_filter', 'akademiata_cpt_tag_filter_shortcode');