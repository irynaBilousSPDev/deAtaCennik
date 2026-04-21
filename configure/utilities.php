<?php

// Utilities functions here

// Add rewrite rule for safe filtering (low priority to avoid WPML conflict)
function add_safe_filter_rewrite_rules()
{
    add_rewrite_rule(
        '^custom-filter/([^/]+)/([^/]+)/?',
        'index.php?taxonomy_filter=$matches[1]&term_filter=$matches[2]',
        'bottom'
    );
}
add_action('init', 'add_safe_filter_rewrite_rules', 20);

// Register custom query vars
function add_filter_query_vars($vars)
{
    $vars[] = 'taxonomy_filter';
    $vars[] = 'term_filter';
    return $vars;
}
add_filter('query_vars', 'add_filter_query_vars');

// Flush rewrite rules on plugin/theme activation
function flush_safe_filter_rewrite_rules()
{
    add_safe_filter_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'flush_safe_filter_rewrite_rules');

// Enqueue AJAX filter scripts
function enqueue_filter_scripts()
{
    wp_enqueue_script(
        'ajax-filter',
        get_template_directory_uri() . '/assets/dist/js/ajaxFilter.js',
        ['jquery'],
        null,
        true
    );

    // Send required data to JS in one object
    wp_localize_script('ajax-filter', 'ajax_filter_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'page_id'  => get_queried_object_id(),
        'lang'     => apply_filters('wpml_current_language', null),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_filter_scripts');


function enqueue_slider_front_scripts() {
    wp_enqueue_script(
        'slider-ajax',
        get_template_directory_uri() . '/assets/dist/js/ajaxSlider.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('slider-ajax', 'ajax_data', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'lang'    => apply_filters('wpml_current_language', null), // ✅ Add WPML language
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_slider_front_scripts');



function render_taxonomy_info($taxonomy_labels) {
    if (empty($taxonomy_labels) || !is_array($taxonomy_labels)) {
        return;
    }

    foreach ($taxonomy_labels as $taxonomy => $label) {
        $terms = get_the_terms(get_the_ID(), $taxonomy);

        if (!empty($terms) && !is_wp_error($terms)) {
            $term_links = array_map(function ($term) use ($taxonomy) {
                if ($taxonomy === 'program') {
                    // Standard taxonomy archive link
                    return sprintf(
                        '<a title="%s" href="%s">%s</a>',
                        esc_attr($term->name),
                        esc_url(get_term_link($term)),
                        esc_html($term->name)
                    );
                } elseif ($taxonomy === 'city') {
                    // Custom city filter link
                    $post_type = get_post_type();
                    $post_type_obj = get_post_type_object($post_type);
                    $base_slug = !empty($post_type_obj->rewrite['slug']) ? $post_type_obj->rewrite['slug'] : $post_type;

                    $base_url = home_url("/$base_slug/");
                    $city_url = add_query_arg('city', $term->slug, $base_url);

                    return sprintf(
                        '<a title="%s" href="%s">%s</a>',
                        esc_attr($term->name),
                        esc_url($city_url),
                        esc_html($term->name)
                    );
                } else {
                    // Just plain text for other taxonomies
                    return esc_html($term->name);
                }
            }, $terms);

            printf(
                '<div class="taxonomy_info">%s:<span class="primary_color">%s</span></div>',
                esc_html($label),
                implode(', ', $term_links)
            );
        }
    }
}


/**
 * Renders structured taxonomy details with custom links for specific taxonomies.
 *
 * @param array  $taxonomy_labels Array of taxonomy slugs as keys and labels as values.
 * @param string $custom_degree_slug Custom slug for the "degree" taxonomy (optional).
 */
function render_taxonomy_details($taxonomy_labels, $custom_degree_slug = 'oferta') {
    if (empty($taxonomy_labels) || !is_array($taxonomy_labels)) {
        return;
    }

    foreach ($taxonomy_labels as $taxonomy => $label) {
        $terms = get_the_terms(get_the_ID(), $taxonomy);

        if (!empty($terms) && !is_wp_error($terms)) {
            $term_links = array_map(function ($term) use ($taxonomy, $custom_degree_slug) {
                $term_slug = $term->slug;

                // Custom link for 'degree' taxonomy
                if ($taxonomy === 'degree' && !empty($custom_degree_slug)) {
                    $term_link = home_url("/{$custom_degree_slug}/{$term_slug}/");
                    return sprintf('<a title="%s" href="%s">%s</a>', esc_attr($custom_degree_slug . ' - ' . $term->name), esc_url($term_link), esc_html($term->name));
                }

                return esc_html($term->name); // Plain text for other taxonomies
            }, $terms);

            // Output the structured HTML
            printf(
                '<div class="taxonomy_info">
                    <div class="row">
                        <div class="col-5 col-md-4 item">%s:</div>
                        <div class="col-7 col-md-8 item">%s</div>
                    </div>
                </div>',
                esc_html($label),
                implode(', ', $term_links)
            );
        }
    }
}

/**
 * Get the YouTube Playlist ID based on taxonomy terms.
 *
 * This function fetches the YouTube playlist ID from an ACF field in
 * the related "YouTube Shorts" CPT that shares the same taxonomy term
 * as the given post. If a YouTube playlist is already set in the ACF
 * field of the current post, it is returned immediately.
 *
 * @param int    $post_id            The ID of the post to check.
 * @param string $category           The taxonomy category to filter by.
 * @param string $youtube_acf_field  The ACF field name storing the YouTube playlist ID.
 * @param string $youtube_playlist   (Optional) Existing YouTube playlist ID to prioritize.
 *
 * @return string The YouTube playlist ID or an empty string if not found.
 */
function get_youtube_playlist_id($post_id, $category, $youtube_acf_field, $youtube_playlist = '') {
    // Get taxonomy terms for the given category
    $terms = get_the_terms($post_id, $category);
    if (!$terms || is_wp_error($terms)) {
        return ''; // Return empty if no terms are found
    }

    // Extract term IDs
    $term_ids = wp_list_pluck($terms, 'term_id');

    // If a YouTube playlist is already set, return it immediately (avoids unnecessary queries)
    if (!empty($youtube_playlist)) {
        return $youtube_playlist;
    }

    // Query related YouTube Shorts CPT
    $args = array(
        'post_type'      => 'youtube_shorts',
        'posts_per_page' => 1, // Fetch only one relevant post
        'fields'         => 'ids', // Retrieve only post IDs (reduces memory usage)
        'tax_query'      => array(
            array(
                'taxonomy' => $category,
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ),
        ),
    );

    $query = new WP_Query($args);

    // If no matching posts are found, return empty
    if (!$query->have_posts()) {
        wp_reset_postdata();
        return '';
    }

    // Get the first post ID from the query results
    $youtube_post_id = $query->posts[0];
    wp_reset_postdata(); // Reset WordPress post data

    // Retrieve the ACF field directly from the post ID
    return get_field($youtube_acf_field, $youtube_post_id) ?: '';
}


function load_more_slides() {
    $post_type = $_POST['post_type'];
    $query = new WP_Query([
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'orderby'        => 'name',
        'order'          => 'ASC',
        'offset'         => 5,
    ]);
    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            get_template_part('template-parts/slider-item');
        endwhile;
    endif;
    wp_die();
}
add_action('wp_ajax_load_more_slides', 'load_more_slides');
add_action('wp_ajax_nopriv_load_more_slides', 'load_more_slides');


///**
// * Updates a recruitment text to display the correct date for the current month.
// *
// * It finds and replaces any "DD month" pattern (e.g., "30 marca")
// * with the last valid day and correct grammatical case of the current month.
// * February is adjusted for leap years.
// *
// * @param string $text Original recruitment message with date
// * @return string Updated message with current month's end date
// */
//function updateRecruitmentText($text)
//{
//    // Mapping of months with their genitive case names and default last day
//    $months = [
//        1 => ['name' => 'stycznia', 'days' => 31],
//        2 => ['name' => 'lutego', 'days' => 28],
//        3 => ['name' => 'marca', 'days' => 31],
//        4 => ['name' => 'kwietnia', 'days' => 30],
//        5 => ['name' => 'maja', 'days' => 31],
//        6 => ['name' => 'czerwca', 'days' => 30],
//        7 => ['name' => 'lipca', 'days' => 31],
//        8 => ['name' => 'sierpnia', 'days' => 31],
//        9 => ['name' => 'września', 'days' => 30],
//        10 => ['name' => 'października', 'days' => 31],
//        11 => ['name' => 'listopada', 'days' => 30],
//        12 => ['name' => 'grudnia', 'days' => 31],
//    ];
//
//    $currentMonth = (int)date('n');
////    $currentMonth = '5';
//    $currentYear = (int)date('Y');
//
//    // Leap year check for February
//    if ($currentMonth === 2 && (($currentYear % 4 === 0 && $currentYear % 100 !== 0) || ($currentYear % 400 === 0))) {
//        $months[2]['days'] = 29;
//    }
//
//    $day = $months[$currentMonth]['days'];
//    $monthName = $months[$currentMonth]['name'];
//
//    // Replace the existing date in text with current month's last day and correct name
//    $updatedText = preg_replace('/\d{1,2} \p{L}+/u', "$day $monthName", $text);
//
//    return $updatedText;
//}

/**
 * Retrieve full_time and part_time ACF price data for a post
 * by matching its 'program' and 'degree' taxonomies with a 'price' post.
 * Ensures that at least one actual price exists before returning.
 *
 * @param int $post_id The ID of the post to match.
 * @return array|null Returns an associative array with 'full_time' and 'part_time' arrays, or null if no match or prices found.
 */
function get_first_price_row_for_post($post_id) {
    $taxonomies = ['program', 'degree', 'city'];
    $current_slugs = [];

    // Collect term slugs for the current post by taxonomy
    foreach ($taxonomies as $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $current_slugs[$taxonomy][] = $term->slug;
            }
        }
    }

    if (empty($current_slugs)) return null;

    // Build a tax_query for WP_Query
    $tax_query = [];
    foreach ($taxonomies as $taxonomy) {
        if (!empty($current_slugs[$taxonomy])) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $current_slugs[$taxonomy],
            ];
        }
    }

    // Query all 'price' posts that match all taxonomies
    $query = new WP_Query([
        'post_type'      => 'price',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            'relation' => 'AND',
            ...$tax_query,
        ],
    ]);

    if (!$query->have_posts()) return null;

    $matched_post_id = null;

    foreach ($query->posts as $price_post) {
        $match = true;

        foreach ($taxonomies as $taxonomy) {
            $price_terms = get_the_terms($price_post->ID, $taxonomy);
            $price_slugs = !empty($price_terms) && !is_wp_error($price_terms)
                ? wp_list_pluck($price_terms, 'slug')
                : [];

            sort($price_slugs);
            $post_slugs = $current_slugs[$taxonomy] ?? [];
            sort($post_slugs);

            if ($price_slugs !== $post_slugs) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $matched_post_id = $price_post->ID;
            break;
        }
    }

    if (!$matched_post_id) return null;

    $full_time = get_field('full_time', $matched_post_id);
    $part_time = get_field('part_time', $matched_post_id);

    // Check if there is at least one price in either full_time or part_time
    $has_price = function($data) {
        $price_keys = ['col_12_rat', 'col_semester', 'col_year'];
        foreach ($data as $year_data) {
            foreach ($price_keys as $key) {
                if (!empty($year_data[$key]['normal_price'])) {
                    return true;
                }
            }
        }
        return false;
    };

    $full_time = is_array($full_time) ? $full_time : [];
    $part_time = is_array($part_time) ? $part_time : [];

    if (!$has_price($full_time) && !$has_price($part_time)) {
        return null; // No price data found
    }

    return [
        'full_time'  => $full_time,
        'part_time'  => $part_time,
    ];
}



