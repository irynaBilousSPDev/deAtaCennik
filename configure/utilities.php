<?php

// Utilities functions here

function akademiata_is_production() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$host = strtolower( (string) $host );

	$production_hosts = array(
		'akademiata.pl',
		'www.akademiata.pl',
	);

	return in_array( $host, $production_hosts, true );
}

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
    $vars[] = 'offer_theme_pg_mba';
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
    $lang = apply_filters('wpml_current_language', null);

    wp_enqueue_script(
        'ajax-filter',
        get_template_directory_uri() . '/assets/dist/js/ajaxFilter.js',
        array('jquery'),
        null,
        true
    );

    wp_localize_script(
        'ajax-filter',
        'ajax_filter_params',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'page_id'  => get_queried_object_id(),
            'lang'     => $lang,
        )
    );

    if (is_post_type_archive(array('postgraduate', 'mba'))) {
        $post_type = get_query_var('post_type');
        if (is_array($post_type)) {
            $post_type = reset($post_type);
        }

        $filter_js = get_template_directory() . '/assets/dist/js/ajaxFilterPgMba.js';
        $filter_ver = file_exists($filter_js) ? filemtime($filter_js) : null;

        wp_enqueue_script(
            'ajax-filter-pg-mba',
            get_template_directory_uri() . '/assets/dist/js/ajaxFilterPgMba.js',
            array('jquery'),
            $filter_ver,
            true
        );

        wp_localize_script(
            'ajax-filter-pg-mba',
            'ajax_filter_pg_mba_params',
            array(
                'ajax_url'      => admin_url('admin-ajax.php'),
                'filter_action' => $post_type === 'mba' ? 'filter_mba' : 'filter_postgraduate',
                'lang'          => $lang,
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_filter_scripts');

/**
 * PG/MBA archives use the same offer-filter layout as bachelor/master pages.
 */
function akademiata_pg_mba_archive_body_classes($classes) {
    if (is_post_type_archive(array('postgraduate', 'mba'))) {
        $classes[] = 'page_offer';
    }

    return $classes;
}
add_filter('body_class', 'akademiata_pg_mba_archive_body_classes');

/**
 * WPML-aware page ID for bachelor/master offer listing pages.
 *
 * @param string $level bachelor|master
 * @return int
 */
function akademiata_get_offer_listing_page_id_for_level($level) {
    static $cache = array();

    if (!in_array($level, array('bachelor', 'master'), true)) {
        return 0;
    }

    if (isset($cache[ $level ])) {
        return $cache[ $level ];
    }

    $paths = array(
        'bachelor' => 'oferta/studia-1-stopnia',
        'master'   => 'oferta/studia-2-stopnia',
    );

    $page = get_page_by_path($paths[ $level ]);
    if (!$page) {
        $cache[ $level ] = 0;
        return 0;
    }

    $lang = apply_filters('wpml_current_language', 'pl');
    $page_id = (int) apply_filters('wpml_object_id', $page->ID, 'page', true, $lang);
    $cache[ $level ] = $page_id > 0 ? $page_id : (int) $page->ID;

    return $cache[ $level ];
}

/**
 * WPML-aware ID of the main Oferta page.
 *
 * @return int
 */
function akademiata_get_oferta_page_id() {
    static $page_id = null;

    if ($page_id !== null) {
        return $page_id;
    }

    $page = get_page_by_path('oferta');
    if (!$page) {
        $page_id = 0;
        return 0;
    }

    $lang    = apply_filters('wpml_current_language', 'pl');
    $translated_id = (int) apply_filters('wpml_object_id', $page->ID, 'page', true, $lang);
    $page_id = $translated_id > 0 ? $translated_id : (int) $page->ID;

    return $page_id;
}

/**
 * Whether the current page uses the Offer page template.
 *
 * @param int|null $page_id Optional page ID.
 * @return bool
 */
function akademiata_is_offer_template_page($page_id = null) {
    if ($page_id === null) {
        if (!is_page()) {
            return false;
        }
        $page_id = get_queried_object_id();
    }

    return get_page_template_slug((int) $page_id) === 'page-offer.php';
}

/**
 * Whether the Perspektywy ranking badge should show in the offer page header.
 *
 * @param int|null $page_id Optional page ID.
 * @return bool
 */
function akademiata_should_show_ranking_perspektywy_badge($page_id = null) {
    return akademiata_is_offer_template_page($page_id);
}

/**
 * City slug for bachelor/master offers (`warszawa` or `wroclaw`).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function akademiata_get_offer_city_slug($post_id = 0) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return 'warszawa';
    }

    $terms = get_the_terms($post_id, 'city');

    if (!empty($terms) && !is_wp_error($terms) && $terms[0]->slug === 'wroclaw') {
        return 'wroclaw';
    }

    return 'warszawa';
}

/**
 * Badge variant: warszawa, wroclaw (singles) or both (offer listing pages).
 *
 * @param int|null $post_id Optional post ID.
 * @return string
 */
function akademiata_get_ranking_perspektywy_badge_variant($post_id = null) {
    $passed = get_query_var('ranking_badge_variant', '');

    if (in_array($passed, array('warszawa', 'wroclaw', 'both'), true)) {
        return $passed;
    }

    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    if (is_page() && akademiata_should_show_ranking_perspektywy_badge($post_id)) {
        return 'both';
    }

    $post_type = $post_id ? get_post_type($post_id) : get_post_type();

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        return akademiata_get_offer_city_slug($post_id) === 'wroclaw' ? 'wroclaw' : 'warszawa';
    }

    return 'both';
}

/**
 * Localized ranking badge string for the current variant.
 *
 * @param string      $field   alt|tooltip_short|subline
 * @param string|null $variant warszawa|wroclaw|both
 * @return string
 */
function akademiata_get_ranking_perspektywy_lang_string($field, $variant = null) {
    if ($variant === null) {
        $variant = akademiata_get_ranking_perspektywy_badge_variant();
    }

    $suffix = '';

    if ($variant === 'warszawa') {
        $suffix = '_warszawa';
    } elseif ($variant === 'wroclaw') {
        $suffix = '_wroclaw';
    }

    return akademiata_get_theme_lang_string('offer_ranking_perspektywy_' . $field . $suffix);
}

/**
 * Ranking badge image URL when the asset exists in the theme.
 *
 * @param string|null $variant warszawa|wroclaw|both
 * @return string
 */
function akademiata_get_ranking_perspektywy_badge_image_url($variant = null) {
    if ($variant === null) {
        $variant = akademiata_get_ranking_perspektywy_badge_variant();
    }

    $files = array(
        'warszawa' => 'assets/dist/img/ranking-perspektywy-2026-warszawa.png',
        'wroclaw'  => 'assets/dist/img/ranking-perspektywy-2026-wroclaw.png',
        'both'     => 'assets/dist/img/ranking-perspektywy-2026-1-miejsce.png',
    );

    $relative = $files[ $variant ] ?? $files['both'];
    $path     = get_template_directory() . '/' . $relative;

    if (!file_exists($path)) {
        return '';
    }

    return get_template_directory_uri() . '/' . $relative;
}

/**
 * Whether the current page is a bachelor or master offer listing.
 *
 * @param int|null $page_id Optional page ID.
 * @return bool
 */
function akademiata_is_bachelor_master_offer_listing_page($page_id = null) {
    if ($page_id === null) {
        if (!is_page()) {
            return false;
        }
        $page_id = get_queried_object_id();
    }

    $page_id = (int) $page_id;

    return $page_id > 0 && in_array(
        $page_id,
        array(
            akademiata_get_offer_listing_page_id_for_level('bachelor'),
            akademiata_get_offer_listing_page_id_for_level('master'),
        ),
        true
    );
}

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

/**
 * Hero slider slides: show_slide on + desktop image present.
 *
 * @param array|null $main_slider ACF main_slider repeater; uses current post if null.
 * @return array<int, array>
 */
function akademiata_get_hero_slider_slides($main_slider = null) {
    if ($main_slider === null) {
        $acf_fields = get_fields();
        $main_slider = $acf_fields['main_slider'] ?? [];
    }

    if (!is_array($main_slider)) {
        return [];
    }

    $slides = [];

    foreach ($main_slider as $idx => $slide) {
        if (isset($slide['show_slide']) && (int) $slide['show_slide'] !== 1) {
            continue;
        }
        if (empty(akademiata_hero_slide_image_urls($slide)['desktop'])) {
            continue;
        }
        if (is_array($slide)) {
            $slide['_akademiata_sort_index'] = (int) $idx;
        }
        $slides[] = $slide;
    }

    usort(
        $slides,
        static function ($a, $b) {
            $a_order = isset($a['kolejnosc']) ? (int) $a['kolejnosc'] : 0;
            $b_order = isset($b['kolejnosc']) ? (int) $b['kolejnosc'] : 0;

            $a_has = $a_order > 0;
            $b_has = $b_order > 0;

            // Slides with explicit order first; the rest keep ACF row order.
            if ($a_has && $b_has && $a_order !== $b_order) {
                return $a_order <=> $b_order;
            }
            if ($a_has !== $b_has) {
                return $a_has ? -1 : 1;
            }

            $a_idx = isset($a['_akademiata_sort_index']) ? (int) $a['_akademiata_sort_index'] : 0;
            $b_idx = isset($b['_akademiata_sort_index']) ? (int) $b['_akademiata_sort_index'] : 0;
            return $a_idx <=> $b_idx;
        }
    );

    foreach ($slides as &$slide) {
        if (is_array($slide)) {
            unset($slide['_akademiata_sort_index']);
        }
    }
    unset($slide);

    return $slides;
}

/**
 * Preload first hero slides in wp_head (call before get_header()).
 */
function akademiata_preload_main_slider_image(array $slides) {
    if (empty($slides)) {
        return;
    }

    $preload_links = [];

    foreach ($slides as $slide) {
        $urls = akademiata_hero_slide_image_urls($slide);
        $desktop = $urls['desktop'] ?? '';
        $mobile = $urls['mobile'] ?? '';

        if ($desktop) {
            $preload_links[] = [
                'href' => $desktop,
                'media' => '(min-width: 768px)',
            ];
        }

        if ($mobile) {
            $preload_links[] = [
                'href' => $mobile,
                'media' => $mobile !== $desktop ? '(max-width: 767px)' : '',
            ];
        }

        // Preload only the first logical slide to avoid competing downloads on initial paint.
        break;
    }

    if (empty($preload_links)) {
        return;
    }

    add_action('wp_head', static function () use ($preload_links) {
        $seen = [];

        foreach ($preload_links as $link) {
            $key = ($link['media'] ?? '') . '|' . $link['href'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $media = !empty($link['media'])
                ? ' media="' . esc_attr($link['media']) . '"'
                : '';

            echo '<link rel="preload" as="image" href="' . esc_url($link['href']) . '"' . $media . '>' . "\n";
        }
    }, 1);
}

/**
 * Resolve hero slide image URLs for desktop, mobile, and nav thumb.
 *
 * @return array{main: string, desktop: string, mobile: string, thumb: string}
 */
function akademiata_hero_slide_image_urls(array $slide) {
    $image = $slide['image'] ?? [];

    if (empty($image) || !is_array($image)) {
        return ['main' => '', 'desktop' => '', 'mobile' => '', 'thumb' => ''];
    }

    $desktop = !empty($image['sizes']['main_slider_banner'])
        ? esc_url($image['sizes']['main_slider_banner'])
        : esc_url($image['url'] ?? '');

    $mobile = !empty($image['sizes']['mobile_slider_banner'])
        ? esc_url($image['sizes']['mobile_slider_banner'])
        : $desktop;

    $thumb = !empty($image['sizes']['program_banner'])
        ? esc_url($image['sizes']['program_banner'])
        : $desktop;

    $main = wp_is_mobile() ? $mobile : $desktop;

    return [
        'main' => $main,
        'desktop' => $desktop,
        'mobile' => $mobile,
        'thumb' => $thumb,
    ];
}

/**
 * Taxonomies used on single bachelor/master offer pages.
 */
function akademiata_single_offer_taxonomies() {
    return [
        'city',
        'program',
        'degree',
        'obtained_title',
        'duration',
        'language',
        'mode',
        'recruitment_date',
    ];
}

/**
 * Taxonomies registered for a post type (bachelor/master, postgraduate, mba, etc.).
 *
 * @return string[]
 */
function akademiata_get_post_taxonomies($post_id) {
    $post_type = get_post_type((int) $post_id);

    if (!$post_type) {
        return [];
    }

    $taxonomies = get_object_taxonomies($post_type);

    if (in_array($post_type, ['bachelor', 'master'], true)) {
        $taxonomies = array_values(array_unique(array_merge($taxonomies, akademiata_single_offer_taxonomies())));
    }

    return $taxonomies;
}

/**
 * Load and cache taxonomy terms for a post (all post types).
 *
 * @return array<string, WP_Term[]>|WP_Term[]
 */
function akademiata_get_offer_terms($post_id, $taxonomy = null) {
    static $cache = [];

    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return $taxonomy ? [] : [];
    }

    if (!isset($cache[$post_id])) {
        $taxonomies = akademiata_get_post_taxonomies($post_id);
        $grouped = [];

        if (!empty($taxonomies)) {
            $terms = wp_get_object_terms($post_id, $taxonomies, [
                'update_term_meta_cache' => false,
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $grouped[$term->taxonomy][] = $term;
                }
            }
        }

        $cache[$post_id] = $grouped;
    }

    if ($taxonomy === null) {
        return $cache[$post_id];
    }

    if (!empty($cache[$post_id][$taxonomy])) {
        return $cache[$post_id][$taxonomy];
    }

    $terms = get_the_terms($post_id, $taxonomy);

    if (empty($terms) || is_wp_error($terms)) {
        return [];
    }

    $cache[$post_id][$taxonomy] = $terms;

    return $terms;
}

/**
 * Translated category term ID for slug "aktualnosci" (WPML-safe).
 *
 * @return int 0 if not found.
 */
function akademiata_get_aktualnosci_category_term_id() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = 0;
    $term   = get_term_by('slug', 'aktualnosci', 'category');

    if (!$term || is_wp_error($term)) {
        return $cached;
    }

    $term_id = (int) $term->term_id;

    if (function_exists('icl_object_id')) {
        $lang = apply_filters('wpml_current_language', null);
        $translated = (int) apply_filters('wpml_object_id', $term_id, 'category', false, $lang);
        if ($translated > 0) {
            $term_id = $translated;
        }
    }

    $cached = $term_id;

    return $cached;
}

/**
 * Permalink of the Aktualności page (WPML-safe).
 *
 * @return string Empty if page not found.
 */
function akademiata_get_aktualnosci_page_url() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = '';
    $page   = get_page_by_path('aktualnosci');

    if (!$page) {
        return $cached;
    }

    $page_id = (int) $page->ID;

    if (function_exists('icl_object_id')) {
        $lang = apply_filters('wpml_current_language', null);
        $translated_id = (int) apply_filters('wpml_object_id', $page_id, 'page', false, $lang);
        if ($translated_id > 0) {
            $page_id = $translated_id;
        }
    }

    $cached = (string) get_permalink($page_id);

    return $cached;
}

