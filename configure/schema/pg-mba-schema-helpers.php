<?php
/**
 * PG/MBA schema helpers — accordion ACF, cennik, kontakt (shared extractors).
 *
 * Template: template-parts/content/content-single-pg-mba.php
 *
 * @package akademiata
 */

/**
 * WPML-safe city slug (same logic as content-single-pg-mba.php).
 */
function akademiata_schema_get_translated_city_slug($slug, $taxonomy) {
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term || is_wp_error($term)) {
        return $slug;
    }

    $mapped_id = function_exists('apply_filters')
        ? (int) apply_filters('wpml_object_id', (int) $term->term_id, $taxonomy, true)
        : (int) $term->term_id;

    if ($mapped_id > 0) {
        $mapped_term = get_term($mapped_id, $taxonomy);
        if ($mapped_term && !is_wp_error($mapped_term)) {
            return (string) $mapped_term->slug;
        }
    }

    return $slug;
}

/**
 * @param array<string, mixed> $item
 */
function akademiata_schema_pg_mba_accordion_notice_visible(array $item) {
    $today = current_time('Ymd');

    $visible_from = !empty($item['accordion_notice_visible_from'])
        ? preg_replace('/[^0-9]/', '', (string) $item['accordion_notice_visible_from'])
        : '';
    $visible_until = !empty($item['accordion_notice_visible_until'])
        ? preg_replace('/[^0-9]/', '', (string) $item['accordion_notice_visible_until'])
        : '';

    if ($visible_from !== '' && $today < $visible_from) {
        return false;
    }
    if ($visible_until !== '' && $today > $visible_until) {
        return false;
    }

    return true;
}

/**
 * @param array<string, mixed> $item
 */
function akademiata_schema_pg_mba_accordion_item_text(array $item) {
    $chunks = array();

    if (akademiata_schema_pg_mba_accordion_notice_visible($item)) {
        $notice_title = trim((string) ($item['accordion_notice_title'] ?? ''));
        if ($notice_title !== '') {
            $chunks[] = $notice_title;
        }
        $notice_content = trim(wp_strip_all_tags((string) ($item['accordion_notice_content'] ?? '')));
        if ($notice_content !== '') {
            $chunks[] = $notice_content;
        }
    }

    $content_data = $item['accordion_contact_content'] ?? $item['accordion_default_content'] ?? null;
    if (!is_array($content_data)) {
        return trim(implode("\n\n", array_filter($chunks)));
    }

    $default_content = trim(wp_strip_all_tags((string) ($content_data['content'] ?? '')));
    if ($default_content !== '') {
        $chunks[] = $default_content;
    }

    $repeater = $content_data['contact_repeater'] ?? array();
    if (!is_array($repeater)) {
        return trim(implode("\n\n", array_filter($chunks)));
    }

    foreach ($repeater as $block) {
        if (!is_array($block)) {
            continue;
        }

        foreach (array('title_position', 'title_name', 'address', 'additional_description') as $key) {
            $value = trim(wp_strip_all_tags((string) ($block[ $key ] ?? '')));
            if ($value !== '') {
                $chunks[] = $value;
            }
        }

        if (!empty($block['additional_description_right'])) {
            $chunks[] = trim(wp_strip_all_tags((string) $block['additional_description_right']));
        }

        if (!empty($block['contact_purpose']) && is_array($block['contact_purpose'])) {
            foreach (array('contact_purpose_left', 'contact_purpose_right') as $key) {
                $value = trim(wp_strip_all_tags((string) ($block['contact_purpose'][ $key ] ?? '')));
                if ($value !== '') {
                    $chunks[] = $value;
                }
            }
        }

        foreach (array('phones', 'additional_phones') as $phone_key) {
            if (empty($block[ $phone_key ]) || !is_array($block[ $phone_key ])) {
                continue;
            }
            foreach ($block[ $phone_key ] as $phone_row) {
                $phone = trim((string) (is_array($phone_row) ? ($phone_row['phone'] ?? '') : ''));
                if ($phone !== '') {
                    $chunks[] = $phone;
                }
            }
        }

        if (!empty($block['emails']) && is_array($block['emails'])) {
            foreach ($block['emails'] as $email_row) {
                $email = trim((string) (is_array($email_row) ? ($email_row['email'] ?? '') : ''));
                if ($email !== '') {
                    $chunks[] = $email;
                }
            }
        }
    }

    return trim(implode("\n\n", array_filter($chunks)));
}

