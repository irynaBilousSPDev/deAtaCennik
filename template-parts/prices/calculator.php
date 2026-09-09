<?php
/**
 * Shared Prices Calculator markup (used on Prices page + single offer pages).
 *
 * Inputs via query vars:
 * - prices_calculator_fixed_key (string) : logical_sync_key to lock calculator to one program (optional)
 * - prices_calculator_fixed_lang (string): 'pl'|'en' (optional)
 * - prices_calculator_hide_more_btn (bool): hide "Więcej o programie" CTA (optional)
 */

$fixed_key = (string) get_query_var('prices_calculator_fixed_key', '');
$fixed_lang = (string) get_query_var('prices_calculator_fixed_lang', '');
$hide_more_btn = (bool) get_query_var('prices_calculator_hide_more_btn', false);
$layout = (string) get_query_var('prices_calculator_layout', '');
$is_single_offer_layout = ($layout === 'single-offer');

// UI language = WPML page language (EN/UK/RU/PL copy).
// Study language = fixed_lang on singles (taxonomy) or UI default — never confuse the two.
$wpml_lang = apply_filters('wpml_current_language', null);
if (!is_string($wpml_lang) || $wpml_lang === '') {
	$loc = function_exists('determine_locale') ? determine_locale() : get_locale();
	$wpml_lang = (is_string($loc) && $loc !== '') ? $loc : 'pl';
}
$ui_lang = function_exists('akademiata_normalize_theme_lang_code')
	? akademiata_normalize_theme_lang_code($wpml_lang)
	: 'pl';

$ui = static function ($key) use ($ui_lang) {
	return function_exists('akademiata_prices_ui_t')
		? akademiata_prices_ui_t($key, $ui_lang)
		: '';
};

$study_lang = !empty($fixed_lang)
	? (strtolower(trim($fixed_lang)) === 'en' ? 'en' : 'pl')
	: ($ui_lang === 'en' ? 'en' : 'pl');
$initial_lang = $study_lang;

// Miasto × Język studiów → zarządzenie (chmurka). Two separate link sets.
$regulamin_urls_plans = apply_filters('ata_prices_regulamin_urls_plans', [
	'wwa' => [
		'pl' => 'https://chmurka.wseiz.pl/index.php/s/LgF9TpCerLtGHb2', // ZARZĄDZENIE 7/2026
		'en' => 'https://chmurka.wseiz.pl/index.php/s/HafmSQsZjqdZEwg', // ZARZĄDZENIE 9/2026
	],
	'wro' => [
		'pl' => 'https://chmurka.wseiz.pl/index.php/s/Jc3zWDzGwQgXPGB', // ZARZĄDZENIE 8/2026
		'en' => 'https://chmurka.wseiz.pl/index.php/s/of9kNrHXxdMEjYT', // ZARZĄDZENIE 10/2026
	],
]);
$regulamin_urls_promos = apply_filters('ata_prices_regulamin_urls_promos', [
	'wwa' => [
		'pl' => 'https://chmurka.wseiz.pl/index.php/s/DncBnzJLJ7kLWkY#pdfviewer',
		'en' => 'https://chmurka.wseiz.pl/index.php/s/FwxKrLBGtgEPNcQ', // ZARZĄDZENIE 18/2026
	],
	'wro' => [
		'pl' => 'https://chmurka.wseiz.pl/index.php/s/DncBnzJLJ7kLWkY#pdfviewer',
		'en' => 'https://chmurka.wseiz.pl/index.php/s/tbbQ2nTs8wtzTXH', // ZARZĄDZENIE 17/2026
	],
]);
$initial_study_lang = $study_lang;
$regulamin_url_plans = $regulamin_urls_plans['wwa'][$initial_study_lang] ?? $regulamin_urls_plans['wwa']['pl'];
$regulamin_url_promos = $regulamin_urls_promos['wwa'][$initial_study_lang] ?? $regulamin_urls_promos['wwa']['pl'];

