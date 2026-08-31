<?php
/**
 * JSON-LD EducationalOccupationalProgram for MBA singles.
 *
 * - optional schema_seo_description (manual SEO, overrides auto description)
 *
 * Template: template-parts/content/content-single-pg-mba.php (single-mba.php)
 *
 * ACF mapped from template:
 * - register_url, offer_partners
 * - why_study_* + why_study_accordion
 * - study_program_structure_* + study_program_structure_accordion + study_program_structure_button
 * - show_cadre_section, cadre_* (kadra)
 * - payments, more_info, full_time, part_time, tab_label_*, title_*_column
 * - discounts_* + discounts_accordion
 * - admission_rules_* + admission_rules_accordion
 * - contact via options: contact_mba / contact_warsaw
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_mba_program_schema($post_id) {
    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'mba') {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $permalink = $base['permalink'];
    $title     = $base['title'];

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'EducationalOccupationalProgram',
        '@id'              => $permalink . '#program',
        'mainEntityOfPage' => $permalink,
        'name'             => $title,
        'url'        => $permalink,
        'provider'   => akademiata_get_schema_provider(),
        'identifier' => (string) $post_id,
    );

    $description = akademiata_schema_build_description($post_id, 'mba');
    if ($description !== '') {
        $schema['description'] = $description;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    $keywords = akademiata_schema_collect_keywords($post_id, 'mba');
    if ($keywords !== array()) {
        $schema['keywords'] = implode(', ', $keywords);
    }

    $modules = akademiata_schema_program_modules(
        akademiata_schema_pg_mba_accordion_labels(get_field('study_program_structure_accordion', $post_id))
    );
    if ($modules !== array()) {
        $schema['hasCourse'] = count($modules) === 1 ? $modules[0] : $modules;
    }

    $prerequisites = akademiata_schema_pg_mba_accordion_labels(get_field('admission_rules_accordion', $post_id));
    if ($prerequisites !== array()) {
        $schema['programPrerequisites'] = array_slice($prerequisites, 0, 12);
    }

    $has_parts = akademiata_schema_pg_mba_collect_has_parts($post_id);
    if ($has_parts !== array()) {
        $schema['subjectOf'] = $has_parts;
    }

    $program_pdf = akademiata_schema_study_program_pdf_url($post_id);
    if ($program_pdf !== '') {
        $schema['workFeatured'] = array(
            '@type'          => 'CreativeWork',
            'name'           => __('Study program (PDF)', 'akademiata'),
            'url'            => $program_pdf,
            'encodingFormat' => 'application/pdf',
        );
    }

    $schema['audience'] = array(
        '@type'        => 'EducationalAudience',
        'audienceType' => __('Prospective students', 'akademiata'),
    );

    $register_url = trim((string) get_field('register_url', $post_id));
    $campus_place = array();

    $city_terms = akademiata_schema_get_terms($post_id, 'city_pg_mba');
    $mode_terms = array_merge(
        akademiata_schema_get_terms($post_id, 'form_pg_mba'),
        akademiata_schema_get_terms($post_id, 'mode_course')
    );
    $duration_terms = akademiata_schema_get_terms($post_id, 'duration_pg_mba');
    $diploma_terms  = akademiata_schema_get_terms($post_id, 'diploma_pg_mba');
    $type_terms     = akademiata_schema_get_terms($post_id, 'type_of_study_pg_mba');
    $language_names = akademiata_schema_term_names($post_id, 'language_pg_mba');

    if ($language_names !== array()) {
        $schema['availableLanguage'] = $language_names;
        $schema['inLanguage']        = (stripos($language_names[0], 'ang') !== false) ? 'en' : 'pl';
    }

    $schema['programType']            = akademiata_schema_program_type_label('mba');
    $schema['educationalProgramMode'] = akademiata_schema_educational_program_mode($mode_terms);

    if ($type_terms !== array()) {
        $schema['occupationalCategory'] = $type_terms[0]->name;
    }

    if ($diploma_terms !== array()) {
        $schema['educationalCredentialAwarded'] = $diploma_terms[0]->name;
    }

    if ($duration_terms !== array()) {
        $duration_iso = akademiata_schema_parse_duration_iso($duration_terms[0]->name);
        if ($duration_iso !== '') {
            $schema['timeToComplete'] = $duration_iso;
        }
    }

    if ($city_terms !== array()) {
        $campus_place = akademiata_schema_place_from_city($city_terms[0]->name);
    }

    $instructors = akademiata_schema_pg_mba_instructors($post_id);
    if ($instructors !== array()) {
        $schema['instructor'] = count($instructors) === 1 ? $instructors[0] : $instructors;
    }

    $contact_point = akademiata_schema_pg_mba_contact_point($post_id, 'mba');
    if (is_array($contact_point)) {
        $schema['provider'] = akademiata_get_schema_provider();
        $schema['provider']['contactPoint'] = $contact_point;
    }

    $offers = akademiata_schema_pg_mba_collect_offers($post_id, $permalink, $register_url, $campus_place);
    if ($offers !== array()) {
        $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
    }

    return akademiata_schema_append_register_action($schema, $register_url);
}

function akademiata_output_mba_program_schema() {
    if (is_admin() || !is_singular('mba')) {
        return;
    }

    akademiata_schema_output_json_ld(
        akademiata_build_mba_program_schema((int) get_queried_object_id())
    );
}

add_action('wp_head', 'akademiata_output_mba_program_schema', 20);