/**
 * Aktualności archive URL with optional query args (e.g. miasto filter).
 *
 * @param array $args Query args; values are sanitized.
 * @return string
 */
function akademiata_get_aktualnosci_page_url_with_args(array $args = array()) {
    $base = akademiata_get_aktualnosci_page_url();
    if ($base === '') {
        return '';
    }

    $clean = array();
    foreach ($args as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $key = sanitize_key($key);
        if ($key === 'miasto') {
            $clean[ $key ] = sanitize_title((string) $value);
        } elseif ($key === 'q') {
            $clean[ $key ] = sanitize_text_field((string) $value);
        } elseif ($key === 'rok') {
            $year = (int) $value;
            if ($year >= 2000 && $year <= (int) gmdate('Y') + 1) {
                $clean[ $key ] = $year;
            }
        } elseif ($key === 'miesiac') {
            $month = (int) $value;
            if ($month >= 1 && $month <= 12) {
                $clean[ $key ] = $month;
            }
        }
    }

    if (empty($clean)) {
        return $base;
    }

    return add_query_arg($clean, $base);
}

/**
 * news_city slug from ?miasto= (front-end filter).
 *
 * @return string Empty when not set.
 */
function akademiata_get_current_news_city_slug_from_request() {
    if (!isset($_GET['miasto'])) {
        return '';
    }

    return sanitize_title(wp_unslash((string) $_GET['miasto']));
}

/**
 * Year / month from ?rok= & ?miesiac= on aktualności archive.
 *
 * @return array{year: int, month: int} 0 = not set.
 */
function akademiata_get_news_archive_date_from_request() {
    $year  = 0;
    $month = 0;

    if (isset($_GET['rok'])) {
        $year = (int) wp_unslash($_GET['rok']);
    }
    if (isset($_GET['miesiac'])) {
        $month = (int) wp_unslash($_GET['miesiac']);
    }

    $max_year = (int) gmdate('Y') + 1;
    if ($year < 2000 || $year > $max_year) {
        $year = 0;
    }
    if ($month < 1 || $month > 12) {
        $month = 0;
    }

    return array(
        'year'  => $year,
        'month' => $month,
    );
}

