<?php
/**
 * Desktop sidebar — PG/MBA favorites toggle (separate from bachelor/master).
 */
?>
<div class="taxonomy_group pg-mba-favorites-filter pg-mba-favorites-filter--desktop"
     id="pg-mba-favorites-filter-desktop"
     hidden>
    <div class="labels_list">
        <label class="pg-mba-favorites-filter__label">
            <input type="checkbox" class="pg-mba-favorites-filter__toggle">
            <span>
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_chip_favorites')); ?><span class="pg-mba-favorites-filter__count"></span>
            </span>
        </label>
    </div>
</div>
