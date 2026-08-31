<?php
/**
 * Bachelor / master schema helpers — ACF sections from content-single-offer.php.
 *
 * @package akademiata
 */

/**
 * Auto-generated main description when schema_seo_description is empty.
 *
 * @param array<string, mixed>|null $price_row
 */
function akademiata_schema_degree_auto_description($post_id, $post_type, $price_row = null) {
    $title = akademiata_schema_clean_text(get_the_title($post_id));
    if ($title === '') {
        return '';
    }

    $degree   = akademiata_schema_join_term_names($post_id, 'degree');
    $city     = akademiata_schema_join_term_names($post_id, 'city');
    $language = akademiata_schema_join_term_names($post_id, 'language');
    $mode     = akademiata_schema_join_term_names($post_id, 'mode');

    $intro = $title;
    if ($degree !== '') {
        $intro .= ' — ' . mb_strtolower($degree);
    }
    if ($city !== '') {
        $intro .= ' w ' . $city;
    }
    if ($language !== '') {
        $intro .= ', język: ' . $language;
    }
    if ($mode !== '') {
        $intro .= ', forma: ' . $mode;
    }
    $intro .= '.';

    $body_parts = array();
    $why_study  = get_field('why_study', $post_id);
    if (is_array($why_study)) {
        foreach ($why_study['why_study_cards'] ?? array() as $card) {
            if (!is_array($card)) {
                continue;
            }
            $summary = akademiata_schema_robot_summary($card['title'] ?? '', $card['content'] ?? '', 22);
            if ($summary !== '') {
                $body_parts[] = $summary;
            }
        }
    }

    $after = get_field('after_studies', $post_id);
    if (is_array($after)) {
        foreach ($after['image_content_slider'] ?? array() as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $label = akademiata_schema_clean_text($slide['title'] ?? '');
            if ($label !== '') {
                $body_parts[] = $label;
            }
        }
    }

    if ($body_parts === array() && is_array($price_row) && !empty($price_row['k'])) {
        $body_parts[] = (string) $price_row['k'];
    }

    $body = implode(' ', array_slice($body_parts, 0, 4));

    return akademiata_schema_robot_summary($intro, $body, 55);
}

/**
 * @param array<string, mixed>|null $price_row
 */
function akademiata_schema_degree_study_parameters_text($post_id, $price_row = null) {
    $parts = array();

    $map = array(
        __('Miasto', 'akademiata')            => akademiata_schema_join_term_names($post_id, 'city'),
        __('Kierunek studiów', 'akademiata')  => akademiata_schema_join_term_names($post_id, 'program'),
        __('Rodzaj studiów', 'akademiata')    => akademiata_schema_join_term_names($post_id, 'degree'),
        __('Uzyskany tytuł', 'akademiata')    => akademiata_schema_join_term_names($post_id, 'obtained_title'),
        __('Czas trwania', 'akademiata')      => akademiata_schema_join_term_names($post_id, 'duration'),
        __('Język studiów', 'akademiata')     => akademiata_schema_join_term_names($post_id, 'language'),
        __('Forma studiów', 'akademiata')     => akademiata_schema_join_term_names($post_id, 'mode'),
    );

    foreach ($map as $label => $value) {
        if ($value !== '') {
            $parts[] = $label . ': ' . $value;
        }
    }

    $ects = akademiata_schema_get_ects_credits($post_id);
    if ($ects !== null) {
        $parts[] = 'Punkty ECTS: ' . $ects;
    }

    if (is_array($price_row) && !empty($price_row['r12'])) {
        $parts[] = sprintf(
            /* translators: %s: monthly price in PLN */
            __('Cena: już od %s zł miesięcznie', 'akademiata'),
            (string) $price_row['r12']
        );
    }

    return implode('. ', $parts);
}

/**
 * @return string[]
 */