/**
 * Active archive filters for pagination / forms (q, miasto, rok, miesiac).
 *
 * @return array<string, string|int>
 */
function akademiata_get_news_archive_active_filter_args() {
    $args = array();

    if (isset($_GET['q'])) {
        $q = trim(wp_unslash((string) $_GET['q']));
        if ($q !== '') {
            $args['q'] = $q;
        }
    }

    $city = akademiata_get_current_news_city_slug_from_request();
    if ($city !== '') {
        $args['miasto'] = $city;
    }

    $date = akademiata_get_news_archive_date_from_request();
    if ($date['year'] > 0) {
        $args['rok'] = $date['year'];
    }
    if ($date['month'] > 0) {
        $args['miesiac'] = $date['month'];
    }

    return $args;
}

/**
 * Aktualności archive URL with specific filter keys removed.
 *
 * @param array<int, string> $remove_keys e.g. miasto, rok, miesiac, q.
 * @return string
 */
function akademiata_get_news_archive_url_without_filters(array $remove_keys = array()) {
    $args = akademiata_get_news_archive_active_filter_args();

    foreach ($remove_keys as $key) {
        $key = sanitize_key((string) $key);
        unset($args[ $key ]);
    }

    return akademiata_get_aktualnosci_page_url_with_args($args);
}

/**
 * Render aktualności archive pagination (label + boxed page numbers).
 *
 * @param array $args paginate_links args: current, total; optional base, format, add_args.
 */
function akademiata_render_news_pagination(array $args) {
    $total = isset($args['total']) ? max(1, (int) $args['total']) : 1;

    if ($total <= 1) {
        return;
    }

    $prev_label = akademiata_get_theme_lang_string('pagination_prev');
    $next_label = akademiata_get_theme_lang_string('pagination_next');

    $pagination_args = array(
        'current'   => max(1, (int) ($args['current'] ?? 1)),
        'total'     => $total,
        'type'      => 'array',
        'mid_size'  => 2,
        'end_size'  => 1,
        'prev_text' => '<span class="news-pagination__icon news-pagination__icon--prev" aria-hidden="true"></span>',
        'next_text' => '<span class="news-pagination__text">' . esc_html($next_label) . '</span>'
            . '<span class="news-pagination__icon news-pagination__icon--next" aria-hidden="true"></span>',
    );

    if (!empty($args['base'])) {
        $pagination_args['base'] = $args['base'];
    }

    if (isset($args['format'])) {
        $pagination_args['format'] = $args['format'];
    }

    if (!empty($args['add_args'])) {
        $pagination_args['add_args'] = $args['add_args'];
    }

    $links = paginate_links($pagination_args);

    if (empty($links) || !is_array($links)) {
        return;
    }

    $aria  = akademiata_get_theme_lang_string('news_pagination_aria');
    $title = akademiata_get_theme_lang_string('news_pagination_heading');
    ?>
    <nav class="news-pagination navigation pagination" aria-label="<?php echo esc_attr($aria); ?>">
        <p class="news-pagination__title"><?php echo esc_html($title); ?></p>
        <div class="news-pagination__links nav-links">
            <?php
            foreach ($links as $link) {
                if (strpos($link, 'page-numbers prev') !== false) {
                    $icon = '<span class="news-pagination__icon news-pagination__icon--prev" aria-hidden="true"></span>';
                    $link = preg_replace(
                        '/(<(?:a|span)[^>]*class="[^"]*\bprev\b[^"]*"[^>]*)(>).*?(<\/(?:a|span)>)/',
                        '$1 aria-label="' . esc_attr($prev_label) . '"$2' . $icon . '$3',
                        $link,
                        1
                    );
                }

                echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
        </div>
    </nav>
    <?php
}

/**
 * city_pg_mba terms for archive tabs (current WPML language, unique slug).
 *
 * @return WP_Term[]
 */
function akademiata_get_city_pg_mba_terms() {
    $cities = get_terms(
        array(
            'taxonomy'   => 'city_pg_mba',
            'hide_empty' => false,
        )
    );

    if (empty($cities) || is_wp_error($cities)) {
        return array();
    }

    $current_lang = apply_filters('wpml_current_language', null);
    $by_slug      = array();

    foreach ($cities as $city) {
        if ($current_lang && function_exists('apply_filters')) {
            $lang_term_id = apply_filters('wpml_object_id', (int) $city->term_id, 'city_pg_mba', false, $current_lang);

            if (!$lang_term_id || (int) $lang_term_id !== (int) $city->term_id) {
                continue;
            }
        }

        if (isset($by_slug[ $city->slug ])) {
            continue;
        }

        $by_slug[ $city->slug ] = $city;
    }

    return array_values($by_slug);
}

/**
 * Current postgraduate or MBA archive post type (from query, not global $post).
 *
 * @return string postgraduate|mba
 */
function akademiata_get_pg_mba_archive_post_type() {
    $post_type = get_query_var('post_type');

    if (is_array($post_type)) {
        $post_type = reset($post_type);
    }

    if (!$post_type && is_singular(array('postgraduate', 'mba'))) {
        $post_type = get_post_type();
    }

    if (in_array($post_type, array('postgraduate', 'mba'), true)) {
        return $post_type;
    }

    return 'postgraduate';
}

/**
 * Archive base slug for postgraduate or MBA.
 *
 * @param string $post_type postgraduate|mba
 * @return string
 */
function akademiata_get_pg_mba_archive_base_slug($post_type) {
    return $post_type === 'mba' ? 'studia-mba' : 'studia-podyplomowe';
}

/**
 * Main PG/MBA archive URL with optional filter query args.
 *
 * @param string      $post_type postgraduate|mba
 * @param string|null $city_slug city_pg_mba slug.
 * @param string|null $theme_slug offer_theme_pg_mba slug.
 * @return string
 */
function akademiata_get_pg_mba_archive_filter_url($post_type, $city_slug = null, $theme_slug = null) {
    $base_slug = akademiata_get_pg_mba_archive_base_slug($post_type);
    $url       = home_url('/' . $base_slug . '/');
    $lang      = apply_filters('wpml_current_language', null);

    if ($lang) {
        $url = apply_filters('wpml_permalink', $url, $lang);
    }

    if ($city_slug) {
        $url = add_query_arg('city_pg_mba', sanitize_title($city_slug), $url);
    }

    if ($theme_slug) {
        $url = add_query_arg('offer_theme_pg_mba', sanitize_title($theme_slug), $url);
    }

    return $url;
}

/**
 * City taxonomy archives for PG/MBA → main archive with shareable filter params.
 */
function akademiata_redirect_pg_mba_city_tax_to_archive_filter() {
    if (!is_tax('city_pg_mba')) {
        return;
    }

    $post_type = get_query_var('post_type');
    if (is_array($post_type)) {
        $post_type = reset($post_type);
    }

    if (!in_array($post_type, array('postgraduate', 'mba'), true)) {
        return;
    }

    $term = get_queried_object();
    if (!$term || is_wp_error($term)) {
        return;
    }

    $url = akademiata_get_pg_mba_archive_filter_url($post_type, $term->slug);

    foreach (array_keys(akademiata_get_pg_mba_filter_taxonomies()) as $taxonomy) {
        if ($taxonomy === 'city_pg_mba' || empty($_GET[ $taxonomy ])) {
            continue;
        }

        foreach ((array) $_GET[ $taxonomy ] as $slug) {
            $slug = sanitize_title(wp_unslash($slug));
            if ($slug) {
                $url = add_query_arg($taxonomy, $slug, $url);
            }
        }
    }

    wp_safe_redirect($url, 302);
    exit;
}
add_action('template_redirect', 'akademiata_redirect_pg_mba_city_tax_to_archive_filter', 1);

/**
 * Keep ?city_pg_mba= and ?offer_theme_pg_mba= on PG/MBA archives (no canonical strip).
 *
 * @param string|false $redirect_url Canonical URL.
 * @return string|false
 */
function akademiata_preserve_pg_mba_archive_filter_query($redirect_url) {
    if (!is_post_type_archive(array('postgraduate', 'mba'))) {
        return $redirect_url;
    }

    foreach (array_keys(akademiata_get_pg_mba_filter_taxonomies()) as $taxonomy) {
        if (!empty($_GET[ $taxonomy ])) {
            return false;
        }
    }

    return $redirect_url;
}
add_filter('redirect_canonical', 'akademiata_preserve_pg_mba_archive_filter_query');

/**
 * Sidebar filter groups for PG/MBA archives (order matches offer-style layout).
 *
 * @return array<string, string> taxonomy => label
 */
function akademiata_get_pg_mba_filter_taxonomies() {
    return array(
        'city_pg_mba'        => akademiata_get_theme_lang_string('pg_mba_filter_location'),
        'offer_theme_pg_mba' => akademiata_get_theme_lang_string('pg_mba_filter_interests'),
        'language_pg_mba'    => akademiata_get_theme_lang_string('pg_mba_filter_language'),
        'form_pg_mba'        => akademiata_get_theme_lang_string('pg_mba_filter_form'),
        'duration_pg_mba'    => akademiata_get_theme_lang_string('pg_mba_filter_duration'),
    );
}

/**
 * Terms used in PG/MBA archive sidebar (at least one post of given type).
 *
 * @param string $taxonomy  Taxonomy slug.
 * @param string $post_type postgraduate|mba
 * @return WP_Term[]
 */
function akademiata_get_taxonomy_terms_for_post_type($taxonomy, $post_type) {
    if (!in_array($post_type, array('postgraduate', 'mba'), true)) {
        return array();
    }

    $all_terms = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if (is_wp_error($all_terms) || empty($all_terms)) {
        return array();
    }

    $lang   = apply_filters('wpml_current_language', null);
    $result = array();

    foreach ($all_terms as $term) {
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => (int) $term->term_id,
                ),
            ),
        );

        if ($lang) {
            $args['lang'] = $lang;
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $result[] = $term;
        }

        wp_reset_postdata();
    }

    return $result;
}

