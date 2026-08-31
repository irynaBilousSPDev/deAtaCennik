<?php
/**
 * Shared JSON-LD helpers for per-CPT schema modules.
 *
 * @package akademiata
 */

/**
 * @param array<string, mixed>|null $schema
 */
function akademiata_schema_output_json_ld($schema) {
    if (!is_array($schema) || $schema === array()) {
        return;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}

/**
 * @param int $post_id
 * @return array{permalink: string, title: string}|null
 */
function akademiata_schema_published_post_base($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0 || get_post_status($post_id) !== 'publish') {
        return null;
    }

    $permalink = get_permalink($post_id);
    $title     = get_the_title($post_id);
    if (!$permalink || $title === '') {
        return null;
    }

    return array(
        'permalink' => $permalink,
        'title'     => $title,
    );
}

/**
 * @return array<string, mixed>|null
 */
function &akademiata_prices_json_schema_cache() {
    static $cache = null;
    return $cache;
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_get_prices_json_for_schema() {
    $cache = &akademiata_prices_json_schema_cache();
    if ($cache !== null) {
        return $cache;
    }

    $path = get_template_directory() . '/prices.json';
    if (!is_readable($path)) {
        $cache = null;
        return null;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    $cache   = is_array($decoded) ? $decoded : null;

    return $cache;
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_find_bachelor_master_price_row($logical_sync_key) {
    $logical_sync_key = trim((string) $logical_sync_key);
    if ($logical_sync_key === '') {
        return null;
    }

    $json = akademiata_get_prices_json_for_schema();
    if (!is_array($json) || empty($json['RAW']) || !is_array($json['RAW'])) {
        return null;
    }

    foreach ($json['RAW'] as $cities) {
        if (!is_array($cities)) {
            continue;
        }
        foreach ($cities as $city_bucket) {
            if (!is_array($city_bucket)) {
                continue;
            }
            foreach ($city_bucket as $rows) {
                if (!is_array($rows)) {
                    continue;
                }
                foreach ($rows as $row) {
                    if (is_array($row) && ($row['ak'] ?? '') === $logical_sync_key) {
                        return $row;
                    }
                }
            }
        }
    }

    return null;
}

function akademiata_get_smart_apply_url_for_key($logical_sync_key) {
    $logical_sync_key = trim((string) $logical_sync_key);
    if ($logical_sync_key === '') {
        return '';
    }

    $json = akademiata_get_prices_json_for_schema();
    if (!is_array($json)) {
        return '';
    }

    if (!empty($json['SA'][ $logical_sync_key ]) && is_string($json['SA'][ $logical_sync_key ])) {
        return $json['SA'][ $logical_sync_key ];
    }

    if (!empty($json['SA_ROWS']) && is_array($json['SA_ROWS'])) {
        foreach ($json['SA_ROWS'] as $row) {
            if (is_array($row) && ($row['key'] ?? '') === $logical_sync_key && !empty($row['url'])) {
                return (string) $row['url'];
            }
        }
    }

    return '';
}

function akademiata_schema_parse_duration_iso($text) {
    $text = trim(wp_strip_all_tags((string) $text));
    if ($text === '') {
        return '';
    }

    if (preg_match('/(\d+)[,.](\d+)\s*rok/i', $text, $m)) {
        $years  = (int) $m[1];
        $months = (int) round(((int) $m[2]) * 12 / 10);
        return 'P' . $years . 'Y' . ($months > 0 ? $months . 'M' : '');
    }

    if (preg_match('/(\d+)\s*rok/i', $text, $m)) {
        return 'P' . (int) $m[1] . 'Y';
    }

    if (preg_match('/(\d+)\s*lat/i', $text, $m)) {
        return 'P' . (int) $m[1] . 'Y';
    }

    if (preg_match('/(\d+)\s*semestr/i', $text, $m)) {
        $months = (int) $m[1] * 6;
        $years  = intdiv($months, 12);
        $rest   = $months % 12;
        return 'P' . $years . 'Y' . ($rest > 0 ? $rest . 'M' : '');
    }

    return '';
}

/**
 * @param int    $post_id
 * @param string $taxonomy
 * @return WP_Term[]
 */
function akademiata_schema_get_terms($post_id, $taxonomy) {
    if (in_array(get_post_type($post_id), array('bachelor', 'master'), true) && function_exists('akademiata_get_offer_terms')) {
        $terms = akademiata_get_offer_terms($post_id, $taxonomy);
        return is_array($terms) ? $terms : array();
    }

    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || !is_array($terms)) {
        return array();
    }

    return $terms;
}

/**
 * @return array{price: float, currency: string}|null
 */
function akademiata_schema_parse_price_text($text) {
    $text = trim(wp_strip_all_tags((string) $text));
    if ($text === '' || !preg_match('/([\d\s.,]+)\s*(zł|pln|€|eur)/iu', $text, $m)) {
        return null;
    }

    $number = (float) str_replace(array(' ', ','), array('', '.'), $m[1]);
    if ($number <= 0) {
        return null;
    }

    $currency_token = strtolower($m[2]);
    return array(
        'price'    => $number,
        'currency' => in_array($currency_token, array('€', 'eur'), true) ? 'EUR' : 'PLN',
    );
}

function akademiata_get_schema_provider() {
    return array(
        '@type' => 'CollegeOrUniversity',
        '@id'   => home_url('/') . '#organization',
        'name'  => 'Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie',
        'url'   => home_url('/'),
    );
}

function akademiata_schema_trim_text($text, $words = 55) {
    $text = trim(wp_strip_all_tags((string) $text));
    return $text === '' ? '' : wp_trim_words($text, $words, '…');
}

/**
 * Short crawler-facing blurb from a heading + body (e.g. accordion title + content).
 */
function akademiata_schema_robot_summary($title, $body, $word_limit = 35) {
    $title = trim(wp_strip_all_tags((string) $title));
    $body  = trim(wp_strip_all_tags((string) $body));
    $body  = preg_replace('/\s+/u', ' ', $body);

    if ($title === '' && $body === '') {
        return '';
    }
    if ($body === '') {
        return wp_trim_words($title, $word_limit, '…');
    }
    if ($title === '') {
        return wp_trim_words($body, $word_limit, '…');
    }

    $title_lower = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);
    $body_start  = function_exists('mb_substr') ? mb_substr($body, 0, max(mb_strlen($title) * 2, 120)) : substr($body, 0, max(strlen($title) * 2, 120));
    $body_lower  = function_exists('mb_strtolower') ? mb_strtolower($body_start) : strtolower($body_start);

    if (strpos($body_lower, $title_lower) !== false) {
        return wp_trim_words($body, $word_limit, '…');
    }

    return wp_trim_words($title . '. ' . $body, $word_limit, '…');
}

/**
 * Optional hand-written schema/SEO description (ACF). Checked before auto-generated text.
 */
function akademiata_schema_get_manual_description($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0 || !function_exists('get_field')) {
        return '';
    }

    $field_names = apply_filters('akademiata_schema_description_fields', array(
        'schema_seo_description',
        'seo_description',
        'offer_schema_description',
    ), $post_id);

    foreach ($field_names as $field_name) {
        $value = get_field($field_name, $post_id);
        if (is_string($value) && trim(wp_strip_all_tags($value)) !== '') {
            return akademiata_schema_trim_text($value, 55);
        }
    }

    return '';
}

