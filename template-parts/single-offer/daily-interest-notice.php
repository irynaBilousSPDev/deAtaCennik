<?php

if (!akademiata_should_show_offer_daily_interest()) {
    return;
}

$interest_close = akademiata_get_theme_lang_string('offer_daily_interest_close');
?>

<div id="offer-daily-interest"
     class="offer-daily-interest"
     hidden
     role="status"
     aria-live="polite"
     aria-atomic="true">
    <div class="offer-daily-interest__inner">
        <div class="offer-daily-interest__top">
            <span class="offer-daily-interest__pulse" aria-hidden="true"></span>
            <p class="offer-daily-interest__title"></p>
        </div>
        <p class="offer-daily-interest__message"></p>
        <button type="button"
                class="offer-daily-interest__close"
                aria-label="<?php echo esc_attr($interest_close); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
</div>