/**
 * Theme terms that have at least one published post of the given PG/MBA type.
 *
 * @param string $post_type postgraduate|mba
 * @return WP_Term[]
 */
function akademiata_get_offer_theme_pg_mba_terms_for_post_type($post_type) {
    return akademiata_get_taxonomy_terms_for_post_type('offer_theme_pg_mba', $post_type);
}

/**
 * WP_Query for PG/MBA/courses archive city tab.
 *
 * @param int         $term_id   city_pg_mba term ID.
 * @param string|null $post_type Post type; defaults to current archive type.
 * @return WP_Query
 */
function akademiata_get_request_offer_theme_pg_mba_slugs() {
    if (empty($_GET['offer_theme_pg_mba'])) {
        return array();
    }

    return array_values(
        array_filter(
            array_map('sanitize_title', (array) $_GET['offer_theme_pg_mba'])
        )
    );
}

/**
 * @param int $city_term_id city_pg_mba term ID.
 * @return array<int, array<string, mixed>>
 */
function akademiata_build_pg_mba_tax_query($city_term_id) {
    return array(
        array(
            'taxonomy' => 'city_pg_mba',
            'field'    => 'term_id',
            'terms'    => (int) $city_term_id,
        ),
    );
}

function akademiata_query_posts_by_city_pg_mba($term_id, $post_type = null) {
    if (!$post_type) {
        $post_type = akademiata_get_pg_mba_archive_post_type();
    }

    $args = array(
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => akademiata_build_pg_mba_tax_query($term_id),
    );

    $lang = apply_filters('wpml_current_language', null);
    if ($lang) {
        $args['lang'] = $lang;
    }

    return new WP_Query($args);
}

/**
 * Apply date_query to aktualności WP_Query args.
 *
 * @param array $args       WP_Query args (by reference).
 * @param int   $year       Four-digit year or 0.
 * @param int   $month      1–12 or 0.
 */
function akademiata_apply_news_archive_date_query(array &$args, $year, $month) {
    $year  = (int) $year;
    $month = (int) $month;

    // Allow filtering by month even when year is not selected.
    if ($year <= 0 && ($month < 1 || $month > 12)) {
        return;
    }

    $date_query = array();

    if ($year > 0) {
        $date_query['year'] = $year;
    }

    if ($month >= 1 && $month <= 12) {
        // WordPress date_query uses "monthnum" (1–12).
        $date_query['monthnum'] = $month;
    }

    $args['date_query'] = array($date_query);
}

/**
 * Years for archive date filter dropdown (only years with published aktualności posts).
 *
 * @return int[]
 */
function akademiata_get_news_archive_year_options() {
    global $wpdb;

    static $cache = array();

    $cat_id = akademiata_get_aktualnosci_category_term_id();
    $lang   = apply_filters('wpml_current_language', 'pl');
    $cache_key = $cat_id . '_' . $lang;

    if (isset($cache[ $cache_key ])) {
        return $cache[ $cache_key ];
    }

    if ($cat_id <= 0) {
        $cache[ $cache_key ] = array();
        return $cache[ $cache_key ];
    }

    $sql = "
        SELECT DISTINCT YEAR(p.post_date) AS year
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            AND tt.taxonomy = 'category'
            AND tt.term_id = %d
        WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
    ";

    $params = array($cat_id);

    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}icl_translations'") === $wpdb->prefix . 'icl_translations') {
        $sql .= "
            AND EXISTS (
                SELECT 1
                FROM {$wpdb->prefix}icl_translations t
                WHERE t.element_id = p.ID
                    AND t.element_type = 'post_post'
                    AND t.language_code = %s
            )
        ";
        $params[] = $lang;
    }

    $sql .= ' ORDER BY year DESC';

    $years = array_map('intval', $wpdb->get_col($wpdb->prepare($sql, $params)));

    $active = akademiata_get_news_archive_date_from_request();
    if ($active['year'] > 0 && !in_array($active['year'], $years, true)) {
        $years[] = $active['year'];
        rsort($years, SORT_NUMERIC);
    }

    $cache[ $cache_key ] = $years;

    return $cache[ $cache_key ];
}

/**
 * Localized month names for archive filter (1–12).
 *
 * @return array<int, string>
 */
