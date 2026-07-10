<?php

if (!akademiata_should_show_offer_daily_interest()) {
    return;
}
?>

<div id="offer-daily-interest"
     class="offer-daily-interest"
     hidden
     role="status"
     aria-live="polite"
     aria-atomic="true">
    <div class="offer-daily-interest__inner">
        <p class="offer-daily-interest__title"></p>
        <p class="offer-daily-interest__message"></p>
        <button type="button"
                class="offer-daily-interest__close"
                aria-label="">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
