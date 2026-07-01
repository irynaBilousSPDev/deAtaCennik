<div class="offer-mobile-dropdown" id="offer-mobile-dropdown" aria-hidden="true">
    <button type="button" class="offer-mobile-dropdown__backdrop" aria-label="<?php esc_attr_e('Zamknij', 'akademiata'); ?>"></button>
    <div class="offer-mobile-dropdown__panel" role="dialog" aria-modal="true" aria-labelledby="offer-mobile-dropdown-title">
        <div class="offer-mobile-dropdown__header">
            <h3 class="offer-mobile-dropdown__title" id="offer-mobile-dropdown-title">
                <?php esc_html_e('Filtruj', 'akademiata'); ?>
                <span class="offer-mobile-dropdown__title-dynamic"></span>
            </h3>
            <button type="button" class="offer-mobile-dropdown__collapse" aria-label="<?php esc_attr_e('Zwiń', 'akademiata'); ?>">
                <svg class="offer-mobile-dropdown__collapse-icon" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
            </button>
        </div>
        <div class="offer-mobile-dropdown__list"></div>
    </div>
</div>
