<?php

require_once dirname(__DIR__, 2) . '/lp-defaults/merge.php';

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults(): array {
	return require __DIR__ . '/content.php';
}

/**
 * Preset card background colors (CSS tokens).
 *
 * @return array<string, string>
 */
function akademiata_home_promos_color_map(): array {
	return [
		'peach'   => '#f2c48a',
		'pink'    => '#f0b8b4',
		'lime'    => '#d4e070',
		'sky'     => '#b5d0ea',
		'lilac'   => '#d0bde0',
		'apricot' => '#f0c090',
	];
}

/**
 * @param array<string, mixed>|false|null $acf_group
 * @return array<string, mixed>
 */
function akademiata_home_promos_fields($acf_group): array {
	$defaults  = akademiata_home_promos_defaults();
	$acf_group = is_array($acf_group) ? $acf_group : [];
	$merged    = akademiata_lp_merge_defaults($defaults, $acf_group);

	if (!empty($merged['cards']) && is_array($merged['cards'])) {
		$default_cards = $defaults['cards'] ?? [];
		foreach ($merged['cards'] as $i => $card) {
			$merged['cards'][ $i ] = akademiata_lp_merge_defaults(
				$default_cards[ $i ] ?? ($default_cards[0] ?? []),
				is_array($card) ? $card : null
			);
		}
	}

	return $merged;
}

/**
 * Resolve card image URL (ACF image or theme static fallback).
 *
 * @param array<string, mixed> $card
 */
function akademiata_home_promos_card_image_url(array $card): string {
	$image = $card['image'] ?? null;
	if (is_array($image) && !empty($image['url'])) {
		return (string) $image['url'];
	}
	if (is_array($image) && !empty($image['ID'])) {
		$url = wp_get_attachment_image_url((int) $image['ID'], 'large');
		if ($url) {
			return $url;
		}
	}

	$rel = trim((string) ($card['image_url'] ?? ''));
	if ($rel === '') {
		return '';
	}

	$path = get_template_directory() . '/' . ltrim($rel, '/');
	if (!is_readable($path)) {
		return '';
	}

	return get_template_directory_uri() . '/' . ltrim($rel, '/');
}

/**
 * @param array<string, mixed> $card
 */
function akademiata_home_promos_card_bg(array $card): string {
	$color = (string) ($card['color'] ?? 'peach');
	if ($color === 'custom') {
		$custom = trim((string) ($card['color_custom'] ?? ''));
		if ($custom !== '') {
			return $custom;
		}
	}

	$map = akademiata_home_promos_color_map();

	return $map[ $color ] ?? $map['peach'];
}

/**
 * Allowed HTML in promo headlines.
 *
 * @return array<string, array<string, bool>>
 */
function akademiata_home_promos_allowed_tags(): array {
	return [
		'strong' => [],
		'em'     => [],
		'br'     => [],
		'span'   => [
			'class' => true,
		],
	];
}
