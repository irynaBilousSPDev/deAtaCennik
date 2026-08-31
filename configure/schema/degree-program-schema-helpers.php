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
    $title = get_the_title($post_id);
    if ($title === '') {
        return '';
    }

    $fragments = array();
    $degree    = akademiata_schema_join_term_names($post_id, 'degree');
    if ($degree !== '') {
        $fragments[] = $title . ' — ' . $degree;
    } else {
        $fragments[] = $title;
    }

    $city = akademiata_schema_join_term_names($post_id, 'city');
    if ($city !== '') {
        $fragments[] = __('City:', 'akademiata') . ' ' . $city;
    }

    $language = akademiata_schema_join_term_names($post_id, 'language');
    if ($language !== '') {
        $fragments[] = __('Language of instruction:', 'akademiata') . ' ' . $language;
    }

    $mode = akademiata_schema_join_term_names($post_id, 'mode');
    if ($mode !== '') {
        $fragments[] = __('Study mode:', 'akademiata') . ' ' . $mode;
    }

    $intro = implode('. ', $fragments);

    $why_study = get_field('why_study', $post_id);
    $body      = '';
    if (is_array($why_study) && !empty($why_study['why_study_cards'][0]['content'])) {
        $body = (string) $why_study['why_study_cards'][0]['content'];
    }

    if (is_array($price_row) && !empty($price_row['k']) && empty($body)) {
        $body = sprintf(
            /* translators: %s: field of study name */
            __('Field of study: %s at ATA.', 'akademiata'),
            (string) $price_row['k']
        );
    }

    return akademiata_schema_robot_summary($intro, $body, 55);
}

/**
 * @param array<string, mixed>|null $price_row
 */
function akademiata_schema_degree_study_parameters_text($post_id, $price_row = null) {
    $parts = array();

    $map = array(
        __('City', 'akademiata')                 => akademiata_schema_join_term_names($post_id, 'city'),
        __('Field of study', 'akademiata')       => akademiata_schema_join_term_names($post_id, 'program'),
        __('Type of studies', 'akademiata')      => akademiata_schema_join_term_names($post_id, 'degree'),
        __('Degree awarded', 'akademiata')       => akademiata_schema_join_term_names($post_id, 'obtained_title'),
        __('Duration', 'akademiata')             => akademiata_schema_join_term_names($post_id, 'duration'),
        __('Language of instruction', 'akademiata') => akademiata_schema_join_term_names($post_id, 'language'),
        __('Study mode', 'akademiata')           => akademiata_schema_join_term_names($post_id, 'mode'),
    );

    foreach ($map as $label => $value) {
        if ($value !== '') {
            $parts[] = $label . ': ' . $value;
        }
    }

    $ects = akademiata_schema_get_ects_credits($post_id);
    if ($ects !== null) {
        $parts[] = 'ECTS: ' . $ects;
    }

    if (is_array($price_row)) {
        if (!empty($price_row['r12'])) {
            $parts[] = sprintf(
                /* translators: %s: monthly price in PLN */
                __('Tuition from %s PLN/month (12 installments)', 'akademiata'),
                (string) $price_row['r12']
            );
        } elseif (!empty($price_row['r10'])) {
            $parts[] = sprintf(
                /* translators: %s: monthly price in PLN */
                __('Tuition from %s PLN/month (10 installments)', 'akademiata'),
                (string) $price_row['r10']
            );
        }
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
        $summary = akademiata_schema_robot_summary($slide['title'] ?? '', $slide['content'] ?? '', 12);
        if ($summary !== '') {
            $categories[] = $summary;
        }
    }

    return array_slice(array_values(array_unique($categories)), 0, 8);
}

/**
 * @return array<string, mixed>|null
 */
function akademiata_schema_degree_ranking_subject($post_id) {
    if (!function_exists('akademiata_get_offer_ranking_icon_url')) {
        return null;
    }

    $icon_url = akademiata_get_offer_ranking_icon_url($post_id);
    if ($icon_url === '') {
        return null;
    }

    $description = '';
    if (function_exists('akademiata_get_ranking_perspektywy_lang_string')) {
        $variant = function_exists('akademiata_get_ranking_perspektywy_badge_variant')
            ? akademiata_get_ranking_perspektywy_badge_variant($post_id)
            : 'both';
        $lines = array_filter(array_map('trim', explode(
            "\n",
            akademiata_get_ranking_perspektywy_lang_string('tooltip_short', $variant)
        )));
        if ($lines !== array()) {
            $description = implode(' ', $lines);
        }
    }

    if ($description === '') {
        $description = sprintf(
            /* translators: %s: program title */
            __('Ranking distinction for the %s program at ATA.', 'akademiata'),
            get_the_title($post_id)
        );
    }

    return akademiata_schema_creative_work(__('Ranking distinction', 'akademiata'), $description);
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
            $chunks[] = $content;
            continue;
        }
        $title = trim(wp_strip_all_tags((string) ($item['title'] ?? '')));
        if ($title !== '') {
            $chunks[] = $title;
        }
    }

    if ($chunks === array()) {
        return null;
    }

    $section_title = trim((string) ($subjects['title'] ?? ''));
    if ($section_title === '') {
        $section_title = __('Course subjects', 'akademiata');
    }

    $description = akademiata_schema_trim_text(implode('; ', $chunks), 120);

    return akademiata_schema_creative_work($section_title, $description);
}