/**
 * @param array<int, array<string, mixed>> $parts
 */
function akademiata_schema_push_has_part(array &$parts, $section_label, $item_title, $item_body, $word_limit = 40) {
    $item_title = trim(wp_strip_all_tags((string) $item_title));
    $summary    = akademiata_schema_robot_summary($item_title, $item_body, $word_limit);

    if ($summary === '') {
        return;
    }

    $part = array(
        '@type'       => 'CreativeWork',
        'name'        => $item_title !== '' ? $item_title : (string) $section_label,
        'description' => $summary,
    );

    $section_label = trim((string) $section_label);
    if ($section_label !== '' && $item_title !== '') {
        $part['isPartOf'] = array(
            '@type' => 'CreativeWork',
            'name'  => $section_label,
        );
    }

    $parts[] = $part;
}

function akademiata_schema_accordion_titles($accordion, $title_key = 'accordion_title') {
    $titles = array();
    if (!is_array($accordion)) {
        return $titles;
    }

    foreach ($accordion as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = '';
        if (!empty($item[ $title_key ])) {
            $title = (string) $item[ $title_key ];
        } elseif (!empty($item['title'])) {
            $title = (string) $item['title'];
        }
        $title = trim(wp_strip_all_tags($title));
        if ($title !== '') {
            $titles[] = $title;
        }
    }

    return $titles;
}

