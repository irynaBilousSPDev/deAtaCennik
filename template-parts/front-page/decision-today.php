<?php

require_once get_template_directory() . '/configure/front-page-defaults/decision-today/fields.php';

$config = akademiata_decision_today_config();

$eyebrow = akademiata_get_theme_lang_string('decision_today_eyebrow');
$title   = akademiata_get_theme_lang_string('decision_today_title');
$lead    = akademiata_get_theme_lang_string('decision_today_lead');

$countdown_target  = $config['countdown_target'] ?? '2026-10-01';
$countdown_parts   = akademiata_decision_today_countdown_parts($countdown_target);
$timer_line_top    = akademiata_get_theme_lang_string('decision_today_timer_line_top');
$timer_line_bottom = akademiata_get_theme_lang_string('decision_today_timer_line_bottom');

$group_title    = akademiata_get_theme_lang_string('decision_today_group_title');
$group_lead     = akademiata_get_theme_lang_string('decision_today_group_lead');
$group_discount = akademiata_get_theme_lang_string('decision_today_group_discount');
$cta_text       = akademiata_get_theme_lang_string('decision_today_cta');
$cta_url        = akademiata_decision_today_cta_url();
$urgency_text   = akademiata_get_theme_lang_string('decision_today_urgency');

$visitor_payload = function_exists('akademiata_site_daily_visitors_payload')
    ? akademiata_site_daily_visitors_payload(akademiata_site_daily_visitors_get_count())
    : array('show' => false, 'message_html' => '');

$timer_labels = array(
    'days'    => akademiata_get_theme_lang_string('decision_today_days'),
    'hours'   => akademiata_get_theme_lang_string('decision_today_hours'),
    'minutes' => akademiata_get_theme_lang_string('decision_today_minutes'),
);

$timezone   = wp_timezone();
$target_dt  = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $countdown_target . ' 00:00:00', $timezone);
$iso_target = $target_dt ? $target_dt->format('c') : $countdown_target . 'T00:00:00';

$avatars = akademiata_decision_today_avatar_images();
?>
<section
    class="home-decision"
    aria-labelledby="home-decision-title"
    data-countdown-target="<?php echo esc_attr($iso_target); ?>"
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
                <div class="home-decision__timer-stack">
                    <?php if ($timer_line_top !== '') : ?>
                        <p class="home-decision__timer-line home-decision__timer-line--top" aria-hidden="true">
                            <?php echo esc_html($timer_line_top); ?>
                        </p>
                    <?php endif; ?>
                    <div class="home-decision__timer-pill" role="timer" aria-live="polite" aria-atomic="true">
                        <span class="home-decision__timer-value" data-unit="days"><?php echo esc_html(akademiata_decision_today_pad_time($countdown_parts['days'])); ?></span>
                        <span class="home-decision__timer-sep" aria-hidden="true">:</span>
                        <span class="home-decision__timer-value" data-unit="hours"><?php echo esc_html(akademiata_decision_today_pad_time($countdown_parts['hours'])); ?></span>
                        <span class="home-decision__timer-sep" aria-hidden="true">:</span>
                        <span class="home-decision__timer-value" data-unit="minutes"><?php echo esc_html(akademiata_decision_today_pad_time($countdown_parts['minutes'])); ?></span>
                    </div>
                    <?php if ($timer_line_bottom !== '') : ?>
                        <p class="home-decision__timer-line home-decision__timer-line--bottom" aria-hidden="true">
                            <?php echo esc_html($timer_line_bottom); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <ul class="home-decision__timer-labels" aria-hidden="true">
                    <li><?php echo esc_html($timer_labels['days']); ?></li>
                    <li><?php echo esc_html($timer_labels['hours']); ?></li>
                    <li><?php echo esc_html($timer_labels['minutes']); ?></li>
                </ul>
            </article>

            <article class="home-decision__group-card">
                <div class="home-decision__group-copy">
                    <?php if ($group_title !== '') : ?>
                        <h3 class="home-decision__group-title">
                            <?php
                            $title_parts = preg_split('/(Taniej!)/u', $group_title, -1, PREG_SPLIT_DELIM_CAPTURE);
                            if (is_array($title_parts) && count($title_parts) > 1) {
                                foreach ($title_parts as $part) {
                                    if ($part === 'Taniej!') {
                                        echo '<span class="home-decision__group-accent">' . esc_html($part) . '</span>';
                                    } elseif ($part !== '') {
                                        echo '<span class="home-decision__group-line">' . esc_html(trim($part)) . '</span>';
                                    }
                                }
                            } else {
                                echo esc_html($group_title);
                            }
                            ?>
                        </h3>
                    <?php endif; ?>
                    <?php if ($group_lead !== '') : ?>
                        <p class="home-decision__group-lead"><?php echo esc_html($group_lead); ?></p>
                    <?php endif; ?>
                    <?php if ($group_discount !== '') : ?>
                        <p class="home-decision__group-discount"><?php echo esc_html($group_discount); ?></p>
                    <?php endif; ?>
                    <?php if ($cta_text !== '' && $cta_url !== '') : ?>
                        <a class="home-decision__cta" href="<?php echo esc_url($cta_url); ?>">
                            <span><?php echo esc_html($cta_text); ?></span>
                            <span class="home-decision__cta-icon" aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="home-decision__group-visual" aria-hidden="true">
                    <span class="home-decision__group-orbit home-decision__group-orbit--1"></span>
                    <span class="home-decision__group-orbit home-decision__group-orbit--2"></span>
                    <span class="home-decision__group-orbit home-decision__group-orbit--3"></span>
                    <span class="home-decision__group-dot home-decision__group-dot--1"></span>
                    <span class="home-decision__group-dot home-decision__group-dot--2"></span>
                    <span class="home-decision__group-dot home-decision__group-dot--3"></span>
                    <span class="home-decision__group-dot home-decision__group-dot--4"></span>
                    <?php foreach ($avatars as $i => $avatar) : ?>
                        <img
                            class="home-decision__avatar home-decision__avatar--<?php echo (int) ($i + 1); ?>"
                            src="<?php echo esc_url($avatar['src']); ?>"
                            alt=""
                            width="72"
                            height="72"
                            loading="lazy"
                            decoding="async"
                        >
                    <?php endforeach; ?>
                    <span class="home-decision__group-hub">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" fill="currentColor"/>
                        </svg>
                    </span>
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
