<?php

/**
 * Display copy for calculator PROMOS on non-PL home-promo cards.
 * Dates in short/name are synced from live sheet JSON (see fields.php).
 *
 * @return array<string, array<string, array<string, string>>>
 */
function akademiata_home_promos_promo_i18n_catalog(): array {
	return [
		'en' => [
			'szybki'      => [
				'name'  => 'One step closer, PLN 1,000 less',
				'tag'   => '−1,000 PLN',
				'short' => 'Registration by 30.09.2026 and contract signed by 30.10.2026.',
			],
			'jednorazowo' => [
				'name'  => 'Upfront payment (one-time discount)',
				'tag'   => '−5% or −10%',
				'short' => 'Pay the semester or the full year upfront and get a discount. Deadline: 10 September or 10 March.',
			],
			'grupie'      => [
				'name'  => 'Cheaper in a group',
				'tag'   => '−200 / −400 PLN',
				'short' => 'Apply together with friends or family (until 30.09.2026).',
			],
			'techart'     => [
				'name'  => 'Technical / arts school graduate',
				'tag'   => '−1,200 PLN',
				'short' => 'This year’s high school graduate from a technical or arts profile.',
			],
			'przejscie'   => [
				'name'  => 'Transfer to ATA',
				'tag'   => '−30%',
				'short' => 'Transfer from another university — discount in the starting semester.',
			],
			'absolwent_pl' => [
				'name'  => 'Graduate continues with discount',
				'tag'   => '−20% / −30%',
				'short' => 'ATA/WAB first-cycle graduates — discount on full second-cycle tuition.',
			],
			'absolwent_en' => [
				'name'  => 'Graduate continues with discount',
				'tag'   => '−20%',
				'short' => 'UTA/WSEiZ graduates — 20% off total Master\'s tuition.',
			],
		],
		'uk' => [
			'szybki'      => [
				'name'  => 'Навчання на крок ближче, на 1 000 zł дешевше',
				'tag'   => '−1 000 zł',
				'short' => 'Реєстрація до 30.09.2026 та підписання договору до 30.10.2026.',
			],
			'jednorazowo' => [
				'name'  => 'Оплата наперед',
				'tag'   => '−5% / −10%',
				'short' => 'Семестр або рік наперед — знижка. Термін: до 10 вересня або 10 березня.',
			],
			'grupie'      => [
				'name'  => 'В групі дешевше',
				'tag'   => '−200 / −400 zł',
				'short' => 'Запишіться разом із групою друзів або родини (до 30.09.2026).',
			],
			'techart'     => [
				'name'  => 'Випускник технічної/мистецької школи',
				'tag'   => '−1 200 zł',
				'short' => 'Цьогорічний випускник з технічного або мистецького профілю.',
			],
			'przejscie'   => [
				'name'  => 'Перехід до ATA',
				'tag'   => '−30%',
				'short' => 'Переведення з іншого ВНЗ — знижка в семестрі старту.',
			],
			'absolwent_pl' => [
				'name'  => 'Абітурієнт продовжує зі знижкою',
				'tag'   => '−20% / −30%',
				'short' => 'Випускники I ступеня ATA/WAB — знижка на все навчання II ступеня.',
			],
			'absolwent_en' => [
				'name'  => 'Випускник продовжує зі знижкою',
				'tag'   => '−20%',
				'short' => 'Випускники UTA/WSEiZ — 20% знижки на все навчання магістратуру.',
			],
		],
		'ru' => [
			'szybki'      => [
				'name'  => 'Обучение на шаг ближе, на 1 000 zł дешевле',
				'tag'   => '−1 000 zł',
				'short' => 'Регистрация до 30.09.2026 и подписание договора до 30.10.2026.',
			],
			'jednorazowo' => [
				'name'  => 'Оплата вперёд',
				'tag'   => '−5% / −10%',
				'short' => 'Семестр или год вперёд — скидка. Срок: до 10 сентября или 10 марта.',
			],
			'grupie'      => [
				'name'  => 'В группе дешевле',
				'tag'   => '−200 / −400 zł',
				'short' => 'Запишитесь вместе с группой друзей или семьи (до 30.09.2026).',
			],
			'techart'     => [
				'name'  => 'Выпускник технической/художественной школы',
				'tag'   => '−1 200 zł',
				'short' => 'Выпускник этого года с техническим или художественным профилем.',
			],
			'przejscie'   => [
				'name'  => 'Переход в ATA',
				'tag'   => '−30%',
				'short' => 'Перевод из другого вуза — скидка в семестре старта.',
			],
			'absolwent_pl' => [
				'name'  => 'Абитуриент продолжает со скидкой',
				'tag'   => '−20% / −30%',
				'short' => 'Выпускники I ступени ATA/WAB — скидка на всё обучение II ступени.',
			],
			'absolwent_en' => [
				'name'  => 'Выпускник продолжает со скидкой',
				'tag'   => '−20%',
				'short' => 'Выпускники UTA/WSEiZ — 20% скидки на всё обучение магистратуры.',
			],
		],
	];
}

/**
 * @return list<string>
 */
function akademiata_home_promos_extract_promo_dates( array $promo ): array {
	$text = trim(
		wp_strip_all_tags( (string) ( $promo['short'] ?? '' ) )
		. ' '
		. wp_strip_all_tags( (string) ( $promo['full'] ?? '' ) )
	);

	if ( $text === '' || ! preg_match_all( '/\d{1,2}\.\d{1,2}\.\d{4}/', $text, $matches ) ) {
		return [];
	}

	return array_values( $matches[0] );
}

/**
 * @param list<string> $dates
 */
function akademiata_home_promos_sync_dates_in_copy( string $copy, array $dates ): string {
	if ( $copy === '' || $dates === [] ) {
		return $copy;
	}

	$i = 0;
	return (string) preg_replace_callback(
		'/\d{1,2}\.\d{1,2}\.\d{4}/',
		static function () use ( $dates, &$i ) {
			$date = $dates[ min( $i, count( $dates ) - 1 ) ] ?? '';
			$i++;
			return $date;
		},
		$copy
	);
}

/**
 * @param array<string, mixed> $promo
 * @return array<string, mixed>
 */
function akademiata_home_promos_localize_promo( array $promo, ?string $lang = null ): array {
	$lang = $lang ?? (
		function_exists( 'akademiata_normalize_theme_lang_code' )
			? akademiata_normalize_theme_lang_code( apply_filters( 'wpml_current_language', 'pl' ) )
			: 'pl'
	);

	if ( $lang === 'pl' || empty( $promo['id'] ) ) {
		return $promo;
	}

	$catalog = akademiata_home_promos_promo_i18n_catalog();
	$id      = (string) $promo['id'];
	$ov      = $catalog[ $lang ][ $id ] ?? ( $catalog['en'][ $id ] ?? null );

	if ( ! is_array( $ov ) ) {
		return $promo;
	}

	$dates = akademiata_home_promos_extract_promo_dates( $promo );
	$out   = $promo;

	foreach ( [ 'name', 'tag', 'short' ] as $key ) {
		if ( empty( $ov[ $key ] ) ) {
			continue;
		}
		$out[ $key ] = akademiata_home_promos_sync_dates_in_copy( (string) $ov[ $key ], $dates );
	}

	return $out;
}