$zarzadzanie_note = [
	'pl' => 'Promocja obowiązuje osoby, które zarejestrują się w systemie rekrutacyjnym po 1 września i dokonają płatności do 31 października.',
	'en' => 'The promotion applies to candidates who register in the recruitment system after 1 September and make payment by 31 October.',
	'uk' => 'Акція діє для осіб, які зареєструються в системі рекрутації після 1 вересня та здійснять оплату до 31 жовтня.',
	'ru' => 'Акция действует для лиц, которые зарегистрируются в системе рекрутации после 1 сентября и произведут оплату до 31 октября.',
];

$i18n_payload = function_exists('akademiata_prices_calculator_i18n_payload')
	? akademiata_prices_calculator_i18n_payload($ui_lang)
	: array();
$i18n_payload['zarzadzaniePromoNote'] = $zarzadzanie_note;
$i18n_payload['regulaminUrlsPlans'] = $regulamin_urls_plans;
$i18n_payload['regulaminUrlsPromos'] = $regulamin_urls_promos;
?>

<script>
	// Study language (sheet RAW.pl / RAW.en). Defaults to taxonomy on offer singles.
	window.lang = window.lang || <?php echo wp_json_encode($study_lang); ?>;
	// UI language (WPML). Must NOT follow study-language toggle / fixed_lang.
	window.PRICES_UI_LANG = window.PRICES_UI_LANG || <?php echo wp_json_encode($ui_lang); ?>;
</script>

<div id="ata-loader" class="prices-loader" role="status" aria-live="polite">
	<div class="prices-loader__spinner" aria-hidden="true"></div>
	<div class="prices-loader__text">
		<?php echo esc_html($ui('loading')); ?>
	</div>
</div>

<div
	class="kalkulator-wse"
	id="kalkulator-content"
	<?php if (!empty(trim($fixed_key))) : ?>
		data-fixed-key="<?php echo esc_attr($fixed_key); ?>"
	<?php endif; ?>
	<?php if (!empty(trim($fixed_lang))) : ?>
		data-fixed-lang="<?php echo esc_attr($fixed_lang); ?>"
	<?php endif; ?>
	<?php if ($hide_more_btn) : ?>
		data-hide-more-btn="1"
	<?php endif; ?>
	<?php if (!empty($layout)) : ?>
		data-layout="<?php echo esc_attr($layout); ?>"
	<?php endif; ?>
