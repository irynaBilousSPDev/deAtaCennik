<?php
/**
 * Per-CPT JSON-LD schema modules.
 *
 * @package akademiata
 */

$schema_dir = __DIR__ . '/schema';

require $schema_dir . '/schema-helpers.php';
require $schema_dir . '/site-schema-helpers.php';
require $schema_dir . '/homepage-schema-helpers.php';
require $schema_dir . '/pg-mba-schema-helpers.php';
require $schema_dir . '/degree-program-schema-helpers.php';
require $schema_dir . '/degree-program-schema.php';
require $schema_dir . '/bachelor-schema.php';
require $schema_dir . '/master-schema.php';
require $schema_dir . '/postgraduate-schema.php';
require $schema_dir . '/mba-schema.php';
require $schema_dir . '/courses-schema.php';
require $schema_dir . '/exams-schema.php';
require $schema_dir . '/homepage-schema.php';
require $schema_dir . '/page-schema.php';
require $schema_dir . '/news-schema.php';
require $schema_dir . '/archive-schema.php';
require $schema_dir . '/site-schema.php';

/** @deprecated Use akademiata_build_bachelor_program_schema() or akademiata_build_master_program_schema() */
function akademiata_build_offer_program_schema($post_id) {
    $post_type = get_post_type((int) $post_id);
    if ($post_type === 'bachelor') {
        return akademiata_build_bachelor_program_schema($post_id);
    }
    if ($post_type === 'master') {
        return akademiata_build_master_program_schema($post_id);
    }
    if ($post_type === 'postgraduate') {
        return akademiata_build_postgraduate_program_schema($post_id);
    }
    if ($post_type === 'mba') {
        return akademiata_build_mba_program_schema($post_id);
    }

    return null;
}

/** @deprecated Use per-CPT output hooks in configure/schema/*.php */
function akademiata_build_offer_course_schema($post_id) {
    return akademiata_build_offer_program_schema($post_id);
}

/** @deprecated */
function akademiata_should_output_offer_program_schema() {
    if (is_admin()) {
        return false;
    }

    return is_singular(array('bachelor', 'master', 'postgraduate', 'mba', 'courses', 'exams'));
}

/** @deprecated */
function akademiata_output_offer_program_schema() {
    // No-op: each CPT registers its own wp_head callback.
}

/** @deprecated */
function akademiata_output_offer_course_schema() {
    // No-op: each CPT registers its own wp_head callback.
}