function akademiata_schema_term_names($post_id, $taxonomy) {
    $terms = akademiata_schema_get_terms($post_id, $taxonomy);
    if ($terms === array()) {
        return array();
    }

    return array_values(array_filter(array_map(static function ($term) {
        return trim((string) $term->name);
    }, $terms)));
}

function akademiata_schema_join_term_names($post_id, $taxonomy) {
    $names = akademiata_schema_term_names($post_id, $taxonomy);
    return $names === array() ? '' : implode(', ', $names);
}

function akademiata_schema_educational_program_mode(array $terms) {
    $modes = akademiata_schema_educational_program_modes($terms);
    return $modes[0] ?? 'fullTime';
}

/**
 * @return string[] schema.org program mode tokens
 */
function akademiata_schema_educational_program_modes(array $terms) {
    $modes = array();

    foreach ($terms as $term) {
        $hay = strtolower($term->slug . ' ' . $term->name);
        if (
            strpos($hay, 'online') !== false
            || strpos($hay, 'niestacjonarn') !== false
            || strpos($hay, 'zaoczn') !== false
            || strpos($hay, 'sobot') !== false
        ) {
            $modes[] = 'blendedLearning';
            continue;
        }
        if (strpos($hay, 'stacjonarn') !== false) {
            $modes[] = 'fullTime';
        }
    }

    if ($modes === array()) {
        $modes[] = 'fullTime';
    }

    return array_values(array_unique($modes));
}

function akademiata_schema_place_from_city($city_name) {
    $city_name = trim((string) $city_name);
    if ($city_name === '') {
        return array();
    }

    return array(
        '@type'   => 'Place',
        'name'    => $city_name,
        'address' => array(
            '@type'           => 'PostalAddress',
            'addressLocality' => $city_name,
            'addressCountry'  => 'PL',
        ),
    );
}

function akademiata_schema_offers_with_location(array $offers, array $place) {
    if ($place === array()) {
        return $offers;
    }

    foreach ($offers as $index => $offer) {
        if (is_array($offer)) {
            $offers[ $index ]['availableAtOrFrom'] = $place;
        }
    }

    return $offers;
}

function akademiata_schema_program_modules(array $module_titles) {
    $modules = array();
    foreach ($module_titles as $title) {
        $title = trim((string) $title);
        if ($title === '') {
            continue;
        }
        $modules[] = array(
            '@type' => 'Course',
            'name'  => $title,
        );
    }

    return $modules;
}

function akademiata_schema_append_register_action(array $schema, $register_url, $fallback_url = '') {
    $register_target = trim((string) $register_url);
    if ($register_target === '' && $fallback_url !== '') {
        $register_target = $fallback_url;
    }
    if ($register_target === '' && !empty($schema['offers'])) {
        $first_offer = is_array($schema['offers']) && isset($schema['offers']['@type'])
            ? $schema['offers']
            : (is_array($schema['offers'][0] ?? null) ? $schema['offers'][0] : null);
        if (is_array($first_offer) && !empty($first_offer['url'])) {
            $register_target = (string) $first_offer['url'];
        }
    }
    if ($register_target !== '') {
        $schema['potentialAction'] = array(
            '@type'  => 'ApplyAction',
            'target' => $register_target,
            'name'   => __('Apply', 'akademiata'),
        );
    }

    return $schema;
}

/**
 * @return array<string, mixed>
 */
