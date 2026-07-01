<?php
/**
 * Mobile offer toolbar — search, quick filter chips, view toggle, clear.
 */

$current_page_slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$quick_chips       = [
    'degree'   => __('Typ studiów', 'akademiata'),
    'city'     => __('Miasto', 'akademiata'),
    'program'  => __('Kierunek studiów', 'akademiata'),
    'language' => __('Język', 'akademiata'),
];
$chip_chevron = '<svg class="offer-mobile-chip__chevron" width="10" height="10" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>';
?>
<div class="offer-mobile-toolbar" aria-label="<?php esc_attr_e('Wyszukiwanie i filtry oferty', 'akademiata'); ?>">
    <label class="offer-mobile-search">
        <span class="visually-hidden"><?php esc_html_e('Wyszukaj kierunek', 'akademiata'); ?></span>
        <svg class="offer-mobile-search__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
            <path d="M20 20l-4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <input type="search"
               class="offer-mobile-search__input"
               placeholder="<?php esc_attr_e('Wyszukaj kierunek lub słowo kluczowe…', 'akademiata'); ?>"
               autocomplete="off"
               inputmode="search">
    </label>

    <div class="offer-mobile-chips" role="toolbar" aria-label="<?php esc_attr_e('Szybkie filtry', 'akademiata'); ?>">
        <div class="offer-mobile-chips__row">
            <button type="button" class="offer-mobile-chip is-active" data-tax="all">
                <?php esc_html_e('Wszystkie', 'akademiata'); ?>
            </button>
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
            <button type="button"
                    class="offer-mobile-chip offer-mobile-chip--more"
                    data-tax="more">
                <svg class="offer-mobile-chip__settings" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M4 6h16v2H4V6zm3 5h10v2H7v-2zm4 5h2v2h-2v-2z"/>
                    <circle fill="currentColor" cx="8" cy="7" r="2"/>
                    <circle fill="currentColor" cx="16" cy="12" r="2"/>
                    <circle fill="currentColor" cx="10" cy="17" r="2"/>
                </svg>
                <?php esc_html_e('Więcej filtrów', 'akademiata'); ?>
            </button>
        </div>
    </div>

    <div class="offer-mobile-actions">
        <?php get_template_part('partials/offer-view-toggle'); ?>
        <button type="button" class="offer-mobile-clear" id="offer-mobile-clear-filters">
            <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="none" stroke="currentColor" stroke-width="2" d="M4 12a8 8 0 0 1 13.66-5.66M20 12a8 8 0 0 1-13.66 5.66"/>
                <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M20 4v5h-5M4 20v-5h5"/>
            </svg>
            <?php esc_html_e('Wyczyść filtry', 'akademiata'); ?>
        </button>
    </div>
</div>
