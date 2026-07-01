<?php
/**
 * Desktop sidebar — favorites toggle styled like filter checkbox rows.
 */
?>
<div class="taxonomy_group offer-favorites-filter offer-favorites-filter--desktop"
     id="offer-favorites-filter-desktop"
     hidden>
    <div class="labels_list">
        <label class="offer-favorites-filter__label">
            <input type="checkbox" class="offer-favorites-filter__toggle">
            <span>
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_chip_favorites')); ?><span class="offer-favorites-filter__count"></span>
            </span>
        </label>
    </div>
</div>