function akademiata_get_news_archive_month_options() {
    $lang = apply_filters('wpml_current_language', 'pl');

    $sets = array(
        'pl' => array(1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień', 5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień', 9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień'),
        'en' => array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'),
        'uk' => array(1 => 'Січень', 2 => 'Лютий', 3 => 'Березень', 4 => 'Квітень', 5 => 'Травень', 6 => 'Червень', 7 => 'Липень', 8 => 'Серпень', 9 => 'Вересень', 10 => 'Жовтень', 11 => 'Листопад', 12 => 'Грудень'),
        'ru' => array(1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'),
    );

    return $sets[ $lang ] ?? $sets['pl'];
}

/**
 * news_city terms for current language (WPML-aware via get_terms).
 *
 * @return WP_Term[]
 */
function akademiata_get_news_city_terms() {
    if (!taxonomy_exists('news_city')) {
        return array();
    }

    $terms = get_terms(
        array(
            'taxonomy'   => 'news_city',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    $order = array('warszawa' => 0, 'wroclaw' => 1);
    usort(
        $terms,
        static function ($a, $b) use ($order) {
            $a_order = $order[ $a->slug ] ?? 99;
            $b_order = $order[ $b->slug ] ?? 99;
            if ($a_order === $b_order) {
                return strcasecmp($a->name, $b->name);
            }
            return $a_order <=> $b_order;
        }
    );

    return $terms;
}

/**
 * Resolve news_city term by slug in the current language.
 *
 * @param string $slug Term slug.
 * @return WP_Term|null
 */
function akademiata_get_news_city_term_by_slug($slug) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '') {
        return null;
    }

    $term = get_term_by('slug', $slug, 'news_city');
    if (!$term || is_wp_error($term)) {
        return null;
    }

    if (function_exists('icl_object_id')) {
        $lang = apply_filters('wpml_current_language', null);
        $translated_id = (int) apply_filters('wpml_object_id', (int) $term->term_id, 'news_city', false, $lang);
        if ($translated_id > 0) {
            $translated = get_term($translated_id, 'news_city');
            if ($translated && !is_wp_error($translated)) {
                $term = $translated;
            }
        }
    }

    return $term;
}

/**
 * Localized news_city labels (fallback when WPML term name is missing).
 *
 * @return array<string, array<string, string>>
 */
function akademiata_news_city_label_map() {
    return array(
        'warszawa' => array(
            'pl' => 'Warszawa',
            'en' => 'Warsaw',
            'uk' => 'Варшава',
            'ru' => 'Варшава',
        ),
        'wroclaw'  => array(
            'pl' => 'Wrocław',
            'en' => 'Wrocław',
            'uk' => 'Вроцлав',
            'ru' => 'Вроцлав',
        ),
    );
}

/**
 * Display name for a news_city term in the current language.
 *
 * @param WP_Term $term City term.
 * @return string
 */
function akademiata_get_news_city_display_name($term) {
    if (!$term || is_wp_error($term)) {
        return '';
    }

    $lang  = apply_filters('wpml_current_language', 'pl');
    $map   = akademiata_news_city_label_map();
    $slug  = $term->slug;

    if (isset($map[ $slug ][ $lang ])) {
        return $map[ $slug ][ $lang ];
    }

    return $term->name;
}

/**
 * Section heading on front page / kierunek blocks.
 *
 * @return string
 */
function akademiata_get_section_aktualnosci_title() {
    $titles = array(
        'pl' => 'AKTUALNOŚCI',
        'en' => 'NEWS',
        'uk' => 'НОВИНИ',
        'ru' => 'НОВОСТИ',
    );
    $lang = apply_filters('wpml_current_language', 'pl');

    return $titles[ $lang ] ?? $titles['pl'];
}

/**
 * Theme UI strings by WPML language (when .mo / WPML String Translation is unavailable).
 *
 * @param string $key String identifier.
 * @return string
 */
function akademiata_get_theme_lang_string($key) {
    static $strings = null;

    if ($strings === null) {
        $strings = array(
            'news_search_label' => array(
                'pl' => 'Szukaj w aktualnościach',
                'en' => 'Search news',
                'uk' => 'Шукати в новинах',
                'ru' => 'Искать в новостях',
            ),
            'news_search_placeholder' => array(
                'pl' => 'Wpisz tytuł lub tekst…',
                'en' => 'Enter title or text…',
                'uk' => 'Введіть заголовок або текст…',
                'ru' => 'Введите заголовок или текст…',
            ),
            'news_search_submit' => array(
                'pl' => 'Szukaj',
                'en' => 'Search',
                'uk' => 'Шукати',
                'ru' => 'Искать',
            ),
            'news_search_clear' => array(
                'pl' => 'Wyczyść',
                'en' => 'Clear',
                'uk' => 'Очистити',
                'ru' => 'Очистить',
            ),
            'news_clear_all_filters' => array(
                'pl' => 'Wyczyść wszystkie filtry',
                'en' => 'Clear all filters',
                'uk' => 'Очистити всі фільтри',
                'ru' => 'Очистить все фильтры',
            ),
            'news_more_filters' => array(
                'pl' => 'Więcej filtrów',
                'en' => 'More filters',
                'uk' => 'Більше фільтрів',
                'ru' => 'Больше фильтров',
            ),
            'news_less_filters' => array(
                'pl' => 'Mniej filtrów',
                'en' => 'Less filters',
                'uk' => 'Менше фільтрів',
                'ru' => 'Меньше фильтров',
            ),
            'news_results_count' => array(
                'pl' => 'Liczba wyników: %d',
                'en' => 'Results: %d',
                'uk' => 'Кількість результатів: %d',
                'ru' => 'Количество результатов: %d',
            ),
            'news_remove_filter' => array(
                'pl' => 'Usuń filtr',
                'en' => 'Remove filter',
                'uk' => 'Видалити фільтр',
                'ru' => 'Удалить фильтр',
            ),
            'see_all_news' => array(
                'pl' => 'Zobacz wszystkie',
                'en' => 'See all',
                'uk' => 'Дивитися всі',
                'ru' => 'Смотреть все',
            ),
            'news_filter_year' => array(
                'pl' => 'Rok',
                'en' => 'Year',
                'uk' => 'Рік',
                'ru' => 'Год',
            ),
            'news_filter_month' => array(
                'pl' => 'Miesiąc',
                'en' => 'Month',
                'uk' => 'Місяць',
                'ru' => 'Месяц',
            ),
            'news_filter_month_colon' => array(
                'pl' => 'Miesiąc:',
                'en' => 'Month:',
                'uk' => 'Місяць:',
                'ru' => 'Месяц:',
            ),
            'news_filter_year_colon' => array(
                'pl' => 'Rok:',
                'en' => 'Year:',
                'uk' => 'Рік:',
                'ru' => 'Год:',
            ),
            'news_filter_all_years' => array(
                'pl' => 'Wszystkie lata',
                'en' => 'All years',
                'uk' => 'Усі роки',
                'ru' => 'Все годы',
            ),
            'news_filter_all_months' => array(
                'pl' => 'Wszystkie miesiące',
                'en' => 'All months',
                'uk' => 'Усі місяці',
                'ru' => 'Все месяцы',
            ),
            'news_filter_apply' => array(
                'pl' => 'Filtruj',
                'en' => 'Filter',
                'uk' => 'Фільтрувати',
                'ru' => 'Фильтр',
            ),
            'news_filter_period' => array(
                'pl' => 'Okres: %s',
                'en' => 'Period: %s',
                'uk' => 'Період: %s',
                'ru' => 'Период: %s',
            ),
            'news_no_results_period' => array(
                'pl' => 'Brak aktualności w wybranym okresie.',
                'en' => 'No news in the selected period.',
                'uk' => 'Немає новин за обраний період.',
                'ru' => 'Нет новостей за выбранный период.',
            ),
            'news_active_filter' => array(
                'pl' => 'Aktualności: %s',
                'en' => 'News: %s',
                'uk' => 'Новини: %s',
                'ru' => 'Новости: %s',
            ),
            'news_results_for' => array(
                'pl' => 'Wyniki dla: „%s”',
                'en' => 'Results for: “%s”',
                'uk' => 'Результати для: «%s»',
                'ru' => 'Результаты для: «%s»',
            ),
            'news_no_city_found' => array(
                'pl' => 'Nie znaleziono wybranego miasta.',
                'en' => 'Selected city was not found.',
                'uk' => 'Обране місто не знайдено.',
                'ru' => 'Выбранный город не найден.',
            ),
            'news_no_results_search' => array(
                'pl' => 'Brak wyników spełniających kryteria wyszukiwania.',
                'en' => 'No results match your search.',
                'uk' => 'Немає результатів за вашим запитом.',
                'ru' => 'Нет результатов по вашему запросу.',
            ),
            'news_no_results_city' => array(
                'pl' => 'Brak aktualności dla wybranego miasta.',
                'en' => 'No news for the selected city.',
                'uk' => 'Немає новин для обраного міста.',
                'ru' => 'Нет новостей для выбранного города.',
            ),
            'news_no_results_generic' => array(
                'pl' => 'Nie znaleziono żadnych wyników.',
                'en' => 'No results found.',
                'uk' => 'Нічого не знайдено.',
                'ru' => 'Ничего не найдено.',
            ),
            'news_read_more' => array(
                'pl' => 'Czytaj więcej',
                'en' => 'Read more',
                'uk' => 'Читати далі',
                'ru' => 'Читать далее',
            ),
            'news_filter_city_nav' => array(
                'pl' => 'Filtruj po mieście',
                'en' => 'Filter by city',
                'uk' => 'Фільтр за містом',
                'ru' => 'Фильтр по городу',
            ),
            'news_pagination_aria' => array(
                'pl' => 'Stronicowanie wpisów',
                'en' => 'Posts pagination',
                'uk' => 'Пагінація записів',
                'ru' => 'Пагинация записей',
            ),
            'news_pagination_heading' => array(
                'pl' => 'Stronicowanie wpisów',
                'en' => 'Posts pagination',
                'uk' => 'Пагінація записів',
                'ru' => 'Пагинация записей',
            ),
            'pagination_prev' => array(
                'pl' => 'Poprzedni',
                'en' => 'Previous',
                'uk' => 'Попередня',
                'ru' => 'Предыдущая',
            ),
            'pagination_next' => array(
                'pl' => 'Następny',
                'en' => 'Next',
                'uk' => 'Наступна',
                'ru' => 'Следующая',
            ),
            'pg_mba_all_cities' => array(
                'pl' => 'Wszystkie lokalizacje',
                'en' => 'All locations',
                'uk' => 'Усі локації',
                'ru' => 'Все локации',
            ),
            'pg_mba_filter_location' => array(
                'pl' => 'Lokalizacja',
                'en' => 'Location',
                'uk' => 'Локація',
                'ru' => 'Локация',
            ),
            'pg_mba_filter_interests' => array(
                'pl' => 'Obszar tematyczny',
                'en' => 'Thematic area',
                'uk' => 'Тематична область',
                'ru' => 'Тематическая область',
            ),
            'pg_mba_filter_language' => array(
                'pl' => 'Język',
                'en' => 'Language',
                'uk' => 'Мова',
                'ru' => 'Язык',
            ),
            'pg_mba_filter_form' => array(
                'pl' => 'Forma studiów',
                'en' => 'Mode of study',
                'uk' => 'Форма навчання',
                'ru' => 'Форма обучения',
            ),
            'pg_mba_filter_duration' => array(
                'pl' => 'Czas trwania',
                'en' => 'Duration',
                'uk' => 'Тривалість',
                'ru' => 'Продолжительность',
            ),
            'pg_mba_no_filter_results' => array(
                'pl' => 'Brak wyników dla wybranych filtrów.',
                'en' => 'No results for the selected filters.',
                'uk' => 'Немає результатів для обраних фільтрів.',
                'ru' => 'Нет результатов для выбранных фильтров.',
            ),
            'pg_mba_no_city_results' => array(
                'pl' => 'Brak wyników dla tej lokalizacji.',
                'en' => 'No results for this location.',
                'uk' => 'Немає результатів для цієї локації.',
                'ru' => 'Нет результатов для этой локации.',
            ),
            'offer_achievements_partners_title' => array(
                'pl' => 'OSIĄGNIĘCIA I PARTNERZY',
                'en' => 'ACHIEVEMENTS AND PARTNERS',
                'uk' => 'ДОСЯГНЕННЯ ТА ПАРТНЕРИ',
                'ru' => 'ДОСТИЖЕНИЯ И ПАРТНЕРЫ',
            ),
            'offer_ranking_perspektywy_alt' => array(
                'pl' => 'Ranking Perspektywy 2026 – 1. miejsce w Warszawie i we Wrocławiu wśród niepublicznych uczelni zawodowych',
                'en' => 'Perspektywy 2026 ranking – 1st place in Warsaw and Wrocław among private vocational universities',
                'uk' => 'Рейтинг Perspektywy 2026 – 1 місце у Варшаві та Вроцлаві серед приватних професійних вузів',
                'ru' => 'Рейтинг Perspektywy 2026 – 1 место в Варшаве и Вроцлаве среди частных профессиональных вузов',
            ),
            'offer_ranking_perspektywy_tooltip_short' => array(
                'pl' => "1. miejsce w Warszawie i we Wrocławiu\n6. miejsce w Polsce\n11. miejsce w rankingu ogólnym",
                'en' => "1st place in Warsaw and Wrocław\n6th place in Poland\n11th place in the overall ranking",
                'uk' => "1 місце у Варшаві та Вроцлаві\n6 місце в Польщі\n11 місце в загальному рейтингу",
                'ru' => "1 место в Варшаве и Вроцлаве\n6 место в Польше\n11 место в общем рейтинге",
            ),
            'offer_ranking_perspektywy_tooltip_hint' => array(
                'pl' => 'Szczegóły rankingu',
                'en' => 'Ranking details',
                'uk' => 'Деталі рейтингу',
                'ru' => 'Подробности рейтинга',
            ),
            'offer_ranking_perspektywy_headline' => array(
                'pl' => '1 MIEJSCE',
                'en' => '1ST PLACE',
                'uk' => '1 МІСЦЕ',
                'ru' => '1 МЕСТО',
            ),
            'offer_ranking_perspektywy_subline' => array(
                'pl' => 'w Warszawie i we Wrocławiu wśród niepublicznych uczelni zawodowych!',
                'en' => 'in Warsaw and Wrocław among private vocational universities!',
                'uk' => 'у Варшаві та Вроцлаві серед приватних професійних вузів!',
                'ru' => 'в Варшаве и Вроцлаве среди частных профессиональных вузов!',
            ),
            'offer_ranking_perspektywy_alt_warszawa' => array(
                'pl' => 'Ranking Perspektywy 2026 – 1. miejsce w Warszawie wśród niepublicznych uczelni zawodowych',
                'en' => 'Perspektywy 2026 ranking – 1st place in Warsaw among private vocational universities',
                'uk' => 'Рейтинг Perspektywy 2026 – 1 місце у Варшаві серед приватних професійних вузів',
                'ru' => 'Рейтинг Perspektywy 2026 – 1 место в Варшаве среди частных профессиональных вузов',
            ),
            'offer_ranking_perspektywy_tooltip_short_warszawa' => array(
                'pl' => "1. miejsce w Warszawie\n6. miejsce w Polsce\n11. miejsce w rankingu ogólnym",
                'en' => "1st place in Warsaw\n6th place in Poland\n11th place in the overall ranking",
                'uk' => "1 місце у Варшаві\n6 місце в Польщі\n11 місце в загальному рейтингу",
                'ru' => "1 место в Варшаве\n6 место в Польше\n11 место в общем рейтинге",
            ),
            'offer_ranking_perspektywy_subline_warszawa' => array(
                'pl' => 'w Warszawie wśród niepublicznych uczelni zawodowych!',
                'en' => 'in Warsaw among private vocational universities!',
                'uk' => 'у Варшаві серед приватних професійних вузів!',
                'ru' => 'в Варшаве среди частных профессиональных вузов!',
            ),
            'offer_ranking_perspektywy_alt_wroclaw' => array(
                'pl' => 'Ranking Perspektywy 2026 – 1. miejsce we Wrocławiu wśród niepublicznych uczelni zawodowych',
                'en' => 'Perspektywy 2026 ranking – 1st place in Wrocław among private vocational universities',
                'uk' => 'Рейтинг Perspektywy 2026 – 1 місце у Вроцлаві серед приватних професійних вузів',
                'ru' => 'Рейтинг Perspektywy 2026 – 1 место во Вроцлаве среди частных профессиональных вузов',
            ),
            'offer_ranking_perspektywy_tooltip_short_wroclaw' => array(
                'pl' => "1. miejsce we Wrocławiu\n6. miejsce w Polsce\n11. miejsce w rankingu ogólnym",
                'en' => "1st place in Wrocław\n6th place in Poland\n11th place in the overall ranking",
                'uk' => "1 місце у Вроцлаві\n6 місце в Польщі\n11 місце в загальному рейтингу",
                'ru' => "1 место во Вроцлаве\n6 место в Польше\n11 место в общем рейтинге",
            ),
            'offer_ranking_perspektywy_subline_wroclaw' => array(
                'pl' => 'we Wrocławiu wśród niepublicznych uczelni zawodowych!',
                'en' => 'in Wrocław among private vocational universities!',
                'uk' => 'у Вроцлаві серед приватних професійних вузів!',
                'ru' => 'во Вроцлаве среди частных профессиональных вузов!',
            ),
        );
    }

    $lang = apply_filters('wpml_current_language', 'pl');

    if (isset($strings[ $key ][ $lang ])) {
        return $strings[ $key ][ $lang ];
    }

    return $strings[ $key ]['pl'] ?? '';
}

/**
 * Default news city when Miasto is not set on a wpis.
 *
 * @return string Term slug.
 */
function akademiata_get_default_news_city_slug() {
    return 'warszawa';
}

/**
 * Assigned news_city term on a wpis (empty if editor left Miasto unchecked).
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function akademiata_get_post_news_city_term($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ($post_id <= 0) {
        return null;
    }

    $terms = get_the_terms($post_id, 'news_city');
    if (!empty($terms) && !is_wp_error($terms)) {
        return $terms[0];
    }

    $slug = get_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, true);
    $slug = sanitize_title((string) $slug);
    if (in_array($slug, array('warszawa', 'wroclaw'), true)) {
        return akademiata_get_news_city_term_by_slug($slug);
    }

    return null;
}

/**
 * Effective city slug for a wpis (assigned term or default Warszawa).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function akademiata_get_post_news_city_slug($post_id = 0) {
    $term = akademiata_get_post_news_city_term($post_id);

    if ($term) {
        return $term->slug;
    }

    return akademiata_get_default_news_city_slug();
}

/**
 * City label for cards and UI (defaults to Warszawa / Warsaw when Miasto is empty).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function akademiata_get_post_news_city_label($post_id = 0) {
    $term = akademiata_get_post_news_city_term($post_id);

    if ($term) {
        return akademiata_get_news_city_display_name($term);
    }

    $default_slug = akademiata_get_default_news_city_slug();
    $map          = akademiata_news_city_label_map();
    $lang         = apply_filters('wpml_current_language', 'pl');

    if (isset($map[ $default_slug ][ $lang ])) {
        return $map[ $default_slug ][ $lang ];
    }

    return $map[ $default_slug ]['pl'] ?? 'Warszawa';
}

/**
 * tax_query for aktualności archive city filter.
 * Warszawa includes posts with no Miasto (default city rule).
 *
 * @param string $city_slug news_city slug from ?miasto=
 * @return array|null Tax query array or null when no city filter.
 */
function akademiata_build_news_city_tax_query($city_slug) {
    $city_slug = sanitize_title((string) $city_slug);

    if ($city_slug === '') {
        return null;
    }

    if ($city_slug === akademiata_get_default_news_city_slug()) {
        $tax_query = array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'news_city',
                'operator' => 'NOT EXISTS',
            ),
        );

        $warsaw = akademiata_get_news_city_term_by_slug($city_slug);
        if ($warsaw) {
            $tax_query[] = array(
                'taxonomy' => 'news_city',
                'field'    => 'term_id',
                'terms'    => array((int) $warsaw->term_id),
                'operator' => 'IN',
            );
        }

        return $tax_query;
    }

    $term = akademiata_get_news_city_term_by_slug($city_slug);
    if (!$term) {
        return array(
            array(
                'taxonomy' => 'news_city',
                'field'    => 'term_id',
                'terms'    => array(0),
            ),
        );
    }

    return array(
        array(
            'taxonomy' => 'news_city',
            'field'    => 'term_id',
            'terms'    => array((int) $term->term_id),
        ),
    );
}

