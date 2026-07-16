<?php

require_once dirname(__DIR__) . '/merge.php';

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_o_uczelni_defaults(): array {
	$defaults = require __DIR__ . '/content.php';

	$uri = get_template_directory_uri();
	$defaults['oucz_kim_section']['badge_url'] = $uri . '/assets/dist/img/ranking-perspektywy-2026-1-miejsce.png';
	$defaults['oucz_absolwenci_section']['badge_url'] = $uri . '/assets/dist/img/ela-logo.svg';

	foreach ($defaults['oucz_historia_section']['steps'] as $i => $step) {
		if (!empty($step['logo_key'])) {
			$defaults['oucz_historia_section']['steps'][$i]['logo_url'] = $uri . '/assets/dist/img/o-uczelni/' . $step['logo_key'];
		}
	}

	foreach ($defaults['oucz_infra_section']['buildings'] as $bi => $building) {
		foreach ($building['gallery'] as $gi => $img) {
			if (!empty($img['theme_key'])) {
				$defaults['oucz_infra_section']['buildings'][$bi]['gallery'][$gi]['url'] = $uri . '/assets/dist/img/o-uczelni/' . $img['theme_key'];
			}
		}
	}

	return $defaults;
}

/**
 * @param array<string, mixed>|false $acf_fields
 * @return array<string, array<string, mixed>>
 */
function akademiata_o_uczelni_fields($acf_fields): array {
	$defaults = akademiata_o_uczelni_defaults();
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
function akademiata_o_uczelni_allowed_tags(): array {
	return [
		'p' => [],
		'br' => [],
		'b' => [],
		'strong' => [],
		'em' => [],
		'i' => [],
		'a' => [
			'href' => true,
			'title' => true,
			'target' => true,
			'rel' => true,
		],
	];
}

/**
 * @param string|null $html
 */
function akademiata_o_uczelni_kses($html): string {
	if ($html === '' || $html === null) {
		return '';
	}

	return wp_kses((string) $html, akademiata_o_uczelni_allowed_tags());
}

/**
 * Resolve image URL from ACF image array, URL string, or theme fallback.
 *
 * @param mixed  $image
 * @param string $fallback_url
 */
function akademiata_o_uczelni_image_url($image, string $fallback_url = ''): string {
	if (is_array($image) && !empty($image['url'])) {
		return (string) $image['url'];
	}
	if (is_string($image) && $image !== '') {
		return $image;
	}

	return $fallback_url;
}