function akademiata_schema_degree_occupational_categories($post_id) {
    $categories = array();
    $after      = get_field('after_studies', $post_id);

    if (!is_array($after)) {
        return $categories;
    }

    foreach ($after['image_content_slider'] ?? array() as $slide) {
        if (!is_array($slide)) {
            continue;
        }
        $title = akademiata_schema_clean_text($slide['title'] ?? '');
        if ($title !== '') {
            $categories[] = mb_strtolower($title);
        }
    }

    return array_slice(array_values(array_unique($categories)), 0, 8);
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_schema_degree_perspektywy_subject($post_id) {
    if (!function_exists('akademiata_get_ranking_perspektywy_lang_string')) {
        return null;
    }

    $variant = function_exists('akademiata_get_ranking_perspektywy_badge_variant')
        ? akademiata_get_ranking_perspektywy_badge_variant($post_id)
        : 'warszawa';

    $lines = array_filter(array_map('trim', explode(
        "\n",
        akademiata_get_ranking_perspektywy_lang_string('tooltip_short', $variant)
    )));

    $headline = function_exists('akademiata_get_theme_lang_string')
        ? trim(akademiata_get_theme_lang_string('offer_ranking_perspektywy_headline') . ' ' . akademiata_get_ranking_perspektywy_lang_string('subline', $variant))
        : '';

    $description = $lines !== array() ? implode(' ', $lines) : $headline;
    if ($description === '') {
        return null;
    }

    return akademiata_schema_creative_work(__('Ranking Perspektywy', 'akademiata'), $description);
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_schema_degree_ela_subject($post_id) {
    $candidates = array();

    if (function_exists('get_field')) {
        $icon = get_field('ranking_icon', $post_id);
        if (is_array($icon)) {
            if (!empty($icon['alt'])) {
                $candidates[] = (string) $icon['alt'];
            }
            if (!empty($icon['url'])) {
                $candidates[] = (string) $icon['url'];
            }
        }

        if (function_exists('akademiata_get_offer_ranking_icon_url')) {
            $icon_url = trim((string) akademiata_get_offer_ranking_icon_url($post_id));
            if ($icon_url !== '') {
                $candidates[] = $icon_url;
            }
        }

        $partners = get_field('offer_partners', $post_id);
        if (is_array($partners)) {
            foreach ($partners['partners_logo'] ?? array() as $logo_row) {
                if (!is_array($logo_row) || empty($logo_row['image']) || !is_array($logo_row['image'])) {
                    continue;
                }
                $image = $logo_row['image'];
                if (!empty($image['alt'])) {
                    $candidates[] = (string) $image['alt'];
                }
                if (!empty($image['title'])) {
                    $candidates[] = (string) $image['title'];
                }
                if (!empty($image['caption'])) {
                    $candidates[] = (string) $image['caption'];
                }
                if (!empty($image['url'])) {
                    $candidates[] = (string) $image['url'];
                }
            }
        }
    }

    foreach ($candidates as $text) {
        $clean = akademiata_schema_clean_text($text);
        if ($clean === '') {
            continue;
        }
        if (preg_match('/\bela\b/iu', $clean)) {
            if (preg_match('/\.(jpe?g|png|gif|webp|svg)(?:\?|$)/iu', $clean)) {
                continue;
            }
            return akademiata_schema_creative_work(__('Wyróżnienie ELA', 'akademiata'), $clean);
        }
        if (preg_match('/(?:^|[\/_-])ela(?:[\/_.-]|$)/iu', $clean)) {
            return akademiata_schema_creative_work(
                __('Wyróżnienie ELA', 'akademiata'),
                __('Kierunek wyróżniony w rankingu ELA.', 'akademiata')
            );
        }
    }

    return null;
}

/**
 * @deprecated Use akademiata_schema_degree_perspektywy_subject()
 * @return array<string, mixed>|null
 */
function akademiata_schema_degree_ranking_subject($post_id) {
    return akademiata_schema_degree_perspektywy_subject($post_id);
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_schema_degree_subjects_subject($post_id) {
    $subjects = get_field('subjects_study', $post_id);
    if (!is_array($subjects)) {
        return null;
    }

    $chunks = array();
    foreach ($subjects['subjects_study_accordion'] ?? array() as $item) {
        if (!is_array($item)) {
            continue;
        }
        $content = trim(wp_strip_all_tags((string) ($item['content'] ?? '')));
        if ($content !== '') {
            $chunks = array_merge($chunks, preg_split('/[;\n\r]+/u', $content) ?: array());
            continue;
        }
        $title = akademiata_schema_clean_text($item['title'] ?? '');
        if ($title !== '') {
            $chunks[] = $title;
        }
    }

    $chunks = array_values(array_filter(array_map('akademiata_schema_clean_text', $chunks)));
    if ($chunks === array()) {
        return null;
    }

    $section_title = akademiata_schema_clean_text($subjects['title'] ?? '');
    if ($section_title === '') {
        $section_title = __('Przedmioty kierunkowe', 'akademiata');
    }

    return akademiata_schema_creative_work($section_title, implode('; ', $chunks));
}

/**
 * @return string
 */
function akademiata_schema_degree_recruitment_step_text($index, array $step) {
    $text = trim(wp_strip_all_tags((string) ($step['text'] ?? '')));
    if ($text !== '') {
        return $text;
    }
    if ((int) $index === 0) {
        return __('Kliknij przycisk', 'akademiata');
    }
    if ((int) $index === 1) {
        return __('Wypełnij formularz', 'akademiata');
    }
    if ((int) $index === 2) {
        return __('Studiuj', 'akademiata');
    }

    return '';
}

/**
 * @return string
 */
function akademiata_schema_degree_program_prerequisites($post_id, $post_type) {
    if ($post_type === 'master') {
        return __(
            'Wymagane dokumenty: wygenerowany uczelniany kwestionariusz z internetowego systemu rekrutacyjnego, dyplom ukończenia studiów wyższych (oryginał do wglądu i kserokopia), dowód osobisty, jedno aktualne i podpisane zdjęcie oraz potwierdzenie uiszczenia opłat związanych z procesem rekrutacji.',
            'akademiata'
        );
    }

    return __(
        'Wymagane dokumenty: wygenerowany uczelniany kwestionariusz z internetowego systemu rekrutacyjnego, oryginał i kserokopia świadectwa dojrzałości, dowód osobisty, jedno aktualne i podpisane zdjęcie o parametrach dowodu osobistego oraz potwierdzenie uiszczenia opłat związanych z procesem rekrutacji.',
        'akademiata'
    );
}

/**
 * @param array<string, mixed>|null $price_row
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_degree_collect_subject_of($post_id, $post_type, $price_row = null, $program_pdf = '') {
    $items = array();

    $parameters = akademiata_schema_degree_study_parameters_text($post_id, $price_row);
    if ($parameters !== '') {
        $items[] = akademiata_schema_creative_work(__('Parametry studiów', 'akademiata'), $parameters);
    }

    $perspektywy = akademiata_schema_degree_perspektywy_subject($post_id);
    if (is_array($perspektywy)) {
        $items[] = $perspektywy;
    }

    $ela = akademiata_schema_degree_ela_subject($post_id);
    if (is_array($ela)) {
        $items[] = $ela;
    }

    $why_study = get_field('why_study', $post_id);
    if (is_array($why_study)) {
        $section = akademiata_schema_clean_text($why_study['title'] ?? '');
        if ($section === '') {
            $section = __('Dlaczego warto studiować w ATA', 'akademiata');
        }

        $card_summaries = array();
        foreach ($why_study['why_study_cards'] ?? array() as $card) {
            if (!is_array($card)) {
                continue;
            }
            $card_title = akademiata_schema_clean_text($card['title'] ?? '');
            $summary    = akademiata_schema_robot_summary($card_title, $card['content'] ?? '', 40);
            if ($summary === '') {
                continue;
            }
            $card_summaries[] = $summary;
            $items[] = akademiata_schema_creative_work($card_title, $summary);
        }

        if ($card_summaries !== array()) {
            $items[] = akademiata_schema_creative_work(
                $section,
                implode(' ', array_slice($card_summaries, 0, 4))
            );
        }
    }

    $after_studies = get_field('after_studies', $post_id);
    if (is_array($after_studies)) {
        $section = akademiata_schema_clean_text($after_studies['title'] ?? '');
        if ($section === '') {
            $section = __('Co możesz robić po tych studiach?', 'akademiata');
        }

        $career_bits = array();
        foreach ($after_studies['image_content_slider'] ?? array() as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $summary = akademiata_schema_robot_summary($slide['title'] ?? '', $slide['content'] ?? '', 18);
            if ($summary !== '') {
                $career_bits[] = $summary;
            }
        }

        if ($career_bits !== array()) {
            $items[] = akademiata_schema_creative_work($section, implode(' ', $career_bits));
        }
    }

    $program_for_you = get_field('program_for_you', $post_id);
    if (is_array($program_for_you)) {
        $section = akademiata_schema_clean_text($program_for_you['title'] ?? '');
        if ($section === '') {
            $section = __('Ten program jest dla Ciebie, jeśli', 'akademiata');
        }

        $bits = array();
        foreach ($program_for_you['cards'] ?? array() as $card) {
            if (!is_array($card)) {
                continue;
            }
            $summary = akademiata_schema_robot_summary($card['title'] ?? '', $card['content'] ?? '', 22);
            if ($summary !== '') {
                $bits[] = $summary;
            }
        }

        if ($bits !== array()) {
            $items[] = akademiata_schema_creative_work($section, implode(' ', $bits));
        }
    }

    if ($program_pdf !== '' || get_field('study_program', $post_id)) {
        $study_program = get_field('study_program', $post_id);
        $percent_parts = array();
        $course_name   = is_array($price_row) && !empty($price_row['k'])
            ? (string) $price_row['k']
            : akademiata_schema_clean_text(get_the_title($post_id));

        if (is_array($study_program)) {
            foreach ($study_program['program_percentages'] ?? array() as $row) {
                if (!is_array($row) || empty($row['percent'])) {
                    continue;
                }
                $label = akademiata_schema_clean_text($row['title'] ?? '');
                $percent_parts[] = ($label !== '' ? $label . ': ' : '') . trim((string) $row['percent']);
            }
        }

        $description_parts = array();
        if ($percent_parts !== array()) {
            $description_parts[] = implode(', ', $percent_parts);
        }
        $ects = akademiata_schema_get_ects_credits($post_id);
        if ($ects !== null) {
            $description_parts[] = 'ECTS: ' . $ects;
        }
        $duration = akademiata_schema_join_term_names($post_id, 'duration');
        if ($duration !== '') {
            $description_parts[] = __('Czas trwania', 'akademiata') . ': ' . $duration;
        }
        $title_awarded = akademiata_schema_join_term_names($post_id, 'obtained_title');
        if ($title_awarded !== '') {
            $description_parts[] = __('Uzyskany tytuł zawodowy', 'akademiata') . ': ' . $title_awarded;
        }
        if ($course_name !== '') {
            $description_parts[] = __('Kierunek studiów', 'akademiata') . ': ' . $course_name;
        }
        if ($description_parts === array()) {
            $description_parts[] = __('Pobierz pełny program studiów (PDF).', 'akademiata');
        }

        $items[] = akademiata_schema_creative_work(
            __('Program i struktura studiów', 'akademiata'),
            implode('. ', $description_parts),
            $program_pdf
        );
    }

    if (is_array($price_row)) {
        $specializations = akademiata_schema_find_price_row_specializations($price_row);
        if ($specializations !== array()) {
            $course = trim((string) ($price_row['k'] ?? get_the_title($post_id)));
            $items[] = akademiata_schema_creative_work(
                sprintf(
                    /* translators: %s: field of study name */
                    __('Specjalności na kierunku %s', 'akademiata'),
                    $course
                ),
                implode('; ', wp_list_pluck($specializations, 'name'))
            );
            foreach ($specializations as $spec) {
                if ($spec['name'] === '') {
                    continue;
                }
                $items[] = akademiata_schema_creative_work($spec['name'], $spec['name'], $spec['url']);
            }
        }
    }

    $subjects_block = akademiata_schema_degree_subjects_subject($post_id);
    if (is_array($subjects_block)) {
        $items[] = $subjects_block;
    }

    $tuition = get_field('tuition_fees', $post_id);
    if (is_array($tuition)) {
        $tuition_text = akademiata_schema_robot_summary(
            (string) ($tuition['sub_title'] ?? __('Opłaty za studia', 'akademiata')),
            (string) ($tuition['title'] ?? ''),
            35
        );
        if ($tuition_text !== '') {
            $items[] = akademiata_schema_creative_work(__('Opłaty za studia', 'akademiata'), $tuition_text);
        }
    }

    $city_slug = function_exists('akademiata_get_offer_city_slug')
        ? akademiata_get_offer_city_slug($post_id)
        : 'warszawa';
    $lang_code = function_exists('akademiata_get_offer_study_language_code')
        ? akademiata_get_offer_study_language_code($post_id)
        : 'pl';

    $regulamin_plans = apply_filters('ata_prices_regulamin_urls_plans', array(
        'wwa' => array('pl' => 'https://chmurka.wseiz.pl/index.php/s/LgF9TpCerLtGHb2'),
        'wro' => array('pl' => 'https://chmurka.wseiz.pl/index.php/s/LgF9TpCerLtGHb2'),
    ));
    $regulamin_promos = apply_filters('ata_prices_regulamin_urls_promos', array(
        'wwa' => array('pl' => 'https://chmurka.wseiz.pl/index.php/s/XnXZQCNepLerqja'),
        'wro' => array('pl' => 'https://chmurka.wseiz.pl/index.php/s/XnXZQCNepLerqja'),
    ));

    $city_key = ($city_slug === 'wroclaw') ? 'wro' : 'wwa';
    $plans_url = $regulamin_plans[ $city_key ][ $lang_code ] ?? $regulamin_plans[ $city_key ]['pl'] ?? '';
    $promos_url = $regulamin_promos[ $city_key ][ $lang_code ] ?? $regulamin_promos[ $city_key ]['pl'] ?? '';

    if ($plans_url !== '') {
        $items[] = akademiata_schema_creative_work(__('Regulamin opłat', 'akademiata'), __('Regulamin opłat za studia', 'akademiata'), $plans_url);
    }
    if ($promos_url !== '') {
        $items[] = akademiata_schema_creative_work(__('Regulamin zniżek i promocji', 'akademiata'), __('Regulamin zniżek i promocji', 'akademiata'), $promos_url);
    }

    $recruitment = get_field('recruitment_rules', $post_id);
    if (is_array($recruitment)) {
        $section = akademiata_schema_clean_text($recruitment['title'] ?? '');
        if ($section === '') {
            $section = __('Zasady rekrutacji', 'akademiata');
        }

        $steps = array();
        foreach ($recruitment['steps'] ?? array() as $index => $step) {
            if (!is_array($step)) {
                continue;
            }
            $step_text = akademiata_schema_degree_recruitment_step_text($index, $step);
            if ($step_text !== '') {
                $steps[] = $step_text;
            }
        }

        $register_url = trim((string) get_field('register_url', $post_id));
        $description  = $steps !== array()
            ? sprintf(
                /* translators: %s: comma-separated recruitment steps */
                __('Proces rekrutacji: %s.', 'akademiata'),
                implode(', ', $steps)
            )
            : __('Proces rekrutacji online w systemie Smart Apply.', 'akademiata');

        $items[] = akademiata_schema_creative_work(
            $section,
            $description,
            $register_url !== '' ? $register_url : ''
        );
    }

    $documents = akademiata_schema_degree_program_prerequisites($post_id, $post_type);
    if ($documents !== '') {
        $items[] = akademiata_schema_creative_work(__('Wymagane dokumenty', 'akademiata'), $documents);
    }

    $last_step = (string) get_field('last_step', $post_id);
    if ($last_step === 'portfolio') {
        $portfolio = get_field('portfolio', $post_id);
        if (is_array($portfolio)) {
            $section = akademiata_schema_clean_text($portfolio['title_section'] ?? '');
            if ($section === '') {
                $section = __('Portfolio', 'akademiata');
            }
            $body = akademiata_schema_robot_summary(
                $portfolio['title'] ?? '',
                $portfolio['description'] ?? '',
                45
            );
            if ($body !== '') {
                $items[] = akademiata_schema_creative_work($section, $body);
            }
        }
    } elseif ($last_step === 'exam') {
        $exam = get_field('exam', $post_id);
        if (is_array($exam)) {
            $section = akademiata_schema_clean_text($exam['title'] ?? '');
            if ($section === '') {
                $section = __('Egzamin', 'akademiata');
            }
            $body = trim(wp_strip_all_tags(
                (string) ($exam['sub_title'] ?? '') . "\n\n" .
                (string) ($exam['content'] ?? '') . "\n\n" .
                (string) ($exam['details'] ?? '')
            ));
            if ($body !== '') {
                $items[] = akademiata_schema_creative_work($section, akademiata_schema_trim_text($body, 55));
            }
        }
    }

    return $items;
}

/**
 * @return array<int, array<string, mixed>>
 * @deprecated Merged into akademiata_schema_degree_collect_subject_of().
 */
function akademiata_schema_degree_collect_has_parts($post_id) {
    return array();
}
