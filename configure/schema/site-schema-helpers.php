<?php
/**
 * Site-wide JSON-LD helpers — homepage, pages, news, archives.
 *
 * @package akademiata
 */

/**
 * CPT singles that register their own JSON-LD (priority 20).
 *
 * @return string[]
 */
function akademiata_schema_offer_single_post_types() {
    return array('bachelor', 'master', 'postgraduate', 'mba', 'courses', 'exams');
}

function akademiata_schema_is_offer_single_view() {
    return is_singular(akademiata_schema_offer_single_post_types());
}

function akademiata_schema_current_language_code() {
    $lang = apply_filters('wpml_current_language', null);
    if (!$lang && defined('ICL_LANGUAGE_CODE')) {
        $lang = ICL_LANGUAGE_CODE;
    }

    return is_string($lang) && $lang !== '' ? $lang : 'pl';
}

/**
 * @return array<string, mixed>
 */
function akademiata_schema_build_website() {
    $home = untrailingslashit(home_url());

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => $home . '/#website',
        'url'             => $home,
        'name'            => get_bloginfo('name'),
        'publisher'       => array('@id' => $home . '/#organization'),
        'inLanguage'      => function_exists('akademiata_schema_bcp47_language')
            ? akademiata_schema_bcp47_language()
            : akademiata_schema_current_language_code(),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array(
                '@type'       => 'EntryPoint',
                'urlTemplate' => $home . '?s={search_term_string}',
            ),
            'query-input' => 'required name=search_term_string',
        ),
    );
}

/**
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function akademiata_schema_build_webpage($url, $title, $description = '', array $extra = array()) {
    $home   = untrailingslashit(home_url());
    $url    = untrailingslashit((string) $url);
    $schema = array_merge(array(
        '@context'   => 'https://schema.org',
        '@type'      => 'WebPage',
        '@id'        => $url . '/#webpage',
        'url'        => $url,
        'name'       => akademiata_schema_clean_text($title),
        'isPartOf'   => array('@id' => $home . '/#website'),
        'about'      => array('@id' => $home . '/#organization'),
        'inLanguage' => function_exists('akademiata_schema_bcp47_language')
            ? akademiata_schema_bcp47_language()
            : akademiata_schema_current_language_code(),
    ), $extra);

    $description = akademiata_schema_clean_text($description);
    if ($description !== '') {
        $schema['description'] = $description;
    }

    return $schema;
}

/**
 * @param int $post_id
 */
function akademiata_schema_is_news_post($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
    if ($post_id <= 0 || get_post_type($post_id) !== 'post') {
        return false;
    }

    $cat_id = function_exists('akademiata_get_aktualnosci_category_term_id')
        ? akademiata_get_aktualnosci_category_term_id()
        : 0;

    return $cat_id > 0 && has_category($cat_id, $post_id);
}

function akademiata_schema_is_news_archive_view() {
    if ((int) get_query_var('ata_date') === 1) {
        return true;
    }

    if (!is_page()) {
        return false;
    }

    $slug = (string) get_post_field('post_name', get_queried_object_id());
    if (in_array($slug, array('aktualnosci', 'news', 'novyny', 'novosti'), true)) {
        return true;
    }

    return is_page_template('page-aktualnosci.php');
}

/**
 * @param int $post_id
 */
function akademiata_schema_page_description($post_id) {
    $post_id = (int) $post_id;
    $manual  = akademiata_schema_get_manual_description($post_id);
    if ($manual !== '') {
        return $manual;
    }

    $excerpt = get_post_field('post_excerpt', $post_id);
    if (is_string($excerpt) && trim($excerpt) !== '') {
        return akademiata_schema_trim_text($excerpt, 55);
    }

    $content = get_post_field('post_content', $post_id);
    if (is_string($content) && trim($content) !== '') {
        return akademiata_schema_trim_text($content, 55);
    }

    return '';
}

/**
 * @param WP_Post[] $posts
 * @return array<string, mixed>|null
 */
function akademiata_schema_build_item_list(array $posts, $list_name, $list_url = '') {
    $elements = array();
    $position = 1;

    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }

        $name = akademiata_schema_clean_text(get_the_title($post));
        $url  = get_permalink($post);
        if ($name === '' || !$url) {
            continue;
        }

        $elements[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'url'      => $url,
            'name'     => $name,
        );
        $position++;
    }

    if ($elements === array()) {
        return null;
    }

    $list = array(
        '@type'           => 'ItemList',
        'name'            => akademiata_schema_clean_text($list_name),
        'itemListOrder'   => 'https://schema.org/ItemListOrderDescending',
        'numberOfItems'   => count($elements),
        'itemListElement' => $elements,
    );

    if ($list_url !== '') {
        $list['url'] = user_trailingslashit((string) $list_url);
    }

    return $list;
}

/**
 * @return WP_Post[]
 */
function akademiata_schema_get_main_archive_posts($limit = 12) {
    global $wp_query;

    $limit = max(1, (int) $limit);
    if (!($wp_query instanceof WP_Query) || empty($wp_query->posts)) {
        return array();
    }

    if (!$wp_query->is_archive && !$wp_query->is_search) {
        return array();
    }

    return array_slice($wp_query->posts, 0, $limit);
}

/**
 * @return WP_Post[]
 */
