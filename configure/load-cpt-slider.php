<?php
function load_remaining_cpt_posts_ajax() {
    if (!isset($_POST['post_type']) || !isset($_POST['offset'])) {
        wp_send_json_error(['message' => 'Invalid request']);
        wp_die();
    }

    $post_type = sanitize_text_field($_POST['post_type']);
    $offset    = intval($_POST['offset']);

    // Get current WPML language
    $current_lang = apply_filters('wpml_current_language', null);

    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => -1, // Load all remaining
        'orderby'        => 'name',
        'post_status'    => 'publish',
        'order'          => 'ASC',
        'offset'         => $offset,
        'lang'           => $current_lang, // WPML compatibility
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) : $query->the_post(); ?>
            <div>
                <div>
                    <div class="product_slide <?php echo esc_attr($post_type); ?>_slide">
                        <?php get_template_part('template-parts/product-slide', null, ['post_id' => get_the_ID()]); ?>
                    </div>
                </div>
            </div>
        <?php endwhile;
        wp_reset_postdata();

        $response = ob_get_clean();
        wp_send_json_success(['html' => $response]);
    } else {
        wp_send_json_error(['message' => 'No additional posts found']);
    }

    wp_die();
}

add_action('wp_ajax_load_remaining_cpt_posts', 'load_remaining_cpt_posts_ajax');
add_action('wp_ajax_nopriv_load_remaining_cpt_posts', 'load_remaining_cpt_posts_ajax');