function akademiata_schema_creative_work($name, $description, $url = '') {
    $item = array(
        '@type'       => 'CreativeWork',
        'name'        => (string) $name,
        'description' => (string) $description,
    );
    if ($url !== '') {
        $item['url'] = $url;
    }

    return $item;
}

/**
 * Related specialization rows from prices.json (same kierunek, non-empty s).
 *
 * @param array<string, mixed> $price_row
 * @return array<int, array{name: string, url: string}>
 */
function akademiata_schema_find_price_row_specializations(array $price_row) {
    $course_name = trim((string) ($price_row['k'] ?? ''));
    if ($course_name === '') {
        return array();
    }

    $json         = akademiata_get_prices_json_for_schema();
    $degree       = isset($price_row['deg']) ? (int) $price_row['deg'] : null;
    $current_key  = trim((string) ($price_row['ak'] ?? ''));
    $current_spec = trim((string) ($price_row['s'] ?? ''));
    $items        = array();

    if (!is_array($json) || empty($json['SA_ROWS']) || !is_array($json['SA_ROWS'])) {
        return $items;
    }

    foreach ($json['SA_ROWS'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (trim((string) ($row['k'] ?? '')) !== $course_name) {
            continue;
        }
        if ($degree !== null && isset($row['deg']) && (int) $row['deg'] !== $degree) {
            continue;
        }

        $spec_name = trim((string) ($row['s'] ?? ''));
        if ($spec_name === '') {
            continue;
        }

        $row_key = trim((string) ($row['key'] ?? ''));
        if ($current_spec !== '' && $spec_name === $current_spec) {
            continue;
        }
        if ($current_spec === '' && $row_key === $current_key) {
            continue;
        }

        $url = trim((string) ($row['url'] ?? ''));
        if ($url === '' && $row_key !== '' && !empty($json['SA'][ $row_key ])) {
            $url = (string) $json['SA'][ $row_key ];
        }
        if ($url === '' && !empty($row['ps'])) {
            $url = (string) $row['ps'];
        }

        $items[] = array(
            'name' => $spec_name,
            'url'  => $url,
        );
    }

    return $items;
}

function akademiata_schema_get_ects_credits($post_id) {
    $program_info = get_field('program_info', $post_id);
    if (!is_array($program_info) || empty($program_info['ects'])) {
        return null;
    }

    $ects = (int) preg_replace('/\D+/', '', (string) $program_info['ects']);
    return $ects > 0 ? $ects : null;
}

function akademiata_schema_build_bachelor_master_offers($price_row, $offer_url, $register_url, $lang_code, $permalink = '') {
    $currency     = ($lang_code === 'en') ? 'EUR' : 'PLN';
    $availability = $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder';
    $base_url     = $offer_url !== '' ? $offer_url : $permalink;
    $offers       = array();

    $one_time_fees = array(
        'rekr' => array(
            'name'     => __('Recruitment fee', 'akademiata'),
            'category' => __('One-time enrollment fee', 'akademiata'),
        ),
        'wps'  => array(
            'name'     => __('Entry fee', 'akademiata'),
            'category' => __('One-time enrollment fee', 'akademiata'),
        ),
    );

    foreach ($one_time_fees as $key => $meta) {
        if (!isset($price_row[ $key ]) || $price_row[ $key ] === '' || $price_row[ $key ] === null) {
            continue;
        }
        $price = (float) $price_row[ $key ];
        if ($key === 'wps' && $price <= 0) {
            continue;
        }
        $offers[] = array(
            '@type'              => 'Offer',
            'name'               => $meta['name'],
            'category'           => $meta['category'],
            'url'                => $base_url,
            'description'        => $meta['name'],
            'availability'       => $availability,
            'priceSpecification' => array(
                '@type'         => 'PriceSpecification',
                'price'         => $price,
                'priceCurrency' => $currency,
            ),
        );
    }

    $installments = array(
        'r12' => 12,
        'r10' => 10,
    );

    foreach ($installments as $key => $count) {
        if (empty($price_row[ $key ])) {
            continue;
        }
        $price = (float) $price_row[ $key ];
        $label = sprintf(
            /* translators: %d: number of monthly installments */
            __('Tuition — %d monthly installments', 'akademiata'),
            $count
        );
        $offers[] = array(
            '@type'              => 'Offer',
            'name'               => $label,
            'category'           => __('Tuition', 'akademiata'),
            'url'                => $base_url,
            'description'        => sprintf(
                /* translators: 1: installment count, 2: price, 3: currency */
                __('%1$d monthly installments of %2$s %3$s', 'akademiata'),
                $count,
                $price,
                $currency
            ),
            'availability'       => $availability,
            'priceSpecification' => array(
                '@type'         => 'UnitPriceSpecification',
                'price'         => $price,
                'priceCurrency' => $currency,
                'unitText'      => __('month', 'akademiata'),
            ),
        );
    }

    return $offers;
}

function akademiata_schema_pg_mba_instructors($post_id) {
    if (!(bool) get_field('show_cadre_section', $post_id)) {
        return array();
    }

    $people_ids = array();
    $source     = (string) get_field('cadre_source', $post_id);
    $groups     = get_field('cadre_groups', $post_id);
    $manual     = get_field('manual_cadre_people', $post_id);

    if (in_array($source, array('taxonomy', 'both'), true) && is_array($groups) && $groups !== array()) {
        $query = new WP_Query(array(
            'post_type'              => 'cadre',
            'post_status'            => 'publish',
            'posts_per_page'         => 8,
            'fields'                 => 'ids',
            'orderby'                => array('menu_order' => 'ASC', 'title' => 'ASC'),
            'suppress_filters'       => false,
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => array(
                array(
                    'taxonomy' => 'cadre_group',
                    'field'    => 'term_id',
                    'terms'    => array_map('intval', $groups),
                ),
            ),
        ));
        if (!empty($query->posts)) {
            $people_ids = array_merge($people_ids, $query->posts);
        }
        wp_reset_postdata();
    }

    if (in_array($source, array('manual', 'both'), true) && is_array($manual)) {
        $people_ids = array_merge($people_ids, array_map('intval', $manual));
    }

    $instructors = array();
    foreach (array_slice(array_values(array_unique(array_filter($people_ids))), 0, 8) as $person_id) {
        $name = get_the_title($person_id);
        if ($name === '') {
            continue;
        }
        $person = array('@type' => 'Person', 'name' => $name);
        $role   = trim((string) get_field('cadre_role', $person_id));
        if ($role !== '') {
            $person['jobTitle'] = $role;
        }
        $instructors[] = $person;
    }

    return $instructors;
}

/**
 * Taxonomies shown in single templates (mirrors render_taxonomy_* lists).
 *
 * @return string[]
 */
function akademiata_schema_template_taxonomies($post_type) {
    switch ($post_type) {
        case 'bachelor':
        case 'master':
            return array('city', 'program', 'degree', 'obtained_title', 'duration', 'language', 'mode');
        case 'postgraduate':
        case 'mba':
            return array('city_pg_mba', 'mode_course', 'type_of_study_pg_mba', 'duration_pg_mba', 'language_pg_mba', 'diploma_pg_mba', 'form_pg_mba');
        case 'courses':
            return array('city_pg_mba', 'mode_course', 'duration_course', 'language', 'instructor_course', 'price_course', 'fee_course');
        case 'exams':
            return array('exam_city', 'exam_date', 'exam_location', 'exam_price');
        default:
            return array();
    }
}

function akademiata_schema_build_description($post_id, $post_type) {
    $manual = akademiata_schema_get_manual_description($post_id);
    if ($manual !== '') {
        return $manual;
    }

    $excerpt = trim((string) get_post_field('post_excerpt', $post_id));
    if ($excerpt !== '') {
        return akademiata_schema_trim_text($excerpt, 55);
    }

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $why_study = get_field('why_study', $post_id);
        if (is_array($why_study) && !empty($why_study['why_study_cards'][0]['content'])) {
            return akademiata_schema_trim_text($why_study['why_study_cards'][0]['content'], 55);
        }
    }

    if (in_array($post_type, array('postgraduate', 'mba'), true)) {
        $description = get_field('why_study_description', $post_id);
        if (is_string($description) && trim($description) !== '') {
            return akademiata_schema_trim_text($description, 55);
        }
    }

    if ($post_type === 'courses') {
        $description = get_field('course_description', $post_id);
        if (is_string($description) && trim($description) !== '') {
            return akademiata_schema_trim_text($description, 55);
        }
    }

    if ($post_type === 'exams') {
        $subtitle = get_field('exam_subtitle', $post_id);
        if (is_string($subtitle) && trim(wp_strip_all_tags($subtitle)) !== '') {
            return akademiata_schema_trim_text($subtitle, 55);
        }

        $rows = get_field('single_exam_content', $post_id);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row) || ($row['acf_fc_layout'] ?? '') !== 'section') {
                    continue;
                }
                $text = trim(wp_strip_all_tags((string) ($row['section_text'] ?? '')));
                if ($text !== '') {
                    return akademiata_schema_trim_text($text, 55);
                }
            }
        }
    }

    $content = (string) get_post_field('post_content', $post_id);
    return akademiata_schema_trim_text($content, 55);
}

