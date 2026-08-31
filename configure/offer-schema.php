<?php
/**
 * JSON-LD Course schema for offer singles (bachelor, master, postgraduate, mba).
 *
 * @package akademiata
 */

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
 * Find bachelor/master price row by logical_sync_key across RAW buckets.
 *
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

    foreach ($json['RAW'] as $lang_bucket => $cities) {
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
                    if (!is_array($row)) {
                        continue;
                    }
                    if (($row['ak'] ?? '') === $logical_sync_key) {
                        return $row;
                    }
                }
            }
        }
    }

    return null;
}

/**
 * @param string $logical_sync_key
 * @return string
 */
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
            if (!is_array($row)) {
                continue;
            }
            if (($row['key'] ?? '') === $logical_sync_key && !empty($row['url'])) {
                return (string) $row['url'];
            }
        }
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
            || strpos($hay, 'niestacjonarn') !== false
            || strpos($hay, 'zaoczn') !== false
            || strpos($hay, 'sobot') !== false
        ) {
            return 'blended';
        }
        if (strpos($hay, 'stacjonarn') !== false) {
            return 'onsite';
        }
    }

    return 'onsite';
}

/**
 * @param string $text
 * @return string ISO 8601 duration or empty.
 */
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
 * @return string
 */
function akademiata_schema_first_term_name($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    return (string) $terms[0]->name;
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
 * @param string $text e.g. "890 zł/month"
 * @return array{price: float, currency: string}|null
 */
function akademiata_schema_parse_price_text($text) {
    $text = trim(wp_strip_all_tags((string) $text));
    if ($text === '') {
        return null;
    }

    if (!preg_match('/([\d\s.,]+)\s*(zł|pln|€|eur)/iu', $text, $m)) {
        return null;
    }

    $number = (float) str_replace(array(' ', ','), array('', '.'), $m[1]);
    if ($number <= 0) {
        return null;
    }

    $currency_token = strtolower($m[2]);
    $currency       = in_array($currency_token, array('€', 'eur'), true) ? 'EUR' : 'PLN';

    return array(
        'price'    => $number,
        'currency' => $currency,
    );
}

/**
 * @return array<string, mixed>
 */
function akademiata_get_schema_provider() {
    return array(
        '@type' => 'EducationalOrganization',
        '@id'   => home_url('/') . '#organization',
        'name'  => 'Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie',
        'url'   => home_url('/'),
    );
}

/**
 * @param string $text
 * @param int    $words
 * @return string
 */
function akademiata_schema_trim_text($text, $words = 55) {
    $text = trim(wp_strip_all_tags((string) $text));
    if ($text === '') {
        return '';
    }

    return wp_trim_words($text, $words, '…');
}

/**
 * @param mixed $accordion
 * @return string[]
 */
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

/**
 * @param int $post_id
 * @return string[]
 */
function akademiata_schema_term_names($post_id, $taxonomy) {
    $terms = akademiata_schema_get_terms($post_id, $taxonomy);
    if ($terms === array()) {
        return array();
    }

    return array_values(array_filter(array_map(static function ($term) {
        return trim((string) $term->name);
    }, $terms)));
}

/**
 * @param int    $post_id
 * @param string $post_type
 * @return string
 */
function akademiata_schema_build_description($post_id, $post_type) {
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

    $content = (string) get_post_field('post_content', $post_id);
    return akademiata_schema_trim_text($content, 55);
}

/**
 * @param int    $post_id
 * @param string $post_type
 * @return string[]
 */
function akademiata_schema_collect_keywords($post_id, $post_type) {
    $taxonomies = array('post_tag');

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $taxonomies = array_merge($taxonomies, array('program', 'city', 'mode', 'language', 'degree', 'department', 'promotions'));
    } else {
        $taxonomies = array_merge($taxonomies, array('city_pg_mba', 'type_of_study_pg_mba', 'form_pg_mba', 'language_pg_mba', 'offer_theme_pg_mba'));
    }

    $keywords = array();
    foreach ($taxonomies as $taxonomy) {
        $keywords = array_merge($keywords, akademiata_schema_term_names($post_id, $taxonomy));
    }

    return array_values(array_unique(array_filter($keywords)));
}

/**
 * @param int    $post_id
 * @param string $post_type
 * @return string[]
 */
