<?php

/**
 * Homepage / ranking offer sliders — all slides SSR, Slick init in ajaxSlider.js.
 */

/**
 * @param string               $post_type CPT slug.
 * @param array<string, mixed> $args      WP_Query overrides.
 * @return array<string, mixed>
 */
function akademiata_get_offer_slider_query_args($post_type, array $args = []) {
    $defaults = [
        'post_type'      => $post_type,
        'posts_per_page' => -1,
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
}

add_action('wp_enqueue_scripts', 'enqueue_slider_front_scripts', 101);
