<?php
/**
 * Sidebar filters for postgraduate / MBA archives.
 */

$post_type  = get_query_var('pg_mba_filter_post_type');
$taxonomies = akademiata_get_pg_mba_filter_taxonomies();

if (!$post_type) {
    $post_type = akademiata_get_pg_mba_archive_post_type();
}

if (!in_array($post_type, array('postgraduate', 'mba'), true)) {
    return;
}
?>
<div id="scroller" class="filter_side">
    <form id="ajax-filter-pg-mba-form">
        <?php foreach ($taxonomies as $taxonomy => $taxonomy_name) :
            $terms = akademiata_get_taxonomy_terms_for_post_type($taxonomy, $post_type);

            if (empty($terms)) {
                continue;
            }
            ?>
            <div class="taxonomy_group mb-3">
                <h2 class="filter_accordion_header active" data-tax="<?php echo esc_attr($taxonomy); ?>">
                    <?php echo esc_html($taxonomy_name); ?>
                    <div class="arrow-open-close"></div>
                </h2>
                <div class="accordion-content" style="display: block">
                    <div class="labels_list">
                        <?php
                        $selected_terms = isset($_GET[ $taxonomy ]) ? (array) $_GET[ $taxonomy ] : array();

                        foreach ($terms as $term) :
                            $checked = in_array($term->slug, $selected_terms, true) ? 'checked' : '';
                            ?>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo esc_attr($taxonomy); ?>[]"
                                       value="<?php echo esc_attr($term->slug); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html($term->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</div>