function akademiata_schema_collect_keywords($post_id, $post_type) {
    $taxonomies = array_merge(array('post_tag'), akademiata_schema_template_taxonomies($post_type));

    $keywords = array();
    foreach ($taxonomies as $taxonomy) {
        $keywords = array_merge($keywords, akademiata_schema_term_names($post_id, $taxonomy));
    }

    return array_values(array_unique(array_filter($keywords)));
}

function akademiata_schema_collect_teaches($post_id, $post_type) {
    $teaches = array();

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $subjects = get_field('subjects_study', $post_id);
        if (is_array($subjects)) {
            foreach ($subjects['subjects_study_accordion'] ?? array() as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $label = akademiata_schema_robot_summary($item['title'] ?? '', $item['content'] ?? '', 18);
                if ($label !== '') {
                    $teaches[] = $label;
                }
            }
        }
    } elseif (in_array($post_type, array('postgraduate', 'mba'), true)) {
        $teaches = akademiata_schema_accordion_titles(get_field('study_program_structure_accordion', $post_id));
    } elseif ($post_type === 'courses') {
        $teaches = akademiata_schema_accordion_titles(get_field('course_accordion', $post_id));
    }

    return array_slice(array_values(array_unique(array_filter($teaches))), 0, 20);
}

function akademiata_schema_collect_prerequisites($post_id) {
    $items     = array();
    $post_type = get_post_type($post_id);

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $program_for_you = get_field('program_for_you', $post_id);
        if (is_array($program_for_you) && !empty($program_for_you['cards'])) {
            foreach ($program_for_you['cards'] as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $label = akademiata_schema_robot_summary($card['title'] ?? '', $card['content'] ?? '', 18);
                if ($label !== '') {
                    $items[] = $label;
                }
            }
        }

        $recruitment = get_field('recruitment_rules', $post_id);
        if (is_array($recruitment) && !empty($recruitment['steps'])) {
            foreach ($recruitment['steps'] as $index => $step) {
                if (!is_array($step)) {
                    continue;
                }
                $text = trim(wp_strip_all_tags((string) ($step['text'] ?? '')));
                if ($text === '' && (int) $index === 0) {
                    $text = __('Kliknij przycisk', 'akademiata');
                } elseif ($text === '' && (int) $index === 1) {
                    $text = __('Wypełnij formularz', 'akademiata');
                }
                if ($text !== '') {
                    $items[] = akademiata_schema_robot_summary('', $text, 18);
                }
            }
        }
    } elseif (in_array($post_type, array('postgraduate', 'mba'), true)) {
        $items = akademiata_schema_pg_mba_accordion_labels(get_field('admission_rules_accordion', $post_id));
    }

    return array_slice(array_values(array_unique(array_filter($items))), 0, 12);
}

