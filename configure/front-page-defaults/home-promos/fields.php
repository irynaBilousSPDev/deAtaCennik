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
function akademiata_home_promos_defaults_uk(): array {
	return require __DIR__ . '/content-uk.php';
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults_ru(): array {
	return require __DIR__ . '/content-ru.php';
}

/**
 * @return list<string>
 */
function akademiata_home_promos_localized_langs(): array {
	return [ 'en', 'uk', 'ru' ];
}

/**
 * @return array<string, mixed>
 */
function akademiata_home_promos_defaults(): array {
	$lang = function_exists('akademiata_normalize_theme_lang_code')
		? akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'))
		: 'pl';

	$map = [
		'en' => 'akademiata_home_promos_defaults_en',
		'uk' => 'akademiata_home_promos_defaults_uk',
		'ru' => 'akademiata_home_promos_defaults_ru',
	];

	if ( isset( $map[ $lang ] ) && is_callable( $map[ $lang ] ) ) {
		return $map[ $lang ]();
	}

	return akademiata_home_promos_defaults_pl();
}

/**
 * Replace values still equal to Polish defaults with the target-language defaults
 * (covers WPML “copy from original” on translated front pages).
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
 * @return array<string, array<string, mixed>>
 */
function akademiata_home_promos_promos_by_id(): array {
	static $index = null;

	if ( $index !== null ) {
		return $index;
	}

	$index = [];
	if ( ! function_exists( 'akademiata_get_calculator_promos' ) ) {
		return $index;
	}

	foreach ( akademiata_get_calculator_promos() as $promo ) {
		if ( ! is_array( $promo ) || empty( $promo['id'] ) ) {
			continue;
		}
		$index[ (string) $promo['id'] ] = $promo;
	}

	return $index;
}

/**
 * @param array<string, mixed> $card
 * @return array{kind: string, id: string, sub: string}|null
 */
function akademiata_home_promos_parse_card_promo_ref( array $card ): ?array {
	$promo_id = sanitize_key( (string) ( $card['promo_id'] ?? '' ) );
	$link     = trim( (string) ( $card['link'] ?? '' ) );
	$sub      = trim( (string) ( $card['sub'] ?? '' ) );

	if ( $link !== '' && strpos( $link, '?' ) !== false ) {
		$query = (string) parse_url( $link, PHP_URL_QUERY );
		if ( $query !== '' ) {
			parse_str( $query, $args );
			if ( $promo_id === '' && ! empty( $args['promo'] ) ) {
				$promo_id = sanitize_key( (string) $args['promo'] );
			}
			if ( $sub === '' && isset( $args['sub'] ) ) {
				$sub = trim( (string) $args['sub'] );
			}
			if ( ! empty( $args['rekr'] ) ) {
				return [
					'kind' => 'rekr',
					'id'   => sanitize_key( (string) $args['rekr'] ),
					'sub'  => $sub,
				];
			}
		}
	}

	if ( $promo_id === '' ) {
		return null;
	}

	return [
		'kind' => 'promo',
		'id'   => $promo_id,
		'sub'  => $sub,
	];
}

/**
 * @param array{kind: string, id: string, sub: string} $ref
 */
function akademiata_home_promos_offer_listing_url( array $ref ): string {
	$base = '';
	if ( function_exists( 'akademiata_get_oferta_page_id' ) ) {
		$page_id = akademiata_get_oferta_page_id();
		if ( $page_id > 0 ) {
			$base = (string) get_permalink( $page_id );
		}
	}
	if ( $base === '' ) {
		$base = home_url( '/oferta/' );
	}

	$args = [];
	if ( ( $ref['kind'] ?? '' ) === 'promo' && ( $ref['id'] ?? '' ) !== '' ) {
		$args['promo'] = $ref['id'];
		if ( ( $ref['sub'] ?? '' ) !== '' ) {
			$args['sub'] = $ref['sub'];
		}
	}

	return $args === [] ? $base : add_query_arg( $args, $base );
}

/**
 * Fill empty card copy from calculator PROMOS (Excel / prices.json). ACF wins when set.
 *
 * @param array<string, mixed> $card
 * @param array<string, mixed> $promo
 * @return array<string, mixed>
 */
function akademiata_home_promos_apply_promo_copy( array $card, array $promo ): array {
	$name  = trim( (string) ( $promo['name'] ?? '' ) );
	$tag   = trim( (string) ( $promo['tag'] ?? '' ) );
	$short = trim( wp_strip_all_tags( (string) ( $promo['short'] ?? '' ) ) );

	if ( trim( (string) ( $card['headline'] ?? '' ) ) === '' && $name !== '' ) {
		$card['headline'] = $name;
	}
	if ( trim( (string) ( $card['value'] ?? '' ) ) === '' && $tag !== '' ) {
		$card['value'] = $tag;
	}
	if ( trim( (string) ( $card['meta'] ?? '' ) ) === '' && $short !== '' ) {
		$card['meta'] = $short;
	}

	return $card;
}

/**
 * @param array<int, array<string, mixed>> $cards
 * @return array<int, array<string, mixed>>
 */
function akademiata_home_promos_sync_cards_from_promos( array $cards ): array {
	$promos_by_id = akademiata_home_promos_promos_by_id();
	$out          = [];

	foreach ( $cards as $card ) {
		if ( ! is_array( $card ) ) {
			continue;
		}

		$ref = akademiata_home_promos_parse_card_promo_ref( $card );
		if ( $ref && ( $ref['kind'] ?? '' ) === 'promo' ) {
			$promo = $promos_by_id[ $ref['id'] ] ?? null;
			if ( ! is_array( $promo ) ) {
				continue;
			}
			$card = akademiata_home_promos_apply_promo_copy( $card, $promo );

			$link = trim( (string) ( $card['link'] ?? '' ) );
			if ( $link === '' || stripos( $link, 'kalkulator-czesnego' ) !== false ) {
				$card['link'] = wp_make_link_relative( akademiata_home_promos_offer_listing_url( $ref ) );
			}
		} elseif ( trim( (string) ( $card['link'] ?? '' ) ) === '' && $ref ) {
			$card['link'] = wp_make_link_relative( akademiata_home_promos_offer_listing_url( $ref ) );
		}

		$out[] = $card;
	}

	return $out;
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

	if ( in_array( $lang, akademiata_home_promos_localized_langs(), true ) ) {
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
			if ( in_array( $lang, akademiata_home_promos_localized_langs(), true ) ) {
				$merged['cards'][ $i ] = akademiata_home_promos_replace_pl_copies(
					$merged['cards'][ $i ],
					is_array( $pl_cards[ $i ] ?? null ) ? $pl_cards[ $i ] : array(),
					is_array( $default_cards[ $i ] ?? null ) ? $default_cards[ $i ] : array()
				);
			}
		}

		$merged['cards'] = akademiata_home_promos_sync_cards_from_promos( $merged['cards'] );
	}

	return $merged;
}

/**
 * @param array<string, mixed> $card
 */
function akademiata_home_promos_card_url( array $card ): string {
	$link = trim( (string) ( $card['link'] ?? '' ) );
	if ( $link === '' ) {
		$ref = akademiata_home_promos_parse_card_promo_ref( $card );
		if ( $ref ) {
			return akademiata_home_promos_offer_listing_url( $ref );
		}
		if ( function_exists( 'akademiata_get_oferta_page_id' ) ) {
			$page_id = akademiata_get_oferta_page_id();
			if ( $page_id > 0 ) {
				return (string) get_permalink( $page_id );
			}
		}
		return home_url( '/oferta/' );
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
