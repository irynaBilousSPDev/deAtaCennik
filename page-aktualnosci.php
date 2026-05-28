<?php
/**
 * Template Name: Aktualnosci Archive (Static Page)
 */

get_header();

// Current page number for custom query on a static Page
$paged = max(1, (int) get_query_var('paged'));

// Read custom search param (avoid using "s" to prevent is_search hijack)
$q_raw = isset($_GET['q']) ? wp_unslash($_GET['q']) : '';
$q     = trim($q_raw); // keep original chars; we'll normalize for SQL only


function ata_normalize_query($str) {
    // Replace smart quotes with straight quotes
    $replacements = [
        '„' => '"', '”' => '"',
        '«' => '"', '»' => '"',
        '‚' => "'", '’' => "'",
        '‘' => "'", '“' => '"',
        '–' => '-', '—' => '-',
        "\xC2\xA0" => ' ', // non-breaking space
    ];
    $str = strtr($str, $replacements);
    // Collapse whitespace
    $str = preg_replace('/\s+/u', ' ', $str);
    return trim($str);
}

$normalized_q = ata_normalize_query($q);

// Get translated category "aktualnosci" (WPML-safe)
$category_slug = 'aktualnosci';
$translated_term_id = 0;

$term = get_term_by('slug', $category_slug, 'category');
if ($term && !is_wp_error($term)) {
    $translated_term_id = (int) $term->term_id;
    if (function_exists('icl_object_id')) {
        $lang = apply_filters('wpml_current_language', null);
        $translated_term_id = (int) apply_filters('wpml_object_id', $translated_term_id, 'category', false, $lang);
    }
}

// Save archive page permalink BEFORE the loop so it won't be affected by the custom query's global $post
$archive_url = get_permalink(get_queried_object_id());

$city_slug         = akademiata_get_current_news_city_slug_from_request();
$active_city_term  = $city_slug ? akademiata_get_news_city_term_by_slug($city_slug) : null;
if ($city_slug && !$active_city_term) {
    $city_slug = '';
}

$archive_date      = akademiata_get_news_archive_date_from_request();
$filter_year       = $archive_date['year'];
$filter_month      = $archive_date['month'];
$archive_filter_args = akademiata_get_news_archive_active_filter_args();

// Build base query (category restricted)
$args = [
    'post_type'      => 'post',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'cat'            => $translated_term_id,
    'lang'           => apply_filters('wpml_current_language', null),
];

$city_tax_query = $city_slug ? akademiata_build_news_city_tax_query($city_slug) : null;
if ($city_tax_query) {
    $args['tax_query'] = $city_tax_query;
}

akademiata_apply_news_archive_date_query($args, $filter_year, $filter_month);

// Apply custom search via filters to support quoted phrases and robust term logic
$search_filter = null;
$order_filter  = null;

