<?php
/**
 * JSON-LD Event schema for exams (egzaminy) singles.
 *
 * Template: template-parts/content/content-single-exams.php (single-exams.php)
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_exams_schema($post_id) {
    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'exams') {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $permalink = $base['permalink'];
    $title     = $base['title'];

    $schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Event',
        '@id'         => $permalink . '#event',
        'name'        => $title,
        'url'         => $permalink,
        'organizer'   => akademiata_get_schema_provider(),
        'eventStatus' => 'https://schema.org/EventScheduled',
    );

    $description = akademiata_schema_build_description($post_id, 'exams');
    if ($description !== '') {
        $schema['description'] = $description;
    }

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['image'] = $image;
    }

    $keywords = akademiata_schema_collect_keywords($post_id, 'exams');
    if ($keywords !== array()) {
        $schema['keywords'] = implode(', ', $keywords);
    }

    if (akademiata_schema_exam_registration_closed($post_id)) {
        $schema['eventStatus'] = 'https://schema.org/EventCancelled';
    }

    foreach (akademiata_schema_term_names($post_id, 'exam_date') as $date_label) {
        $parsed_date = akademiata_schema_parse_event_date($date_label);
        if ($parsed_date !== '') {
            $schema['startDate'] = $parsed_date;
            break;
        }
    }

    $city_name     = akademiata_schema_first_term_name($post_id, 'exam_city');
    $location_name = akademiata_schema_first_term_name($post_id, 'exam_location');
    $place_name    = $location_name !== '' ? $location_name : $city_name;

    if ($place_name !== '') {
        $location = array(
            '@type' => 'Place',
            'name'  => $place_name,
        );
        if ($city_name !== '') {
            $location['address'] = array(
                '@type'           => 'PostalAddress',
                'addressLocality' => $city_name,
                'addressCountry'  => 'PL',
            );
        }
        $schema['location'] = $location;
    }

    $location_hay = strtolower($city_name . ' ' . $location_name);
    if (strpos($location_hay, 'online') !== false || strpos($location_hay, 'zdaln') !== false) {
        $schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
    } else {
        $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
    }

    $register_url = trim((string) get_field('register_url', $post_id));
    if (akademiata_schema_exam_registration_closed($post_id)) {
        $register_url = '';
    }

    $offers = akademiata_schema_build_taxonomy_offers(
        $post_id,
        array('exam_price'),
        $permalink,
        $register_url
    );
    if ($offers !== array()) {
        $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
    }

    return akademiata_schema_append_register_action($schema, $register_url);
}

function akademiata_output_exams_schema() {
    if (is_admin() || !is_singular('exams')) {
        return;
    }

    akademiata_schema_output_json_ld(
        akademiata_build_exams_schema((int) get_queried_object_id())
    );
}

add_action('wp_head', 'akademiata_output_exams_schema', 20);