/**
 * @param array<string, mixed>|null $price_row
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_degree_collect_subject_of($post_id, $post_type, $price_row = null, $program_pdf = '') {
    $items = array();

    $parameters = akademiata_schema_degree_study_parameters_text($post_id, $price_row);
    if ($parameters !== '') {
        $items[] = akademiata_schema_creative_work(__('Study parameters', 'akademiata'), $parameters);
    }

    $ranking = akademiata_schema_degree_ranking_subject($post_id);
    if (is_array($ranking)) {
        $items[] = $ranking;
    }

    if (is_array($price_row)) {
        $specializations = akademiata_schema_find_price_row_specializations($price_row);
        if ($specializations !== array()) {
            $course = trim((string) ($price_row['k'] ?? get_the_title($post_id)));
            $items[] = akademiata_schema_creative_work(
                sprintf(
                    /* translators: %s: field of study name */
                    __('Specializations in %s', 'akademiata'),
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

    if ($program_pdf !== '') {
        $study_program = get_field('study_program', $post_id);
        $percent_parts = array();
        if (is_array($study_program)) {
            foreach ($study_program['program_percentages'] ?? array() as $row) {
                if (!is_array($row) || empty($row['percent'])) {
                    continue;
                }
                $label = trim((string) ($row['title'] ?? ''));
                $percent_parts[] = ($label !== '' ? $label . ': ' : '') . trim((string) $row['percent']);
            }
        }
        $description = $percent_parts !== array()
            ? implode(', ', $percent_parts)
            : __('Download the full study program (PDF).', 'akademiata');
        $items[] = akademiata_schema_creative_work(
            __('Program and structure of studies', 'akademiata'),
            $description,
            $program_pdf
        );
    }

    $tuition = get_field('tuition_fees', $post_id);
    if (is_array($tuition)) {
        $tuition_text = akademiata_schema_robot_summary(
            (string) ($tuition['sub_title'] ?? __('Tuition fees', 'akademiata')),
            (string) ($tuition['title'] ?? ''),
            35
        );
        if ($tuition_text !== '') {
            $items[] = akademiata_schema_creative_work(__('Tuition fees', 'akademiata'), $tuition_text);
        }
    }

    $items = array_merge($items, akademiata_schema_degree_collect_has_parts($post_id));

    return $items;
}

