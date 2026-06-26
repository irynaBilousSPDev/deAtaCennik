<?php

require_once dirname(__DIR__) . '/merge.php';

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_rankingi_defaults(): array {
    return require __DIR__ . '/content.php';
}

/**
 * @param array<string, mixed>|false $acf_fields
 * @return array<string, array<string, mixed>>
 */
function akademiata_rankingi_fields($acf_fields): array {
    $defaults = akademiata_rankingi_defaults();
    $acf_fields = is_array($acf_fields) ? $acf_fields : [];
    $merged = [];

    foreach ($defaults as $section_key => $section_defaults) {
        $merged[$section_key] = akademiata_lp_merge_defaults(
            $section_defaults,
            $acf_fields[$section_key] ?? null
        );
    }

    return $merged;
}

/**
 * @return array<string, array<string, bool>>
 */
function akademiata_rankingi_title_allowed_tags(): array {
    return ['br' => []];
}

/**
 * @param string|null $text
 */
function akademiata_rankingi_title_normalize($text): string {
    if ($text === '' || $text === null) {
        return '';
    }

    return preg_replace('/\r\n|\r|\n/', '<br>', (string) $text);
}

/**
 * @param string|null $text
 */
function akademiata_rankingi_title_html($text): string {
    if ($text === '' || $text === null) {
        return '';
    }

    return wp_kses(akademiata_rankingi_title_normalize($text), akademiata_rankingi_title_allowed_tags());
}

/**
 * @param string|null $title
 * @param string|null $highlight
 */
function akademiata_rankingi_echo_title_mark($title, $highlight = ''): void {
    if ($title === '' || $title === null) {
        return;
    }

    $allowed = akademiata_rankingi_title_allowed_tags();

    if ($highlight !== '' && $highlight !== null && strpos($title, $highlight) !== false) {
        $parts = explode($highlight, $title, 2);
        echo wp_kses(akademiata_rankingi_title_normalize($parts[0]), $allowed);
        echo '<mark>' . esc_html($highlight) . '</mark>';
        echo wp_kses(akademiata_rankingi_title_normalize($parts[1] ?? ''), $allowed);
        return;
    }

    echo wp_kses(akademiata_rankingi_title_normalize($title), $allowed);
}

/**
 * @param string|null $title
 * @param string|null $emphasis
 */
function akademiata_rankingi_echo_title_em($title, $emphasis = ''): void {
    if ($title === '' || $title === null) {
        return;
    }

    echo wp_kses(akademiata_rankingi_title_normalize($title), akademiata_rankingi_title_allowed_tags());

    if ($emphasis !== '' && $emphasis !== null) {
        echo ' <em>' . wp_kses(akademiata_rankingi_title_normalize($emphasis), akademiata_rankingi_title_allowed_tags()) . '</em>';
    }
}

/**
 * Default theme-bundled video for the rankingi LP (bypasses WP 2 MB upload limit).
 */
function akademiata_rankingi_theme_video_filename(): string {
    return 'ATAMISTRZEMSWIATA1.mp4';
}

function akademiata_rankingi_theme_video_path(): string {
    return get_template_directory() . '/static/video/' . akademiata_rankingi_theme_video_filename();
}

function akademiata_rankingi_theme_video_url(): string {
    if (!is_readable(akademiata_rankingi_theme_video_path())) {
        return '';
    }

    return get_template_directory_uri() . '/static/video/' . akademiata_rankingi_theme_video_filename();
}
