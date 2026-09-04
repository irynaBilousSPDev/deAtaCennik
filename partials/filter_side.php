<div id="scroller" class="filter_side">
    <?php get_template_part('partials/offer-favorites-filter-desktop'); ?>
    <?php
    $taxonomies = array(
        'degree'         => akademiata_get_theme_lang_string('offer_filter_degree'),
        'city'           => akademiata_get_theme_lang_string('offer_chip_city'),
        'program'        => akademiata_get_theme_lang_string('offer_chip_program'),
        'language'       => akademiata_get_theme_lang_string('offer_filter_language'),
        'duration'       => akademiata_get_theme_lang_string('offer_filter_duration'),
        'obtained_title' => akademiata_get_theme_lang_string('offer_filter_obtained_title'),
        'post_tag'       => akademiata_get_theme_lang_string('offer_filter_interests'),
        'mode'           => akademiata_get_theme_lang_string('offer_filter_mode'),
    );
    $current_page_slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $offer_filter_action = akademiata_get_offer_filter_action();
    ?>

    <form id="ajax-filter-form"
          data-zarzadzanie-promo-block="<?php echo akademiata_is_zarzadzanie_price_override_active() ? '1' : '0'; ?>"
          data-zarzadzanie-program-slugs="<?php echo esc_attr(wp_json_encode(akademiata_get_zarzadzanie_program_filter_slugs())); ?>"
          data-zarzadzanie-allowed-promos="<?php echo esc_attr(wp_json_encode(akademiata_get_zarzadzanie_listing_allowed_promo_ids())); ?>"
          data-offer-filter-action="<?php echo esc_attr($offer_filter_action); ?>">
        <?php foreach ($taxonomies as $taxonomy => $taxonomy_name) :
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]);

            if ($taxonomy === 'degree' && !empty($terms)) {
                $matching_term = array_filter($terms, function ($term) use ($current_page_slug) {
                    return $term->slug === basename($current_page_slug);
                });

                if (!empty($matching_term)) {
                    continue;
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
                        $selected_terms = akademiata_get_selected_filter_terms_from_request($taxonomy);

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
        <?php endif; ?>

            <?php if ($taxonomy === 'city') :
                akademiata_render_offer_promotions_filter_group($offer_filter_action);
            endif; ?>

        <?php endforeach; ?>
    </form>
</div>
