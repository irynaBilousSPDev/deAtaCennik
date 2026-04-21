<?php
//page offer
// Generalized filter function
function filter_posts_by_taxonomies($post_type, $taxonomies)
{
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit  = isset($_POST['limit']) ? intval($_POST['limit']) : 9;

    $args = [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'offset'         => $offset,
        'order'          => 'ASC',
        'orderby'        => 'title',
        'no_found_rows'  => true,
        'lang'           => apply_filters('wpml_current_language', null),
    ];

    $form_data = [];
    if (!empty($_POST['form_data'])) {
        parse_str($_POST['form_data'], $form_data);
    } elseif (!empty($_GET)) {
        $form_data = $_GET;
    }

    $tax_query = ['relation' => 'AND'];
    foreach ($taxonomies as $taxonomy) {
        if (!empty($form_data[$taxonomy])) {
            $terms = (array) $form_data[$taxonomy];
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN',
            ];
        }
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);

    ob_start(); //  Start capturing output

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('./partials/card_post');
        }
    }

    wp_reset_postdata();

    $html = ob_get_clean(); // 🧠 Get captured output

    wp_send_json_success([
        'html' => $html,
        'count' => $query->post_count,
        'offset' => $offset,
    ]);
}





// AJAX handler for general offer page
function filter_posts_ajax()
{
    $post_types = ['bachelor', 'master'];
    $taxonomies = ['degree', 'program', 'language', 'duration', 'obtained_title', 'post_tag', 'mode', 'department', 'city','recruitment_date'];
    filter_posts_by_taxonomies($post_types, $taxonomies);
}

add_action('wp_ajax_filter_posts', 'filter_posts_ajax');
add_action('wp_ajax_nopriv_filter_posts', 'filter_posts_ajax');

// AJAX handler for Bachelor degree page
function filter_bachelor_ajax()
{
    $post_type = 'bachelor';
    $taxonomies = ['degree', 'program', 'language', 'duration', 'obtained_title', 'post_tag', 'mode', 'department', 'city', 'recruitment_date'];
    filter_posts_by_taxonomies($post_type, $taxonomies);
}

add_action('wp_ajax_filter_bachelor', 'filter_bachelor_ajax');
add_action('wp_ajax_nopriv_filter_bachelor', 'filter_bachelor_ajax');

// AJAX handler for Master degree page
function filter_master_ajax()
{
    $post_type = 'master';
    $taxonomies = ['degree', 'program', 'language', 'duration', 'obtained_title', 'post_tag', 'mode', 'department', 'city', 'recruitment_date'];
    filter_posts_by_taxonomies($post_type, $taxonomies);
}

add_action('wp_ajax_filter_master', 'filter_master_ajax');
add_action('wp_ajax_nopriv_filter_master', 'filter_master_ajax');

