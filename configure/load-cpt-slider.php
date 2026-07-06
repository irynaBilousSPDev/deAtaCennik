<?php

/**
 * Homepage / ranking offer sliders — SSR first batch + AJAX for the rest.
 */

function akademiata_offer_slider_initial_count() {
    return 8;
}

function akademiata_offer_slider_batch_size() {
    return 12;
}

/**
 * @param string               $post_type CPT slug.
 * @param array<string, mixed> $args      WP_Query overrides.
 * @return array<string, mixed>
 */
function akademiata_get_offer_slider_query_args($post_type, array $args = []) {
    $defaults = [
        'post_type'      => $post_type,
        'posts_per_page' => akademiata_offer_slider_initial_count(),
        'orderby'        => 'name',
        'post_status'    => 'publish',
        'order'          => 'ASC',
        'lang'           => apply_filters('wpml_current_language', null),
        'no_found_rows'  => true,
    ];

    return wp_parse_args($args, $defaults);
}

/**
 * @param string $post_type CPT slug.
 */
function akademiata_render_offer_slider_slide($post_type) {
    ?>
    <div>
        <div>
            <div class="product_slide <?php echo esc_attr($post_type); ?>_slide">
                <?php get_template_part('template-parts/product-slide', null, ['post_id' => get_the_ID()]); ?>
            </div>
        </div>
    </div>
    <?php
}

function load_remaining_cpt_posts_ajax() {
    if (!isset($_POST['post_type']) || !isset($_POST['offset'])) {
        wp_send_json_error(['message' => 'Invalid request']);
        wp_die();
    }

    $post_type = sanitize_text_field(wp_unslash($_POST['post_type']));
    $offset    = max(0, (int) $_POST['offset']);
    $batch     = akademiata_offer_slider_batch_size();

    $query = new WP_Query(
        akademiata_get_offer_slider_query_args(
            $post_type,
            [
                'posts_per_page' => $batch,
                'offset'         => $offset,
                'no_found_rows'  => false,
            ]
        )
    );

    if (!$query->have_posts()) {
        wp_send_json_error(['message' => 'No additional posts found']);
        wp_die();
    }

    ob_start();
    while ($query->have_posts()) {
        $query->the_post();
        akademiata_render_offer_slider_slide($post_type);
    }
    wp_reset_postdata();

    $count = (int) $query->post_count;

    wp_send_json_success(
        [
            'html'        => ob_get_clean(),
            'has_more'    => $count >= $batch,
            'next_offset' => $offset + $count,
        ]
    );

    wp_die();
}

add_action('wp_ajax_load_remaining_cpt_posts', 'load_remaining_cpt_posts_ajax');
add_action('wp_ajax_nopriv_load_remaining_cpt_posts', 'load_remaining_cpt_posts_ajax');

function akademiata_should_enqueue_offer_slider_scripts() {
    return is_front_page() || is_page_template('page-ranking-ela.php');
}

function enqueue_slider_front_scripts() {
    if (!akademiata_should_enqueue_offer_slider_scripts()) {
        return;
    }

    $slider_js_path = get_template_directory() . '/assets/dist/js/ajaxSlider.js';
    $slider_js_ver  = file_exists($slider_js_path) ? filemtime($slider_js_path) : null;

    wp_enqueue_script(
        'slider-ajax',
        get_template_directory_uri() . '/assets/dist/js/ajaxSlider.js',
        ['jquery', 'vendors-js'],
        $slider_js_ver,
        true
    );

    wp_localize_script(
        'slider-ajax',
        'ajax_data',
        [
            'ajaxurl'       => admin_url('admin-ajax.php'),
            'lang'          => apply_filters('wpml_current_language', null),
            'initial_count' => akademiata_offer_slider_initial_count(),
        ]
    );
}

add_action('wp_enqueue_scripts', 'enqueue_slider_front_scripts', 101);