if ($normalized_q !== '') {
    global $wpdb;

    // detect exact-phrase: wrapped in quotes
    $is_phrase = false;
    if (strlen($normalized_q) >= 2) {
        $first = $normalized_q[0];
        $last  = $normalized_q[strlen($normalized_q) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $is_phrase = true;
        }
    }

    // Clean content inside quotes for phrase; or split into terms
    if ($is_phrase) {
        $phrase = trim($normalized_q, "\"'");
        $phrase = trim($phrase);
        $like   = '%' . $wpdb->esc_like($phrase) . '%';

        // Inject WHERE for exact phrase in title OR content
        $search_filter = function ($search, $wp_query) use ($wpdb, $like) {
            $conditions = [];
            $conditions[] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", $like);
            $conditions[] = $wpdb->prepare("{$wpdb->posts}.post_content LIKE %s", $like);
            $custom = ' AND (' . implode(' OR ', $conditions) . ') ';
            return $custom;
        };

        // Prioritize title matches
        $order_filter = function ($orderby, $wp_query) use ($wpdb, $like) {
            $case = $wpdb->prepare(
                "CASE
                    WHEN {$wpdb->posts}.post_title LIKE %s THEN 0
                    ELSE 1
                 END",
                $like
            );
            return "$case, {$wpdb->posts}.post_date DESC";
        };
    } else {
        // Tokenize by spaces, require all terms (AND), search title OR content
        $terms = preg_split('/\s+/u', $normalized_q, -1, PREG_SPLIT_NO_EMPTY);
        $likes = array_map(function ($t) use ($wpdb) {
            return '%' . $wpdb->esc_like($t) . '%';
        }, $terms);

        $search_filter = function ($search, $wp_query) use ($wpdb, $likes) {
            $and_groups = [];
            foreach ($likes as $like) {
                $and_groups[] = $wpdb->prepare("({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", $like, $like);
            }
            if ($and_groups) {
                return ' AND ' . implode(' AND ', $and_groups) . ' ';
            }
            return $search;
        };

        // Gentle title prioritization for multi-term
        $order_filter = function ($orderby, $wp_query) use ($wpdb, $likes) {
            $parts = [];
            foreach ($likes as $like) {
                $parts[] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", $like);
            }
            $case = 'CASE WHEN (' . implode(' OR ', $parts) . ') THEN 0 ELSE 1 END';
            return "$case, {$wpdb->posts}.post_date DESC";
        };
    }

    add_filter('posts_search', $search_filter, 20, 2);
    add_filter('posts_orderby', $order_filter, 20, 2);
}

// Run the query
$query = new WP_Query($args);

// Remove filters so they don't leak globally
if ($search_filter) {
    remove_filter('posts_search', $search_filter, 20);
}
if ($order_filter) {
    remove_filter('posts_orderby', $order_filter, 20);
}
?>

<div class="news_archive category_<?php echo esc_attr($category_slug); ?>">
    <div class="container">
        <?php the_breadcrumb(); ?>
        <h1 class="news-archive-title">
            <span class="news-archive-title__text"><?php the_title(); ?></span>
            <span class="news-archive-title__accent" aria-hidden="true"></span>
        </h1>

        <?php
        get_template_part(
            'partials/aktualnosci',
            'archive-panel',
            array(
                'archive_url'  => $archive_url,
                'city_slug'    => $city_slug,
                'filter_year'  => $filter_year,
                'filter_month' => $filter_month,
                'search_q'     => $q,
            )
        );
        ?>

        <?php
        get_template_part(
            'partials/aktualnosci',
            'archive-status',
            array(
                'city_slug'        => $city_slug,
                'active_city_term' => $active_city_term,
                'filter_year'      => $filter_year,
                'filter_month'     => $filter_month,
                'search_q'         => $q,
                'found_posts'      => (int) $query->found_posts,
            )
        );
        ?>

        <?php if ($city_slug && !$active_city_term) : ?>
            <p class="search-results-info">
                <?php echo esc_html(akademiata_get_theme_lang_string('news_no_city_found')); ?>
            </p>
        <?php endif; ?>

        <?php if ($query->have_posts()) : ?>
            <div class="posts_wrapper_news">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php get_template_part('partials/card_post_news'); ?>
                <?php endwhile; ?>
            </div>

            <?php

            wp_reset_postdata();

            $pagination_args = array(
                'base'    => trailingslashit($archive_url) . '%_%',
                'format'  => 'page/%#%/',
                'current' => $paged,
                'total'   => max(1, (int) $query->max_num_pages),
            );

            if (!empty($archive_filter_args)) {
                $pagination_args['add_args'] = $archive_filter_args;
            }

            akademiata_render_news_pagination($pagination_args);
            ?>

        <?php else : ?>
            <p>
                <?php
                if ($q !== '') {
                    echo esc_html(akademiata_get_theme_lang_string('news_no_results_search'));
                } elseif ($active_city_term) {
                    echo esc_html(akademiata_get_theme_lang_string('news_no_results_city'));
                } elseif ($filter_year > 0 || $filter_month > 0) {
                    echo esc_html(akademiata_get_theme_lang_string('news_no_results_period'));
                } else {
                    echo esc_html(akademiata_get_theme_lang_string('news_no_results_generic'));
                }
                ?>
            </p>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
