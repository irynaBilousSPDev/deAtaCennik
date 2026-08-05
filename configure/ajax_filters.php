<?php

function akademiata_offer_listing_initial_count() {
    return 24;
}

function akademiata_offer_listing_load_more_count() {
    return 18;
}

/**
 * @return string[]
 */
function akademiata_get_offer_listing_taxonomies() {
    return array(
        'degree',
        'program',
        'language',
        'duration',
        'obtained_title',
        'post_tag',
        'mode',
        'department',
        'city',
        'recruitment_date',
    );
}

/**
 * @param string $filter_action filter_bachelor|filter_master|filter_posts
 * @return string|string[]
 */
function akademiata_get_post_types_for_offer_filter_action($filter_action) {
    switch ($filter_action) {
        case 'filter_bachelor':
            return 'bachelor';
        case 'filter_master':
            return 'master';
        case 'filter_posts':
        default:
            return array('bachelor', 'master');
    }
}

/**
 * Parse QUERY_STRING keeping duplicate keys (e.g. program=a&program=b).
 * PHP $_GET keeps only the last value for repeated keys without [].
 *
 * @param string[] $allowed_keys Taxonomy / filter keys to collect.
 * @return array<string, string[]>
 */
function akademiata_parse_query_string_multi(array $allowed_keys) {
    $result       = array();
    $query_string = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';

    if ($query_string === '' || empty($allowed_keys)) {
        return $result;
    }

    $allowed = array_fill_keys($allowed_keys, true);

    foreach (explode('&', $query_string) as $pair) {
        if ($pair === '') {
            continue;
        }

        $parts   = explode('=', $pair, 2);
        $raw_key = rawurldecode(str_replace('+', ' ', $parts[0]));
        $key     = preg_replace('/\[\]$/', '', $raw_key);

        if ($key === '' || !isset($allowed[ $key ]) || !isset($parts[1])) {
            continue;
        }

        $value = rawurldecode(str_replace('+', ' ', $parts[1]));
        if ($value === '') {
            continue;
        }

        $result[ $key ][] = $value;
    }

    return $result;
}

/**
 * Selected filter term slugs for one taxonomy from the current request URL.
 *
 * @param string   $taxonomy
 * @param string[] $allowed_keys Optional allow-list (defaults to bachelor/master listing taxonomies).
 * @return string[]
 */
function akademiata_get_selected_filter_terms_from_request($taxonomy, array $allowed_keys = array()) {
    if (empty($allowed_keys)) {
        $allowed_keys = akademiata_get_offer_listing_taxonomies();
    }

    static $cache = array();
    $cache_key    = implode('|', $allowed_keys);

    if (!isset($cache[ $cache_key ])) {
        $cache[ $cache_key ] = akademiata_parse_query_string_multi($allowed_keys);
    }

    return isset($cache[ $cache_key ][ $taxonomy ])
        ? $cache[ $cache_key ][ $taxonomy ]
        : array();
}

/**
 * @param array<string, mixed>|null $raw Optional raw request data.
 * @return array<string, string[]>
 */
function akademiata_parse_offer_filter_form_data($raw = null) {
    if ($raw !== null) {
        $form_data = is_array($raw) ? $raw : array();
    } elseif (!empty($_POST['form_data'])) {
        parse_str(wp_unslash($_POST['form_data']), $form_data);
        $form_data = is_array($form_data) ? $form_data : array();
    } else {
        // Prefer QUERY_STRING so repeated keys survive (unlike $_GET).
        $form_data = akademiata_parse_query_string_multi(akademiata_get_offer_listing_taxonomies());
    }

    $parsed = array();

    foreach (akademiata_get_offer_listing_taxonomies() as $taxonomy) {
        if (empty($form_data[ $taxonomy ])) {
            continue;
        }

        $terms = array_values(
            array_filter(
                array_map('sanitize_title', (array) $form_data[ $taxonomy ])
            )
        );

        if (!empty($terms)) {
            $parsed[ $taxonomy ] = $terms;
        }
    }

    return $parsed;
}

/**
 * @param string               $filter_action filter_bachelor|filter_master|filter_posts
 * @param array<string, mixed> $form_data     Parsed taxonomy filters.
 * @param int                  $offset
 * @param int                  $limit
 * @return array<string, mixed>
 */