function akademiata_schema_study_program_pdf_url($post_id) {
    $post_type = get_post_type($post_id);

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $study_program = get_field('study_program', $post_id);
        if (is_array($study_program) && !empty($study_program['button']['button']['button_link'])) {
            return esc_url_raw((string) $study_program['button']['button']['button_link']);
        }
        return '';
    }

    if (in_array($post_type, array('postgraduate', 'mba'), true)) {
        $button = get_field('study_program_structure_button', $post_id);
        return $button ? esc_url_raw((string) $button) : '';
    }

    return '';
}

function akademiata_schema_program_type_label($post_type, array $degree_terms = array()) {
    if ($post_type === 'bachelor') {
        if ($degree_terms !== array()) {
            return trim((string) $degree_terms[0]->name) . ', ' . __('field of study', 'akademiata');
        }
        return __('Bachelor degree program', 'akademiata');
    }
    if ($post_type === 'master') {
        if ($degree_terms !== array()) {
            return trim((string) $degree_terms[0]->name) . ', ' . __('field of study', 'akademiata');
        }
        return __('Master degree program', 'akademiata');
    }
    if ($post_type === 'mba') {
        return 'MBA';
    }
    if ($post_type === 'postgraduate') {
        return __('Postgraduate program', 'akademiata');
    }

    if ($degree_terms !== array()) {
        return (string) $degree_terms[0]->name;
    }

    return '';
}