/**
 * Short robot summary from accordion title + inner content.
 *
 * @param array<string, mixed> $item
 */
function akademiata_schema_pg_mba_accordion_item_label(array $item) {
    $title = trim(wp_strip_all_tags((string) ($item['accordion_title'] ?? '')));
    $text  = akademiata_schema_pg_mba_accordion_item_text($item);

    return akademiata_schema_robot_summary($title, $text, 18);
}

/**
 * @param array<string, mixed> $item
 */
function akademiata_schema_pg_mba_accordion_item_summary(array $item) {
    $title = trim(wp_strip_all_tags((string) ($item['accordion_title'] ?? '')));
    $text  = akademiata_schema_pg_mba_accordion_item_text($item);

    return akademiata_schema_robot_summary($title, $text, 40);
}

/**
 * @param mixed $accordion
 * @return string[]
 */
function akademiata_schema_pg_mba_accordion_labels($accordion) {
    $labels = array();
    if (!is_array($accordion)) {
        return $labels;
    }

    foreach ($accordion as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = akademiata_schema_pg_mba_accordion_item_label($item);
        if ($label !== '') {
            $labels[] = $label;
        }
    }

    return $labels;
}

/**
 * @param mixed  $accordion
 * @param string $section_label
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_pg_mba_accordion_has_parts($accordion, $section_label) {
    $parts = array();
    if (!is_array($accordion)) {
        return $parts;
    }

    foreach ($accordion as $item) {
        if (!is_array($item)) {
            continue;
        }

        $title   = trim(wp_strip_all_tags((string) ($item['accordion_title'] ?? '')));
        $text    = akademiata_schema_pg_mba_accordion_item_text($item);
        $summary = akademiata_schema_pg_mba_accordion_item_summary($item);

        if ($summary === '') {
            continue;
        }

        $part = array(
            '@type'       => 'CreativeWork',
            'name'        => $title !== '' ? $title : $section_label,
            'description' => $summary,
        );

        if ($section_label !== '' && $title !== '') {
            $part['isPartOf'] = array(
                '@type' => 'CreativeWork',
                'name'  => $section_label,
            );
        }

        $parts[] = $part;
    }

    return $parts;
}

/**
 * All accordion + section description blocks from the PG/MBA single template.
 *
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_pg_mba_collect_has_parts($post_id) {
    $sections = array(
        array(
            'title'     => get_field('why_study_title', $post_id) ?: __('Dlaczego warto studiować', 'akademiata'),
            'sub_title' => get_field('why_study_sub_title', $post_id),
            'desc'      => get_field('why_study_description', $post_id),
            'accordion' => get_field('why_study_accordion', $post_id),
        ),
        array(
            'title'     => __('Program i struktura studiów', 'akademiata'),
            'sub_title' => get_field('study_program_structure_sub_title', $post_id),
            'desc'      => get_field('study_program_structure_description', $post_id),
            'accordion' => get_field('study_program_structure_accordion', $post_id),
        ),
        array(
            'title'     => get_field('discounts_title', $post_id) ?: __('Zniżki', 'akademiata'),
            'sub_title' => get_field('discounts_sub_title', $post_id),
            'desc'      => get_field('discounts_description', $post_id),
            'accordion' => get_field('discounts_accordion', $post_id),
        ),
        array(
            'title'     => get_field('admission_rules_title', $post_id) ?: __('Zasady rekrutacji', 'akademiata'),
            'sub_title' => get_field('admission_rules_sub_title', $post_id),
            'desc'      => get_field('admission_rules_description', $post_id),
            'accordion' => get_field('admission_rules_accordion', $post_id),
        ),
    );

    $parts = array();

    foreach ($sections as $section) {
        $section_label = trim((string) $section['title']);
        if ($section_label === '') {
            continue;
        }

        $intro_summary = akademiata_schema_robot_summary(
            (string) ($section['sub_title'] ?? ''),
            (string) ($section['desc'] ?? ''),
            40
        );
        if ($intro_summary !== '') {
            $parts[] = array(
                '@type'       => 'CreativeWork',
                'name'        => $section_label,
                'description' => $intro_summary,
            );
        }

        $parts = array_merge($parts, akademiata_schema_pg_mba_accordion_has_parts($section['accordion'], $section_label));
    }

    $more_info = trim(wp_strip_all_tags((string) get_field('more_info', $post_id)));
    if ($more_info !== '') {
        $parts[] = array(
            '@type'       => 'CreativeWork',
            'name'        => __('Tuition fees — additional information', 'akademiata'),
            'description' => akademiata_schema_robot_summary('', $more_info, 40),
        );
    }

    return $parts;
}

/**
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_pg_mba_payment_offers($post_id, $permalink, $register_url) {
    $payments     = get_field('payments', $post_id);
    $offers       = array();
    $availability = $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder';
    $base_url     = $register_url !== '' ? $register_url : $permalink;

    if (!is_array($payments)) {
        return $offers;
    }

    foreach ($payments as $key => $item) {
        if (!is_array($item)) {
            continue;
        }

        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            if ((int) $key === 0) {
                $title = __('Recruitment fee (one-time)', 'akademiata');
            } elseif ((int) $key === 1) {
                $title = __('Entry fee (one-time)', 'akademiata');
            }
        }

        $price    = trim((string) ($item['price'] ?? ''));
        $currency = trim((string) ($item['currency'] ?? ''));
        if ($title === '' || $price === '') {
            continue;
        }

        $parsed = akademiata_schema_parse_price_text($price . ' ' . $currency);
        if (!is_array($parsed)) {
            continue;
        }

        $offer = array(
            '@type'         => 'Offer',
            'name'          => $title,
            'url'           => $base_url,
            'price'         => $parsed['price'],
            'priceCurrency' => $parsed['currency'],
            'availability'  => $availability,
        );

        $description = trim(wp_strip_all_tags((string) ($item['description'] ?? '')));
        if ($description !== '') {
            $offer['description'] = akademiata_schema_trim_text($description, 40);
        }

        $offers[] = $offer;
    }

    return $offers;
}

/**
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_pg_mba_table_offers($post_id, $permalink, $register_url) {
    if (!function_exists('akademiata_pg_mba_get_price_tabs') || !function_exists('akademiata_pg_mba_price_columns')) {
        return array();
    }

    $tabs         = akademiata_pg_mba_get_price_tabs($post_id);
    $columns      = akademiata_pg_mba_price_columns();
    $offers       = array();
    $availability = $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder';
    $base_url     = $register_url !== '' ? $register_url : $permalink;

    $column_titles = array(
        get_field('title_first_column', $post_id) ?: __('1 installment', 'akademiata'),
        get_field('title_second_column', $post_id) ?: __('2 installments', 'akademiata'),
        get_field('title_third_column', $post_id) ?: __('4 installments', 'akademiata'),
        get_field('title_fourth_column', $post_id) ?: __('8 installments', 'akademiata'),
        get_field('title_fifth_column', $post_id) ?: __('6 installments', 'akademiata'),
        get_field('title_sixth_column', $post_id) ?: __('9 installments', 'akademiata'),
    );

    foreach ($tabs as $tab_key => $tab) {
        $currency = ($tab_key === 'part_time') ? 'EUR' : 'PLN';
        $rows     = is_array($tab['data'] ?? null) ? $tab['data'] : array();
        $row      = is_array($rows[0] ?? null) ? $rows[0] : array();

        foreach ($columns as $index => $col) {
            $col_data = is_array($row[ $col['key'] ] ?? null) ? $row[ $col['key'] ] : array();
            $flag     = $col_data[ $col['flag'] ] ?? array();
            $has_promo = is_array($flag) && in_array('promotion', $flag, true);

            $price_raw = '';
            if ($has_promo && !empty($col_data[ $col['promo'] ])) {
                $price_raw = (string) $col_data[ $col['promo'] ];
            } elseif (!empty($col_data[ $col['normal'] ])) {
                $price_raw = (string) $col_data[ $col['normal'] ];
            }

            $price_raw = trim(str_replace(array(' ', ','), array('', '.'), $price_raw));
            if ($price_raw === '' || !is_numeric($price_raw)) {
                continue;
            }

            $offers[] = array(
                '@type'         => 'Offer',
                'name'          => trim((string) $tab['label']) . ' — ' . ($column_titles[ $index ] ?? ''),
                'url'           => $base_url,
                'price'         => (float) $price_raw,
                'priceCurrency' => $currency,
                'availability'  => $availability,
            );
        }
    }

    return $offers;
}

/**
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_pg_mba_collect_offers($post_id, $permalink, $register_url, array $campus_place = array()) {
    $offers = array_merge(
        akademiata_schema_pg_mba_payment_offers($post_id, $permalink, $register_url),
        akademiata_schema_pg_mba_table_offers($post_id, $permalink, $register_url)
    );

    if ($offers === array() && function_exists('akademiata_pg_mba_get_teaser_price_text')) {
        $parsed = akademiata_schema_parse_price_text(akademiata_pg_mba_get_teaser_price_text($post_id));
        if (is_array($parsed)) {
            $offers[] = array(
                '@type'         => 'Offer',
                'name'          => __('Monthly tuition (8 installments)', 'akademiata'),
                'url'           => $register_url !== '' ? $register_url : $permalink,
                'price'         => $parsed['price'],
                'priceCurrency' => $parsed['currency'],
                'availability'  => $register_url !== '' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
            );
        }
    }

    return akademiata_schema_offers_with_location($offers, $campus_place);
}

/**
 * Mirrors contact options resolution in content-single-pg-mba.php.
 *
 * @return string|null ACF options page slug/post_id
 */
