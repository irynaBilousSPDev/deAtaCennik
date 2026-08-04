<?php

require_once dirname(__DIR__, 2) . '/lp-defaults/merge.php';

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults(): array {
	return require __DIR__ . '/content.php';
}

/**
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
function akademiata_home_promos_fields( $acf_group ): array {
	$defaults  = akademiata_home_promos_defaults();
	$acf_group = is_array( $acf_group ) ? $acf_group : [];
	$merged    = akademiata_lp_merge_defaults( $defaults, $acf_group );

	if ( ! empty( $merged['cards'] ) && is_array( $merged['cards'] ) ) {
		$default_cards = $defaults['cards'] ?? [];
		foreach ( $merged['cards'] as $i => $card ) {
			$merged['cards'][ $i ] = akademiata_lp_merge_defaults(
				$default_cards[ $i ] ?? ( $default_cards[0] ?? [] ),
				is_array( $card ) ? $card : null
			);
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
 * @param array<int, array<string, mixed>> $cards
 * @return array<int, array<int, array<string, mixed>>>
 */
function akademiata_home_promos_columns( array $cards ): array {
	$by_area = [];
	foreach ( $cards as $card ) {
		$area = preg_replace( '/[^a-f]/', '', (string) ( $card['area'] ?? '' ) );
		if ( $area === '' ) {
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

	if ( $columns === [] ) {
		return [ array_values( $cards ) ];
	}

	return $columns;
}
