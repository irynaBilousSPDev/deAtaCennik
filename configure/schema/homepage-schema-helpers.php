<?php
/**
 * Homepage JSON-LD — rich Organization graph + dynamic subjectOf from ACF.
 *
 * @package akademiata
 */

/**
 * BCP 47 language tag (pl → pl-PL).
 */
function akademiata_schema_bcp47_language($lang_code = '') {
    $lang_code = $lang_code !== '' ? $lang_code : akademiata_schema_current_language_code();

    $map = array(
        'pl' => 'pl-PL',
        'en' => 'en-US',
        'uk' => 'uk-UA',
        'ru' => 'ru-RU',
    );

    return $map[ $lang_code ] ?? $lang_code;
}

/**
 * WPML-safe permalink for a page slug.
 */
function akademiata_schema_page_url_by_slug($slug) {
    $slug = trim((string) $slug, '/');
    if ($slug === '') {
        return '';
    }

    $page = get_page_by_path($slug);
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

    $url = get_permalink($page_id);

    return $url ? untrailingslashit($url) : '';
}

/**
 * @return array<string, string>
 */
function akademiata_schema_homepage_contact_defaults() {
    return apply_filters('akademiata_schema_homepage_contact', array(
        'street'  => 'ul. Olszewska 12',
        'zip'     => '00-792',
        'city'    => 'Warszawa',
        'country' => 'PL',
        'phone'   => '+48 22 825 80 34',
        'email'   => 'rekrutacja@akademiata.pl',
    ));
}

/**
 * @return array<string, mixed>
 */
function akademiata_get_schema_homepage_organization(array $acf_fields = array()) {
    $home    = untrailingslashit(home_url());
    $contact = akademiata_schema_homepage_contact_defaults();

    $org = array(
        '@type'         => 'CollegeOrUniversity',
        '@id'           => $home . '/#organization',
        'name'          => __('ATA | Akademia Techniczno-Artystyczna', 'akademiata'),
        'legalName'     => __('Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata'),
        'alternateName' => __('Akademia Techniczno-Artystyczna', 'akademiata'),
        'url'           => $home,
        'foundingDate'  => '1995',
        'description'   => __(
            'Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie to uczelnia oferująca studia I i II stopnia, studia podyplomowe, MBA, kursy oraz egzaminy w Warszawie, Wrocławiu i online.',
            'akademiata'
        ),
        'address'       => array(
            '@type'           => 'PostalAddress',
            'streetAddress'   => $contact['street'],
            'postalCode'      => $contact['zip'],
            'addressLocality' => $contact['city'],
            'addressCountry'  => $contact['country'],
        ),
        'telephone'     => $contact['phone'],
        'email'         => $contact['email'],
        'areaServed'    => array(
            array('@type' => 'City', 'name' => __('Warszawa', 'akademiata')),
            array('@type' => 'City', 'name' => __('Wrocław', 'akademiata')),
            array(
                '@type' => 'Place',
                'name'  => __('Online', 'akademiata'),
            ),
        ),
    );

    if (function_exists('akademiata_get_header_logo')) {
        $logo = akademiata_get_header_logo();
        if (!empty($logo['url'])) {
            $org['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $logo['url'],
            );
        }
    }

    $hero_image = akademiata_schema_homepage_hero_image_url($acf_fields);
    if ($hero_image !== '') {
        $org['image'] = array(
            '@type' => 'ImageObject',
            'url'   => $hero_image,
        );
    }

    $catalog = akademiata_schema_build_homepage_offer_catalog();
    if ($catalog !== null) {
        $org['hasOfferCatalog'] = $catalog;
    }

    $recruitment_points = akademiata_schema_welyo_recruitment_contact_points();
    if ($recruitment_points !== array()) {
        $existing = array();
        if (!empty($org['telephone']) || !empty($org['email'])) {
            $existing[] = array(
                '@type'       => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone'   => $org['telephone'] ?? '',
                'email'       => $org['email'] ?? '',
                'name'        => __('Sekretariat / rekrutacja (dane główne)', 'akademiata'),
            );
        }
        $org['contactPoint'] = array_values(array_filter(array_merge($existing, $recruitment_points)));
        if (count($org['contactPoint']) === 1) {
            $org['contactPoint'] = $org['contactPoint'][0];
        }
    }

    return $org;
}