/**
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_degree_collect_has_parts($post_id) {
    $parts = array();

    $why_study = get_field('why_study', $post_id);
    if (is_array($why_study)) {
        $section = trim((string) ($why_study['title'] ?? ''));
        if ($section === '') {
            $section = __('Dlaczego warto studiować w ATA', 'akademiata');
        }
        foreach ($why_study['why_study_cards'] ?? array() as $card) {
            if (!is_array($card)) {
                continue;
            }
            akademiata_schema_push_has_part($parts, $section, $card['title'] ?? '', $card['content'] ?? '');
        }
    }

    $after_studies = get_field('after_studies', $post_id);
    if (is_array($after_studies)) {
        $section = trim((string) ($after_studies['title'] ?? ''));
        if ($section === '') {
            $section = __('Co możesz robić po tych studiach?', 'akademiata');
        }
        $intro = akademiata_schema_robot_summary(
            (string) ($after_studies['sub_title'] ?? ''),
            '',
            30
        );
        if ($intro !== '') {
            $parts[] = array(
                '@type'       => 'CreativeWork',
                'name'        => $section,
                'description' => $intro,
            );
        }
        foreach ($after_studies['image_content_slider'] ?? array() as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            akademiata_schema_push_has_part($parts, $section, $slide['title'] ?? '', $slide['content'] ?? '');
        }
    }

    $program_for_you = get_field('program_for_you', $post_id);
    if (is_array($program_for_you)) {
        $section = trim((string) ($program_for_you['title'] ?? ''));
        if ($section === '') {
            $section = __('Ten program jest dla Ciebie, jeśli:', 'akademiata');
        }
        foreach ($program_for_you['cards'] ?? array() as $card) {
            if (!is_array($card)) {
                continue;
            }
            akademiata_schema_push_has_part($parts, $section, $card['title'] ?? '', $card['content'] ?? '');
        }
    }

    $study_program = get_field('study_program', $post_id);
    if (is_array($study_program)) {
        $section = trim((string) ($study_program['title'] ?? ''));
        if ($section === '') {
            $section = __('Program i struktura studiów', 'akademiata');
        }
        $intro = akademiata_schema_robot_summary(
            (string) ($study_program['sub_title'] ?? ''),
            '',
            30
        );
        if ($intro !== '') {
            $parts[] = array(
                '@type'       => 'CreativeWork',
                'name'        => $section,
                'description' => $intro,
            );
        }
        foreach ($study_program['program_percentages'] ?? array() as $item) {
            if (!is_array($item)) {
                continue;
            }
            $percent = trim((string) ($item['percent'] ?? ''));
            if ($percent === '') {
                continue;
            }
            akademiata_schema_push_has_part(
                $parts,
                $section,
                (string) ($item['title'] ?? ''),
                $percent,
                20
            );
        }
    }

    $subjects_study = get_field('subjects_study', $post_id);
    if (is_array($subjects_study)) {
        $section = trim((string) ($subjects_study['title'] ?? ''));
        if ($section === '') {
            $section = __('Przedmioty / moduły', 'akademiata');
        }
        foreach ($subjects_study['subjects_study_accordion'] ?? array() as $item) {
            if (!is_array($item)) {
                continue;
            }
            akademiata_schema_push_has_part($parts, $section, $item['title'] ?? '', $item['content'] ?? '');
        }
    }

    $recruitment = get_field('recruitment_rules', $post_id);
    if (is_array($recruitment)) {
        $section = trim((string) ($recruitment['title'] ?? ''));
        if ($section === '') {
            $section = __('Zasady rekrutacji', 'akademiata');
        }
        $intro = akademiata_schema_robot_summary(
            (string) ($recruitment['sub_title'] ?? ''),
            '',
            30
        );
        if ($intro !== '') {
            $parts[] = array(
                '@type'       => 'CreativeWork',
                'name'        => $section,
                'description' => $intro,
            );
        }
        foreach ($recruitment['steps'] ?? array() as $index => $step) {
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
                akademiata_schema_push_has_part($parts, $section, (string) ((int) $index + 1), $text, 25);
            }
        }
    }

    $last_step = (string) get_field('last_step', $post_id);
    if ($last_step === 'portfolio') {
        $portfolio = get_field('portfolio', $post_id);
        if (is_array($portfolio)) {
            $section = trim((string) ($portfolio['title_section'] ?? ''));
            if ($section === '') {
                $section = __('Portfolio', 'akademiata');
            }
            akademiata_schema_push_has_part($parts, $section, $portfolio['title'] ?? '', $portfolio['description'] ?? '');
            foreach ($portfolio['portfolio_works'] ?? array() as $work) {
                if (!is_array($work)) {
                    continue;
                }
                akademiata_schema_push_has_part($parts, $section, '', $work['details'] ?? '', 35);
            }
            if (!empty($portfolio['portfolio_works_description'])) {
                akademiata_schema_push_has_part($parts, $section, '', $portfolio['portfolio_works_description'], 35);
            }
        }
    } elseif ($last_step === 'exam') {
        $exam = get_field('exam', $post_id);
        if (is_array($exam)) {
            $section = trim((string) ($exam['title'] ?? ''));
            if ($section === '') {
                $section = __('Egzamin', 'akademiata');
            }
            $body = trim(wp_strip_all_tags(
                (string) ($exam['sub_title'] ?? '') . "\n\n" .
                (string) ($exam['content'] ?? '') . "\n\n" .
                (string) ($exam['details'] ?? '')
            ));
            akademiata_schema_push_has_part($parts, $section, $section, $body, 40);

            $kurs = $exam['kurs'] ?? null;
            if (is_array($kurs)) {
                $kurs_section = trim((string) ($kurs['title'] ?? ''));
                if ($kurs_section === '') {
                    $kurs_section = __('Kurs przygotowawczy', 'akademiata');
                }
                akademiata_schema_push_has_part($parts, $kurs_section, $kurs_section, $kurs['content'] ?? '', 40);
            }
        }
    }

    return $parts;
}
