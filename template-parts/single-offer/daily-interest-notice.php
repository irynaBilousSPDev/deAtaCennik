<?php

if (!akademiata_should_show_offer_daily_interest()) {
    return;
}

$interest_payload = akademiata_offer_daily_interest_track_current_view();
$interest_visible = !empty($interest_payload['show']);
$interest_title   = akademiata_get_theme_lang_string('offer_daily_interest_title');
$interest_message = !empty($interest_payload['message'])
    ? akademiata_offer_daily_interest_message_html((int) $interest_payload['count'])
    : '';
$interest_close = akademiata_get_theme_lang_string('offer_daily_interest_close');
?>

<div id="offer-daily-interest"
     class="offer-daily-interest<?php echo $interest_visible ? ' is-visible' : ''; ?>"
     <?php echo $interest_visible ? '' : 'hidden'; ?>
     role="status"
     aria-live="polite"
     aria-atomic="true">
    <div class="offer-daily-interest__inner">
        <div class="offer-daily-interest__top">
            <span class="offer-daily-interest__pulse" aria-hidden="true"></span>
            <?php if ($interest_title !== '') : ?>
                <p class="offer-daily-interest__title"><?php echo esc_html($interest_title); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($interest_message !== '') : ?>
            <p class="offer-daily-interest__message">
                <?php
                echo wp_kses(
                    $interest_message,
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                    )
                );
                ?>
            </p>
        <?php endif; ?>
        <button type="button"
                class="offer-daily-interest__close"
                aria-label="<?php echo esc_attr($interest_close); ?>">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
</div>