/**
 * @param array<string, mixed> $acf_fields
 */
function akademiata_schema_homepage_hero_image_url(array $acf_fields) {
    if (!function_exists('akademiata_get_hero_slider_slides') || !function_exists('akademiata_hero_slide_image_urls')) {
        return '';
    }

    $slides = akademiata_get_hero_slider_slides($acf_fields['main_slider'] ?? array());
    if ($slides === array()) {
        return '';
    }

    $urls = akademiata_hero_slide_image_urls($slides[0]);

    return !empty($urls['desktop']) ? (string) $urls['desktop'] : '';
}

/**
 * @param array<string, mixed> $slide
 */
function akademiata_schema_homepage_hero_slide_button(array $slide) {
    $button = $slide['button'] ?? array();
    if (!is_array($button)) {
        return array();
    }

    if (isset($button['button']) && is_array($button['button'])) {
        return $button['button'];
    }

    return $button;
}

/**
 * @param array<string, mixed> $slide
 */
function akademiata_schema_homepage_hero_slide_name(array $slide) {
    $button = akademiata_schema_homepage_hero_slide_button($slide);
    $text   = akademiata_schema_clean_text($button['button_text'] ?? '');
    if ($text !== '') {
        return $text;
    }

    $title = akademiata_schema_clean_text($slide['title'] ?? '');
    if ($title !== '') {
        return $title;
    }

    $image = is_array($slide['image'] ?? null) ? $slide['image'] : array();

    return akademiata_schema_clean_text($image['alt'] ?? '');
}

/**
 * @param array<string, mixed> $slide
 */
function akademiata_schema_homepage_hero_slide_url(array $slide) {
    $button = akademiata_schema_homepage_hero_slide_button($slide);
    $url    = !empty($button['button_link']) ? (string) $button['button_link'] : '';

    return $url !== '' ? esc_url_raw($url) : '';
}

/**
 * @param array<string, mixed> $slide
 */
function akademiata_schema_homepage_hero_slide_description(array $slide) {
    $parts = array();

    $title = akademiata_schema_clean_text($slide['title'] ?? '');
    if ($title !== '') {
        $parts[] = $title;
    }

    $button = akademiata_schema_homepage_hero_slide_button($slide);
    $text   = akademiata_schema_clean_text($button['button_text'] ?? '');
    if ($text !== '' && $text !== $title) {
        $parts[] = $text;
    }

    $image = is_array($slide['image'] ?? null) ? $slide['image'] : array();
    $alt   = akademiata_schema_clean_text($image['alt'] ?? '');
    if ($alt !== '' && !in_array($alt, $parts, true)) {
        $parts[] = $alt;
    }

    return implode('. ', $parts);
}

/**
 * @param array<string, mixed> $acf_fields
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_collect_homepage_hero_slider_subject_of(array $acf_fields) {
    if (!function_exists('akademiata_get_hero_slider_slides')) {
        return array();
    }

    $slides = akademiata_get_hero_slider_slides($acf_fields['main_slider'] ?? array());
    if ($slides === array()) {
        return array();
    }

    $items = array();
    $seen  = array();

    foreach ($slides as $index => $slide) {
        if (!is_array($slide)) {
            continue;
        }

        $name = akademiata_schema_homepage_hero_slide_name($slide);
        if ($name === '') {
            $name = sprintf(
                /* translators: %d: slide number */
                __('Slajd %d', 'akademiata'),
                $index + 1
            );
        }

        $url  = akademiata_schema_homepage_hero_slide_url($slide);
        $desc = akademiata_schema_homepage_hero_slide_description($slide);
        $key  = mb_strtolower($name) . '|' . $url;
        if (isset($seen[ $key ])) {
            continue;
        }
        $seen[ $key ] = true;

        $items[] = akademiata_schema_creative_work($name, $desc !== '' ? $desc : $name, $url);
    }

    return $items;
}

/**
 * @param array<string, mixed> $acf_fields
 * @return array<string, mixed>|null
 */
