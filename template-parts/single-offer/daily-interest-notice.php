<?php

if (!akademiata_should_show_offer_daily_interest()) {
    return;
}

$interest_payload = akademiata_offer_daily_interest_track_current_view();
$interest_visible = !empty($interest_payload['show']);
$interest_title   = akademiata_get_theme_lang_string('offer_daily_interest_title');
$interest_message = !empty($interest_payload['message']) ? $interest_payload['message'] : '';
$interest_close   = akademiata_get_theme_lang_string('offer_daily_interest_close');
?>

<div id="offer-daily-interest"
     class="offer-daily-interest<?php echo $interest_visible ? ' is-visible' : ''; ?>"
     <?php echo $interest_visible ? '' : 'hidden'; ?>
     role="status"
     aria-live="polite"
     aria-atomic="true">
    <div class="offer-daily-interest__inner">
        <?php if ($interest_title !== '') : ?>
            <p class="offer-daily-interest__title"><?php echo esc_html($interest_title); ?></p>
        <?php endif; ?>
        <?php if ($interest_message !== '') : ?>
            <p class="offer-daily-interest__message"><?php echo esc_html($interest_message); ?></p>
        <?php endif; ?>
        <button type="button"
                class="offer-daily-interest__close"
                aria-label="<?php echo esc_attr($interest_close); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
