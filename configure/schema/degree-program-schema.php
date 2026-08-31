<?php
/**
 * Shared EducationalOccupationalProgram builder for bachelor and master.
 *
 * Template: template-parts/content/content-single-offer.php
 * Singles: single-bachelor.php, single-master.php
 *
 * @package akademiata
 */

/**
 * @param int    $post_id
 * @param string $expected_post_type bachelor|master
 * @return array<string, mixed>|null
 */
function akademiata_schema_build_degree_program($post_id, $expected_post_type) {
    $post_id   = (int) $post_id;
    $post_type = get_post_type($post_id);

    if ($post_type !== $expected_post_type) {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $permalink = $base['permalink'];
    $title     = $base['title'];
    $clean_title = akademiata_schema_clean_text($title);

    $logical_sync_key = trim((string) get_post_meta($post_id, 'logical_sync_key', true));
    $price_row        = $logical_sync_key !== '' ? akademiata_find_bachelor_master_price_row($logical_sync_key) : null;
    $program_pdf      = akademiata_schema_study_program_pdf_url($post_id);

    $schema = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'EducationalOccupationalProgram',
        '@id'              => $permalink . '#program',
        'mainEntityOfPage' => $permalink,
        'name'             => $clean_title !== '' ? $clean_title : $title,
        'url'              => $permalink,
        'provider'         => akademiata_get_schema_provider(),
        'identifier'       => (string) $post_id,
    );

    $description = akademiata_schema_get_manual_description($post_id);
    if ($description === '') {
        $description = akademiata_schema_degree_auto_description($post_id, $post_type, $price_row);
    }
    if ($description === '') {
        $description = akademiata_schema_build_description($post_id, $post_type);
    }
    if ($description !== '') {
        $schema['description'] = $description;
    }

    if (is_array($price_row) && !empty($price_row['s'])) {
        $schema['alternateName'] = (string) $price_row['s'];
    } elseif (is_array($price_row) && !empty($price_row['k'])) {
        $schema['alternateName'] = sprintf(
            /* translators: %s: field of study */
            __('Kierunek: %s', 'akademiata'),
            (string) $price_row['k']
        );
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    $keywords = akademiata_schema_collect_keywords($post_id, $post_type);
    if ($keywords !== array()) {
        $schema['keywords'] = implode(', ', $keywords);
    }

    $modules = akademiata_schema_program_modules(akademiata_schema_collect_teaches($post_id, $post_type));
    if ($modules !== array()) {
        $schema['hasCourse'] = count($modules) === 1 ? $modules[0] : $modules;
    }

    $documents_prereq = akademiata_schema_degree_program_prerequisites($post_id, $post_type);
    if ($documents_prereq !== '') {
        $schema['programPrerequisites'] = $documents_prereq;
    }

    $subject_of = akademiata_schema_degree_collect_subject_of($post_id, $post_type, $price_row, $program_pdf);
    if ($subject_of !== array()) {
        $schema['subjectOf'] = $subject_of;
    }

    if ($program_pdf !== '') {
        $schema['workFeatured'] = array(
            '@type'          => 'CreativeWork',
            'name'           => __('Program studiów (PDF)', 'akademiata'),
            'url'            => $program_pdf,
            'encodingFormat' => 'application/pdf',
        );
    }

    $schema['audience'] = array(
        '@type'        => 'EducationalAudience',
        'audienceType' => __('Kandydaci na studia', 'akademiata'),
    );

    $register_url = trim((string) get_field('register_url', $post_id));
    $offer_url    = $register_url;
    $campus_place = array();

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

    $schema['programType'] = akademiata_schema_program_type_label($post_type, $degree_terms);

    $program_modes = akademiata_schema_educational_program_modes($mode_terms);
    $schema['educationalProgramMode'] = count($program_modes) === 1 ? $program_modes[0] : $program_modes;

    $occupational = akademiata_schema_degree_occupational_categories($post_id);
    if ($occupational !== array()) {
        $schema['occupationalCategory'] = count($occupational) === 1 ? $occupational[0] : $occupational;
    } elseif ($program_terms !== array()) {
        $schema['occupationalCategory'] = $program_terms[0]->name;
    }

    if ($title_terms !== array()) {
        $schema['educationalCredentialAwarded'] = $title_terms[0]->name;
    }

    $duration_names = akademiata_schema_term_names($post_id, 'duration');
    $duration_iso   = akademiata_schema_parse_duration_iso($duration_names[0] ?? '');
    if ($duration_iso !== '') {
        $schema['timeToComplete'] = $duration_iso;
    }

    $ects = akademiata_schema_get_ects_credits($post_id);
    if ($ects !== null) {
        $schema['numberOfCredits'] = $ects;
    }

    if ($city_terms !== array()) {
        $campus_place = akademiata_schema_place_from_city($city_terms[0]->name);
    }

    if ($logical_sync_key !== '') {
        $schema['sku'] = $logical_sync_key;
    }

    if ($offer_url === '' && $logical_sync_key !== '') {
        $offer_url = akademiata_get_smart_apply_url_for_key($logical_sync_key);
    }

    if (is_array($price_row)) {
        if (!empty($price_row['k']) && empty($occupational) && empty($schema['occupationalCategory'])) {
            $schema['occupationalCategory'] = (string) $price_row['k'];
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
            $lang_code,
            $permalink
        );
        $offers = akademiata_schema_offers_with_location($offers, $campus_place);
        if ($offers !== array()) {
            $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
        }
    }

    $apply_url = $register_url !== '' ? $register_url : $offer_url;

    return akademiata_schema_append_register_action($schema, $apply_url);
}

