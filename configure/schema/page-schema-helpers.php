<?php
/**
 * Page JSON-LD — template registry and shared builders.
 *
 * Schema reads live ACF/post content on each request (no theme-side cache).
 * After editing a page in WP admin, JSON-LD updates on next page load
 * (clear WP Rocket on prod if HTML is cached).
 *
 * @package akademiata
 */

/**
 * LP templates → fields helper (same merge as page template).
 *
 * @return array<string, array{fields_callback: string, fields_file: string, filter: string, webpage_type?: string}>
 */
function akademiata_schema_lp_page_templates() {
    $theme = get_template_directory();

    return array(
        'page-o-uczelni.php'               => array(
            'fields_callback' => 'akademiata_o_uczelni_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/o-uczelni/fields.php',
            'filter'          => 'akademiata_schema_page_o_uczelni_subject_of',
            'webpage_type'    => 'AboutPage',
        ),
        'page-rankingi.php'                => array(
            'fields_callback' => 'akademiata_rankingi_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/rankingi/fields.php',
            'filter'          => 'akademiata_schema_page_rankingi_subject_of',
        ),
        'page-zasady-rekrutacji.php'       => array(
            'fields_callback' => 'akademiata_zasady_rekrutacji_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/zasady-rekrutacji/fields.php',
            'filter'          => 'akademiata_schema_page_zasady_rekrutacji_subject_of',
        ),
        'page-katalog-kierunkow.php'       => array(
            'fields_callback' => 'akademiata_katalog_kierunkow_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/katalog-kierunkow/fields.php',
            'filter'          => 'akademiata_schema_page_katalog_kierunkow_subject_of',
        ),
        'page-porownanie-kierunkow.php'    => array(
            'fields_callback' => 'akademiata_porownanie_kierunkow_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/porownanie-kierunkow/fields.php',
            'filter'          => 'akademiata_schema_page_porownanie_kierunkow_subject_of',
        ),
        'page-studiuj-to-co-cie-kreci.php' => array(
            'fields_callback' => 'akademiata_studiuj_to_co_cie_kreci_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/studiuj-to-co-cie-kreci/fields.php',
            'filter'          => 'akademiata_schema_page_studiuj_subject_of',
        ),
        'page-projekty-i-pracownie.php'    => array(
            'fields_callback' => 'akademiata_projekty_i_pracownie_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/projekty-i-pracownie/fields.php',
            'filter'          => 'akademiata_schema_page_projekty_subject_of',
        ),
        'page-kreowanie-przestrzeni.php'   => array(
            'fields_callback' => 'akademiata_kreowanie_przestrzeni_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/kreowanie-przestrzeni/fields.php',
            'filter'          => 'akademiata_schema_page_kreowanie_subject_of',
        ),
        'page-portfolio-sprawdzian.php'    => array(
            'fields_callback' => 'akademiata_portfolio_sprawdzian_fields',
            'fields_file'     => $theme . '/configure/lp-defaults/portfolio-sprawdzian/fields.php',
            'filter'          => 'akademiata_schema_page_portfolio_subject_of',
        ),
    );
}

/**
 * @return array<string, callable>|array<string, string>
 */
function akademiata_schema_special_page_builders() {
    return array(
        'page-faq.php'                 => 'akademiata_build_faq_page_schema',
        'page-contact.php'             => 'akademiata_build_contact_page_schema',
        'page-offer.php'               => 'akademiata_build_offer_listing_page_schema',
        'page-cadre.php'               => 'akademiata_build_cadre_page_schema',
        'page-template-prices.php'     => 'akademiata_build_prices_page_schema',
        'page-template-open-day.php'   => 'akademiata_build_open_day_page_schema',
        'page-ranking-ela.php'         => 'akademiata_build_ranking_ela_page_schema',
        'page-quiz.php'                => 'akademiata_build_quiz_page_schema',
        'page-template-thank-you.php'  => 'akademiata_build_thank_you_page_schema',
    );
}

/**
 * @param int $post_id
 */
function akademiata_schema_get_page_template_file($post_id) {
    $slug = get_page_template_slug((int) $post_id);

    return is_string($slug) ? $slug : '';
}

/**
 * @param int $post_id
 * @return array{subject_of: array<int, array<string, mixed>>, faq_entities: array<int, array<string, mixed>>}
 */
function akademiata_schema_collect_lp_page_subject_of($post_id) {
    $template = akademiata_schema_get_page_template_file($post_id);
    $map      = akademiata_schema_lp_page_templates();

    if ($template === '' || empty($map[ $template ])) {
        return array('subject_of' => array(), 'faq_entities' => array());
    }

    $config = $map[ $template ];
    $data   = akademiata_schema_lp_collect_page_fields(
        $post_id,
        $config['fields_callback'],
        $config['fields_file']
    );

    $subject_of = apply_filters(
        $config['filter'],
        $data['subject_of'],
        (int) $post_id
    );

    return array(
        'subject_of'   => is_array($subject_of) ? $subject_of : array(),
        'faq_entities' => is_array($data['faq_entities']) ? $data['faq_entities'] : array(),
    );
}

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_schema_page_webpage_shell($post_id, $webpage_type = 'WebPage') {
    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $extra = array();
    if ($webpage_type !== '' && $webpage_type !== 'WebPage') {
        $extra['@type'] = $webpage_type;
    }

    $schema = akademiata_schema_build_webpage(
        $base['permalink'],
        $base['title'],
        akademiata_schema_page_description($post_id),
        $extra
    );

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['primaryImageOfPage'] = $image;
    }

    $modified = get_the_modified_date('c', $post_id);
    if ($modified) {
        $schema['dateModified'] = $modified;
    }

    return $schema;
}

