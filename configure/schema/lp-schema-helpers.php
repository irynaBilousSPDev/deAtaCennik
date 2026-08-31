<?php
/**
 * LP / page ACF → JSON-LD subjectOf (full section coverage for robots).
 *
 * Reads live ACF via the same *_fields() merge helpers as page templates — updates on page save.
 *
 * @package akademiata
 */

/**
 * @param array<string, mixed> $section
 */
function akademiata_schema_lp_section_label(array $section, $section_key = '') {
    foreach (array('title', 'panel_title', 'label_title', 'eyebrow', 'watermark') as $key) {
        $val = akademiata_schema_clean_text($section[ $key ] ?? '');
        if ($val !== '') {
            return $val;
        }
    }

    $composed = trim(
        akademiata_schema_clean_text($section['title_before'] ?? '')
        . ' '
        . akademiata_schema_clean_text($section['title_accent'] ?? '')
        . ' '
        . akademiata_schema_clean_text($section['title_after'] ?? '')
    );
    if ($composed !== '') {
        return preg_replace('/\s+/u', ' ', $composed);
    }

    if ($section_key !== '') {
        $slug = preg_replace('/^(oucz|rank|rekr|kato|kreo|poro|posp|prop|stik)_/u', '', (string) $section_key);
        $slug = str_replace('_section', '', $slug);
        $slug = str_replace('_', ' ', $slug);

        return ucfirst(trim($slug));
    }

    return '';
}

/**
 * @param array<string, mixed> $section
 */