function akademiata_schema_collect_teaches($post_id, $post_type) {
    $teaches = array();

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $subjects = get_field('subjects_study', $post_id);
        if (is_array($subjects)) {
            $teaches = akademiata_schema_accordion_titles($subjects['subjects_study_accordion'] ?? array(), 'title');
        }
    } else {
        $teaches = akademiata_schema_accordion_titles(get_field('study_program_structure_accordion', $post_id));
    }

    return array_slice(array_values(array_unique(array_filter($teaches))), 0, 20);
}

/**
 * @param int $post_id
 * @return string[]
 */
function akademiata_schema_collect_prerequisites($post_id) {
    $items = array();
    $post_type = get_post_type($post_id);

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $program_for_you = get_field('program_for_you', $post_id);
        if (is_array($program_for_you) && !empty($program_for_you['cards'])) {
            foreach ($program_for_you['cards'] as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $title = trim(wp_strip_all_tags((string) ($card['title'] ?? '')));
                if ($title !== '') {
                    $items[] = $title;
                }
            }
        }

        $recruitment = get_field('recruitment_rules', $post_id);
        if (is_array($recruitment) && !empty($recruitment['steps'])) {
            foreach ($recruitment['steps'] as $step) {
                if (!is_array($step)) {
                    continue;
                }
                $text = trim(wp_strip_all_tags((string) ($step['text'] ?? '')));
                if ($text !== '') {
                    $items[] = $text;
                }
            }
        }
    } else {
        $items = akademiata_schema_accordion_titles(get_field('admission_rules_accordion', $post_id));
    }

    return array_slice(array_values(array_unique(array_filter($items))), 0, 12);
}

/**
 * @param int $post_id
 * @return int|null
 */
function akademiata_schema_get_ects_credits($post_id) {
    $program_info = get_field('program_info', $post_id);
    if (!is_array($program_info) || empty($program_info['ects'])) {
        return null;
    }

    $ects = (int) preg_replace('/\D+/', '', (string) $program_info['ects']);
    return $ects > 0 ? $ects : null;
}

/**
 * @param array<string, mixed> $price_row
 * @param string               $offer_url
 * @param string               $register_url
 * @param string               $lang_code
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_build_bachelor_master_offers($price_row, $offer_url, $register_url, $lang_code) {
    $currency     = ($lang_code === 'en') ? 'EUR' : 'PLN';
    $availability = $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder';
    $base_url     = $offer_url !== '' ? $offer_url : '';
    $offers       = array();

    $map = array(
        'r12'  => __('Monthly tuition (12 installments)', 'akademiata'),
        'r10'  => __('Monthly tuition (10 installments)', 'akademiata'),
        'rekr' => __('Recruitment fee', 'akademiata'),
        'wps'  => __('Entry fee', 'akademiata'),
    );

    foreach ($map as $key => $label) {
        if (empty($price_row[ $key ])) {
            continue;
        }
        $offers[] = array(
            '@type'         => 'Offer',
            'name'          => $label,
            'url'           => $base_url,
            'price'         => (float) $price_row[ $key ],
            'priceCurrency' => $currency,
            'availability'  => $availability,
        );
    }

    return $offers;
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>
 */
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

    $people_ids = array_slice(array_values(array_unique(array_filter($people_ids))), 0, 8);
    $instructors = array();

    foreach ($people_ids as $person_id) {
        $name = get_the_title($person_id);
        if ($name === '') {
            continue;
        }
        $person = array(
            '@type' => 'Person',
            'name'  => $name,
        );
        $role = trim((string) get_field('cadre_role', $person_id));
        if ($role !== '') {
            $person['jobTitle'] = $role;
        }
        $instructors[] = $person;
    }

    return $instructors;
}

/**
 * @param int $post_id
 * @return string
 */
function akademiata_schema_study_program_pdf_url($post_id) {
    $post_type = get_post_type($post_id);

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $study_program = get_field('study_program', $post_id);
        if (is_array($study_program) && !empty($study_program['button']['button']['button_link'])) {
            return esc_url_raw((string) $study_program['button']['button']['button_link']);
        }
        return '';
    }

    $button = get_field('study_program_structure_button', $post_id);
    return $button ? esc_url_raw((string) $button) : '';
}

/**
 * @param int $post_id
 * @return string
 */