/**
 * @param WP_Term[] $terms
 * @return string onsite|online|blended
 */
function akademiata_schema_course_mode_from_terms(array $terms) {
    foreach ($terms as $term) {
        $hay = strtolower($term->slug . ' ' . $term->name);
        if (
            strpos($hay, 'online') !== false
            || strpos($hay, 'zdaln') !== false
        ) {
            return 'online';
        }
        if (
            strpos($hay, 'niestacjonarn') !== false
            || strpos($hay, 'zaoczn') !== false
            || strpos($hay, 'sobot') !== false
            || strpos($hay, 'hybrid') !== false
        ) {
            return 'blended';
        }
        if (strpos($hay, 'stacjonarn') !== false) {
            return 'onsite';
        }
    }

    return 'onsite';
}

function akademiata_schema_first_term_name($post_id, $taxonomy) {
    $terms = akademiata_schema_get_terms($post_id, $taxonomy);
    return $terms !== array() ? (string) $terms[0]->name : '';
}

/**
 * @return string ISO 8601 date or empty.
 */
function akademiata_schema_parse_event_date($text) {
    $text = trim(wp_strip_all_tags((string) $text));
    if ($text === '') {
        return '';
    }

    if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
    }

    if (preg_match('/\b(\d{1,2})[./](\d{1,2})[./](\d{4})\b/', $text, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }

    $timestamp = strtotime($text);
    if ($timestamp !== false) {
        return gmdate('Y-m-d', $timestamp);
    }

    return '';
}

function akademiata_schema_exam_registration_closed($post_id) {
    $terms = get_the_terms($post_id, 'exam_date');
    if (is_wp_error($terms) || !is_array($terms)) {
        return false;
    }

    foreach ($terms as $term) {
        if (isset($term->slug) && $term->slug === 'rejestracja-na-egzamin-zamknieta') {
            return true;
        }
    }

    return false;
}

function akademiata_schema_build_taxonomy_offers($post_id, array $taxonomies, $permalink, $register_url) {
    $offers       = array();
    $availability = $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder';
    $base_url     = $register_url !== '' ? $register_url : $permalink;

    foreach ($taxonomies as $taxonomy) {
        foreach (akademiata_schema_term_names($post_id, $taxonomy) as $label) {
            $parsed = akademiata_schema_parse_price_text($label);
            if (!is_array($parsed)) {
                continue;
            }
            $offers[] = array(
                '@type'         => 'Offer',
                'name'          => $label,
                'url'           => $base_url,
                'price'         => $parsed['price'],
                'priceCurrency' => $parsed['currency'],
                'availability'  => $availability,
            );
        }
    }

    return $offers;
}
