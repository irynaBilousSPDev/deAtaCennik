<?php
/**
 * JSON-LD for non-LP page templates (FAQ, contact, offer listing, etc.).
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_faq_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id, 'FAQPage');
    if ($schema === null) {
        return null;
    }

    $parts = array();
    $title = trim(wp_strip_all_tags((string) get_field('accordion_main_title', $post_id)));
    $sub   = trim(wp_strip_all_tags((string) get_field('accordion_main_sub_title', $post_id)));
    $desc  = trim(wp_strip_all_tags((string) get_field('accordion_main_description', $post_id)));

    if ($title !== '' || $desc !== '') {
        $parts[] = akademiata_schema_creative_work(
            $title !== '' ? $title : __('FAQ', 'akademiata'),
            akademiata_schema_robot_summary($title, trim($sub . "\n\n" . $desc), 45)
        );
    }

    $faq_items = akademiata_schema_query_faq_posts_for_page($post_id);
    $section   = __('Najczęściej zadawane pytania', 'akademiata');
    akademiata_schema_lp_push_faq_subject_of($parts, $faq_items, $section);

    $faq_entities = akademiata_schema_lp_collect_faq_entities($faq_items);
    if ($faq_entities !== array()) {
        $schema['mainEntity'] = $faq_entities;
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_contact_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id, 'ContactPage');
    if ($schema === null) {
        return null;
    }

    $parts          = array();
    $contact_points = array();
    $header         = get_field('contact_header', $post_id);
    $header         = is_array($header) ? $header : array();
    $rows           = is_array($header['contact_header_repeater'] ?? null) ? $header['contact_header_repeater'] : array();

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $school  = trim(wp_strip_all_tags((string) ($row['school_name'] ?? '')));
        $address = trim(wp_strip_all_tags((string) ($row['address'] ?? '')));
        $phone   = trim((string) ($row['phone'] ?? ''));
        $email   = trim((string) ($row['email'] ?? ''));
        $bank    = trim(wp_strip_all_tags((string) ($row['bank_account_number_details'] ?? '')));

        if ($school === '' && $address === '' && $phone === '' && $email === '') {
            continue;
        }

        $summary = trim(implode('. ', array_filter(array($school, $address, $phone, $email, $bank))));
        $parts[] = akademiata_schema_creative_work(
            $school !== '' ? $school : __('Kontakt', 'akademiata'),
            akademiata_schema_trim_text($summary, 45)
        );

        $point = array(
            '@type'       => 'ContactPoint',
            'contactType' => __('Admissions', 'akademiata'),
        );
        if ($school !== '') {
            $point['name'] = $school;
        }
        if ($address !== '') {
            $point['areaServed'] = $address;
        }
        if ($phone !== '') {
            $point['telephone'] = preg_replace('/[^0-9+]/', '', $phone) ?: $phone;
        }
        if ($email !== '') {
            $point['email'] = $email;
        }
        $contact_points[] = $point;
    }

    if ($contact_points !== array()) {
        $schema['mainEntity'] = count($contact_points) === 1 ? $contact_points[0] : $contact_points;
    }

    $acc_title = trim(wp_strip_all_tags((string) get_field('accordion_main_title', $post_id)));
    $acc_desc  = trim(wp_strip_all_tags((string) get_field('accordion_main_description', $post_id)));
    if ($acc_title !== '' || $acc_desc !== '') {
        $parts[] = akademiata_schema_creative_work(
            $acc_title !== '' ? $acc_title : __('Informacje kontaktowe', 'akademiata'),
            akademiata_schema_robot_summary($acc_title, $acc_desc, 45)
        );
    }

    $accordion = get_field('accordion_universal', $post_id);
    if (is_array($accordion)) {
        akademiata_schema_push_accordion_rows($parts, $accordion, __('Dodatkowe informacje', 'akademiata'));
    }

    $terms = get_terms(array('taxonomy' => 'contact_city', 'hide_empty' => true));
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $contacts = get_posts(array(
                'post_type'      => 'contact',
                'post_status'    => 'publish',
                'numberposts'    => -1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'contact_city',
                        'field'    => 'term_id',
                        'terms'    => (int) $term->term_id,
                    ),
                ),
                'suppress_filters' => false,
            ));

            foreach ((array) $contacts as $contact_post) {
                $rows = get_field('accordion_universal', $contact_post->ID);
                if (!is_array($rows)) {
                    continue;
                }
                akademiata_schema_push_accordion_rows($parts, $rows, $term->name);
            }
        }
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_offer_listing_page_schema($post_id) {
    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $posts = akademiata_schema_collect_offer_listing_posts($post_id, 48);
    $list  = akademiata_schema_build_item_list(
        $posts,
        $base['title'],
        $base['permalink']
    );

    $schema = akademiata_schema_build_collection_page(
        $base['permalink'],
        $base['title'],
        akademiata_schema_page_description($post_id),
        $list
    );

    $parts = array(
        akademiata_schema_creative_work(
            __('Filtr oferty studiów', 'akademiata'),
            __('Strona umożliwia filtrowanie kierunków studiów I i II stopnia według miasta, trybu, języka, czasu trwania i promocji.', 'akademiata')
        ),
    );

    $modified = get_the_modified_date('c', $post_id);
    if ($modified) {
        $schema['dateModified'] = $modified;
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_cadre_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id);
    if ($schema === null) {
        return null;
    }

    $parts = array();
    $sections = get_field('page_sections', $post_id);

    if (is_array($sections)) {
        foreach ($sections as $section) {
            if (!is_array($section) || ($section['acf_fc_layout'] ?? '') !== 'cadre_section') {
                continue;
            }

            $section_title = trim(wp_strip_all_tags((string) ($section['title'] ?? '')));
            $subtitle      = trim(wp_strip_all_tags((string) ($section['subtitle'] ?? '')));
            if ($section_title !== '') {
                $parts[] = akademiata_schema_creative_work(
                    $section_title,
                    akademiata_schema_robot_summary($section_title, $subtitle, 40)
                );
            }

            $people = $section['people'] ?? array();
            if (!is_array($people)) {
                continue;
            }

            foreach ($people as $person_post) {
                if (!$person_post instanceof WP_Post) {
                    $person_post = get_post((int) $person_post);
                }
                if (!$person_post instanceof WP_Post) {
                    continue;
                }

                $name = get_the_title($person_post);
                $role = trim(wp_strip_all_tags((string) get_field('cadre_role', $person_post->ID)));
                $bio  = trim(wp_strip_all_tags((string) get_field('cadre_bio', $person_post->ID)));
                $body = trim($role . '. ' . $bio);

                akademiata_schema_push_has_part(
                    $parts,
                    $section_title !== '' ? $section_title : __('Kadra', 'akademiata'),
                    $name,
                    $body,
                    45
                );
            }
        }
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_prices_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id);
    if ($schema === null) {
        return null;
    }

    $home = untrailingslashit(home_url());
    $parts = array(
        akademiata_schema_creative_work(
            __('Kalkulator czesnego', 'akademiata'),
            __('Interaktywny kalkulator kosztów studiów I i II stopnia — warianty płatności, promocje i opłaty rekrutacyjne. Dane z pliku prices.json (motyw) i Google Apps Script.', 'akademiata'),
            $home . '/prices.json'
        ),
        akademiata_schema_creative_work(
            __('Zapis na studia', 'akademiata'),
            __('Po wyborze kierunku kalkulator kieruje do systemu rekrutacyjnego SmartApply.', 'akademiata')
        ),
    );

    $schema['potentialAction'] = array(
        '@type'  => 'SearchAction',
        'target' => array(
            '@type'       => 'EntryPoint',
            'urlTemplate' => untrailingslashit(get_permalink($post_id)) . '?key={program_key}',
        ),
        'query-input' => 'required name=program_key',
    );

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_open_day_page_schema($post_id) {
    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $permalink     = $base['permalink'];
    $event_date    = trim(wp_strip_all_tags((string) get_field('open_day_event_date', $post_id)));
    $info_text     = trim(wp_strip_all_tags((string) get_field('open_day_info_text', $post_id)));
    $schedule_title = trim(wp_strip_all_tags((string) get_field('open_day_schedule_title', $post_id)));
    $schedule_items = get_field('open_day_schedule_items', $post_id);
    $schedule_items = is_array($schedule_items) ? $schedule_items : array();

    $webpage = akademiata_schema_page_webpage_shell($post_id);
    $parts   = array();

    if ($info_text !== '') {
        $parts[] = akademiata_schema_creative_work(
            __('Dzień Otwarty — informacje', 'akademiata'),
            akademiata_schema_trim_text($info_text, 45)
        );
    }

    foreach ($schedule_items as $item) {
        $line = trim(wp_strip_all_tags(is_string($item) ? $item : (string) ($item['text'] ?? '')));
        if ($line === '') {
            continue;
        }
        akademiata_schema_push_has_part(
            $parts,
            $schedule_title !== '' ? $schedule_title : __('Plan dnia', 'akademiata'),
            $line,
            $line,
            30
        );
    }

    $org = akademiata_get_schema_organization();
    $event = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Event',
        '@id'         => untrailingslashit($permalink) . '/#event',
        'name'        => $base['title'],
        'description' => akademiata_schema_page_description($post_id),
        'url'         => $permalink,
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'organizer'           => array('@id' => $org['@id']),
        'location'            => array(
            '@type'   => 'Place',
            'name'    => __('Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata'),
            'address' => array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'ul. Olszewska 12',
                'addressLocality' => 'Warszawa',
                'postalCode'      => '00-792',
                'addressCountry'  => 'PL',
            ),
        ),
    );

    if ($event_date !== '') {
        $event['startDate'] = $event_date;
    }

    $hero = get_field('open_day_hero_image', $post_id);
    if (is_array($hero) && !empty($hero['url'])) {
        $event['image'] = (string) $hero['url'];
    }

    $nodes = akademiata_schema_page_schema_graph_nodes($webpage, $parts);
    $nodes[] = $event;

    return $nodes;
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_ranking_ela_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id);
    if ($schema === null) {
        return null;
    }

    $parts = array();
    $acf   = function_exists('get_fields') ? (get_fields($post_id) ?: array()) : array();

    foreach (array('investment_section', 'ranking_section', 'features_section', 'value_section') as $section_key) {
        $section = is_array($acf[ $section_key ] ?? null) ? $acf[ $section_key ] : array();
        if ($section === array()) {
            continue;
        }

        $label = akademiata_schema_lp_section_label($section, $section_key);
        if ($label === '') {
            $label = ucfirst(str_replace('_', ' ', $section_key));
        }

        akademiata_schema_lp_collect_from_section($parts, $section, $label);

        if ($section_key === 'ranking_section' && !empty($section['positions']) && is_array($section['positions'])) {
            foreach ($section['positions'] as $position) {
                if (!is_array($position)) {
                    continue;
                }
                $pos_label = trim(wp_strip_all_tags((string) ($position['title'] ?? $position['label'] ?? '')));
                if (!empty($position['item']) && is_array($position['item'])) {
                    akademiata_schema_lp_push_generic_rows($parts, $position['item'], $pos_label !== '' ? $pos_label : $label);
                }
            }
        }
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_quiz_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id);
    if ($schema === null) {
        return null;
    }

    $title = trim(wp_strip_all_tags((string) get_field('main_title', $post_id)));
    $parts = array();

    if ($title !== '') {
        $parts[] = akademiata_schema_creative_work(
            $title,
            __('Interaktywny quiz pomagający dopasować kierunek studiów.', 'akademiata')
        );
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_thank_you_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id);
    if ($schema === null) {
        return null;
    }

    $content = get_post_field('post_content', $post_id);
    $parts   = array();

    if (is_string($content) && trim(wp_strip_all_tags($content)) !== '') {
        $parts[] = akademiata_schema_creative_work(
            get_the_title($post_id),
            akademiata_schema_trim_text($content, 40)
        );
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_default_page_schema($post_id) {
    $schema = akademiata_schema_page_webpage_shell($post_id);
    if ($schema === null) {
        return null;
    }

    $parts   = array();
    $content = get_post_field('post_content', $post_id);

    if (is_string($content) && trim(wp_strip_all_tags($content)) !== '') {
        $parts[] = akademiata_schema_creative_work(
            get_the_title($post_id),
            akademiata_schema_trim_text($content, 55)
        );
    }

    return akademiata_schema_page_schema_graph_nodes($schema, $parts);
}

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null
 */
function akademiata_build_lp_page_schema($post_id) {
    $template = akademiata_schema_get_page_template_file($post_id);
    $map      = akademiata_schema_lp_page_templates();

    if ($template === '' || empty($map[ $template ])) {
        return null;
    }

    $config       = $map[ $template ];
    $webpage_type = $config['webpage_type'] ?? 'WebPage';
    $schema       = akademiata_schema_page_webpage_shell($post_id, $webpage_type);
    if ($schema === null) {
        return null;
    }

    $data = akademiata_schema_collect_lp_page_subject_of($post_id);

    if ($template === 'page-katalog-kierunkow.php') {
        $list = akademiata_schema_collect_katalog_course_item_list($post_id);
        if ($list !== array()) {
            $schema['mainEntity'] = $list[0];
        }
    }

    if ($template === 'page-rankingi.php') {
        $schema['citation'] = __('Ranking Uczelni Zawodowych Perspektywy 2026; Ekonomiczne Losy Absolwentów (ELA) — dane ZUS.', 'akademiata');
    }

    return akademiata_schema_page_schema_graph_nodes(
        $schema,
        $data['subject_of'],
        $data['faq_entities']
    );
}
