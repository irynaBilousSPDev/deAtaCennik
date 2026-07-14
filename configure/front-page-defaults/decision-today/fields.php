<?php

require_once dirname(__DIR__, 2) . '/lp-defaults/merge.php';

/**
 * @return array<string, mixed>
 */
function akademiata_decision_today_defaults(): array {
    return require __DIR__ . '/content.php';
}

/**
 * @param array<string, mixed>|false|null $acf_group
 * @return array<string, mixed>
 */
function akademiata_decision_today_fields($acf_group): array {
    $defaults  = akademiata_decision_today_defaults();
    $acf_group = is_array($acf_group) ? $acf_group : [];
    $merged    = akademiata_lp_merge_defaults($defaults, $acf_group);

    $layout = isset($merged['layout']) ? sanitize_key((string) $merged['layout']) : 'cards';
    if (!in_array($layout, array('cards', 'compact'), true)) {
        $layout = 'cards';
    }
    $merged['layout'] = $layout;

    $merged['enabled'] = !empty($merged['enabled']);

    $target = trim((string) ($merged['countdown_target'] ?? ''));
    if ($target === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $target)) {
        $target = (string) ($defaults['countdown_target'] ?? '2026-10-01');
    }
    $merged['countdown_target'] = $target;

    return $merged;
}

/**
 * @return string
 */
function akademiata_decision_today_group_button_url(array $section) {
    $url = trim((string) ($section['group_button_url'] ?? ''));
    if ($url !== '') {
        return $url;
    }

    return akademiata_get_zasady_rekrutacji_page_url();
}

/**
 * Permalink of Zasady rekrutacji (WPML-safe).
 *
 * @return string
 */
function akademiata_get_zasady_rekrutacji_page_url() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = '';
    $page   = get_page_by_path('zasady-rekrutacji');

    if (!$page) {
        return $cached;
    }

    $page_id = (int) $page->ID;

    if (function_exists('icl_object_id')) {
        $lang = apply_filters('wpml_current_language', null);
        $translated_id = (int) apply_filters('wpml_object_id', $page_id, 'page', false, $lang);
        if ($translated_id > 0) {
            $page_id = $translated_id;
        }
    }

    $cached = (string) get_permalink($page_id);

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
 * @return array<int, array{label: string, bg: string}>
 */
function akademiata_decision_today_avatar_presets() {
    return array(
        array('label' => 'A', 'bg' => '#5B8DEF'),
        array('label' => 'K', 'bg' => '#3CB8A7'),
        array('label' => 'M', 'bg' => '#F2C94C'),
        array('label' => 'J', 'bg' => '#EB5757'),
        array('label' => 'L', 'bg' => '#D9A679'),
    );
}