function akademiata_schema_pg_mba_contact_options_id($post_id, $post_type) {
    if (!in_array($post_type, array('postgraduate', 'mba'), true)) {
        return null;
    }

    $wroclaw_slug  = akademiata_schema_get_translated_city_slug('wroclaw', 'city_pg_mba');
    $warszawa_slug = akademiata_schema_get_translated_city_slug('warszawa', 'city_pg_mba');
    $online_slug   = akademiata_schema_get_translated_city_slug('online', 'city_pg_mba');

    if ($post_type === 'postgraduate' && has_term($wroclaw_slug, 'city_pg_mba', $post_id)) {
        return 'contact_postgraduate';
    }
    if ($post_type === 'mba' && has_term($wroclaw_slug, 'city_pg_mba', $post_id)) {
        return 'contact_mba';
    }
    if (in_array($post_type, array('mba', 'postgraduate'), true) && has_term($warszawa_slug, 'city_pg_mba', $post_id)) {
        return 'contact_warsaw';
    }
    if ($post_type === 'mba' && has_term($online_slug, 'city_pg_mba', $post_id)) {
        return 'contact_mba';
    }
    if ($post_type === 'postgraduate' && has_term($online_slug, 'city_pg_mba', $post_id)) {
        return 'contact_postgraduate';
    }

    return null;
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_schema_pg_mba_contact_point($post_id, $post_type) {
    $options_id = akademiata_schema_pg_mba_contact_options_id($post_id, $post_type);
    if ($options_id === null) {
        return null;
    }

    $contact = get_field('contact_content', $options_id);
    if (!is_array($contact) || $contact === array()) {
        return null;
    }

    $warszawa_slug = akademiata_schema_get_translated_city_slug('warszawa', 'city_pg_mba');

    $point = array('@type' => 'ContactPoint');
    $name  = trim(wp_strip_all_tags((string) ($contact['title'] ?? '')));
    if ($name !== '') {
        $point['name'] = $name;
    }

    $address = trim(wp_strip_all_tags((string) ($contact['address'] ?? '')));
    if ($address !== '') {
        $point['areaServed'] = $address;
    }

    if (!empty($contact['phones']) && is_array($contact['phones'])) {
        foreach ($contact['phones'] as $row) {
            $number = trim((string) (is_array($row) ? ($row['number'] ?? '') : ''));
            if ($number !== '') {
                $point['telephone'] = $number;
                break;
            }
        }
    }

    $email = '';
    if (has_term($warszawa_slug, 'city_pg_mba', $post_id)) {
        if ($post_type === 'postgraduate' && !empty($contact['email_warsaw_postgraduate'])) {
            $email = sanitize_email((string) $contact['email_warsaw_postgraduate']);
        }
        if ($post_type === 'mba' && !empty($contact['email_warsaw_mba'])) {
            $email = sanitize_email((string) $contact['email_warsaw_mba']);
        }
    } elseif (!empty($contact['email'])) {
        $email = sanitize_email((string) $contact['email']);
    }

    if ($email !== '') {
        $point['email'] = $email;
    }

    if (count($point) === 1) {
        return null;
    }

    $point['contactType'] = __('Admissions', 'akademiata');

    return $point;
}
