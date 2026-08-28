<?php
/**
 * Mobile offer toolbar — search, quick filter chips, view toggle, clear.
 */

$current_page_slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$offer_filter_action = akademiata_get_offer_filter_action();
$has_promo_filter    = !empty(akademiata_get_listing_promos_for_filter($offer_filter_action));
$promotions_label    = akademiata_get_theme_lang_string('offer_filter_promotions');
$quick_chips       = [
    'degree'   => akademiata_get_theme_lang_string('offer_chip_degree'),
    'city'     => akademiata_get_theme_lang_string('offer_chip_city'),
    'program'  => akademiata_get_theme_lang_string('offer_chip_program'),
    'language' => akademiata_get_theme_lang_string('offer_chip_language'),
];
$chip_chevron = '<svg class="offer-mobile-chip__chevron" width="10" height="10" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>';
?>
<div class="offer-mobile-toolbar" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_toolbar_aria')); ?>">
    <div class="offer-mobile-chips" role="toolbar" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_quick_filters_aria')); ?>">
        <div class="offer-mobile-chips__row">
            <button type="button" class="offer-mobile-chip is-active" data-tax="all">
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_chip_all')); ?>
            </button>
            <?php get_template_part('partials/offer-favorites-chip'); ?>
            <?php foreach (['degree' => $quick_chips['degree'], 'city' => $quick_chips['city'], 'program' => $quick_chips['program']] as $taxonomy => $label) : ?>
                <?php
                if ($taxonomy === 'degree' && !in_array($current_page_slug, ['offer', 'oferta'], true)) {
                    continue;
                }
                ?>
                <button type="button"
                        class="offer-mobile-chip offer-mobile-chip--dropdown"
                        data-tax="<?php echo esc_attr($taxonomy); ?>"
                        data-label="<?php echo esc_attr($label); ?>">
                    <?php echo esc_html($label); ?>
                    <?php echo $chip_chevron; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="offer-mobile-chips__row">
            <button type="button"
                    class="offer-mobile-chip offer-mobile-chip--dropdown"
                    data-tax="language"
                    data-label="<?php echo esc_attr($quick_chips['language']); ?>">
                <?php echo esc_html($quick_chips['language']); ?>
                <?php echo $chip_chevron; ?>
            </button>
            <?php if ($has_promo_filter) : ?>
                <button type="button"
                        class="offer-mobile-chip offer-mobile-chip--dropdown offer-mobile-chip--promotions"
                        data-tax="promotions"
                        data-label="<?php echo esc_attr($promotions_label); ?>">
                    <?php echo akademiata_get_promotions_filter_badge_html(); ?>
                    <?php echo esc_html($promotions_label); ?>
                    <?php echo $chip_chevron; ?>
                </button>
            <?php endif; ?>
            <button type="button"
                    class="offer-mobile-chip offer-mobile-chip--more"
                    data-tax="more">
                <svg class="offer-mobile-chip__settings" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M4 6h16v2H4V6zm3 5h10v2H7v-2zm4 5h2v2h-2v-2z"/>
                    <circle fill="currentColor" cx="8" cy="7" r="2"/>
                    <circle fill="currentColor" cx="16" cy="12" r="2"/>
                    <circle fill="currentColor" cx="10" cy="17" r="2"/>
                </svg>
                <?php echo esc_html(akademiata_get_theme_lang_string('news_more_filters')); ?>
            </button>
        </div>
    </div>

    <div class="offer-mobile-actions">
        <div class="offer-mobile-actions__start">
            <button type="button"
                    class="offer-mobile-clear"
                    id="offer-mobile-clear-filters"
                    hidden>
                <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="2" d="M4 12a8 8 0 0 1 13.66-5.66M20 12a8 8 0 0 1-13.66 5.66"/>
                    <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M20 4v5h-5M4 20v-5h5"/>
                </svg>
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_clear_filters')); ?>
            </button>
            <div class="offer-mobile-search" id="offer-mobile-search-panel" hidden>
                <label class="offer-mobile-search__label">
                    <span class="visually-hidden"><?php echo esc_html(akademiata_get_theme_lang_string('offer_search_label')); ?></span>
                    <input type="search"
                           class="offer-mobile-search__input"
                           placeholder="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_search_placeholder')); ?>"
                           autocomplete="off"
                           inputmode="search">
                </label>
            </div>
        </div>
        <div class="offer-mobile-tool-group">
            <?php get_template_part('partials/offer-view-toggle'); ?>
            <button type="button"
                    class="offer-mobile-tool-group__btn offer-mobile-search-toggle"
                    aria-expanded="false"
                    aria-controls="offer-mobile-search-panel"
                    aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_search_label')); ?>">
                <svg class="offer-mobile-tool-group__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20l-4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>