function akademiata_schema_lp_section_intro(array $section) {
    $chunks = array();

    foreach (array(
        'eyebrow',
        'lead',
        'intro',
        'text',
        'description',
        'lede',
        'label_text',
        'note_text',
        'hero_reassure',
        'text_suffix',
        'col_left',
        'highlight',
        'history_lede',
    ) as $key) {
        if (empty($section[ $key ])) {
            continue;
        }
        $chunks[] = wp_strip_all_tags((string) $section[ $key ]);
    }

    $title_bits = array_filter(array(
        akademiata_schema_clean_text($section['title_before'] ?? ''),
        akademiata_schema_clean_text($section['title_accent'] ?? ''),
        akademiata_schema_clean_text($section['title_after'] ?? ''),
        akademiata_schema_clean_text($section['title_emphasis'] ?? ''),
    ));
    if ($title_bits !== array() && empty($section['title'])) {
        $chunks[] = implode(' ', $title_bits);
    }

    return trim(preg_replace('/\s+/u', ' ', implode("\n\n", array_filter($chunks))));
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @param array<string, mixed>             $section
 */
function akademiata_schema_lp_push_section_intro(array &$parts, array $section, $section_label) {
    $section_label = trim((string) $section_label);
    if ($section_label === '') {
        return;
    }

    $intro = akademiata_schema_lp_section_intro($section);
    if ($intro === '') {
        return;
    }

    $parts[] = akademiata_schema_creative_work(
        $section_label,
        akademiata_schema_robot_summary($section_label, $intro, 45)
    );
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @param array<int, array<string, mixed>> $rows
 */
function akademiata_schema_lp_push_stat_rows(array &$parts, array $rows, $section_label, $group_name = '') {
    $group_name = trim((string) $group_name);
    if ($group_name === '') {
        $group_name = __('Statystyki', 'akademiata');
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $value = trim(wp_strip_all_tags((string) ($row['value'] ?? $row['number'] ?? '')));
        $label = trim(wp_strip_all_tags((string) ($row['label'] ?? $row['text'] ?? '')));
        $note  = trim(wp_strip_all_tags((string) ($row['note'] ?? '')));

        if ($value === '' && $label === '') {
            continue;
        }

        $name = trim($value . ' ' . $label);
        $body = $note !== '' ? $note : $label;
        akademiata_schema_push_has_part($parts, $section_label, $name, $body, 40);
    }
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @param array<int, array<string, mixed>> $rows
 */
function akademiata_schema_lp_push_generic_rows(array &$parts, array $rows, $section_label, $word_limit = 40) {
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $title = trim(wp_strip_all_tags((string) (
            $row['title']
            ?? $row['name']
            ?? $row['alt']
            ?? $row['question']
            ?? $row['step_label']
            ?? $row['label']
            ?? $row['place']
            ?? $row['text']
            ?? ''
        )));

        $body_parts = array();
        foreach (array(
            'text',
            'answer',
            'description',
            'intro',
            'note',
            'big_stat',
            'tag',
            'year',
            'loc',
            'level',
            'salary',
            'time_to_job',
            'unemployment',
            'badge_main',
            'badge_sub',
            'place',
            'emphasis',
            'headline_1',
            'headline_2',
            'meta',
        ) as $key) {
            if (!empty($row[ $key ]) && !is_array($row[ $key ])) {
                $body_parts[] = wp_strip_all_tags((string) $row[ $key ]);
            }
        }

        if (!empty($row['items']) && is_array($row['items'])) {
            foreach ($row['items'] as $item) {
                if (is_string($item)) {
                    $body_parts[] = wp_strip_all_tags($item);
                } elseif (is_array($item) && !empty($item['text'])) {
                    $body_parts[] = wp_strip_all_tags((string) $item['text']);
                }
            }
        }

        if (!empty($row['facts']) && is_array($row['facts'])) {
            foreach ($row['facts'] as $fact) {
                if (is_array($fact) && !empty($fact['text'])) {
                    $body_parts[] = wp_strip_all_tags((string) $fact['text']);
                }
            }
        }

        if (!empty($row['meta']) && is_array($row['meta'])) {
            foreach ($row['meta'] as $meta) {
                if (is_array($meta) && !empty($meta['label'])) {
                    $body_parts[] = wp_strip_all_tags((string) $meta['label']);
                }
            }
        }

        $body = trim(preg_replace('/\s+/u', ' ', implode('. ', array_filter($body_parts))));
        if ($title === '' && $body === '') {
            continue;
        }

        $url = trim((string) ($row['details_url'] ?? $row['url'] ?? $row['cta_primary_url'] ?? $row['cta_url'] ?? ''));
        if ($url !== '' && $title !== '') {
            $parts[] = array_merge(
                akademiata_schema_creative_work($title, akademiata_schema_robot_summary($title, $body, $word_limit), $url),
                $section_label !== '' ? array(
                    'isPartOf' => array(
                        '@type' => 'CreativeWork',
                        'name'  => $section_label,
                    ),
                ) : array()
            );
            continue;
        }

        akademiata_schema_push_has_part($parts, $section_label, $title, $body, $word_limit);
    }
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @param array<string, mixed>             $section
 */
function akademiata_schema_lp_collect_from_section(array &$parts, array $section, $section_label) {
    $section_label = trim((string) $section_label);
    if ($section_label === '') {
        return;
    }

    akademiata_schema_lp_push_section_intro($parts, $section, $section_label);

    $repeaters = array(
        'stats'         => 'stats',
        'chips'         => 'stats',
        'hero_bar'      => 'stats',
        'items'         => 'generic',
        'steps'         => 'generic',
        'cards'         => 'generic',
        'courses'       => 'generic',
        'buildings'     => 'generic',
        'pillars'       => 'generic',
        'partners'      => 'generic',
        'ranks'         => 'generic',
        'history_steps' => 'generic',
        'programs'      => 'generic',
        'paths'         => 'generic',
        'events'        => 'generic',
        'gallery'       => 'generic',
        'positions'     => 'generic',
        'downloads'     => 'generic',
        'links'         => 'links',
        'cities'        => 'generic',
        'columns'       => 'columns',
        'campuses'      => 'campuses',
    );

    foreach ($repeaters as $key => $mode) {
        if (empty($section[ $key ]) || !is_array($section[ $key ])) {
            continue;
        }

        if ($mode === 'columns') {
            foreach ($section[ $key ] as $column) {
                if (!is_array($column)) {
                    continue;
                }
                $col_label = trim(wp_strip_all_tags((string) ($column['title'] ?? '')));
                if ($col_label === '') {
                    $col_label = $section_label;
                }
                if (!empty($column['items']) && is_array($column['items'])) {
                    akademiata_schema_lp_push_generic_rows($parts, $column['items'], $col_label);
                }
            }
            continue;
        }

        if ($mode === 'campuses') {
            foreach ($section[ $key ] as $campus) {
                if (!is_array($campus)) {
                    continue;
                }
                $campus_label = trim(wp_strip_all_tags((string) ($campus['title'] ?? $campus['tag'] ?? '')));
                if ($campus_label === '') {
                    $campus_label = $section_label;
                }
                akademiata_schema_lp_push_section_intro($parts, $campus, $campus_label);
                if (!empty($campus['programs']) && is_array($campus['programs'])) {
                    akademiata_schema_lp_push_generic_rows($parts, $campus['programs'], $campus_label);
                }
            }
            continue;
        }

        if ($mode === 'stats') {
            akademiata_schema_lp_push_stat_rows($parts, $section[ $key ], $section_label);
            continue;
        }

        if ($mode === 'links') {
            foreach ($section[ $key ] as $link) {
                if (!is_array($link)) {
                    continue;
                }
                $text = trim(wp_strip_all_tags((string) ($link['text'] ?? '')));
                if ($text === '') {
                    continue;
                }
                akademiata_schema_push_has_part($parts, $section_label, $text, $text, 20);
            }
            continue;
        }

        akademiata_schema_lp_push_generic_rows($parts, $section[ $key ], $section_label);
    }

    if (!empty($section['table_rows']) && is_array($section['table_rows'])) {
        akademiata_schema_lp_push_generic_rows($parts, $section['table_rows'], $section_label);
    }

    if (!empty($section['cards']) && is_array($section['cards'])) {
        // rank_ela nested programs inside positions handled in generic if flattened later
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function akademiata_schema_lp_collect_faq_entities(array $items) {
    $entities = array();

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $question = trim(wp_strip_all_tags((string) (
            $item['question']
            ?? $item['accordion_title']
            ?? $item['title']
            ?? ''
        )));

        $answer = trim(wp_strip_all_tags((string) (
            $item['answer']
            ?? ($item['accordion_default_content']['content'] ?? '')
            ?? $item['text']
            ?? ''
        )));

        if ($question === '' || $answer === '') {
            continue;
        }

        $entities[] = array(
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => akademiata_schema_trim_text($answer, 80),
            ),
        );
    }

    return $entities;
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @param array<int, array<string, mixed>> $items
 */
function akademiata_schema_lp_push_faq_subject_of(array &$parts, array $items, $section_label) {
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $question = trim(wp_strip_all_tags((string) (
            $item['question']
            ?? $item['accordion_title']
            ?? ''
        )));
        $answer = trim(wp_strip_all_tags((string) (
            $item['answer']
            ?? ($item['accordion_default_content']['content'] ?? '')
            ?? ''
        )));

        if ($question === '') {
            continue;
        }

        akademiata_schema_push_has_part($parts, $section_label, $question, $answer, 50);
    }
}

/**
 * Load merged LP fields and collect subjectOf for all sections.
 *
 * @return array{subject_of: array<int, array<string, mixed>>, faq_entities: array<int, array<string, mixed>>}
 */
function akademiata_schema_lp_collect_page_fields($post_id, $fields_callback, $fields_file) {
    $post_id = (int) $post_id;
    $parts   = array();
    $faq     = array();

    if (!is_readable($fields_file)) {
        return array('subject_of' => $parts, 'faq_entities' => $faq);
    }

    require_once $fields_file;

    if (!function_exists($fields_callback)) {
        return array('subject_of' => $parts, 'faq_entities' => $faq);
    }

    $acf    = function_exists('get_fields') ? (get_fields($post_id) ?: array()) : array();
    $fields = call_user_func($fields_callback, $acf);

    if (!is_array($fields)) {
        return array('subject_of' => $parts, 'faq_entities' => $faq);
    }

    foreach ($fields as $section_key => $section) {
        if (!is_array($section)) {
            continue;
        }

        if ($section_key === 'oucz_subnav' || $section_key === 'rekr_quick_nav') {
            $label = __('Nawigacja po stronie', 'akademiata');
            akademiata_schema_lp_collect_from_section($parts, $section, $label);
            continue;
        }

        if (strpos((string) $section_key, '_faq_section') !== false) {
            $label = akademiata_schema_lp_section_label($section, $section_key) ?: __('FAQ', 'akademiata');
            akademiata_schema_lp_push_section_intro($parts, $section, $label);
            $items = is_array($section['items'] ?? null) ? $section['items'] : array();
            akademiata_schema_lp_push_faq_subject_of($parts, $items, $label);
            $faq = array_merge($faq, akademiata_schema_lp_collect_faq_entities($items));
            continue;
        }

        $label = akademiata_schema_lp_section_label($section, $section_key);
        if ($label === '') {
            continue;
        }

        akademiata_schema_lp_collect_from_section($parts, $section, $label);
    }

    return array(
        'subject_of'   => $parts,
        'faq_entities' => $faq,
    );
}
