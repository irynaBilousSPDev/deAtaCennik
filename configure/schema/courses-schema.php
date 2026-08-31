<?php
/**
 * JSON-LD Course schema for courses (kursy) singles.
 *
 * Template: template-parts/content/content-single-courses.php (single-courses.php)
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_courses_schema($post_id) {
    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'courses') {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $permalink = $base['permalink'];
    $title     = $base['title'];

    $schema = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Course',
        '@id'      => $permalink . '#course',
        'name'     => $title,
        'url'      => $permalink,
        'provider' => akademiata_get_schema_provider(),
    );

    $description = akademiata_schema_build_description($post_id, 'courses');
    if ($description !== '') {
        $schema['description'] = $description;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    $keywords = akademiata_schema_collect_keywords($post_id, 'courses');
    if ($keywords !== array()) {
        $schema['keywords'] = implode(', ', $keywords);
    }

    $mode_terms = akademiata_schema_get_terms($post_id, 'mode_course');
    $schema['courseMode'] = akademiata_schema_course_mode_from_terms($mode_terms);

    $duration_text = akademiata_schema_first_term_name($post_id, 'duration_course');
    $duration_iso  = akademiata_schema_parse_duration_iso($duration_text);
    if ($duration_iso !== '') {
        $schema['timeRequired'] = $duration_iso;
    }

    $language_names = akademiata_schema_term_names($post_id, 'language');
    if ($language_names !== array()) {
        $schema['inLanguage']        = (stripos($language_names[0], 'ang') !== false) ? 'en' : 'pl';
        $schema['availableLanguage'] = $language_names;
    }

    $city_name = akademiata_schema_first_term_name($post_id, 'city_pg_mba');
    $place     = akademiata_schema_place_from_city($city_name);

    $instructor_names = akademiata_schema_term_names($post_id, 'instructor_course');
    if ($instructor_names !== array()) {
        $instructors = array();
        foreach ($instructor_names as $name) {
            $instructors[] = array(
                '@type' => 'Person',
                'name'  => $name,
            );
        }
        $schema['instructor'] = count($instructors) === 1 ? $instructors[0] : $instructors;
    }

    $module_titles = akademiata_schema_collect_teaches($post_id, 'courses');
    if ($module_titles !== array()) {
        $schema['teaches'] = $module_titles;
    }

    $register_url = trim((string) get_field('register_url', $post_id));
    $offers       = akademiata_schema_build_taxonomy_offers(
        $post_id,
        array('price_course', 'fee_course'),
        $permalink,
        $register_url
    );
    $offers = akademiata_schema_offers_with_location($offers, $place);
    if ($offers !== array()) {
        $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
    }

    return akademiata_schema_append_register_action($schema, $register_url);
}

function akademiata_output_courses_schema() {
    if (is_admin() || !is_singular('courses')) {
        return;
    }

    akademiata_schema_output_json_ld(
        akademiata_build_courses_schema((int) get_queried_object_id())
    );
}

add_action('wp_head', 'akademiata_output_courses_schema', 20);
