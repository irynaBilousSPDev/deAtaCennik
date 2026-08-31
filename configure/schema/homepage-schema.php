<?php
/**
 * JSON-LD for the front page — Organization, WebSite, WebPage (@graph).
 *
 * @package akademiata
 */

/**
 * @return array<int, array<string, mixed>>
 */
function akademiata_build_homepage_schema_graph() {
    $page_id    = (int) get_queried_object_id();
    $acf_fields = function_exists('get_fields') ? (get_fields($page_id) ?: array()) : array();
    if (!is_array($acf_fields)) {
        $acf_fields = array();
    }

    if (empty($acf_fields['two_column_banner']) && function_exists('get_field')) {
        $banners = get_field('two_column_banner', $page_id);
        if (is_array($banners)) {
            $acf_fields['two_column_banner'] = $banners;
        }
    }

    $org     = akademiata_get_schema_homepage_organization($acf_fields);
    $website = akademiata_schema_build_homepage_website();
    $webpage = akademiata_schema_build_homepage_webpage($acf_fields);

    return array($org, $website, $webpage);
}

function akademiata_output_homepage_schema() {
    if (is_admin() || !is_front_page()) {
        return;
    }

    akademiata_schema_output_json_ld_graph(akademiata_build_homepage_schema_graph());
}
