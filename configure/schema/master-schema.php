<?php
/**
 * JSON-LD EducationalOccupationalProgram for master singles.
 *
 * Template: template-parts/content/content-single-offer.php (single-master.php)
 *
 * @package akademiata
 */

/**
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function akademiata_build_master_program_schema($post_id) {
    return akademiata_schema_build_degree_program((int) $post_id, 'master');
}

function akademiata_output_master_program_schema() {
    if (is_admin() || !is_singular('master')) {
        return;
    }

    akademiata_schema_output_json_ld(
        akademiata_build_master_program_schema((int) get_queried_object_id())
    );
}

add_action('wp_head', 'akademiata_output_master_program_schema', 20);
