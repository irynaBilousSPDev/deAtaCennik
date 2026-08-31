<?php
/**
 * JSON-LD WebPage for static pages.
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_page_schema($post_id) {
    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'page') {
        return null;
    }

    $base = akademiata_schema_published_post_base($post_id);
    if ($base === null) {
        return null;
    }

    $schema = akademiata_schema_build_webpage(
        $base['permalink'],
        $base['title'],
        akademiata_schema_page_description($post_id)
    );

    $image = get_the_post_thumbnail_url($post_id, 'full');
    if ($image) {
        $schema['primaryImageOfPage'] = $image;
    }

    $modified = get_the_modified_date('c', $post_id);
    if ($modified) {
        $schema['dateModified'] = $modified;
    }

    return $schema;
}

function akademiata_output_page_schema() {
    if (is_admin() || !is_singular('page') || is_front_page()) {
        return;
    }

    akademiata_schema_output_json_ld(
        akademiata_build_page_schema((int) get_queried_object_id())
    );
}
