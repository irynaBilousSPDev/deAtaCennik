<div id="scroller" class="filter_side">
    <?php
    $taxonomies = array(
//        'recruitment_date' => akademiata_get_theme_lang_string('...'),
        'degree'         => akademiata_get_theme_lang_string('offer_filter_degree'),
        'city'         => akademiata_get_theme_lang_string('offer_chip_city'),
        'program'        => akademiata_get_theme_lang_string('offer_chip_program'),
//        'department'     => __('Wydział', 'akademiata'),
        'language'       => akademiata_get_theme_lang_string('offer_filter_language'),
        'duration'       => akademiata_get_theme_lang_string('offer_filter_duration'),
        'obtained_title' => akademiata_get_theme_lang_string('offer_filter_obtained_title'),
        'post_tag'       => akademiata_get_theme_lang_string('offer_filter_interests'),
        'mode'           => akademiata_get_theme_lang_string('offer_filter_mode'),
    );
    // Get the current page slug
    $current_page_slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    ?>

    <form id="ajax-filter-form">
        <?php foreach ($taxonomies as $taxonomy => $taxonomy_name) :
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]);

            // Skip the "degree" taxonomy if the page slug matches a term slug
            if ($taxonomy === 'degree' && !empty($terms)) {
                $matching_term = array_filter($terms, function ($term) use ($current_page_slug) {
                    return $term->slug === basename($current_page_slug); // Check if slug matches
                });

                if (!empty($matching_term)) {
                    continue; // Skip this taxonomy group
                }
            }
            ?>

            <?php if (!is_wp_error($terms)) : ?>
            <div class="taxonomy_group mb-3">
                <h2 class="filter_accordion_header" data-tax="<?php echo esc_attr($taxonomy); ?>">
                    <?php echo esc_html($taxonomy_name); ?>
                    <div class="arrow-open-close" aria-hidden="true"></div>
                </h2>
                <div class="accordion-content">
                    <div class="labels_list">
                        <?php
                        $selected_terms = isset($_GET[$taxonomy]) ? (array)$_GET[$taxonomy] : [];

                        foreach ($terms as $term) :
                            $checked = in_array($term->slug, $selected_terms) ? 'checked' : '';
                            ?>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo esc_attr($taxonomy); ?>[]"
                                       value="<?php echo esc_attr($term->slug); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html($term->name); ?>
<!--                                --><?php //echo esc_html($term->name . ' [' . $term->count . ']'); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </form>
</div>