function akademiata_schema_query_news_archive_posts($limit = 12) {
    if (!function_exists('akademiata_get_aktualnosci_category_term_id')) {
        return array();
    }

    $cat_id = akademiata_get_aktualnosci_category_term_id();
    if ($cat_id <= 0) {
        return array();
    }

    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => max(1, (int) $limit),
        'cat'                 => $cat_id,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    );

    if ((int) get_query_var('ata_date') === 1) {
        $date_query = array_filter(array(
            'year'  => (int) get_query_var('year') ?: null,
            'month' => (int) get_query_var('monthnum') ?: null,
            'day'   => (int) get_query_var('day') ?: null,
        ));
        if ($date_query !== array()) {
            $args['date_query'] = array($date_query);
        }
    } elseif (function_exists('akademiata_get_news_archive_date_from_request')) {
        $date = akademiata_get_news_archive_date_from_request();
        if (function_exists('akademiata_apply_news_archive_date_query')) {
            akademiata_apply_news_archive_date_query($args, $date['year'], $date['month']);
        }
    }

    if (function_exists('akademiata_get_current_news_city_slug_from_request')
        && function_exists('akademiata_apply_news_city_filter_to_query')) {
        $city = akademiata_get_current_news_city_slug_from_request();
        if ($city !== '') {
            akademiata_apply_news_city_filter_to_query($args, $city);
        }
    }

    $query = new WP_Query($args);

    return is_array($query->posts) ? $query->posts : array();
}

/**
 * @return WP_Post[]
 */
function akademiata_schema_query_program_taxonomy_posts($term, $limit = 12) {
    if (!($term instanceof WP_Term)) {
        return array();
    }

    $query = new WP_Query(array(
        'post_type'      => array('bachelor', 'master'),
        'post_status'    => 'publish',
        'posts_per_page' => max(1, (int) $limit),
        'no_found_rows'  => true,
        'tax_query'      => array(
            array(
                'taxonomy' => $term->taxonomy,
                'field'    => 'term_id',
                'terms'    => (int) $term->term_id,
            ),
        ),
        'lang'           => apply_filters('wpml_current_language', null),
    ));

    return is_array($query->posts) ? $query->posts : array();
}

function akademiata_schema_get_archive_heading() {
    if ((int) get_query_var('ata_date') === 1) {
        $year     = (int) get_query_var('year');
        $monthnum = (int) get_query_var('monthnum');
        $day      = (int) get_query_var('day');

        if ($day && $year && $monthnum) {
            return date_i18n('j F Y', mktime(0, 0, 0, $monthnum, $day, $year));
        }
        if ($monthnum && $year) {
            return date_i18n('F Y', mktime(0, 0, 0, $monthnum, 1, $year));
        }
        if ($year) {
            return (string) $year;
        }
    }

    if (is_home() && !is_front_page()) {
        $posts_page_id = (int) get_option('page_for_posts');
        if ($posts_page_id > 0) {
            return akademiata_schema_clean_text(get_the_title($posts_page_id));
        }

        return __('Blog', 'akademiata');
    }

    if (is_search()) {
        return sprintf(
            /* translators: %s: search query */
            __('Wyniki wyszukiwania: %s', 'akademiata'),
            akademiata_schema_clean_text(get_search_query())
        );
    }

    if (is_tax('program')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            return sprintf(
                /* translators: %s: program name */
                __('Kierunek studiów: %s', 'akademiata'),
                $term->name
            );
        }
    }

    if (is_tax() || is_category() || is_tag()) {
        return akademiata_schema_clean_text(single_term_title('', false));
    }

    if (is_post_type_archive()) {
        $post_type = get_query_var('post_type');
        if (is_array($post_type)) {
            $post_type = $post_type[0] ?? '';
        }

        $mapped = akademiata_schema_get_cpt_archive_page_title((string) $post_type);
        if ($mapped !== '') {
            return $mapped;
        }

        return akademiata_schema_clean_text(post_type_archive_title('', false));
    }

    if (is_page()) {
        return akademiata_schema_clean_text(get_the_title());
    }

    return '';
}

function akademiata_schema_get_cpt_archive_page_title($post_type) {
    $map = array(
        'postgraduate' => 'studia-podyplomowe',
        'mba'          => 'studia-mba',
        'courses'      => 'kursy',
        'exams'        => 'egzaminy',
        'bachelor'     => 'oferta',
        'master'       => 'oferta',
    );

    $post_type = sanitize_key((string) $post_type);
    if ($post_type === '' || empty($map[ $post_type ])) {
        return '';
    }

    $page = get_page_by_path($map[ $post_type ]);
    if (!$page) {
        return '';
    }

    $page_id = (int) $page->ID;
    if (function_exists('icl_object_id')) {
        $lang = apply_filters('wpml_current_language', null);
        $translated = (int) apply_filters('wpml_object_id', $page_id, 'page', false, $lang);
        if ($translated > 0) {
            $page_id = $translated;
        }
    }

    return akademiata_schema_clean_text(get_the_title($page_id));
}

/**
 * @param array<string, mixed>|null $item_list
 * @return array<string, mixed>|null
 */
function akademiata_schema_build_collection_page($url, $title, $description = '', $item_list = null) {
    $schema = akademiata_schema_build_webpage($url, $title, $description, array(
        '@type' => 'CollectionPage',
    ));

    if (is_array($item_list)) {
        $schema['mainEntity'] = $item_list;
    }

    return $schema;
}

function akademiata_schema_current_canonical_url() {
    if (is_singular()) {
        $url = get_permalink();
        return $url ? user_trailingslashit($url) : home_url('/');
    }

    if (is_search()) {
        return home_url('/?s=' . rawurlencode(get_search_query()));
    }

    global $wp;
    if (isset($wp->request)) {
        return user_trailingslashit(home_url('/' . ltrim((string) $wp->request, '/')));
    }

    return home_url('/');
}
