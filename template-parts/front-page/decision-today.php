<?php

require_once get_template_directory() . '/configure/front-page-defaults/decision-today/fields.php';

$config = akademiata_decision_today_config();

// PL static copy — WPML after section approval.
$eyebrow            = 'DECYZJA NA DZIŚ';
$title              = 'Zacznij studia w Akademia Techniczno-Artystyczna bez odkładania decyzji';
$lead               = '';
$group_title_before = 'W Grupie';
$group_title_accent = 'Taniej!';
$group_lead         = 'Zapis razem z grupą znajomych lub rodziny i zyskaj aż do';
$group_discount     = '−200 / −400 zł';
$group_valid_until  = 'Ważne do 30.09.2026';
$cta_text           = 'Zaproś znajomych';
$share_url          = akademiata_decision_today_share_url();
$share_text         = akademiata_decision_today_share_text();
$share_title        = 'Udostępnij ofertę';
$share_copied       = 'Skopiowano — wklej w wiadomości';
$share_channels     = akademiata_decision_today_share_channels();
$urgency_text       = 'Pospiesz się — liczba miejsc jest ograniczona!';

$visitors_enabled = function_exists('akademiata_site_daily_visitors_is_enabled')
    && akademiata_site_daily_visitors_is_enabled();

$countdown_target   = $config['countdown_target'] ?? '2026-10-01';
$countdown_parts    = akademiata_decision_today_countdown_parts($countdown_target);
$timer_line_top     = 'Start';
$timer_line_bottom  = 'studiów';

$timer_units = array(
    array(
        'key'   => 'days',
        'value' => akademiata_decision_today_pad_time($countdown_parts['days']),
        'label' => 'DNI',
    ),
    array(
        'key'   => 'hours',
        'value' => akademiata_decision_today_pad_time($countdown_parts['hours']),
        'label' => 'GODZIN',
    ),
    array(
        'key'   => 'minutes',
        'value' => akademiata_decision_today_pad_time($countdown_parts['minutes']),
        'label' => 'MINUT',
    ),
);

$timezone   = wp_timezone();
$target_dt  = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $countdown_target . ' 00:00:00', $timezone);
$countdown_ts = $target_dt ? $target_dt->getTimestamp() : 0;
$group_visual_url = akademiata_decision_today_group_visual_url();
?>
<section
    class="home-decision"
    aria-labelledby="home-decision-title"
    data-countdown-ts="<?php echo esc_attr((string) $countdown_ts); ?>"
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
                <h1 id="home-decision-title" class="home-decision__title"><?php echo esc_html($title); ?></h1>
            <?php endif; ?>
        </header>

        <div class="home-decision__cards">
            <article class="home-decision__timer-card">
                <div class="home-decision__timer-stack">
                    <?php if ($timer_line_top !== '') : ?>
                        <p class="home-decision__timer-line home-decision__timer-line--top" aria-hidden="true">
                            <?php echo esc_html($timer_line_top); ?>
                        </p>
                    <?php endif; ?>
                    <div class="home-decision__timer-pill" role="timer" aria-live="polite" aria-atomic="true">
                        <?php foreach ($timer_units as $index => $unit) : ?>
                            <?php if ($index > 0) : ?>
                                <span class="home-decision__timer-sep" aria-hidden="true">:</span>
                            <?php endif; ?>
                            <span class="home-decision__timer-value" data-unit="<?php echo esc_attr($unit['key']); ?>"><?php echo esc_html($unit['value']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($timer_line_bottom !== '') : ?>
                        <p class="home-decision__timer-line home-decision__timer-line--bottom" aria-hidden="true">
                            <?php echo esc_html($timer_line_bottom); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <ul class="home-decision__timer-labels" aria-hidden="true">
                    <?php foreach ($timer_units as $unit) : ?>
                        <li><?php echo esc_html($unit['label']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>

            <article class="home-decision__group-card">
                <div class="home-decision__group-copy">
                    <?php if ($group_title_before !== '' || $group_title_accent !== '') : ?>
                        <h3 class="home-decision__group-title">
                            <?php if ($group_title_before !== '') : ?>
                                <span class="home-decision__group-line"><?php echo esc_html($group_title_before); ?></span>
                            <?php endif; ?>
                            <?php if ($group_title_accent !== '') : ?>
                                <span class="home-decision__group-accent"><?php echo esc_html(' ' . $group_title_accent); ?></span>
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
                        class="home-decision__group-scene"
                        src="<?php echo esc_url($group_visual_url); ?>"
                        alt=""
                        width="430"
                        height="208"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
            </article>
        </div>

        <?php if ($visitors_enabled || $urgency_text !== '') : ?>
            <div class="home-decision__status">
                <?php if ($visitors_enabled) : ?>
                    <div
                        class="home-decision__status-item home-decision__status-item--visitors"
                        data-site-daily-visitors
                        hidden
                    >
                        <span class="home-decision__status-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 19V5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M4 19h16" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M8 15l3-4 3 2 4-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <p class="home-decision__status-text">
                            Dziś
                            <span class="home-decision__visitor-count" data-site-daily-visitors-count></span>
                            <span class="home-decision__visitor-word" data-site-daily-visitors-word></span>
                            zainteresowało się studiami na ATA
                        </p>
                    </div>
                <?php endif; ?>
                <?php if ($visitors_enabled && $urgency_text !== '') : ?>
                    <span class="home-decision__status-divider" aria-hidden="true" hidden></span>
                <?php endif; ?>
                <?php if ($urgency_text !== '') : ?>
                    <p class="home-decision__status-item home-decision__status-item--urgency"><?php echo esc_html($urgency_text); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
