<?php
/**
 * Mobile offer toolbar — search, quick filter chips, view toggle, clear.
 * Visible via CSS on page-template-page-offer only (≤990px).
 */

$current_page_slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$quick_chips       = [
    'degree'   => __('Typ studiów', 'akademiata'),
    'city'     => __('Miasto', 'akademiata'),
    'program'  => __('Kierunek studiów', 'akademiata'),
    'language' => __('Język', 'akademiata'),
];
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
        <button type="button"
                class="offer-mobile-chip is-active"
                data-tax="all">
            <?php esc_html_e('Wszystkie', 'akademiata'); ?>
        </button>
        <?php foreach ($quick_chips as $taxonomy => $label) : ?>
            <?php
            if ($taxonomy === 'degree' && !in_array($current_page_slug, ['offer', 'oferta'], true)) {
                continue;
            }
            ?>
            <button type="button"
                    class="offer-mobile-chip taxonomy-filter-toggle"
                    data-tax="<?php echo esc_attr($taxonomy); ?>">
                <?php echo esc_html($label); ?>
            </button>
        <?php endforeach; ?>
        <button type="button"
                class="offer-mobile-chip offer-mobile-chip--more taxonomy-filter-toggle"
                data-tax="more">
            <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M12 8a4 4 0 1 0-.001-8.001A4 4 0 0 0 12 8zm0 10a4 4 0 1 0-.001-8.001A4 4 0 0 0 12 18zm0 6a4 4 0 1 0-.001-8.001A4 4 0 0 0 12 24z"/>
            </svg>
            <?php esc_html_e('Więcej filtrów', 'akademiata'); ?>
        </button>
    </div>

    <div class="offer-mobile-actions">
        <?php get_template_part('partials/offer-view-toggle'); ?>
        <button type="button" class="offer-mobile-clear" id="offer-mobile-clear-filters">
            <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="none" stroke="currentColor" stroke-width="2" d="M4 12h16M12 4v16"/>
            </svg>
            <?php esc_html_e('Wyczyść filtry', 'akademiata'); ?>
        </button>
    </div>
</div>
