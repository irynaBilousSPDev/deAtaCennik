<?php

require_once dirname(__DIR__) . '/merge.php';

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_zasady_rekrutacji_defaults(): array {
    return require __DIR__ . '/content.php';
}

/**
 * @param array<string, mixed>|false $acf_fields
 * @return array<string, array<string, mixed>>
 */
function akademiata_zasady_rekrutacji_fields($acf_fields): array {
    $defaults = akademiata_zasady_rekrutacji_defaults();
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
 * @return array<string, string>
 */
function akademiata_zasady_rekrutacji_static_images(): array {
    return [
        'hero'      => 'rekrutacja-hero.png',
        'reassure'  => 'rekrutacja-reassure.png',
    ];
}

/**
 * @param string $key hero|reassure
 */
function akademiata_zasady_rekrutacji_static_image_url(string $key): string {
    $files = akademiata_zasady_rekrutacji_static_images();
    if (!isset($files[$key])) {
        return '';
    }
    $path = get_template_directory() . '/static/img/' . $files[$key];
    if (!is_readable($path)) {
        return '';
    }
    return get_template_directory_uri() . '/static/img/' . $files[$key];
}
