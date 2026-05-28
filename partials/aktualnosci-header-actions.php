<?php
/**
 * City links + optional "Zobacz wszystkie" (archive layout).
 *
 * Args: current_city_slug, see_all_url, show_see_all (default true), preserve_query_args, variant.
 */

$current_city_slug   = isset($args['current_city_slug']) ? sanitize_title((string) $args['current_city_slug']) : '';
$see_all_url         = !empty($args['see_all_url']) ? $args['see_all_url'] : akademiata_get_aktualnosci_page_url();
$show_see_all        = !isset($args['show_see_all']) || $args['show_see_all'];
$preserve_query_args = isset($args['preserve_query_args']) && is_array($args['preserve_query_args'])
    ? $args['preserve_query_args']
    : array();
$variant             = isset($args['variant']) ? sanitize_key((string) $args['variant']) : 'default';

get_template_part(
    'partials/aktualnosci',
    'city-links',
    array(
        'current_city_slug'   => $current_city_slug,
        'see_all_url'         => $see_all_url,
        'show_see_all'        => $show_see_all,
        'preserve_query_args' => $preserve_query_args,
        'variant'             => $variant,
    )
);
