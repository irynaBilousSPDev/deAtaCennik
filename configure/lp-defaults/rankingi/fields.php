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
 * Default theme-bundled video for the rankingi LP (bypasses WP 2 MB upload limit).
 */
function akademiata_rankingi_theme_video_filename(): string {
    return 'ATAMISTRZEMSWIATA1.mp4';
}

function akademiata_rankingi_theme_video_path(): string {
    return get_template_directory() . '/assets/dist/video/' . akademiata_rankingi_theme_video_filename();
}

function akademiata_rankingi_theme_video_url(): string {
    if (!is_readable(akademiata_rankingi_theme_video_path())) {
        return '';
    }

    return get_template_directory_uri() . '/assets/dist/video/' . akademiata_rankingi_theme_video_filename();
}

/**
 * @param array<string, mixed> $film
 */
function akademiata_rankingi_resolve_video_url(array $film): string {
    if (!empty($film['video_url'])) {
        return (string) $film['video_url'];
    }

    if (is_array($film['video'] ?? null) && !empty($film['video']['url'])) {
        return (string) $film['video']['url'];
    }

    return akademiata_rankingi_theme_video_url();
}
