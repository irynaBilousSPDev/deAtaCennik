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
    return [
        'br' => [],
        'mark' => [],
        'em' => [],
    ];
}

/**
 * @param string|null $text
 */
function akademiata_rankingi_title_html($text): string {
    if ($text === '' || $text === null) {
        return '';
    }

    return wp_kses((string) $text, akademiata_rankingi_title_allowed_tags());
}

/**
 * @param string|null $title
 * @param string|null $highlight
 */
function akademiata_rankingi_echo_title_mark($title, $highlight = ''): void {
    if ($title === '' || $title === null) {
        return;
    }

    $title = (string) $title;

    if ($highlight !== '' && $highlight !== null && strpos($title, $highlight) !== false) {
        $parts = explode($highlight, $title, 2);
        echo esc_html($parts[0]);
        echo '<mark>' . esc_html($highlight) . '</mark>';
        echo esc_html($parts[1] ?? '');
        return;
    }

    echo wp_kses($title, akademiata_rankingi_title_allowed_tags());
}

/**
 * @param string|null $title
 * @param string|null $emphasis
 */
function akademiata_rankingi_echo_title_em($title, $emphasis = ''): void {
    if ($title !== '' && $title !== null) {
        echo esc_html((string) $title);
    }

    if ($emphasis !== '' && $emphasis !== null) {
        echo ' <em>' . esc_html((string) $emphasis) . '</em>';
    }
}

/**
 * Default theme-bundled video for the rankingi LP (bypasses WP 2 MB upload limit).
 */
function akademiata_rankingi_theme_video_filename(): string {
    return 'ATAMISTRZEMSWIATA.mp4';
}

function akademiata_rankingi_theme_video_path(): string {
    return get_template_directory() . '/static/video/' . akademiata_rankingi_theme_video_filename();
}

function akademiata_rankingi_theme_video_exists(): bool {
    return is_readable(akademiata_rankingi_theme_video_path());
}

function akademiata_rankingi_theme_video_url(): string {
    return get_template_directory_uri() . '/static/video/' . akademiata_rankingi_theme_video_filename();
}
