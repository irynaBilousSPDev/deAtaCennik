<?php

require_once dirname(__DIR__, 2) . '/lp-defaults/merge.php';

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults_pl(): array {
	return require __DIR__ . '/content.php';
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults_en(): array {
	return require __DIR__ . '/content-en.php';
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults(): array {
	$lang = function_exists('akademiata_normalize_theme_lang_code')
		? akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'))
		: 'pl';

	if ($lang === 'en') {
		return akademiata_home_promos_defaults_en();
	}

	return akademiata_home_promos_defaults_pl();
}

/**
 * Replace values still equal to Polish defaults with the target-language defaults
 * (covers WPML “copy from original” on the EN front page).
 *
 * @param array<string, mixed> $merged
 * @param array<string, mixed> $pl
 * @param array<string, mixed> $localized
 * @return array<string, mixed>
 */
function akademiata_home_promos_replace_pl_copies(array $merged, array $pl, array $localized): array {
	foreach ($localized as $key => $localized_val) {
		if (!array_key_exists($key, $merged)) {
			continue;
		}

		$pl_val  = $pl[ $key ] ?? null;
		$current = $merged[ $key ];

		if (is_array($localized_val) && function_exists('array_is_list') && array_is_list($localized_val)) {
			if (!is_array($current)) {
				continue;
			}
			foreach ($localized_val as $i => $item_en) {
				if (!isset($current[ $i ]) || !is_array($item_en)) {
					continue;
				}
				$current[ $i ] = akademiata_home_promos_replace_pl_copies(
					$current[ $i ],
					is_array($pl_val[ $i ] ?? null) ? $pl_val[ $i ] : array(),
					$item_en
				);
			}
			$merged[ $key ] = $current;
			continue;
		}

		if (is_array($localized_val)) {
			$merged[ $key ] = akademiata_home_promos_replace_pl_copies(
				is_array($current) ? $current : array(),
				is_array($pl_val) ? $pl_val : array(),
				$localized_val
			);
			continue;
		}

		if ($current === $pl_val || $current === '' || $current === null) {
			$merged[ $key ] = $localized_val;
		}
	}

	return $merged;
}

/**
 * @return array<string, string>
 */
function akademiata_home_promos_color_map(): array {
	return [
		'peach'   => '#ffc862',
		'pink'    => '#fd9ea7',
		'lime'    => '#c5e84a',
		'sky'     => '#82b2e6',
		'lilac'   => '#bf9bff',
		'apricot' => '#ffc862',
	];
}

/**
 * @param array<string, mixed>|false|null $acf_group
 * @return array<string, mixed>
 */
function akademiata_home_promos_fields( $acf_group ): array {
	$defaults_pl = akademiata_home_promos_defaults_pl();
	$defaults    = akademiata_home_promos_defaults();
	$acf_group   = is_array( $acf_group ) ? $acf_group : [];
	$merged      = akademiata_lp_merge_defaults( $defaults, $acf_group );

	$lang = function_exists('akademiata_normalize_theme_lang_code')
		? akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'))
		: 'pl';

	if ( $lang === 'en' ) {
		$merged = akademiata_home_promos_replace_pl_copies( $merged, $defaults_pl, $defaults );
	}

	if ( ! empty( $merged['cards'] ) && is_array( $merged['cards'] ) ) {
		$default_cards = $defaults['cards'] ?? [];
		$pl_cards      = $defaults_pl['cards'] ?? [];
		foreach ( $merged['cards'] as $i => $card ) {
			$merged['cards'][ $i ] = akademiata_lp_merge_defaults(
				$default_cards[ $i ] ?? ( $default_cards[0] ?? [] ),
				is_array( $card ) ? $card : null
			);
			if ( $lang === 'en' ) {
				$merged['cards'][ $i ] = akademiata_home_promos_replace_pl_copies(
					$merged['cards'][ $i ],
					is_array( $pl_cards[ $i ] ?? null ) ? $pl_cards[ $i ] : array(),
					is_array( $default_cards[ $i ] ?? null ) ? $default_cards[ $i ] : array()
				);
			}
		}
	}

	return $merged;
}

/**
 * @param array<string, mixed> $card
 */
function akademiata_home_promos_card_url( array $card ): string {
	$link = trim( (string) ( $card['link'] ?? '' ) );
	if ( $link === '' ) {
		return home_url( '/kalkulator-czesnego/' );
	}
	if ( preg_match( '#^https?://#i', $link ) ) {
		return $link;
	}

	return home_url( '/' . ltrim( $link, '/' ) );
}

/**
 * @param array<string, mixed> $card
 */
function akademiata_home_promos_card_image_url( array $card ): string {
	$image = $card['image'] ?? null;
	if ( is_array( $image ) && ! empty( $image['url'] ) ) {
		return (string) $image['url'];
	}
	if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
		$url = wp_get_attachment_image_url( (int) $image['ID'], 'large' );
		if ( $url ) {
			return $url;
		}
	}

	$rel = trim( (string) ( $card['image_url'] ?? '' ) );
	if ( $rel === '' ) {
		return '';
	}

	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	return get_template_directory_uri() . '/' . ltrim( $rel, '/' );
}

/**
 * @param array<string, mixed> $card
 */
function akademiata_home_promos_card_bg( array $card ): string {
	$color = (string) ( $card['color'] ?? 'peach' );
	if ( $color === 'custom' ) {
		$custom = trim( (string) ( $card['color_custom'] ?? '' ) );
		if ( $custom !== '' ) {
			return $custom;
		}
	}

	$map = akademiata_home_promos_color_map();

	return $map[ $color ] ?? $map['peach'];
}

/**
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

/**
 * Ensure each card has a unique grid slot (a–f). Duplicate/default "a" used to hide siblings.
 *
 * @param array<int, array<string, mixed>> $cards
 * @return array<int, array<string, mixed>>
 */
function akademiata_home_promos_normalize_card_areas( array $cards ): array {
	$letters = [ 'a', 'b', 'c', 'd', 'e', 'f' ];
	$used    = [];
	$out     = [];

	foreach ( array_values( $cards ) as $i => $card ) {
		if ( ! is_array( $card ) ) {
			continue;
		}

		$area = preg_replace( '/[^a-f]/', '', (string) ( $card['area'] ?? '' ) );
		if ( $area === '' || isset( $used[ $area ] ) ) {
			$area = $letters[ $i ] ?? '';
		}
		if ( $area === '' || isset( $used[ $area ] ) ) {
			$area = 'z' . $i;
		}

		$used[ $area ] = true;
		$card['area']  = $area;
		$out[]         = $card;
	}

	return $out;
}

/**
 * @param array<int, array<string, mixed>> $cards
 * @return array<int, array<int, array<string, mixed>>>
 */
function akademiata_home_promos_columns( array $cards ): array {
	$cards = akademiata_home_promos_normalize_card_areas( $cards );
	if ( $cards === [] ) {
		return [];
	}

	$by_area = [];
	foreach ( $cards as $card ) {
		$area = (string) ( $card['area'] ?? '' );
		if ( $area === '' || isset( $by_area[ $area ] ) ) {
			continue;
		}
		$by_area[ $area ] = $card;
	}

	$order = [
		[ 'a', 'e' ],
		[ 'b', 'd' ],
		[ 'c', 'f' ],
	];

	$columns = [];
	foreach ( $order as $areas ) {
		$col = [];
		foreach ( $areas as $area ) {
			if ( isset( $by_area[ $area ] ) ) {
				$col[] = $by_area[ $area ];
			}
		}
		if ( $col !== [] ) {
			$columns[] = $col;
		}
	}

	if ( count( $by_area ) < count( $cards ) || $columns === [] ) {
		return [ $cards ];
	}

	return $columns;
}