/**
 * @param array<string, mixed>             $schema
 * @param array<int, array<string, mixed>> $subject_of
 * @param array<int, array<string, mixed>> $faq_entities
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_page_schema_graph_nodes(array $schema, array $subject_of = array(), array $faq_entities = array()) {
    if ($subject_of !== array()) {
        $schema['subjectOf'] = $subject_of;
    }

    $nodes = array($schema);

    if ($faq_entities !== array()) {
        $permalink = untrailingslashit((string) ($schema['url'] ?? ''));
        $nodes[]   = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            '@id'        => $permalink . '/#faq',
            'url'        => $permalink,
            'isPartOf'   => array('@id' => ($schema['@id'] ?? $permalink . '/#webpage')),
            'mainEntity' => $faq_entities,
        );
    }

    return $nodes;
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_query_faq_posts_for_page($post_id) {
    if (!function_exists('get_field')) {
        return array();
    }

    $terms = get_terms(array(
        'taxonomy'   => 'faq_topics',
        'hide_empty' => true,
    ));

    if (is_wp_error($terms) || $terms === array()) {
        return array();
    }

    $term_ids = array_map(static function ($term) {
        return (int) $term->term_id;
    }, $terms);

    $faq_ids = get_posts(array(
        'post_type'              => 'faq',
        'post_status'            => 'publish',
        'numberposts'            => -1,
        'tax_query'              => array(
            array(
                'taxonomy' => 'faq_topics',
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ),
        ),
        'orderby'                => array('menu_order' => 'ASC', 'title' => 'ASC'),
        'suppress_filters'       => false,
        'update_post_meta_cache' => false,
        'update_term_cache'      => false,
        'fields'                 => 'ids',
    ));

    $items = array();

    foreach ((array) $faq_ids as $faq_id) {
        $faq_id = (int) $faq_id;
        $rows   = get_field('accordion_universal', $faq_id);

        if (!is_array($rows) || $rows === array()) {
            $content = get_post_field('post_content', $faq_id);
            if (is_string($content) && trim(wp_strip_all_tags($content)) !== '') {
                $items[] = array(
                    'accordion_title'             => get_the_title($faq_id),
                    'accordion_default_content'   => array('content' => $content),
                );
            }
            continue;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $items[] = $row;
        }
    }

    return $items;
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @param array<int, array<string, mixed>> $accordion_rows
 * @param string                           $section_label
 */
function akademiata_schema_push_accordion_rows(array &$parts, array $accordion_rows, $section_label) {
    foreach ($accordion_rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $title = trim(wp_strip_all_tags((string) (
            $row['accordion_title']
            ?? $row['title']
            ?? ''
        )));

        $body = '';
        if (!empty($row['accordion_default_content']['content'])) {
            $body = (string) $row['accordion_default_content']['content'];
        } elseif (!empty($row['accordion_contact_content'])) {
            $body = wp_json_encode($row['accordion_contact_content']);
        }

        $body = trim(wp_strip_all_tags($body));
        if ($title === '' && $body === '') {
            continue;
        }

        akademiata_schema_push_has_part($parts, $section_label, $title, $body, 45);
    }
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_collect_offer_listing_posts($post_id, $limit = 48) {
    if (!function_exists('akademiata_get_offer_filter_action') || !function_exists('akademiata_get_offer_listing_query_args')) {
        return array();
    }

    $query = new WP_Query(
        akademiata_get_offer_listing_query_args(
            akademiata_get_offer_filter_action(),
            akademiata_parse_offer_filter_form_data(),
            0,
            max(1, (int) $limit)
        )
    );

    return is_array($query->posts) ? $query->posts : array();
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_collect_katalog_course_item_list($post_id) {
    $template = akademiata_schema_get_page_template_file($post_id);
    if ($template !== 'page-katalog-kierunkow.php') {
        return array();
    }

    $theme = get_template_directory();
    require_once $theme . '/configure/lp-defaults/katalog-kierunkow/fields.php';

    $fields  = akademiata_katalog_kierunkow_fields(function_exists('get_fields') ? (get_fields($post_id) ?: array()) : array());
    $courses = is_array($fields['kato_catalog_section']['courses'] ?? null) ? $fields['kato_catalog_section']['courses'] : array();
    $title   = akademiata_schema_clean_text($fields['kato_catalog_section']['title'] ?? __('Katalog kierunków', 'akademiata'));
    $url     = get_permalink($post_id);

    $elements = array();
    $position = 1;

    foreach ($courses as $course) {
        if (!is_array($course)) {
            continue;
        }

        $name = akademiata_schema_clean_text($course['title'] ?? '');
        $item_url = trim((string) ($course['details_url'] ?? ''));
        if ($name === '' || $item_url === '') {
            continue;
        }

        $elements[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'url'      => $item_url,
            'name'     => $name,
        );
        $position++;
    }

    if ($elements === array()) {
        return array();
    }

    return array(
        array(
            '@type'           => 'ItemList',
            'name'            => $title,
            'url'             => $url ? user_trailingslashit($url) : '',
            'numberOfItems'   => count($elements),
            'itemListElement' => $elements,
        ),
    );
}
