<?php
/**
 * Favorites filter chip — mobile toolbar + desktop sidebar.
 *
 * Args: chip_class — extra class(es), e.g. offer-favorites-chip--desktop.
 */
$chip_class = isset($args['chip_class']) ? (string) $args['chip_class'] : '';
?>
<button type="button"
        class="offer-mobile-chip offer-favorites-chip <?php echo esc_attr($chip_class); ?>"
        data-tax="favorites">
    <svg class="offer-favorites-chip__icon" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
    </svg>
    <?php echo esc_html(akademiata_get_theme_lang_string('offer_chip_favorites')); ?><span class="offer-favorites-chip__count"></span>
</button>