/**
 * Apply city filter to a WP_Query args array (taxonomy + post meta, OR logic).
 *
 * @param array  $args      Query args (modified in place).
 * @param string $city_slug news_city slug from ?miasto=
 */
function akademiata_apply_news_city_filter_to_query(array &$args, $city_slug) {
    $city_slug = sanitize_title((string) $city_slug);
    if ($city_slug === '') {
        return;
    }

    $args['akademiata_news_city'] = $city_slug;

    static $filter_added = false;
    if (!$filter_added) {
        add_filter('posts_where', 'akademiata_filter_posts_where_by_news_city', 10, 2);
        $filter_added = true;
    }
}

/**
 * SQL WHERE for news city archive filter (matches taxonomy term OR stored meta slug).
 *
 * @param string    $where   WHERE clause.
 * @param WP_Query  $query   Query object.
 * @return string
 */
function akademiata_filter_posts_where_by_news_city($where, $query) {
    if (!($query instanceof WP_Query)) {
        return $where;
    }

    $city_slug = $query->get('akademiata_news_city');
    if (!$city_slug) {
        return $where;
    }

    global $wpdb;

    $city_slug = sanitize_title((string) $city_slug);
    $meta_key  = AKADEMIATA_NEWS_CITY_META_KEY;

    if ($city_slug === akademiata_get_default_news_city_slug()) {
        $wroclaw_term = akademiata_get_news_city_term_by_slug('wroclaw');
        $exclude      = array();

        if ($wroclaw_term) {
            $exclude[] = $wpdb->prepare(
                "EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tr.object_id = {$wpdb->posts}.ID
                    AND tt.taxonomy = 'news_city'
                    AND tt.term_id = %d
                )",
                (int) $wroclaw_term->term_id
            );
        }

        $exclude[] = $wpdb->prepare(
            "EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = {$wpdb->posts}.ID
                AND pm.meta_key = %s
                AND pm.meta_value = %s
            )",
            $meta_key,
            'wroclaw'
        );

        if (!empty($exclude)) {
            $where .= ' AND NOT (' . implode(' OR ', $exclude) . ')';
        }

        return $where;
    }

    $term  = akademiata_get_news_city_term_by_slug($city_slug);
    $parts = array();

    if ($term) {
        $parts[] = $wpdb->prepare(
            "EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = {$wpdb->posts}.ID
                AND tt.taxonomy = 'news_city'
                AND tt.term_id = %d
            )",
            (int) $term->term_id
        );
    }

    $parts[] = $wpdb->prepare(
        "EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm
            WHERE pm.post_id = {$wpdb->posts}.ID
            AND pm.meta_key = %s
            AND pm.meta_value = %s
        )",
        $meta_key,
        $city_slug
    );

    if (!empty($parts)) {
        $where .= ' AND (' . implode(' OR ', $parts) . ')';
    }

    return $where;
}