>
	<script type="application/json" id="prices-i18n">
		<?php echo wp_json_encode($i18n_payload, JSON_UNESCAPED_UNICODE); ?>
	</script>

	<div class="prices-empty" id="prices-empty" style="display:none" role="status" aria-live="polite">
		<div class="prices-empty__title" data-empty-title><?php echo esc_html($ui('emptyTitle')); ?></div>
		<div class="prices-empty__text" data-empty-text><?php echo esc_html($ui('emptyText')); ?></div>
	</div>

	<!-- Hidden/locked rows on single offer; JS will hide if fixed-key is present -->
	<div class="sec" data-prices-row="city"><?php echo esc_html($ui('city')); ?></div>
	<div class="seg" id="city-row" data-prices-row="city">
		<button type="button" class="seg-btn on" data-val="wwa"><?php echo esc_html__('Warszawa', 'akademiata'); ?></button>
		<button type="button" class="seg-btn" data-val="wro"><?php echo esc_html__('Wrocław', 'akademiata'); ?></button>
	</div>

	<div class="sec" data-prices-row="lang"><?php echo esc_html($ui('studyLang')); ?></div>
	<div class="seg" id="lang-row" data-prices-row="lang">
		<button type="button" class="seg-btn<?php echo $initial_lang === 'pl' ? ' on' : ''; ?>" data-val="pl">
			<span class="seg-btn__short"><?php echo esc_html($ui('polishShort')); ?></span>
			<span class="seg-btn__long"><?php echo esc_html($ui('polishLong')); ?></span>
		</button>
		<button type="button" class="seg-btn<?php echo $initial_lang === 'en' ? ' on' : ''; ?>" data-val="en">
			<span class="seg-btn__short"><?php echo esc_html($ui('englishShort')); ?></span>
			<span class="seg-btn__long"><?php echo esc_html($ui('englishLong')); ?></span>
		</button>
	</div>

	<div id="uaby-wrap" style="display:none">
		<div class="uaby-row" id="uaby-row">
			<div class="uaby-chk" id="uaby-chk"></div>
			<span class="uaby-lbl"><?php echo esc_html($ui('uabyLabel')); ?></span>
		</div>
	</div>

	<div class="sec" data-prices-row="program">
		<span class="sec__short"><?php echo esc_html($ui('selectedProgram')); ?></span>
		<span class="sec__long">
			<?php echo esc_html($ui('program')); ?>
			<span class="badge" id="prog-count">
				<span data-prog-count-num>—</span>&nbsp;
				<span data-prog-count-label<?php echo $ui_lang === 'en' ? ' style="text-transform: lowercase;"' : ''; ?>>
					<?php echo esc_html($ui('options')); ?>
				</span>
			</span>
			<span style="font-size: 14px; font-weight: 400; font-family: 'Lato', sans-serif; text-transform: lowercase; letter-spacing: 0.05em;">
				(<?php echo esc_html($ui('chooseProgram')); ?>)
			</span>
		</span>
	</div>
	<div class="sel-wrap" data-prices-row="program">
		<button type="button" class="sel-mobile" id="prog-sel-mobile" aria-haspopup="listbox" aria-controls="prog-sel">
			<div class="sel-mobile__text">
				<div class="sel-mobile__title" data-prog-mobile-title>—</div>
				<div class="sel-mobile__meta" data-prog-mobile-meta>—</div>
			</div>
			<span class="sel-mobile__arr" aria-hidden="true"></span>
		</button>
		<select id="prog-sel"></select>
	</div>

	<div id="mode-wrap" style="display:none;margin-bottom:12px">
		<div class="sec"><?php echo esc_html($ui('studyMode')); ?></div>
		<div class="pills" id="mode-row"></div>
	</div>

	<div id="eu-wrap" style="display:none;margin-bottom:12px">
		<div class="sec"><?php echo esc_html($ui('countryGroup')); ?></div>
		<div class="pills" id="eu-row">
			<button type="button" class="pill on" data-val="eu">EU / CIS / Ukraine</button>
			<button type="button" class="pill" data-val="non-eu">Other countries</button>
		</div>
	</div>

	<div class="sec sec--row" data-hide-when-empty>
		<span><?php echo esc_html($ui('paymentOption')); ?></span>
		<span class="sec-row__aside">
			<a class="sec-link" data-regulamin-link="plans" href="<?php echo esc_url($regulamin_url_plans); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html($ui('terms')); ?><span class="sec-link__arr" aria-hidden="true"></span>
			</a>
			<span
				class="sec-hint"
				data-plans-hint
			data-hint-right="<?php echo esc_attr($ui('swipeRight')); ?>"
			data-hint-left="<?php echo esc_attr($ui('swipeLeft')); ?>"
			data-dir="right"
		>
			<?php echo esc_html($ui('swipeRight')); ?>
			</span>
		</span>
	</div>
	<div id="plans-wrap" data-hide-when-empty></div>

	<template id="plan-card-template">
		<div class="pc" data-plan-card>
			<!-- Mobile-only header -->
			<div class="pc-h pc-h--mobile">
				<div class="pc-ic" aria-hidden="true"></div>
				<div class="lbl" data-plan-label></div>
			</div>

			<!-- Desktop-only legacy layout (keeps existing desktop styles) -->
			<div class="lbl pc-lbl--desktop" data-plan-label-desktop></div>
			<div class="pc-price--desktop">
				<div class="pc-price-line">
					<span class="pr pc-pr--desktop" data-plan-price-desktop></span>
					<span class="pc-unit pc-unit--desktop" data-plan-unit-desktop></span>
				</div>
				<div class="pc-was pc-was--desktop" data-plan-was-desktop style="display:none"></div>
			</div>

			<!-- Mobile-only price row -->
			<div class="pc-price pc-price--mobile">
				<div class="pc-price-line">
					<span class="pr" data-plan-price></span>
					<span class="pc-unit" data-plan-unit></span>
				</div>
				<div class="pc-was" data-plan-was style="display:none"></div>
			</div>
			<div class="pc-disc" data-plan-disc style="display:none"></div>
			<div class="sv" data-plan-sv style="display:none"></div>
			<div class="pc-pick" data-plan-pick style="display:none"><?php echo esc_html($ui('mostPopular')); ?></div>
		</div>
	</template>

	<div id="promos" class="promos-section" style="display:none" data-hide-when-empty>
		<div class="sec sec--row">
			<span><?php echo esc_html($ui('discounts')); ?></span>
			<a class="sec-link" data-regulamin-link="promos" href="<?php echo esc_url($regulamin_url_promos); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html($ui('terms')); ?><span class="sec-link__arr" aria-hidden="true"></span>
			</a>
		</div>
		<p class="promo-campaign-note" data-zarzadzanie-note style="display:none"></p>
		<div id="promos-inner"></div>

		<template id="promo-card-template">
			<div class="promo-card">
				<div class="pc-head">
					<div class="pc-chk"></div>
					<div class="pc-info">
						<div class="pc-name" data-promo-name></div>
						<div class="pc-short" data-promo-short></div>
					</div>
					<div class="pc-tag" data-promo-tag></div>
					<button class="pc-arr" type="button" aria-label="<?php echo esc_attr($ui('expand')); ?>" data-promo-arr>▾</button>
				</div>
				<div class="pc-body" data-promo-body style="display:none">
					<div data-promo-body-text></div>
					<div class="pc-subopts" data-promo-subopts style="display:none"></div>
				</div>
			</div>
		</template>
	</div>

	<div id="sum-box" style="display:none" data-hide-when-empty>
		<div class="sum">
			<div class="sl">
				<div class="sp" data-sum-sp>—</div>
				<div class="sn" data-sum-sn>—</div>
			</div>
			<div class="sr">
				<div class="sprice" data-sum-price>—</div>
				<div class="ssave" data-sum-save style="display:none"></div>
			</div>
		</div>
	</div>

	<div id="rekr-promos" class="promos-section rekr-promos-section" style="display:none" data-hide-when-empty>
		<div class="sec sec--row">
			<span class="sec-title-wrap">
				<?php echo esc_html($ui('discounts')); ?>
				<span class="sec-badge"><?php echo esc_html($ui('rekrBadge')); ?></span>
			</span>
		</div>
		<div id="rekr-promos-inner"></div>
		<div class="promo-rule" id="rekr-promos-note"></div>
	</div>

	<div class="enr" id="enr-box" data-hide-when-empty>
		<div class="enr-title"><?php echo esc_html($ui('oneTimeFees')); ?></div>
		<div class="enr-items" id="enr-items">
			<div class="ei" data-enr-item="admission">
				<div class="en" data-enr-label="admission"><?php echo esc_html($ui('feeAdmission')); ?></div>
				<div class="ev" data-enr-value="admission">—</div>
				<div class="ei-was" data-enr-was="admission" style="display:none"></div>
			</div>

			<div class="ei ei--promo" data-enr-item="entry">
				<div class="en" data-enr-label="entry"><?php echo esc_html($ui('feeEntry')); ?></div>
				<div class="ev" data-enr-value="entry">—</div>
				<div class="eb" data-enr-badge="entry" style="display:none">
					<span class="eb-ic" aria-hidden="true">⏰</span>
					<span data-enr-badge-text="entry"></span>
				</div>
			</div>

			<div class="ei ei--total" data-enr-item="total">
				<div class="en" data-enr-label="total"><?php echo esc_html($ui('feeTotal')); ?></div>
				<div class="ev" data-enr-value="total">—</div>
				<div class="es" data-enr-savings style="display:none"></div>
			</div>
		</div>
	</div>

	<div
		class="cta-row"
		data-hide-when-empty
		<?php if ($is_single_offer_layout) : ?>
			style="display:flex; justify-content:flex-end;"
		<?php endif; ?>
	>
		<a id="btn-more" class="btn-sec" href="#" rel="noopener noreferrer"<?php echo $hide_more_btn ? ' style="display:none"' : ''; ?>>
			<?php echo esc_html($ui('ctaMore')); ?>
		</a>
		<a id="btn-apply" class="btn-pri" href="#" rel="noopener noreferrer"><?php echo esc_html($ui('ctaApply')); ?></a>
	</div>

	<div class="note" id="note-bot"></div>
</div>
