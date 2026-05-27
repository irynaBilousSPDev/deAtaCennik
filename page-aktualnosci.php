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
        <h1 class="mb-5"><?php the_title(); ?></h1>

        <div class="aktualnosci-city-filter">
            <?php
            get_template_part(
                'partials/aktualnosci',
                'header-actions',
                [
                    'current_city_slug' => $city_slug,
                    'see_all_url'       => $archive_url,
                    'show_see_all'      => (bool) $city_slug,
                ]
            );
            ?>
        </div>

        <?php if ($city_slug) : ?>
            <p class="news-city-filter-info mb-4">
                <?php
                $filter_city_label = $active_city_term
                    ? akademiata_get_news_city_display_name($active_city_term)
                    : akademiata_get_post_news_city_label(0);
                printf(
                    esc_html__('Aktualności: %s', 'akademiata'),
                    esc_html($filter_city_label)
                );
                ?>
            </p>
        <?php endif; ?>

        <!-- Search form (uses ?q=... to stay on this Page, avoid is_search) -->
        <form class="news-search mb-5" method="get" action="<?php echo esc_url($archive_url); ?>">
            <label for="news-search-input" class="screen-reader-text">
                <?php echo esc_html(akademiata_get_theme_lang_string('news_search_label')); ?>
            </label>
            <input
                    id="news-search-input"
                    type="search"
                    name="q"
                    value="<?php echo esc_attr($q); ?>"
                    placeholder="<?php echo esc_attr(akademiata_get_theme_lang_string('news_search_placeholder')); ?>"
                    aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('news_search_label')); ?>"
            />
            <button type="submit"><?php echo esc_html(akademiata_get_theme_lang_string('news_search_submit')); ?></button>
            <?php if ($city_slug !== '') : ?>
                <input type="hidden" name="miasto" value="<?php echo esc_attr($city_slug); ?>" />
            <?php endif; ?>
            <?php if ($q !== '') : ?>
                <a class="clear-search" href="<?php echo esc_url(akademiata_get_aktualnosci_page_url_with_args(['miasto' => $city_slug])); ?>">
                    <?php echo esc_html(akademiata_get_theme_lang_string('news_search_clear')); ?>
                </a>
            <?php endif; ?>
        </form>

        <?php if ($q !== '') : ?>
            <p class="search-results-info">
                <?php printf(esc_html__('Wyniki dla: “%s”', 'akademiata'), esc_html($q)); ?>
            </p>
        <?php endif; ?>

        <?php if ($city_slug && !$active_city_term) : ?>
            <p class="search-results-info">
                <?php esc_html_e('Nie znaleziono wybranego miasta.', 'akademiata'); ?>
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

            // Pagination for a static Page
            $pagination_args = [
                'base'      => trailingslashit($archive_url) . '%_%',
                'format'    => 'page/%#%/',
                'current'   => $paged,
                'total'     => max(1, (int) $query->max_num_pages),
                'type'      => 'array',
                'prev_text' => __('Poprzedni', 'akademiata'),
                'next_text' => __('Następny', 'akademiata'),
            ];

            // Preserve search + city filter across pages
            $pagination_add_args = [];
            if ($q !== '') {
                $pagination_add_args['q'] = $q;
            }
            if ($city_slug !== '') {
                $pagination_add_args['miasto'] = $city_slug;
            }
            if (!empty($pagination_add_args)) {
                $pagination_args['add_args'] = $pagination_add_args;
            }

            $pagination_links = paginate_links($pagination_args);

            if ($pagination_links) : ?>
                <nav class="navigation pagination" aria-label="<?php esc_attr_e('Stronicowanie wpisów', 'akademiata'); ?>">
                    <h2 class="screen-reader-text"><?php _e('Stronicowanie wpisów', 'akademiata'); ?></h2>
                    <div class="nav-links">
                        <?php foreach ($pagination_links as $link) {
                            echo $link;
                        } ?>
                    </div>
                </nav>
            <?php endif; ?>

        <?php else : ?>
            <p>
                <?php
                if ($q !== '') {
                    esc_html_e('Brak wyników spełniających kryteria wyszukiwania.', 'akademiata');
                } elseif ($active_city_term) {
                    esc_html_e('Brak aktualności dla wybranego miasta.', 'akademiata');
                } else {
                    esc_html_e('Nie znaleziono żadnych wyników.', 'akademiata');
                }
                ?>
            </p>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
