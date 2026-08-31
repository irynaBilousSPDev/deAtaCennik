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
 * @param int $post_id
 * @return string
 */
function akademiata_schema_offer_description($post_id) {
    $excerpt = trim((string) get_post_field('post_excerpt', $post_id));
    if ($excerpt !== '') {
        return wp_trim_words($excerpt, 40, '…');
    }

    $content = trim(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));
    if ($content !== '') {
        return wp_trim_words($content, 40, '…');
    }

    return '';
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
        '@context'    => 'https://schema.org',
        '@type'       => 'Course',
        '@id'         => $permalink . '#course',
        'name'        => $title,
        'url'         => $permalink,
        'provider'    => akademiata_get_schema_provider(),
        'isAccessibleForFree' => false,
    );

    $description = akademiata_schema_offer_description($post_id);
    if ($description !== '') {
        $schema['description'] = $description;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    $register_url = trim((string) get_field('register_url', $post_id));
    $offer_url    = $register_url;

    if (in_array($post_type, array('bachelor', 'master'), true)) {
        $city_terms      = akademiata_schema_get_terms($post_id, 'city');
        $mode_terms      = akademiata_schema_get_terms($post_id, 'mode');
        $language_terms  = akademiata_schema_get_terms($post_id, 'language');
        $duration_terms  = akademiata_schema_get_terms($post_id, 'duration');
        $degree_terms    = akademiata_schema_get_terms($post_id, 'degree');
        $program_terms   = akademiata_schema_get_terms($post_id, 'program');
        $title_terms     = akademiata_schema_get_terms($post_id, 'obtained_title');

        $lang_code = function_exists('akademiata_get_offer_study_language_code')
            ? akademiata_get_offer_study_language_code($post_id)
            : 'pl';
        $schema['inLanguage'] = $lang_code;

        if (!empty($program_terms)) {
            $schema['about'] = $program_terms[0]->name;
        }

        if (!empty($degree_terms)) {
            $schema['educationalLevel'] = $degree_terms[0]->name;
        }

        if (!empty($title_terms)) {
            $schema['educationalCredentialAwarded'] = $title_terms[0]->name;
        }

        if (!empty($duration_terms)) {
            $duration_text = $duration_terms[0]->name;
            $duration_iso  = akademiata_schema_parse_duration_iso($duration_text);
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
                '@type' => 'Place',
                'name'  => $city_terms[0]->name,
                'address' => array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_terms[0]->name,
                    'addressCountry'  => 'PL',
                ),
            );
        }

        $schema['hasCourseInstance'] = $course_instance;

        $logical_sync_key = trim((string) get_post_meta($post_id, 'logical_sync_key', true));
        if ($offer_url === '' && $logical_sync_key !== '') {
            $offer_url = akademiata_get_smart_apply_url_for_key($logical_sync_key);
        }

        $price_row = $logical_sync_key !== '' ? akademiata_find_bachelor_master_price_row($logical_sync_key) : null;
        if (is_array($price_row) && !empty($price_row['r12'])) {
            $currency = ($lang_code === 'en') ? 'EUR' : 'PLN';
            $schema['offers'] = array(
                '@type'         => 'Offer',
                'url'           => $offer_url !== '' ? $offer_url : $permalink,
                'price'         => (float) $price_row['r12'],
                'priceCurrency' => $currency,
                'description'   => __('Monthly tuition (12 installments)', 'akademiata'),
                'availability'  => $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
            );
        }
    } else {
        $city_terms     = akademiata_schema_get_terms($post_id, 'city_pg_mba');
        $mode_terms     = akademiata_schema_get_terms($post_id, 'form_pg_mba');
        $language_terms = akademiata_schema_get_terms($post_id, 'language_pg_mba');
        $duration_terms = akademiata_schema_get_terms($post_id, 'duration_pg_mba');
        $diploma_terms  = akademiata_schema_get_terms($post_id, 'diploma_pg_mba');
        $type_terms     = akademiata_schema_get_terms($post_id, 'type_of_study_pg_mba');

        if (!empty($language_terms)) {
            $schema['inLanguage'] = (stripos($language_terms[0]->name, 'ang') !== false) ? 'en' : 'pl';
        }

        if (!empty($type_terms)) {
            $schema['about'] = $type_terms[0]->name;
        }

        $schema['educationalLevel'] = ($post_type === 'mba')
            ? __('MBA', 'akademiata')
            : __('Postgraduate studies', 'akademiata');

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
                '@type' => 'Place',
                'name'  => $city_terms[0]->name,
                'address' => array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_terms[0]->name,
                    'addressCountry'  => 'PL',
                ),
            );
        }

        $schema['hasCourseInstance'] = $course_instance;

        if (function_exists('akademiata_pg_mba_get_teaser_price_text')) {
            $parsed = akademiata_schema_parse_price_text(akademiata_pg_mba_get_teaser_price_text($post_id));
            if (is_array($parsed)) {
                $schema['offers'] = array(
                    '@type'         => 'Offer',
                    'url'           => $offer_url !== '' ? $offer_url : $permalink,
                    'price'         => $parsed['price'],
                    'priceCurrency' => $parsed['currency'],
                    'description'   => __('Monthly tuition (8 installments)', 'akademiata'),
                    'availability'  => $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
                );
            }
        }
    }

    if ($register_url !== '') {
        $schema['potentialAction'] = array(
            '@type'  => 'RegisterAction',
            'target' => $register_url,
            'name'   => __('Apply', 'akademiata'),
        );
    } elseif (!empty($schema['offers']['url'])) {
        $schema['potentialAction'] = array(
            '@type'  => 'RegisterAction',
            'target' => $schema['offers']['url'],
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