function akademiata_schema_offer_description($post_id) {
    return akademiata_schema_build_description($post_id, (string) get_post_type($post_id));
}

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_offer_course_schema($post_id) {
    $post_id   = (int) $post_id;
    $post_type = get_post_type($post_id);

    if (!in_array($post_type, array('bachelor', 'master', 'postgraduate', 'mba'), true)) {
        return null;
    }

    if (get_post_status($post_id) !== 'publish') {
        return null;
    }

    $permalink = get_permalink($post_id);
    $title     = get_the_title($post_id);
    if (!$permalink || $title === '') {
        return null;
    }

    $schema = array(
        '@context'            => 'https://schema.org',
        '@type'               => 'Course',
        '@id'                 => $permalink . '#course',
        'name'                => $title,
        'url'                 => $permalink,
        'provider'            => akademiata_get_schema_provider(),
        'isAccessibleForFree' => false,
        'identifier'          => (string) $post_id,
    );

    $description = akademiata_schema_build_description($post_id, $post_type);
    if ($description !== '') {
        $schema['description'] = $description;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    $keywords = akademiata_schema_collect_keywords($post_id, $post_type);
    if ($keywords !== array()) {
        $schema['keywords'] = implode(', ', $keywords);
    }

    $teaches = akademiata_schema_collect_teaches($post_id, $post_type);
    if ($teaches !== array()) {
        $schema['teaches'] = $teaches;
    }

    $prerequisites = akademiata_schema_collect_prerequisites($post_id);
    if ($prerequisites !== array()) {
        $schema['coursePrerequisites'] = $prerequisites;
    }

    $program_pdf = akademiata_schema_study_program_pdf_url($post_id);
    if ($program_pdf !== '') {
        $schema['workFeatured'] = array(
            '@type'      => 'CreativeWork',
            'name'       => __('Study program (PDF)', 'akademiata'),
            'url'        => $program_pdf,
            'encodingFormat' => 'application/pdf',
        );
    }

    $schema['audience'] = array(
        '@type'        => 'EducationalAudience',
        'audienceType' => __('Prospective students', 'akademiata'),
    );

    $register_url = trim((string) get_field('register_url', $post_id));
    $offer_url    = $register_url;

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $city_terms     = akademiata_schema_get_terms($post_id, 'city');
        $mode_terms     = akademiata_schema_get_terms($post_id, 'mode');
        $degree_terms   = akademiata_schema_get_terms($post_id, 'degree');
        $program_terms  = akademiata_schema_get_terms($post_id, 'program');
        $title_terms    = akademiata_schema_get_terms($post_id, 'obtained_title');
        $language_names = akademiata_schema_term_names($post_id, 'language');

        $lang_code = function_exists('akademiata_get_offer_study_language_code')
            ? akademiata_get_offer_study_language_code($post_id)
            : 'pl';
        $schema['inLanguage'] = $lang_code;

        if ($language_names !== array()) {
            $schema['availableLanguage'] = $language_names;
        }

        if (!empty($program_terms)) {
            $schema['about'] = $program_terms[0]->name;
        }

        if (!empty($degree_terms)) {
            $schema['educationalLevel'] = $degree_terms[0]->name;
            $schema['learningResourceType'] = $degree_terms[0]->name;
        }

        if (!empty($title_terms)) {
            $schema['educationalCredentialAwarded'] = $title_terms[0]->name;
        }

        if (!empty($duration_terms)) {
            $duration_text = akademiata_schema_term_names($post_id, 'duration');
            $duration_text = $duration_text[0] ?? '';
            $duration_iso  = akademiata_schema_parse_duration_iso($duration_text);
            if ($duration_iso !== '') {
                $schema['timeRequired'] = $duration_iso;
            }
        }

        $ects = akademiata_schema_get_ects_credits($post_id);
        if ($ects !== null) {
            $schema['numberOfCredits'] = $ects;
        }

        $course_instance = array(
            '@type'      => 'CourseInstance',
            'courseMode' => akademiata_schema_course_mode_from_terms($mode_terms),
        );

        if (!empty($city_terms)) {
            $course_instance['location'] = array(
                '@type'   => 'Place',
                'name'    => $city_terms[0]->name,
                'address' => array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_terms[0]->name,
                    'addressCountry'  => 'PL',
                ),
            );
        }

        $schema['hasCourseInstance'] = $course_instance;

        $logical_sync_key = trim((string) get_post_meta($post_id, 'logical_sync_key', true));
        if ($logical_sync_key !== '') {
            $schema['courseCode'] = $logical_sync_key;
        }

        if ($offer_url === '' && $logical_sync_key !== '') {
            $offer_url = akademiata_get_smart_apply_url_for_key($logical_sync_key);
        }

        $price_row = $logical_sync_key !== '' ? akademiata_find_bachelor_master_price_row($logical_sync_key) : null;
        if (is_array($price_row)) {
            if (!empty($price_row['k']) && empty($schema['about'])) {
                $schema['about'] = (string) $price_row['k'];
            }
            if (!empty($price_row['s'])) {
                $schema['alternativeHeadline'] = (string) $price_row['s'];
            }
            if (!empty($price_row['ps'])) {
                $schema['sameAs'] = (string) $price_row['ps'];
            }

            $offers = akademiata_schema_build_bachelor_master_offers(
                $price_row,
                $offer_url !== '' ? $offer_url : $permalink,
                $register_url,
                $lang_code
            );
            if ($offers !== array()) {
                $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
            }
        }
    } else {
        $city_terms     = akademiata_schema_get_terms($post_id, 'city_pg_mba');
        $mode_terms     = akademiata_schema_get_terms($post_id, 'form_pg_mba');
        $duration_terms = akademiata_schema_get_terms($post_id, 'duration_pg_mba');
        $diploma_terms  = akademiata_schema_get_terms($post_id, 'diploma_pg_mba');
        $type_terms     = akademiata_schema_get_terms($post_id, 'type_of_study_pg_mba');
        $language_names = akademiata_schema_term_names($post_id, 'language_pg_mba');

        if ($language_names !== array()) {
            $schema['availableLanguage'] = $language_names;
            $schema['inLanguage']        = (stripos($language_names[0], 'ang') !== false) ? 'en' : 'pl';
        }

        if (!empty($type_terms)) {
            $schema['about'] = $type_terms[0]->name;
        }

        $schema['educationalLevel'] = ($post_type === 'mba')
            ? __('MBA', 'akademiata')
            : __('Postgraduate studies', 'akademiata');
        $schema['learningResourceType'] = $schema['educationalLevel'];

        if (!empty($diploma_terms)) {
            $schema['educationalCredentialAwarded'] = $diploma_terms[0]->name;
        }

        if (!empty($duration_terms)) {
            $duration_iso = akademiata_schema_parse_duration_iso($duration_terms[0]->name);
            if ($duration_iso !== '') {
                $schema['timeRequired'] = $duration_iso;
            }
        }

        $course_instance = array(
            '@type'      => 'CourseInstance',
            'courseMode' => akademiata_schema_course_mode_from_terms($mode_terms),
        );

        if (!empty($city_terms)) {
            $course_instance['location'] = array(
                '@type'   => 'Place',
                'name'    => $city_terms[0]->name,
                'address' => array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_terms[0]->name,
                    'addressCountry'  => 'PL',
                ),
            );
        }

        $instructors = akademiata_schema_pg_mba_instructors($post_id);
        if ($instructors !== array()) {
            $course_instance['instructor'] = count($instructors) === 1 ? $instructors[0] : $instructors;
        }

        $schema['hasCourseInstance'] = $course_instance;

        if (function_exists('akademiata_pg_mba_get_teaser_price_text')) {
            $parsed = akademiata_schema_parse_price_text(akademiata_pg_mba_get_teaser_price_text($post_id));
            if (is_array($parsed)) {
                $schema['offers'] = array(
                    '@type'         => 'Offer',
                    'name'          => __('Monthly tuition (8 installments)', 'akademiata'),
                    'url'           => $offer_url !== '' ? $offer_url : $permalink,
                    'price'         => $parsed['price'],
                    'priceCurrency' => $parsed['currency'],
                    'availability'  => $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
                );
            }
        }
    }

    $register_target = $register_url;
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
            '@type'  => 'RegisterAction',
            'target' => $register_target,
            'name'   => __('Apply', 'akademiata'),
        );
    }

    return $schema;
}

function akademiata_should_output_offer_course_schema() {
    if (is_admin()) {
        return false;
    }

    return is_singular(array('bachelor', 'master', 'postgraduate', 'mba'));
}

function akademiata_output_offer_course_schema() {
    if (!akademiata_should_output_offer_course_schema()) {
        return;
    }

    $schema = akademiata_build_offer_course_schema((int) get_queried_object_id());
    if (!is_array($schema) || $schema === array()) {
        return;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}

add_action('wp_head', 'akademiata_output_offer_course_schema', 20);
