<?php

require_once dirname(__DIR__) . '/merge.php';

/**
 * Relative dir under theme `static/img/`.
 */
function akademiata_o_uczelni_static_subdir(): string {
	return 'images_about_us';
}

/**
 * Theme URL for a file in `static/img/images_about_us/`, or empty if missing.
 *
 * @param string $filename e.g. logo-wyzsza-szkola-handlowa-wroclaw.png
 */
function akademiata_o_uczelni_static_url(string $filename): string {
	$filename = ltrim($filename, '/');
	if ($filename === '') {
		return '';
	}
	$rel = 'static/img/' . akademiata_o_uczelni_static_subdir() . '/' . $filename;
	$path = get_template_directory() . '/' . $rel;
	if (!is_readable($path)) {
		return '';
	}

	return get_template_directory_uri() . '/' . $rel;
}

/**
 * Map remote upload basenames → local filenames in images_about_us.
 *
 * @return array<string, string>
 */
function akademiata_o_uczelni_remote_basename_map(): array {
	return [
		// Rada Pracodawców
		'saint-gobain-logo.png' => 'partner-saint-gobain.png',
		'orange-logo.jpg' => 'partner-orange.jpg',
		'hydroprojekt_LOGO.png' => 'partner-hydroprojekt.png',
		'jeronimomartinslogo655.png' => 'partner-jeronimo-martins.png',
		'PSR_logo_RGB.png' => 'partner-psr.png',
		'dom_development_nowe_logo_rgb.png' => 'partner-dom-development.png',
		'14618328083381aea5234c521f9ed81b8a601a39ddea6d3392cc0b.png' => 'partner-ricoh.png',
		'warsztat-pr-logo-duze3.png' => 'partner-warsztat-pr.png',
		'RGB_VEOLIA_HD.png' => 'partner-veolia.png',
		'general-electric-ge-vector-logo.png' => 'partner-general-electric.png',
		'csm_xbs_group_241823feaa.png' => 'partner-xbs-group.png',
		'forte.png' => 'partner-forte.png',
		'1200px-Kampinoski_okragle_podstawowe.png' => 'partner-kampinoski-park-narodowy.png',
		'logo-ilot.png' => 'partner-ilot.png',
		// Logo uczelni
		'logo.png' => 'logo-wseiz-dawne.png',
		// Infrastruktura — Olszewska
		'IMG_0201.jpg' => 'infra-olszewska-01.jpg',
		'IMG_0204.jpg' => 'infra-olszewska-02.jpg',
		'IMG_0206.jpg' => 'infra-olszewska-03.jpg',
		'IMG_0208.jpg' => 'infra-olszewska-04.jpg',
		'IMG_0291.jpg' => 'infra-olszewska-05.jpg',
		'IMG_0297.jpg' => 'infra-olszewska-06.jpg',
		'IMG_0303.jpg' => 'infra-olszewska-07.jpg',
		'IMG_0311.jpg' => 'infra-olszewska-08.jpg',
		// Grójecka
		'001.jpg' => 'infra-grojecka-01.jpg',
		'002.jpg' => 'infra-grojecka-02.jpg',
		'008.jpg' => 'infra-grojecka-03.jpg',
		'009.jpg' => 'infra-grojecka-04.jpg',
		'010.jpg' => 'infra-grojecka-05.jpg',
		'011.jpg' => 'infra-grojecka-06.jpg',
		'012.jpg' => 'infra-grojecka-07.jpg',
		'xF1.jpg' => 'infra-grojecka-08.jpg',
		// Rejtana
		'fot-2-1-scaled-e1618468176646.jpg' => 'infra-rejtana-01.jpg',
		'03.jpg' => 'infra-rejtana-02.jpg',
		'04.jpg' => 'infra-rejtana-03.jpg',
		'05.jpg' => 'infra-rejtana-04.jpg',
		'O_II_02.jpg' => 'infra-rejtana-05.jpg',
		'O_I_05.jpg' => 'infra-rejtana-06.jpg',
		'IMG_2718-scaled.jpg' => 'infra-rejtana-07.jpg',
		'DSCF3435.jpg' => 'infra-rejtana-08.jpg',
		// Klaudyn
		'klaudyn-laski-049.jpg' => 'infra-klaudyn-01.jpg',
		'klaudyn-laski-053.jpg' => 'infra-klaudyn-02.jpg',
		'klaudyn-laski-061.jpg' => 'infra-klaudyn-03.jpg',
		'klaudyn-laski-073.jpg' => 'infra-klaudyn-04.jpg',
		'klaudyn-laski-079.jpg' => 'infra-klaudyn-05.jpg',
		'klaudyn-laski-082.jpg' => 'infra-klaudyn-06.jpg',
		'klaudyn-laski-094.jpg' => 'infra-klaudyn-07.jpg',
		'fot-1-scaled.jpg' => 'infra-klaudyn-08.jpg',
	];
}

/**
 * Prefer local static file for known remote uploads / theme_key.
 */
/**
 * Same file as site header (`partials/header.php`).
 */
function akademiata_o_uczelni_header_logo_url(): string {
	$rel = 'static/img/ATA_logo_main.webp';
	$path = get_template_directory() . '/' . $rel;
	if (!is_readable($path)) {
		return '';
	}

	return get_template_directory_uri() . '/' . $rel;
}