function akademiata_schema_homepage_offer_sliders_subject(array $acf_fields) {
    if (empty($acf_fields['offers']) || !is_array($acf_fields['offers'])) {
        return null;
    }

    $labels = array(
        'bachelor'     => __('Studia I stopnia', 'akademiata'),
        'master'       => __('Studia II stopnia', 'akademiata'),
        'postgraduate' => __('Studia podyplomowe', 'akademiata'),
        'mba'          => __('Studia MBA', 'akademiata'),
        'courses'      => __('Kursy', 'akademiata'),
    );

    $lines = array();
    foreach ($acf_fields['offers'] as $type) {
        if (!isset($labels[ $type ])) {
            continue;
        }
        $archive = get_post_type_archive_link($type);
        $lines[] = $labels[ $type ] . ($archive ? ' — ' . untrailingslashit($archive) : '');
    }

    if ($lines === array()) {
        return null;
    }

    return akademiata_schema_creative_work(
        __('Slidery kierunków na stronie głównej', 'akademiata'),
        sprintf(
            /* translators: %s: comma-separated offer sliders */
            __('Karuzele z kierunkami i linkami do pełnej oferty: %s.', 'akademiata'),
            implode('; ', $lines)
        )
    );
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_schema_build_homepage_offer_catalog() {
    $entries = array(
        array(
            'name'        => __('Studia I stopnia', 'akademiata'),
            'url'         => get_post_type_archive_link('bachelor'),
            'type'        => 'EducationalOccupationalProgram',
            'description' => __('Studia pierwszego stopnia oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
        array(
            'name'        => __('Studia II stopnia', 'akademiata'),
            'url'         => get_post_type_archive_link('master'),
            'type'        => 'EducationalOccupationalProgram',
            'description' => __('Studia drugiego stopnia oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
        array(
            'name'        => __('Studia podyplomowe', 'akademiata'),
            'url'         => get_post_type_archive_link('postgraduate') ?: akademiata_schema_page_url_by_slug('studia-podyplomowe'),
            'type'        => 'EducationalOccupationalProgram',
            'description' => __('Studia podyplomowe oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
        array(
            'name'        => __('Studia MBA', 'akademiata'),
            'url'         => get_post_type_archive_link('mba') ?: akademiata_schema_page_url_by_slug('studia-mba'),
            'type'        => 'EducationalOccupationalProgram',
            'description' => __('Studia MBA oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
        array(
            'name'        => __('Kursy', 'akademiata'),
            'url'         => get_post_type_archive_link('courses') ?: akademiata_schema_page_url_by_slug('kursy'),
            'type'        => 'Service',
            'description' => __('Kursy oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
        array(
            'name'        => __('Egzaminy', 'akademiata'),
            'url'         => get_post_type_archive_link('exams') ?: akademiata_schema_page_url_by_slug('egzaminy'),
            'type'        => 'Service',
            'description' => __('Egzaminy oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
        array(
            'name'        => __('Studia online', 'akademiata'),
            'url'         => akademiata_schema_page_url_by_slug('oferta'),
            'type'        => 'EducationalOccupationalProgram',
            'description' => __('Studia online oferowane przez Akademię Techniczno-Artystyczną.', 'akademiata'),
        ),
    );

    $items = array();
    foreach ($entries as $entry) {
        $url = is_string($entry['url']) ? untrailingslashit($entry['url']) : '';
        if ($url === '') {
            continue;
        }

        $items[] = array(
            '@type' => 'Offer',
            'name'  => $entry['name'],
            'url'   => $url,
            'itemOffered' => array(
                '@type'       => $entry['type'],
                'name'        => $entry['name'],
                'description' => $entry['description'],
            ),
        );
    }

    if ($items === array()) {
        return null;
    }

    return array(
        '@type'            => 'OfferCatalog',
        'name'             => __('Oferta edukacyjna ATA', 'akademiata'),
        'itemListElement'  => $items,
    );
}

/**
 * @param array<string, mixed> $acf_fields
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_collect_homepage_subject_of(array $acf_fields) {
    $items = array();

    $hero_slides = akademiata_schema_collect_homepage_hero_slider_subject_of($acf_fields);
    if ($hero_slides !== array()) {
        $items = array_merge($items, $hero_slides);
    }

    $offer_types = array();
    if (!empty($acf_fields['offers']) && is_array($acf_fields['offers'])) {
        $labels = array(
            'bachelor'     => __('studia I stopnia', 'akademiata'),
            'master'       => __('studia II stopnia', 'akademiata'),
            'postgraduate' => __('studia podyplomowe', 'akademiata'),
            'mba'          => __('studia MBA', 'akademiata'),
            'courses'      => __('kursy', 'akademiata'),
        );
        foreach ($acf_fields['offers'] as $type) {
            if (isset($labels[ $type ])) {
                $offer_types[] = $labels[ $type ];
            }
        }
    }

    $offer_line = $offer_types !== array()
        ? implode(', ', $offer_types)
        : __('studia I stopnia, studia II stopnia, studia podyplomowe, studia MBA, kursy i egzaminy', 'akademiata');

    $items[] = akademiata_schema_creative_work(
        __('Oferta edukacyjna', 'akademiata'),
        sprintf(
            /* translators: %s: comma-separated offer types */
            __('Strona główna prezentuje ofertę edukacyjną ATA: %s.', 'akademiata'),
            $offer_line
        )
    );

    $offer_sliders = akademiata_schema_homepage_offer_sliders_subject($acf_fields);
    if (is_array($offer_sliders)) {
        $items[] = $offer_sliders;
    }

    $main_title = akademiata_schema_clean_text($acf_fields['main_title'] ?? '');
    if ($main_title !== '') {
        $items[] = akademiata_schema_creative_work(
            $main_title,
            $main_title
        );
    }

    $items[] = akademiata_schema_creative_work(
        __('Studia w Warszawie, Wrocławiu i online', 'akademiata'),
        __('ATA prowadzi ofertę edukacyjną dla kandydatów zainteresowanych studiami w Warszawie, Wrocławiu oraz online.', 'akademiata')
    );

    if (!empty($acf_fields['counter']) && is_array($acf_fields['counter'])) {
        $counter_bits = array();
        foreach ($acf_fields['counter'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $value = akademiata_schema_clean_text($row['counter'] ?? '');
            $label = akademiata_schema_clean_text($row['counter_title'] ?? '');
            if ($value === '' && $label === '') {
                continue;
            }
            $counter_bits[] = trim($value . ' ' . $label);
        }
        if ($counter_bits !== array()) {
            $items[] = akademiata_schema_creative_work(
                __('Liczby uczelni', 'akademiata'),
                sprintf(
                    /* translators: %s: comma-separated counter labels */
                    __('Uczelnia prezentuje na stronie głównej: %s.', 'akademiata'),
                    implode(', ', $counter_bits)
                )
            );
        }
    }

    if (function_exists('akademiata_home_rankings_fields')) {
        require_once get_template_directory() . '/configure/front-page-defaults/home-rankings/fields.php';
        $rankings = akademiata_home_rankings_fields($acf_fields['home_rankings'] ?? null);
        $rank_title = akademiata_schema_clean_text($rankings['title'] ?? '');
        $rank_lead  = akademiata_schema_clean_text($rankings['lead'] ?? '');
        if ($rank_title !== '' || $rank_lead !== '') {
            $rank_bits = array();
            if ($rank_lead !== '') {
                $rank_bits[] = $rank_lead;
            }
            if (!empty($rankings['film']) && is_array($rankings['film'])) {
                $film_title = akademiata_schema_clean_text($rankings['film']['title'] ?? '');
                $film_sub   = akademiata_schema_clean_text($rankings['film']['subtitle'] ?? '');
                if ($film_title !== '') {
                    $rank_bits[] = trim($film_title . ' ' . $film_sub);
                }
            }
            foreach (array('perspektywy', 'ela') as $block_key) {
                if (empty($rankings[ $block_key ]['stats']) || !is_array($rankings[ $block_key ]['stats'])) {
                    continue;
                }
                foreach ($rankings[ $block_key ]['stats'] as $stat) {
                    if (!is_array($stat)) {
                        continue;
                    }
                    $value = akademiata_schema_clean_text($stat['value'] ?? '');
                    $bold  = akademiata_schema_clean_text($stat['label_bold'] ?? '');
                    $label = akademiata_schema_clean_text($stat['label'] ?? '');
                    if ($value === '') {
                        continue;
                    }
                    $rank_bits[] = trim($value . ' ' . $bold . ' ' . $label);
                }
            }
            $items[] = akademiata_schema_creative_work(
                $rank_title !== '' ? $rank_title : __('Pozycja w rankingach', 'akademiata'),
                implode(' ', array_slice($rank_bits, 0, 8))
            );
            if (!empty($rankings['sources'])) {
                $items[] = akademiata_schema_creative_work(
                    __('Źródła rankingów', 'akademiata'),
                    akademiata_schema_clean_text($rankings['sources'])
                );
            }
        }
    }

    if (!empty($acf_fields['two_column_banner']) && is_array($acf_fields['two_column_banner'])) {
        foreach ($acf_fields['two_column_banner'] as $banner) {
            if (!is_array($banner)) {
                continue;
            }
            $line1 = akademiata_schema_clean_text($banner['title_line_1'] ?? '');
            $line2 = akademiata_schema_clean_text($banner['title_line_2'] ?? '');
            $name  = trim($line1 . ' ' . $line2);
            if ($name === '') {
                continue;
            }
            $url  = !empty($banner['button_link']) ? esc_url_raw($banner['button_link']) : '';
            $desc = $name;
            $btn  = akademiata_schema_clean_text($banner['button_text'] ?? '');
            if ($btn !== '') {
                $desc = $btn . ($name !== $btn ? ' — ' . $name : '');
            }
            $items[] = akademiata_schema_creative_work($name, $desc, $url);
        }
    }

    if (!empty($acf_fields['your_interests']) && is_array($acf_fields['your_interests'])) {
        $section = akademiata_schema_clean_text($acf_fields['your_interests']['title'] ?? '');
        if ($section === '') {
            $section = __('Obszary zainteresowań', 'akademiata');
        }
        $interest_names = array();
        foreach ($acf_fields['your_interests']['interests'] ?? array() as $interest) {
            if (!is_array($interest)) {
                continue;
            }
            $title = akademiata_schema_clean_text($interest['title'] ?? '');
            if ($title !== '') {
                $interest_names[] = $title;
            }
        }
        if ($interest_names !== array()) {
            $items[] = akademiata_schema_creative_work(
                $section,
                sprintf(
                    /* translators: %s: comma-separated interest area names */
                    __('Strona główna kieruje do obszarów: %s.', 'akademiata'),
                    implode(', ', $interest_names)
                )
            );
            foreach ($acf_fields['your_interests']['interests'] ?? array() as $interest) {
                if (!is_array($interest)) {
                    continue;
                }
                $title = akademiata_schema_clean_text($interest['title'] ?? '');
                if ($title === '') {
                    continue;
                }
                $link = is_array($interest['link'] ?? null) ? ($interest['link']['url'] ?? '') : '';
                $items[] = akademiata_schema_creative_work($title, $title, is_string($link) ? $link : '');
            }
        }
    }

    if (function_exists('akademiata_home_promos_fields')) {
        require_once get_template_directory() . '/configure/front-page-defaults/home-promos/fields.php';
        $promos = akademiata_home_promos_fields($acf_fields['home_promos'] ?? null);
        if (!empty($promos['show']) && !empty($promos['cards']) && is_array($promos['cards'])) {
            $promo_bits = array();
            foreach ($promos['cards'] as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $headline = akademiata_schema_clean_text(wp_strip_all_tags((string) ($card['headline'] ?? '')));
                $meta     = akademiata_schema_clean_text($card['meta'] ?? '');
                $text     = akademiata_schema_clean_text($card['text'] ?? '');
                $bit      = $headline;
                if ($text !== '') {
                    $bit .= ($bit !== '' ? ' — ' : '') . $text;
                }
                if ($meta !== '') {
                    $bit .= ($bit !== '' ? ' · ' : '') . $meta;
                }
                if ($bit !== '') {
                    $promo_bits[] = $bit;
                }
            }
            if ($promo_bits !== array()) {
                $items[] = akademiata_schema_creative_work(
                    akademiata_schema_clean_text($promos['title'] ?? '') ?: __('Promocje', 'akademiata'),
                    implode('; ', $promo_bits)
                );
            }
        }
    }

    if (!empty($acf_fields['our_students']) && is_array($acf_fields['our_students'])) {
        $students_title = akademiata_schema_clean_text($acf_fields['our_students']['title'] ?? '');
        if ($students_title !== '') {
            $playlist = '';
            if (function_exists('akademiata_normalize_youtube_playlist_id')) {
                $playlist = akademiata_normalize_youtube_playlist_id($acf_fields['our_students']['data_youtube_playlist'] ?? '');
            }
            $desc = __('Sekcja prezentuje historie i materiały wideo naszych studentów.', 'akademiata');
            if ($playlist !== '') {
                $desc .= ' YouTube playlist: ' . $playlist . '.';
            }
            $items[] = akademiata_schema_creative_work($students_title, $desc);
        }
    }

    if (function_exists('akademiata_get_aktualnosci_page_url')) {
        $news_url = akademiata_get_aktualnosci_page_url();
        if ($news_url !== '') {
            $news_desc = __('Sekcja prezentuje aktualności uczelni z Warszawy i Wrocławia.', 'akademiata');
            if (function_exists('akademiata_get_aktualnosci_category_term_id')) {
                $cat_id = akademiata_get_aktualnosci_category_term_id();
                if ($cat_id > 0) {
                    $recent = get_posts(array(
                        'post_type'      => 'post',
                        'posts_per_page' => 3,
                        'post_status'    => 'publish',
                        'cat'            => $cat_id,
                        'no_found_rows'  => true,
                    ));
                    $headlines = array();
                    foreach ($recent as $post) {
                        if ($post instanceof WP_Post) {
                            $headlines[] = akademiata_schema_clean_text(get_the_title($post));
                        }
                    }
                    if ($headlines !== array()) {
                        $news_desc .= ' ' . sprintf(
                            /* translators: %s: recent news headlines */
                            __('Ostatnie wpisy: %s.', 'akademiata'),
                            implode('; ', $headlines)
                        );
                    }
                }
            }
            $items[] = akademiata_schema_creative_work(
                __('Aktualności', 'akademiata'),
                $news_desc,
                $news_url
            );
        }
    }

    $contact = akademiata_schema_homepage_contact_defaults();
    $items[] = akademiata_schema_creative_work(
        __('Kontakt', 'akademiata'),
        sprintf(
            /* translators: 1: street address, 2: postal code and city, 3: phone, 4: email */
            __('Adres: %1$s, %2$s. Telefon: %3$s. E-mail: %4$s.', 'akademiata'),
            $contact['street'],
            $contact['zip'] . ' ' . $contact['city'],
            $contact['phone'],
            $contact['email']
        )
    );

    $welyo = akademiata_schema_welyo_recruitment_subject_of();
    if (is_array($welyo)) {
        $items[] = $welyo;
    }

    return apply_filters('akademiata_schema_homepage_subject_of', $items, $acf_fields);
}

/**
 * @return array<string, mixed>
 */
function akademiata_schema_build_homepage_website() {
    $home = untrailingslashit(home_url());

    return array(
        '@type'      => 'WebSite',
        '@id'        => $home . '/#website',
        'url'        => $home,
        'name'       => __('Akademia Techniczno-Artystyczna', 'akademiata'),
        'inLanguage' => akademiata_schema_bcp47_language(),
        'publisher'  => array('@id' => $home . '/#organization'),
    );
}

/**
 * @param array<string, mixed> $acf_fields
 * @return array<string, mixed>
 */
function akademiata_schema_build_homepage_webpage(array $acf_fields) {
    $home = untrailingslashit(home_url());
    $org  = akademiata_get_schema_homepage_organization($acf_fields);

    $description = $org['description'] ?? '';
    $main_title  = akademiata_schema_clean_text($acf_fields['main_title'] ?? '');
    if ($main_title !== '' && $description === '') {
        $description = $main_title;
    }

    $webpage = array(
        '@type'        => 'WebPage',
        '@id'          => $home . '/#webpage',
        'url'          => $home,
        'name'         => $org['legalName'] ?? $org['name'],
        'description'  => $description,
        'inLanguage'   => akademiata_schema_bcp47_language(),
        'isPartOf'     => array('@id' => $home . '/#website'),
        'mainEntity'   => array('@id' => $home . '/#organization'),
        'about'        => array('@id' => $home . '/#organization'),
    );

    $subject_of = akademiata_schema_collect_homepage_subject_of($acf_fields);
    if ($subject_of !== array()) {
        $webpage['subjectOf'] = $subject_of;
    }

    return $webpage;
}
