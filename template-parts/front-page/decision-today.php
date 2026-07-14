<?php

require_once get_template_directory() . '/configure/front-page-defaults/decision-today/fields.php';

$config = akademiata_decision_today_config();

$eyebrow = akademiata_get_theme_lang_string('decision_today_eyebrow');
$title   = akademiata_get_theme_lang_string('decision_today_title');
$lead    = akademiata_get_theme_lang_string('decision_today_lead');

$countdown_target   = $config['countdown_target'] ?? '2026-10-01';
$countdown_parts    = akademiata_decision_today_countdown_parts($countdown_target);
$timer_watermark    = akademiata_get_theme_lang_string('decision_today_timer_watermark');

$group_title_before = akademiata_get_theme_lang_string('decision_today_group_title_before');
$group_title_accent = akademiata_get_theme_lang_string('decision_today_group_title_accent');
$group_lead         = akademiata_get_theme_lang_string('decision_today_group_lead');
$group_discount     = akademiata_get_theme_lang_string('decision_today_group_discount');
$group_valid_until  = akademiata_get_theme_lang_string('decision_today_group_valid_until');
$cta_text           = akademiata_get_theme_lang_string('decision_today_cta');
$share_url          = akademiata_decision_today_share_url();
$share_text         = akademiata_decision_today_share_text();
$share_title        = akademiata_get_theme_lang_string('decision_today_share_title');
$share_copied       = akademiata_get_theme_lang_string('decision_today_share_copied');
$share_channels     = akademiata_decision_today_share_channels();
$urgency_text       = akademiata_get_theme_lang_string('decision_today_urgency');

$visitor_payload = function_exists('akademiata_site_daily_visitors_payload')
    ? akademiata_site_daily_visitors_payload(akademiata_site_daily_visitors_get_count())
    : array('show' => false, 'message_html' => '');

$timer_units = array(
    array(
        'key'   => 'days',
        'value' => akademiata_decision_today_pad_time($countdown_parts['days']),
        'label' => akademiata_get_theme_lang_string('decision_today_days'),
    ),
    array(
        'key'   => 'hours',
        'value' => akademiata_decision_today_pad_time($countdown_parts['hours']),
        'label' => akademiata_get_theme_lang_string('decision_today_hours'),
    ),
    array(
        'key'   => 'minutes',
        'value' => akademiata_decision_today_pad_time($countdown_parts['minutes']),
        'label' => akademiata_get_theme_lang_string('decision_today_minutes'),
    ),
    array(
        'key'   => 'seconds',
        'value' => akademiata_decision_today_pad_time($countdown_parts['seconds']),
        'label' => akademiata_get_theme_lang_string('decision_today_seconds'),
    ),
);

$timezone   = wp_timezone();
$target_dt  = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $countdown_target . ' 00:00:00', $timezone);
$iso_target = $target_dt ? $target_dt->format('c') : $countdown_target . 'T00:00:00';

$group_visual_url = akademiata_decision_today_group_visual_url();
?>
<section
    class="home-decision"
    aria-labelledby="home-decision-title"
    data-countdown-target="<?php echo esc_attr($iso_target); ?>"
    data-share-url="<?php echo esc_url($share_url); ?>"
    data-share-text="<?php echo esc_attr($share_text); ?>"
    data-share-copied="<?php echo esc_attr($share_copied); ?>"
