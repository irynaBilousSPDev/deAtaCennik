<?php

/**
 * Homepage “Decyzja na dziś” section — static config (no ACF).
 *
 * @return array<string, mixed>
 */
function akademiata_decision_today_config(): array {
    return array(
        'countdown_target' => '2026-10-01',
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
 * @return array{days: int, hours: int, minutes: int, total_seconds: int, expired: bool}
 */
function akademiata_decision_today_countdown_parts($date) {
    $timezone = wp_timezone();
    $target   = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', $timezone);

    if (!$target) {
        return array(
            'days'          => 0,
            'hours'         => 0,
            'minutes'       => 0,
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
            'total_seconds' => 0,
            'expired'       => true,
        );
    }

    $days    = (int) floor($seconds / DAY_IN_SECONDS);
    $hours   = (int) floor(($seconds % DAY_IN_SECONDS) / HOUR_IN_SECONDS);
    $minutes = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);

    return array(
        'days'          => $days,
        'hours'         => $hours,
        'minutes'       => $minutes,
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
 * @return array<int, array{src: string, alt: string}>
 */
function akademiata_decision_today_avatar_images() {
    $base = get_template_directory_uri() . '/static/img/decision-today';

    return array(
        array('src' => $base . '/avatar-1.jpg', 'alt' => 'Student ATA'),
        array('src' => $base . '/avatar-2.jpg', 'alt' => 'Student ATA'),
        array('src' => $base . '/avatar-3.jpg', 'alt' => 'Student ATA'),
        array('src' => $base . '/avatar-4.jpg', 'alt' => 'Student ATA'),
        array('src' => $base . '/avatar-5.jpg', 'alt' => 'Student ATA'),
    );
}
