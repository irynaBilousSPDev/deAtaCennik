<?php
/**
 * City links + optional "Zobacz wszystkie" (archive layout).
 *
 * Args: current_city_slug, see_all_url, show_see_all (default true).
 */

$current_city_slug = isset($args['current_city_slug']) ? sanitize_title((string) $args['current_city_slug']) : '';
$see_all_url       = !empty($args['see_all_url']) ? $args['see_all_url'] : akademiata_get_aktualnosci_page_url();
$show_see_all      = !isset($args['show_see_all']) || $args['show_see_all'];

get_template_part(
    'partials/aktualnosci',
    'city-links',
    array(
        'current_city_slug' => $current_city_slug,
        'see_all_url'       => $see_all_url,
        'show_see_all'      => $show_see_all,
    )
);