/**
 * Ensure news_city term exists in a language; return term ID.
 *
 * @param string $slug Term slug (e.g. warszawa).
 * @param string $lang WPML language code.
 * @return int 0 if unavailable.
 */
function akademiata_ensure_news_city_term_id($slug, $lang) {
    static $cache = array();

    $slug = sanitize_title($slug);
    $lang = sanitize_key($lang);
    $key  = $slug . '|' . $lang;

    if (isset($cache[ $key ])) {
        return $cache[ $key ];
    }

    $cache[ $key ] = 0;

    if (!taxonomy_exists('news_city') || $slug === '') {
        return $cache[ $key ];
    }

    $previous_lang = null;
    if (function_exists('apply_filters') && has_filter('wpml_current_language')) {
        $previous_lang = apply_filters('wpml_current_language', null);
        do_action('wpml_switch_language', $lang);
    }

    $term = get_term_by('slug', $slug, 'news_city');

    if (!$term || is_wp_error($term)) {
        $labels = akademiata_news_city_label_map();
        $name   = $labels[ $slug ][ $lang ] ?? ($labels[ $slug ]['pl'] ?? $slug);
        $insert = wp_insert_term($name, 'news_city', array('slug' => $slug));

        if (!is_wp_error($insert)) {
            $term = get_term((int) $insert['term_id'], 'news_city');
        }
    }

    if ($previous_lang !== null) {
        do_action('wpml_switch_language', $previous_lang);
    }

    if ($term && !is_wp_error($term)) {
        $cache[ $key ] = (int) $term->term_id;
    }

    return $cache[ $key ];
}

/**
 * WPML language code for a post (defaults to pl).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function akademiata_get_post_wpml_language($post_id) {
    $post_id = (int) $post_id;
    $lang    = 'pl';

    if ($post_id > 0 && function_exists('apply_filters')) {
        $lang = apply_filters(
            'wpml_element_language_code',
            'pl',
            array(
                'element_id'   => $post_id,
                'element_type' => 'post_post',
            )
        );
    }

    return $lang ? sanitize_key($lang) : 'pl';
}

/**
 * Map submitted news_city term IDs to the post language (slug-based).
 *
 * @param int[] $term_ids Raw IDs from tax_input or REST.
 * @param int   $post_id  Post ID.
 * @return int[]
 */
function akademiata_resolve_news_city_term_ids_for_post(array $term_ids, $post_id) {
    $lang    = akademiata_get_post_wpml_language($post_id);
    $slugs   = array();
    $allowed = array('warszawa', 'wroclaw');

    foreach ($term_ids as $term_id) {
        $term_id = (int) $term_id;
        if ($term_id <= 0) {
            continue;
        }

        $term = get_term($term_id, 'news_city');
        if (!$term || is_wp_error($term)) {
            continue;
        }

        $slug = sanitize_title($term->slug);
        if (in_array($slug, $allowed, true)) {
            $slugs[] = $slug;
        }
    }

    $slugs = array_values(array_unique($slugs));
    if (count($slugs) > 1) {
        $slugs = array_slice($slugs, -1);
    }

    $resolved = array();
    foreach ($slugs as $slug) {
        $tid = akademiata_ensure_news_city_term_id($slug, $lang);
        if ($tid > 0) {
            $resolved[] = $tid;
        }
    }

    return $resolved;
}

/**
 * Assign news_city on a wpis (suppresses WPML term filters during write).
 *
 * @param int   $post_id  Post ID.
 * @param int[] $term_ids Term IDs in the post language.
 */
function akademiata_set_post_news_city_terms($post_id, array $term_ids) {
    $post_id = (int) $post_id;
    if ($post_id <= 0 || !taxonomy_exists('news_city')) {
        return;
    }

    global $sitepress;
    $sitepress_removed = false;

    if (isset($sitepress) && is_object($sitepress) && method_exists($sitepress, 'set_object_terms_action')) {
        remove_action('set_object_terms', array($sitepress, 'set_object_terms_action'), 1);
        $sitepress_removed = true;
    }

    wp_set_object_terms($post_id, $term_ids, 'news_city', false);
    clean_object_term_cache($post_id, 'news_city');

    if ($sitepress_removed) {
        add_action('set_object_terms', array($sitepress, 'set_object_terms_action'), 1, 6);
    }
}

if (!defined('AKADEMIATA_NEWS_CITY_META_KEY')) {
    define('AKADEMIATA_NEWS_CITY_META_KEY', '_akademiata_news_city_slug');
}

function akademiata_register_news_city_post_meta() {
    register_post_meta(
        'post',
        AKADEMIATA_NEWS_CITY_META_KEY,
        array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => static function () {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => static function ($value) {
                $slug = sanitize_title((string) $value);
                return in_array($slug, array('warszawa', 'wroclaw'), true) ? $slug : '';
            },
        )
    );
}

add_action('init', 'akademiata_register_news_city_post_meta', 15);

/**
 * @param int $post_id Post ID.
 * @return string Empty, warszawa, or wroclaw (stored value only).
 */
function akademiata_get_saved_news_city_slug($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ($post_id <= 0) {
        return '';
    }

    $slug = get_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, true);
    $slug = sanitize_title((string) $slug);

    if (in_array($slug, array('warszawa', 'wroclaw'), true)) {
        return $slug;
    }

    $terms = get_the_terms($post_id, 'news_city');
    if (!empty($terms) && !is_wp_error($terms)) {
        $term_slug = sanitize_title($terms[0]->slug);
        if (in_array($term_slug, array('warszawa', 'wroclaw'), true)) {
            return $term_slug;
        }
    }

    return '';
}

/**
 * Save news_city from checkbox term IDs (exact IDs from the metabox).
 *
 * @param int   $post_id Post ID.
 * @param int[] $raw_ids Term IDs from tax_input checkboxes.
 */
