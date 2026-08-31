<?php
/**
 * JSON-LD CollectionPage for CPT/taxonomy/search archives.
 *
 * @package akademiata
 */

/**
 * @return array<string, mixed>|null
 */
function akademiata_build_archive_schema() {
    if (akademiata_schema_is_news_archive_view()) {
        return null;
    }

    $is_archive = is_post_type_archive()
        || is_tax()
        || is_category()
        || is_tag()
        || is_author()
        || is_search()
        || (is_home() && !is_front_page());

    if (!$is_archive) {
        return null;
    }

    $url   = akademiata_schema_current_canonical_url();
    $title = akademiata_schema_get_archive_heading();
    if ($title === '') {
        return null;
    }

    $description = '';
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $description = akademiata_schema_trim_text(term_description($term), 55);
        }
    }

    $posts = array();
    if (is_tax('program')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $posts = akademiata_schema_query_program_taxonomy_posts($term, 12);
        }
    } else {
        $posts = akademiata_schema_get_main_archive_posts(12);
    }

    $item_list = akademiata_schema_build_item_list($posts, $title, $url);

    return akademiata_schema_build_collection_page($url, $title, $description, $item_list);
}

function akademiata_output_archive_schema() {
    if (is_admin()) {
        return;
    }

    akademiata_schema_output_json_ld(akademiata_build_archive_schema());
}
