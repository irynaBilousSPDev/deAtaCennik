<?php

/**
 * Homepage “Decyzja na dziś” section — static config (no ACF).
 *
 * @return array<string, mixed>
 */
function akademiata_decision_today_config(): array {
    return array(
        'countdown_target'  => '2026-10-01',
        'promo_valid_until' => '2026-09-30',
    );
}

/**
 * @return string
 */
function akademiata_decision_today_cta_url() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $oferta_id = akademiata_get_oferta_page_id();
    if ($oferta_id > 0) {
        $cached = (string) get_permalink($oferta_id);
        return $cached;
    }

    $page_id = akademiata_get_offer_listing_page_id_for_level('bachelor');
    if ($page_id > 0) {
        $cached = (string) get_permalink($page_id);
        return $cached;
    }

    $cached = home_url('/');

    return $cached;
}

/**
 * @param string $date Y-m-d
 * @return array{days: int, hours: int, minutes: int, seconds: int, total_seconds: int, expired: bool}
 */
function akademiata_decision_today_countdown_parts($date) {
    $timezone = wp_timezone();
    $target   = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', $timezone);

    if (!$target) {
        return array(
            'days'          => 0,
            'hours'         => 0,
            'minutes'       => 0,
            'seconds'       => 0,
            'total_seconds' => 0,
            'expired'       => true,
        );
    }

    $now     = new DateTimeImmutable('now', $timezone);
    $seconds = $target->getTimestamp() - $now->getTimestamp();

    if ($seconds <= 0) {
        return array(
            'days'          => 0,
            'hours'         => 0,
            'minutes'       => 0,
            'seconds'       => 0,
            'total_seconds' => 0,
            'expired'       => true,
        );
    }

    $days    = (int) floor($seconds / DAY_IN_SECONDS);
    $hours   = (int) floor(($seconds % DAY_IN_SECONDS) / HOUR_IN_SECONDS);
    $minutes = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);
    $secs    = (int) ($seconds % MINUTE_IN_SECONDS);

    return array(
        'days'          => $days,
        'hours'         => $hours,
        'minutes'       => $minutes,
        'seconds'       => $secs,
        'total_seconds' => $seconds,
        'expired'       => false,
    );
}

/**
 * @param int $value
 */
function akademiata_decision_today_pad_time($value) {
    return str_pad((string) max(0, (int) $value), 2, '0', STR_PAD_LEFT);
}

/**
 * @return string
 */
function akademiata_decision_today_share_url() {
    return akademiata_decision_today_cta_url();
}

/**
 * Full share text (message + offer URL).
 *
 * @return string
 */
function akademiata_decision_today_share_text() {
    $url     = akademiata_decision_today_share_url();
    $pattern = akademiata_get_theme_lang_string('decision_today_share_message');

    if ($pattern === '') {
        return $url;
    }

    if (strpos($pattern, '%s') !== false) {
        return sprintf($pattern, $url);
    }

    return trim($pattern . ' ' . $url);
}

/**
 * Share channels shown after “Zaproś znajomych”.
 *
 * @return array<int, array{id: string, label_key: string, mode: string}>
 */
function akademiata_decision_today_share_channels() {
    return array(
        array('id' => 'whatsapp',  'label_key' => 'decision_today_share_whatsapp',  'mode' => 'link'),
        array('id' => 'messenger', 'label_key' => 'decision_today_share_messenger', 'mode' => 'link'),
        array('id' => 'telegram',  'label_key' => 'decision_today_share_telegram',  'mode' => 'link'),
        array('id' => 'instagram', 'label_key' => 'decision_today_share_instagram', 'mode' => 'copy'),
        array('id' => 'tiktok',    'label_key' => 'decision_today_share_tiktok',    'mode' => 'copy'),
        array('id' => 'snapchat',  'label_key' => 'decision_today_share_snapchat',  'mode' => 'copy'),
        array('id' => 'copy',      'label_key' => 'decision_today_share_copy',      'mode' => 'copy'),
        array('id' => 'native',    'label_key' => 'decision_today_share_more',      'mode' => 'native'),
    );
}

/**
 * @return string
 */
function akademiata_decision_today_group_visual_url() {
    return get_template_directory_uri() . '/static/img/decision-today/group-people.png';
}
