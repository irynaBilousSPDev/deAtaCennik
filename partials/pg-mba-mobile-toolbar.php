<?php
/**
 * Mobile/tablet toolbar for PG/MBA listing archives (separate from offer toolbar).
 */

$taxonomies = akademiata_get_pg_mba_filter_taxonomies();
$chip_chevron = '<svg class="offer-mobile-chip__chevron" width="10" height="10" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>';

$quick_chips = array(
    'city_pg_mba'        => $taxonomies['city_pg_mba'] ?? __('Lokalizacja', 'akademiata'),
    'offer_theme_pg_mba' => $taxonomies['offer_theme_pg_mba'] ?? __('Obszar tematyczny', 'akademiata'),
    'language_pg_mba'    => $taxonomies['language_pg_mba'] ?? __('Język', 'akademiata'),
);
?>
<div class="pg-mba-mobile-toolbar" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_toolbar_aria')); ?>">
    <div class="offer-mobile-chips" role="toolbar" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_quick_filters_aria')); ?>">
        <div class="offer-mobile-chips__row">
            <button type="button" class="offer-mobile-chip is-active" data-tax="all">
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_chip_all')); ?>
            </button>
            <?php get_template_part('partials/pg-mba-favorites-chip'); ?>
            <?php foreach (array('city_pg_mba', 'offer_theme_pg_mba') as $taxonomy) : ?>
                <button type="button"
                        class="offer-mobile-chip offer-mobile-chip--dropdown"
                        data-tax="<?php echo esc_attr($taxonomy); ?>"
                        data-label="<?php echo esc_attr($quick_chips[ $taxonomy ]); ?>">
                    <?php echo esc_html($quick_chips[ $taxonomy ]); ?>
                    <?php echo $chip_chevron; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="offer-mobile-chips__row">
            <button type="button"
                    class="offer-mobile-chip offer-mobile-chip--dropdown"
                    data-tax="language_pg_mba"
                    data-label="<?php echo esc_attr($quick_chips['language_pg_mba']); ?>">
                <?php echo esc_html($quick_chips['language_pg_mba']); ?>
                <?php echo $chip_chevron; ?>
            </button>
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

    <div class="offer-listing-selection">
        <?php get_template_part('partials/tags_container'); ?>
    </div>

    <div class="offer-mobile-actions">
        <div class="offer-mobile-actions__left">
            <?php get_template_part('partials/offer-view-toggle'); ?>
        </div>
        <div class="offer-mobile-actions__end">
            <div class="offer-mobile-search" id="pg-mba-mobile-search-panel" hidden>
                <label class="offer-mobile-search__label">
                    <span class="visually-hidden"><?php echo esc_html(akademiata_get_theme_lang_string('offer_search_label')); ?></span>
                    <svg class="offer-mobile-search__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
                        <path d="M20 20l-4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input type="search"
                           class="offer-mobile-search__input"
                           placeholder="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_search_placeholder')); ?>"
                           autocomplete="off"
                           inputmode="search">
                    <button type="button"
                            class="offer-mobile-search__clear"
                            aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_search_clear')); ?>"
                            hidden>
                        <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </button>
                </label>
            </div>
            <button type="button"
                    class="offer-mobile-search-toggle"
                    aria-expanded="false"
                    aria-controls="pg-mba-mobile-search-panel"
                    aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_search_label')); ?>">
                <svg class="offer-mobile-search-toggle__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20l-4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <button type="button"
                    class="offer-mobile-clear"
                    id="pg-mba-mobile-clear-filters"
                    hidden>
                <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="2" d="M4 12a8 8 0 0 1 13.66-5.66M20 12a8 8 0 0 1-13.66 5.66"/>
                    <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M20 4v5h-5M4 20v-5h5"/>
                </svg>
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_clear_filters')); ?>
            </button>
        </div>
    </div>
</div>