function akademiata_o_uczelni_localize_url(string $url = '', string $theme_key = ''): string {
	if ($theme_key !== '') {
		$local = akademiata_o_uczelni_static_url($theme_key);
		if ($local !== '') {
			return $local;
		}
	}

	$url = trim($url);
	if ($url === '') {
		return '';
	}

	if (strpos($url, '/static/img/' . akademiata_o_uczelni_static_subdir() . '/') !== false) {
		return $url;
	}

	$path = (string) (wp_parse_url($url, PHP_URL_PATH) ?: '');
	$base = $path !== '' ? wp_basename($path) : '';
	if ($base === '') {
		return $url;
	}

	$map = akademiata_o_uczelni_remote_basename_map();
	if (isset($map[$base])) {
		$local = akademiata_o_uczelni_static_url($map[$base]);
		if ($local !== '') {
			return $local;
		}
	}

	// Already a theme-local images_about_us basename.
	$local = akademiata_o_uczelni_static_url($base);
	if ($local !== '') {
		return $local;
	}

	return $url;
}

/**
 * Resolve theme assets after defaults/ACF merge (keeps page off external media hosts).
 *
 * @param array<string, array<string, mixed>> $fields
 * @return array<string, array<string, mixed>>
 */
function akademiata_o_uczelni_resolve_static_assets(array $fields): array {
	if (isset($fields['oucz_kim_section']) && is_array($fields['oucz_kim_section'])) {
		$kim = &$fields['oucz_kim_section'];
		$kim['badge_url'] = akademiata_o_uczelni_localize_url(
			(string) ($kim['badge_url'] ?? ''),
			'badge-ranking-perspektywy-2026.png'
		);
		$kim['logo_image_old_url'] = akademiata_o_uczelni_localize_url(
			(string) ($kim['logo_image_old_url'] ?? ''),
			'logo-wseiz-dawne.png'
		);
		$logo_new = trim((string) ($kim['logo_image_new_url'] ?? ''));
		if ($logo_new === '' || strpos($logo_new, 'logo-ata-obecne') !== false) {
			$kim['logo_image_new_url'] = akademiata_o_uczelni_header_logo_url();
		} else {
			$kim['logo_image_new_url'] = akademiata_o_uczelni_localize_url($logo_new);
		}
		// Legacy single-image field → treat as dawne logo when new pair empty.
		if (($kim['logo_image_old_url'] ?? '') === '' && !empty($kim['logo_image_url'])) {
			$kim['logo_image_old_url'] = akademiata_o_uczelni_localize_url((string) $kim['logo_image_url']);
		}
	}

	if (isset($fields['oucz_absolwenci_section']) && is_array($fields['oucz_absolwenci_section'])) {
		$fields['oucz_absolwenci_section']['badge_url'] = akademiata_o_uczelni_localize_url(
			(string) ($fields['oucz_absolwenci_section']['badge_url'] ?? ''),
			'badge-ela.svg'
		);
	}

	if (!empty($fields['oucz_historia_section']['steps']) && is_array($fields['oucz_historia_section']['steps'])) {
		foreach ($fields['oucz_historia_section']['steps'] as $i => $step) {
			$fields['oucz_historia_section']['steps'][$i]['logo_url'] = akademiata_o_uczelni_localize_url(
				(string) ($step['logo_url'] ?? ''),
				(string) ($step['logo_key'] ?? '')
			);
		}
	}

	if (!empty($fields['oucz_wspolpraca_section']['partners']) && is_array($fields['oucz_wspolpraca_section']['partners'])) {
		foreach ($fields['oucz_wspolpraca_section']['partners'] as $i => $partner) {
			$fields['oucz_wspolpraca_section']['partners'][$i]['url'] = akademiata_o_uczelni_localize_url(
				(string) ($partner['url'] ?? ''),
				(string) ($partner['theme_key'] ?? '')
			);
		}
	}

	if (!empty($fields['oucz_infra_section']['buildings']) && is_array($fields['oucz_infra_section']['buildings'])) {
		foreach ($fields['oucz_infra_section']['buildings'] as $bi => $building) {
			if (empty($building['gallery']) || !is_array($building['gallery'])) {
				continue;
			}
			foreach ($building['gallery'] as $gi => $img) {
				$fields['oucz_infra_section']['buildings'][$bi]['gallery'][$gi]['url'] = akademiata_o_uczelni_localize_url(
					(string) ($img['url'] ?? ''),
					(string) ($img['theme_key'] ?? '')
				);
			}
		}
	}

	return $fields;
}

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_o_uczelni_defaults(): array {
	$defaults = require __DIR__ . '/content.php';

	return akademiata_o_uczelni_resolve_static_assets($defaults);
}

/**
 * @param array<string, mixed>|false $acf_fields
 * @return array<string, array<string, mixed>>
 */
function akademiata_o_uczelni_fields($acf_fields): array {
	$defaults = require __DIR__ . '/content.php';
	$acf_fields = is_array($acf_fields) ? $acf_fields : [];
	$merged = [];

	foreach ($defaults as $section_key => $section_defaults) {
		$merged[$section_key] = akademiata_lp_merge_defaults(
			$section_defaults,
			$acf_fields[$section_key] ?? null
		);
	}

	return akademiata_o_uczelni_resolve_static_assets($merged);
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
		return akademiata_o_uczelni_localize_url((string) $image['url']);
	}
	if (is_string($image) && $image !== '') {
		return akademiata_o_uczelni_localize_url($image);
	}

	return akademiata_o_uczelni_localize_url($fallback_url);
}
