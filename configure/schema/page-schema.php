<?php
/**
 * JSON-LD for WordPress pages — routes by page template.
 *
 * Reads live ACF / post content (updates after page save; prod HTML cache may need WP Rocket clear).
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<int, array<string, mixed>>|null Graph nodes or single schema wrapped as one node.
 */
function akademiata_build_page_schema_nodes($post_id) {
    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'page') {
        return null;
    }

    $template  = akademiata_schema_get_page_template_file($post_id);
    $special   = akademiata_schema_special_page_builders();
    $lp_map    = akademiata_schema_lp_page_templates();

    if ($template !== '' && isset($special[ $template ]) && is_callable($special[ $template ])) {
        return call_user_func($special[ $template ], $post_id);
    }

    if ($template !== '' && isset($lp_map[ $template ])) {
        $nodes = akademiata_build_lp_page_schema($post_id);
        if ($nodes !== null) {
            return $nodes;
        }
    }

    return akademiata_build_default_page_schema($post_id);
}

/**
 * @param int $post_id
 * @return array<string, mixed>|null Deprecated single-node return; prefer nodes builder.
 */
function akademiata_build_page_schema($post_id) {
    $nodes = akademiata_build_page_schema_nodes($post_id);
    if (!is_array($nodes) || $nodes === array()) {
        return null;
    }

    if (count($nodes) === 1) {
        return $nodes[0];
    }

    foreach ($nodes as &$node) {
        unset($node['@context']);
    }
    unset($node);

    return array(
        '@context' => 'https://schema.org',
        '@graph'   => $nodes,
    );
}

function akademiata_output_page_schema() {
    if (is_admin() || !is_singular('page') || is_front_page()) {
        return;
    }

    $nodes = akademiata_build_page_schema_nodes((int) get_queried_object_id());
    if (!is_array($nodes) || $nodes === array()) {
        return;
    }

    akademiata_schema_output_json_ld_graph($nodes);
}