function akademiata_save_post_news_city_from_term_ids($post_id, array $raw_ids) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return;
    }

    $raw_ids  = array_values(array_filter(array_map('intval', $raw_ids)));
    $slug     = '';
    $save_ids = array();

    foreach ($raw_ids as $term_id) {
        $term = get_term((int) $term_id, 'news_city');
        if (!$term || is_wp_error($term)) {
            continue;
        }

        $candidate = sanitize_title($term->slug);
        if (in_array($candidate, array('warszawa', 'wroclaw'), true)) {
            $slug = $candidate;
            break;
        }
    }

    if ($slug !== '') {
        $term = get_term_by('slug', $slug, 'news_city');
        if ($term && !is_wp_error($term)) {
            $save_ids = array((int) $term->term_id);
        } else {
            $slug = '';
        }
    }

    if ($slug === '') {
        delete_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY);
    } else {
        update_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, $slug);
    }

    akademiata_set_post_news_city_terms($post_id, $save_ids);
}

/**
 * Persist slug to post meta and sync news_city taxonomy term.
 *
 * @param int    $post_id Post ID.
 * @param string $slug    Empty, warszawa, or wroclaw.
 */
function akademiata_save_post_news_city_slug($post_id, $slug) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return;
    }

    $slug = sanitize_title((string) $slug);
    if (!in_array($slug, array('', 'warszawa', 'wroclaw'), true)) {
        $slug = '';
    }

    if ($slug === '') {
        akademiata_save_post_news_city_from_term_ids($post_id, array());
        return;
    }

    $term = get_term_by('slug', $slug, 'news_city');
    if ($term && !is_wp_error($term)) {
        akademiata_save_post_news_city_from_term_ids($post_id, array((int) $term->term_id));
        return;
    }

    akademiata_save_post_news_city_from_term_ids($post_id, array());
}

/**
 * Whether a degree term slug maps to studia I stopnia (bachelor).
 */
function akademiata_degree_slug_is_bachelor_level($slug) {
    $slug = (string) $slug;

    return (bool) preg_match(
        '/(?:^|-)(?:studia-)?(?:1|i)(?:-st|stopn)/i',
        $slug
    );
}

/**
 * Whether a degree term slug maps to studia II stopnia (master).
 */
function akademiata_degree_slug_is_master_level($slug) {
    $slug = (string) $slug;

    return (bool) preg_match(
        '/(?:^|-)(?:studia-)?(?:2|ii)(?:-st|stopn)/i',
        $slug
    );
}

/**
 * Degree term IDs used on published offers of a given CPT (fallback when slugs are ambiguous).
 *
 * @param string $post_type bachelor|master
 * @return int[]
 */
function akademiata_collect_degree_term_ids_for_post_type($post_type) {
    $post_type = sanitize_key($post_type);

    if (!in_array($post_type, array('bachelor', 'master'), true)) {
        return array();
    }

    $post_ids = get_posts(
        array(
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => 50,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'lang'                   => apply_filters('wpml_current_language', null),
        )
    );

    $term_ids = array();

    foreach ($post_ids as $post_id) {
        $terms = get_the_terms((int) $post_id, 'degree');
        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            $term_ids[] = (int) $term->term_id;
        }
    }

    return array_values(array_unique($term_ids));
}

/**
 * Degree term IDs for filtering wpisy by studia I / II stopnia on kierunek pages.
 *
 * @param string $level bachelor|master
 * @return int[]
 */
function akademiata_get_news_degree_term_ids_for_level($level) {
    static $cache = array();

    $level = sanitize_key($level);
    if (!in_array($level, array('bachelor', 'master'), true)) {
        return array();
    }

    if (isset($cache[$level])) {
        return $cache[$level];
    }

    $bachelor_ids = array();
    $master_ids   = array();
    $terms        = get_terms(
        array(
            'taxonomy'   => 'degree',
            'hide_empty' => false,
        )
    );

    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            if (akademiata_degree_slug_is_bachelor_level($term->slug)) {
                $bachelor_ids[] = (int) $term->term_id;
            }
            if (akademiata_degree_slug_is_master_level($term->slug)) {
                $master_ids[] = (int) $term->term_id;
            }
        }
    }

    if (empty($bachelor_ids)) {
        $bachelor_ids = akademiata_collect_degree_term_ids_for_post_type('bachelor');
    }
    if (empty($master_ids)) {
        $master_ids = akademiata_collect_degree_term_ids_for_post_type('master');
    }

    $cache['bachelor'] = $bachelor_ids;
    $cache['master']   = $master_ids;

    return $cache[$level];
}

/**
 * Aktualności linked to a program term.
 * Empty degree on post = both levels (oba) when $level is bachelor|master.
 *
 * @param WP_Term|int     $program_term Program term or term ID.
 * @param string|null     $level        bachelor|master, or null for all levels on this kierunek.
 * @param array           $query_args   Optional WP_Query overrides.
 * @return WP_Query
 */
function akademiata_query_program_related_news($program_term, $level = null, $query_args = array()) {
    if (is_numeric($program_term)) {
        $program_term = get_term((int) $program_term, 'program');
    }

    if (!$program_term || is_wp_error($program_term)) {
        return new WP_Query(array('post__in' => array(0)));
    }

    $tax_query = array(
        'relation' => 'AND',
        array(
            'taxonomy' => 'program',
            'field'    => 'term_id',
            'terms'    => array((int) $program_term->term_id),
        ),
    );

    if ($level !== null && $level !== '') {
        $level = sanitize_key($level);
        if (in_array($level, array('bachelor', 'master'), true)) {
            $degree_ids = akademiata_get_news_degree_term_ids_for_level($level);
            if (!empty($degree_ids)) {
                $tax_query[] = array(
                    'relation' => 'OR',
                    array(
                        'taxonomy' => 'degree',
                        'operator' => 'NOT EXISTS',
                    ),
                    array(
                        'taxonomy' => 'degree',
                        'field'    => 'term_id',
                        'terms'    => $degree_ids,
                        'operator' => 'IN',
                    ),
                );
            }
        }
    }

    $defaults = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 6,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'tax_query'           => $tax_query,
        'lang'                => apply_filters('wpml_current_language', null),
    );

    $cat_id = akademiata_get_aktualnosci_category_term_id();
    if ($cat_id > 0) {
        $defaults['cat'] = $cat_id;
    }

    return new WP_Query(wp_parse_args($query_args, $defaults));
}

/**
 * Friendlier taxonomy labels on the post edit screen.
 */
function akademiata_news_post_taxonomy_gettext($translated, $text, $domain) {
    if ($domain !== 'akademiata' || !is_admin()) {
        return $translated;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'post') {
        return $translated;
    }

    // Plain strings only — __() here would recurse via gettext and crash admin.
    $map = array(
        'Programs' => 'Kierunki, których dotyczy wpis',
        'Degree'   => 'Stopień studiów (puste = oba)',
    );

    if (isset($map[ $text ])) {
        return $map[ $text ];
    }

    return $translated;
}

add_filter('gettext', 'akademiata_news_post_taxonomy_gettext', 10, 3);

function render_taxonomy_info($taxonomy_labels) {
    if (empty($taxonomy_labels) || !is_array($taxonomy_labels)) {
        return;
    }

    $post_id = get_the_ID();
    $post_type = get_post_type($post_id);
    $post_type_obj = $post_type ? get_post_type_object($post_type) : null;
    $base_slug = ($post_type_obj && !empty($post_type_obj->rewrite['slug']))
        ? $post_type_obj->rewrite['slug']
        : $post_type;

    foreach ($taxonomy_labels as $taxonomy => $label) {
        $terms = get_the_terms($post_id, $taxonomy);

        if (!empty($terms) && !is_wp_error($terms)) {
            $term_links = array_map(function ($term) use ($taxonomy, $base_slug) {
                if ($taxonomy === 'program') {
                    // Standard taxonomy archive link
                    return sprintf(
                        '<a title="%s" href="%s">%s</a>',
                        esc_attr($term->name),
                        esc_url(get_term_link($term)),
                        esc_html($term->name)
                    );
                } elseif ($taxonomy === 'city_pg_mba') {
                    return sprintf(
                        '<a title="%s" href="%s">%s</a>',
                        esc_attr($term->name),
                        esc_url(get_term_link($term)),
                        esc_html($term->name)
                    );
                } elseif ($taxonomy === 'city') {
                    // Custom city filter link (bachelor / master archives)
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

    $post_id = get_the_ID();

    foreach ($taxonomy_labels as $taxonomy => $label) {
        $terms = get_the_terms($post_id, $taxonomy);

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
    $terms = ($category === 'program')
        ? akademiata_get_offer_terms($post_id, $category)
        : get_the_terms($post_id, $category);

    if (empty($terms) || is_wp_error($terms)) {
        return '';
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