function akademiata_get_offer_listing_query_args($filter_action, array $form_data = array(), $offset = 0, $limit = 0) {
    if ($limit <= 0) {
        $limit = akademiata_offer_listing_initial_count();
    }

    $args = array(
        'post_type'      => akademiata_get_post_types_for_offer_filter_action($filter_action),
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'offset'         => max(0, (int) $offset),
        'order'          => 'ASC',
        'orderby'        => 'title',
        'no_found_rows'  => true,
        'lang'           => apply_filters('wpml_current_language', null),
    );

    $tax_query = array('relation' => 'AND');

    foreach (akademiata_get_offer_listing_taxonomies() as $taxonomy) {
        if (empty($form_data[ $taxonomy ])) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $form_data[ $taxonomy ],
            'operator' => 'IN',
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    return $args;
}

/**
 * @param WP_Query $query
 * @return string
 */
function akademiata_render_offer_listing_cards(WP_Query $query) {
    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('./partials/card_post');
        }
    }

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * @param string $filter_action
 */
function akademiata_run_offer_listing_ajax($filter_action) {
    $allowed = array('filter_bachelor', 'filter_master', 'filter_posts');
    if (!in_array($filter_action, $allowed, true)) {
        wp_send_json_error(array('message' => 'Invalid filter action'));
        wp_die();
    }

    $offset    = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
    $limit     = isset($_POST['limit']) ? max(1, (int) $_POST['limit']) : akademiata_offer_listing_initial_count();
    $form_data = akademiata_parse_offer_filter_form_data();

    $query = new WP_Query(
        akademiata_get_offer_listing_query_args($filter_action, $form_data, $offset, $limit)
    );

    $count = (int) $query->post_count;

    wp_send_json_success(
        array(
            'html'        => akademiata_render_offer_listing_cards($query),
            'count'       => $count,
            'has_more'    => $count >= $limit,
            'next_offset' => $offset + $count,
            'offset'      => $offset,
        )
    );

    wp_die();
}

function filter_posts_ajax() {
    akademiata_run_offer_listing_ajax('filter_posts');
}

add_action('wp_ajax_filter_posts', 'filter_posts_ajax');
add_action('wp_ajax_nopriv_filter_posts', 'filter_posts_ajax');

function filter_bachelor_ajax() {
    akademiata_run_offer_listing_ajax('filter_bachelor');
}

add_action('wp_ajax_filter_bachelor', 'filter_bachelor_ajax');
add_action('wp_ajax_nopriv_filter_bachelor', 'filter_bachelor_ajax');

function filter_master_ajax() {
    akademiata_run_offer_listing_ajax('filter_master');
}

add_action('wp_ajax_filter_master', 'filter_master_ajax');
add_action('wp_ajax_nopriv_filter_master', 'filter_master_ajax');

/**
 * PG/MBA archive filter — all matching posts, card_post_pg_mba template.
 *
 * @param string|string[] $post_type postgraduate|mba
 * @param string[]        $taxonomies Taxonomy slugs.
 */
function filter_pg_mba_posts_by_taxonomies($post_type, array $taxonomies) {
    $args = array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'order'          => 'ASC',
        'orderby'        => 'title',
        'no_found_rows'  => true,
        'lang'           => apply_filters('wpml_current_language', null),
    );

    $form_data = array();
    if (!empty($_POST['form_data'])) {
        parse_str(wp_unslash($_POST['form_data']), $form_data);
    } else {
        $form_data = akademiata_parse_query_string_multi($taxonomies);
    }

    $tax_query = array('relation' => 'AND');
    foreach ($taxonomies as $taxonomy) {
        if (empty($form_data[ $taxonomy ])) {
            continue;
        }

        $terms = array_values(
            array_filter(
                array_map('sanitize_title', (array) $form_data[ $taxonomy ])
            )
        );

        if (empty($terms)) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $terms,
            'operator' => 'IN',
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('partials/card_post_pg_mba');
        }
    }

    wp_reset_postdata();

    wp_send_json_success(
        array(
            'html'  => ob_get_clean(),
            'count' => $query->post_count,
        )
    );
}

function filter_postgraduate_ajax() {
    filter_pg_mba_posts_by_taxonomies(
        'postgraduate',
        array_keys(akademiata_get_pg_mba_filter_taxonomies())
    );
}

add_action('wp_ajax_filter_postgraduate', 'filter_postgraduate_ajax');
add_action('wp_ajax_nopriv_filter_postgraduate', 'filter_postgraduate_ajax');

function filter_mba_ajax() {
    filter_pg_mba_posts_by_taxonomies(
        'mba',
        array_keys(akademiata_get_pg_mba_filter_taxonomies())
    );
}

add_action('wp_ajax_filter_mba', 'filter_mba_ajax');
add_action('wp_ajax_nopriv_filter_mba', 'filter_mba_ajax');