>
    <div class="home-decision__inner container">
        <header class="home-decision__header">
            <?php if ($eyebrow !== '') : ?>
                <p class="home-decision__eyebrow"><?php echo esc_html($eyebrow); ?></p>
            <?php endif; ?>
            <?php if ($title !== '') : ?>
                <h2 id="home-decision-title" class="home-decision__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($lead !== '') : ?>
                <p class="home-decision__lead"><?php echo esc_html($lead); ?></p>
            <?php endif; ?>
        </header>

        <div class="home-decision__cards">
            <article class="home-decision__timer-card">
                <?php if ($timer_watermark !== '') : ?>
                    <p class="home-decision__timer-watermark" aria-hidden="true"><?php echo esc_html($timer_watermark); ?></p>
                <?php endif; ?>
                <div class="home-decision__timer-box" role="timer" aria-live="polite" aria-atomic="true">
                    <?php foreach ($timer_units as $index => $unit) : ?>
                        <?php if ($index > 0) : ?>
                            <span class="home-decision__timer-colon" aria-hidden="true"></span>
                        <?php endif; ?>
                        <div class="home-decision__timer-unit">
                            <span class="home-decision__timer-value" data-unit="<?php echo esc_attr($unit['key']); ?>"><?php echo esc_html($unit['value']); ?></span>
                            <span class="home-decision__timer-label"><?php echo esc_html($unit['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="home-decision__group-card">
                <div class="home-decision__group-copy">
                    <?php if ($group_title_before !== '' || $group_title_accent !== '') : ?>
                        <h3 class="home-decision__group-title">
                            <?php if ($group_title_before !== '') : ?>
                                <span><?php echo esc_html($group_title_before); ?></span>
                            <?php endif; ?>
                            <?php if ($group_title_accent !== '') : ?>
                                <span class="home-decision__group-accent"><?php echo esc_html($group_title_accent); ?></span>
                            <?php endif; ?>
                        </h3>
                    <?php endif; ?>
                    <?php if ($group_lead !== '') : ?>
                        <p class="home-decision__group-lead"><?php echo esc_html($group_lead); ?></p>
                    <?php endif; ?>
                    <?php if ($group_discount !== '') : ?>
                        <p class="home-decision__group-discount"><?php echo esc_html($group_discount); ?></p>
                    <?php endif; ?>
                    <?php if ($group_valid_until !== '') : ?>
                        <p class="home-decision__group-valid"><?php echo esc_html($group_valid_until); ?></p>
                    <?php endif; ?>
                    <?php if ($cta_text !== '' && $share_url !== '') : ?>
                        <div class="home-decision__share">
                            <button
                                type="button"
                                class="home-decision__cta"
                                data-share-toggle
                                aria-expanded="false"
                                aria-controls="home-decision-share-menu"
                            >
                                <span><?php echo esc_html($cta_text); ?></span>
                                <span class="home-decision__cta-icon" aria-hidden="true">→</span>
                            </button>
                            <div
                                id="home-decision-share-menu"
                                class="home-decision__share-menu"
                                data-share-menu
                                hidden
                            >
                                <?php if ($share_title !== '') : ?>
                                    <p class="home-decision__share-title"><?php echo esc_html($share_title); ?></p>
                                <?php endif; ?>
                                <div class="home-decision__share-grid" role="list">
                                    <?php foreach ($share_channels as $channel) : ?>
                                        <?php
                                        $channel_id    = $channel['id'] ?? '';
                                        $channel_mode  = $channel['mode'] ?? 'link';
                                        $channel_label = akademiata_get_theme_lang_string($channel['label_key'] ?? '');

                                        if ($channel_id === '' || $channel_label === '') {
                                            continue;
                                        }
                                        ?>
                                        <button
                                            type="button"
                                            class="home-decision__share-item home-decision__share-item--<?php echo esc_attr($channel_id); ?>"
                                            data-share-channel="<?php echo esc_attr($channel_id); ?>"
                                            data-share-mode="<?php echo esc_attr($channel_mode); ?>"
                                            role="listitem"
                                            <?php echo $channel_mode === 'native' ? 'data-share-native hidden' : ''; ?>
                                        >
                                            <span class="home-decision__share-icon" aria-hidden="true"></span>
                                            <span class="home-decision__share-label"><?php echo esc_html($channel_label); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="home-decision__share-toast" data-share-toast hidden></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="home-decision__group-visual" aria-hidden="true">
                    <img
                        class="home-decision__group-art"
                        src="<?php echo esc_url($group_visual_url); ?>"
                        alt=""
                        width="492"
                        height="445"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
            </article>
        </div>

        <?php if (!empty($visitor_payload['show']) || $urgency_text !== '') : ?>
            <div class="home-decision__status">
                <?php if (!empty($visitor_payload['show'])) : ?>
                    <div class="home-decision__status-item home-decision__status-item--visitors">
                        <span class="home-decision__status-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 19V5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M4 19h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 15l3-4 3 2 4-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <p class="home-decision__status-text"><?php echo wp_kses_post($visitor_payload['message_html']); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($visitor_payload['show']) && $urgency_text !== '') : ?>
                    <span class="home-decision__status-divider" aria-hidden="true"></span>
                <?php endif; ?>
                <?php if ($urgency_text !== '') : ?>
                    <p class="home-decision__status-item home-decision__status-item--urgency"><?php echo esc_html($urgency_text); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
