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

// Language detection (WPML-first, with fallbacks).
// This controls:
// - initial UI state (default selected language button)
// - EN copy for a few strings until translations are finalized
$is_en = false;
if (!empty($fixed_lang)) {
	$is_en = (strtolower(trim($fixed_lang)) === 'en');
} else {
	$wpml_lang = apply_filters('wpml_current_language', null);
	if (is_string($wpml_lang) && $wpml_lang !== '') {
		$is_en = (strtolower($wpml_lang) === 'en');
	} else {
		$loc = function_exists('determine_locale') ? determine_locale() : get_locale();
		$is_en = (is_string($loc) && stripos($loc, 'en') === 0);
	}
}

$initial_lang = $is_en ? 'en' : 'pl';
?>

<script>
	// Ensure calculator starts in the correct language for the current WPML version.
	// (The JS bundle defaults to 'pl' unless this is set before it runs.)
	window.lang = window.lang || <?php echo wp_json_encode($initial_lang); ?>;
	// UI language (WPML). This should NOT change when user switches "study language".
	window.PRICES_UI_LANG = window.PRICES_UI_LANG || <?php echo wp_json_encode($initial_lang); ?>;
</script>

<div id="ata-loader" class="prices-loader" role="status" aria-live="polite">
	<div class="prices-loader__spinner" aria-hidden="true"></div>
	<div class="prices-loader__text">
		<?php echo $is_en ? esc_html__('Loading current prices...', 'akademiata') : esc_html__('Ładowanie aktualnych cen...', 'akademiata'); ?>
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
		<?php echo wp_json_encode([
			'ctaMore' => $is_en ? __('More about the program →', 'akademiata') : __('Więcej o programie →', 'akademiata'),
			'ctaApply' => $is_en ? __('Apply now →', 'akademiata') : __('Zapisz się →', 'akademiata'),
			'feeAdmission' => $is_en ? __('Recruitment fee', 'akademiata') : __('Opłata rekrutacyjna', 'akademiata'),
			'feeApplication' => $is_en ? __('Application fee', 'akademiata') : __('Opłata aplikacyjna', 'akademiata'),
			'feeEntry' => $is_en ? __('Enrollment fee', 'akademiata') : __('Wpisowe', 'akademiata'),
			'feeTotal' => $is_en ? __('Total on enrollment', 'akademiata') : __('Razem przy zapisie', 'akademiata'),
			'mostPopular' => $is_en ? __('Most popular', 'akademiata') : __('Najczęściej wybierany', 'akademiata'),
			'savePrefix' => $is_en ? __('You save', 'akademiata') : __('oszczędzasz', 'akademiata'),
			'savePerYearSuffix' => $is_en ? __('/year', 'akademiata') : __('/rok', 'akademiata'),
			'insteadOfPrefix' => $is_en ? __('instead of', 'akademiata') : __('zamiast', 'akademiata'),
			'andSaveText' => $is_en ? __('— you save', 'akademiata') : __('— oszczędzasz', 'akademiata'),
			// Temporary EN overrides for PL promos (edit here).
			'promoOverrides' => $is_en ? [
				'jednorazowo' => [
					'name' => 'Upfront payment (one-time discount)',
					'tag' => '−5% or −10%',
					'short' => 'Pay the semester or the full year upfront and get a discount.',
					'full' => 'Deadline: 10 September (winter / full year) or 10 March (summer). Cannot be combined with "Transfer to ATA" and "Graduate continues with discount (PL)".',
				],
				'szybki' => [
					'name' => 'Fast start',
					'tag' => '−1,000 PLN',
					'short' => 'Registration by 30.06.2026 and contract signed by 31.07.2026.',
					'full' => 'Applies to Bachelor (1st cycle) only. Discount is split proportionally across both semesters.',
				],
				'grupie' => [
					'name' => 'Cheaper in a group',
					'tag' => '−200 / −400 PLN',
					'short' => 'Apply together with friends or family (until 30.09.2026).',
					'full' => '2–4 people = 200 PLN, 5+ people = 400 PLN. Documents must be submitted on the same day.',
					'so' => [
						[ 'v' => 200, 'l' => '2–4 people (−200 PLN)' ],
						[ 'v' => 400, 'l' => '5+ people (−400 PLN)' ],
					],
				],
				'techart' => [
					'name' => 'Technical / arts school graduate',
					'tag' => '−1,200 PLN',
					'short' => 'This year’s high school graduate from a technical or arts profile.',
					'full' => 'The profile must be clearly indicated by the school name or track on the diploma/certificate.',
				],
				'przejscie' => [
					'name' => 'Transfer to ATA',
					'tag' => '−30%',
					'short' => 'Transfer from another university — discount in the starting semester.',
					'full' => 'Cannot be combined with any other promotion. Not available to candidates previously removed from ATA/WSEiZ.',
				],
				'absolwent_pl' => [
					'name' => 'Graduate continues with discount (PL)',
					'tag' => '−20% (or −30%)',
					'short' => 'ATA/WAB Bachelor graduates — discount for the entire Master’s program.',
					'full' => 'Grade 5.0 (Wrocław) = 30%. Cannot be combined with other promotions.',
					'so' => [
						[ 'v' => 0.2, 'l' => 'Standard result (−20%)' ],
						[ 'v' => 0.3, 'l' => 'Grade 5.0 / Wrocław (−30%)' ],
					],
				],
			] : [],
			'emptyTitle' => $is_en ? __('Pricing coming soon', 'akademiata') : __('Cennik w przygotowaniu', 'akademiata'),
			'emptyText' => $is_en
				? __('We will publish the updated pricing for this program soon. If you need help, contact us — we’ll be happy to assist.', 'akademiata')
				: __('Wkrótce udostępnimy aktualny cennik dla tego programu. Jeśli chcesz, skontaktuj się z nami — chętnie pomożemy.', 'akademiata'),
		], JSON_UNESCAPED_UNICODE); ?>
	</script>

	<div class="prices-empty" id="prices-empty" style="display:none" role="status" aria-live="polite">
		<div class="prices-empty__title" data-empty-title><?php echo $is_en ? esc_html__('Pricing coming soon', 'akademiata') : esc_html__('Cennik w przygotowaniu', 'akademiata'); ?></div>
		<div class="prices-empty__text" data-empty-text><?php echo $is_en ? esc_html__('We will publish the updated pricing for this program soon. If you need help, contact us — we’ll be happy to assist.', 'akademiata') : esc_html__('Wkrótce udostępnimy aktualny cennik dla tego programu. Jeśli chcesz, skontaktuj się z nami — chętnie pomożemy.', 'akademiata'); ?></div>
	</div>

	<!-- Hidden/locked rows on single offer; JS will hide if fixed-key is present -->
	<div class="sec" data-prices-row="city"><?php echo $is_en ? esc_html__('City', 'akademiata') : esc_html__('Miasto', 'akademiata'); ?></div>
	<div class="seg" id="city-row" data-prices-row="city">
		<button type="button" class="seg-btn on" data-val="wwa"><?php echo esc_html__('Warszawa', 'akademiata'); ?></button>
		<button type="button" class="seg-btn" data-val="wro"><?php echo esc_html__('Wrocław', 'akademiata'); ?></button>
	</div>

	<div class="sec" data-prices-row="lang"><?php echo $is_en ? esc_html__('Study language', 'akademiata') : esc_html__('Język studiów', 'akademiata'); ?></div>
	<div class="seg" id="lang-row" data-prices-row="lang">
		<button type="button" class="seg-btn<?php echo $initial_lang === 'pl' ? ' on' : ''; ?>" data-val="pl">
			<span class="seg-btn__short"><?php echo $is_en ? esc_html__('Polish', 'akademiata') : esc_html__('Polski', 'akademiata'); ?></span>
			<span class="seg-btn__long"><?php echo $is_en ? esc_html__('Studies in Polish', 'akademiata') : esc_html__('Studia w języku polskim', 'akademiata'); ?></span>
		</button>
		<button type="button" class="seg-btn<?php echo $initial_lang === 'en' ? ' on' : ''; ?>" data-val="en">
			<span class="seg-btn__short"><?php echo esc_html__('English', 'akademiata'); ?></span>
			<span class="seg-btn__long"><?php echo $is_en ? esc_html__('Studies in English', 'akademiata') : esc_html__('Studia w języku angielskim', 'akademiata'); ?></span>
		</button>
	</div>

	<div id="uaby-wrap" style="display:none">
		<div class="uaby-row" id="uaby-row">
			<div class="uaby-chk" id="uaby-chk"></div>
			<span class="uaby-lbl"><?php echo $is_en ? esc_html__('I am a citizen of Ukraine or Belarus', 'akademiata') : esc_html__('Jestem obywatelem Ukrainy lub Białorusi', 'akademiata'); ?></span>
		</div>
	</div>

	<div class="sec" data-prices-row="program">
		<span class="sec__short"><?php echo $is_en ? esc_html__('Selected program', 'akademiata') : esc_html__('Wybrany kierunek', 'akademiata'); ?></span>
		<span class="sec__long">
			<?php echo $is_en ? esc_html__('Program', 'akademiata') : esc_html__('Program', 'akademiata'); ?>
			<span class="badge" id="prog-count">
				<span data-prog-count-num>—</span>&nbsp;
				<span data-prog-count-label<?php echo $is_en ? ' style="text-transform: lowercase;"' : ''; ?>>
					<?php echo $is_en ? esc_html__('options', 'akademiata') : esc_html__('opcji', 'akademiata'); ?>
				</span>
			</span>
			<span style="font-size: 14px; font-weight: 400; font-family: 'Lato', sans-serif; text-transform: lowercase; letter-spacing: 0.05em;">
				(<?php echo $is_en ? esc_html__('choose your program', 'akademiata') : esc_html__('wybierz swój program', 'akademiata'); ?>)
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
		<div class="sec"><?php echo $is_en ? esc_html__('Study mode', 'akademiata') : esc_html__('Forma studiów', 'akademiata'); ?></div>
		<div class="pills" id="mode-row"></div>
	</div>

	<div id="eu-wrap" style="display:none;margin-bottom:12px">
		<div class="sec"><?php echo esc_html__('Country group', 'akademiata'); ?></div>
		<div class="pills" id="eu-row">
			<button type="button" class="pill on" data-val="eu">EU / CIS / Ukraine</button>
			<button type="button" class="pill" data-val="non-eu">Other countries</button>
		</div>
	</div>

	<div class="sec sec--row" data-hide-when-empty>
		<span><?php echo $is_en ? esc_html__('Payment option', 'akademiata') : esc_html__('Wariant płatności', 'akademiata'); ?></span>
		<span
			class="sec-hint"
			data-plans-hint
			data-hint-right="<?php echo $is_en ? esc_attr__('Swipe →', 'akademiata') : esc_attr__('Przesuń →', 'akademiata'); ?>"
			data-hint-left="<?php echo $is_en ? esc_attr__('← Swipe', 'akademiata') : esc_attr__('← Przesuń', 'akademiata'); ?>"
			data-dir="right"
		>
			<?php echo $is_en ? esc_html__('Swipe →', 'akademiata') : esc_html__('Przesuń →', 'akademiata'); ?>
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
				<span class="pr pc-pr--desktop" data-plan-price-desktop></span>
				<span class="pc-unit pc-unit--desktop" data-plan-unit-desktop></span>
			</div>

			<!-- Mobile-only price row -->
			<div class="pc-price pc-price--mobile">
				<span class="pr" data-plan-price></span>
				<span class="pc-unit" data-plan-unit></span>
			</div>
			<div class="pc-disc" data-plan-disc style="display:none"></div>
			<div class="sv" data-plan-sv style="display:none"></div>
			<div class="pc-pick" data-plan-pick style="display:none"><?php echo $is_en ? esc_html__('Most popular', 'akademiata') : esc_html__('Najczęściej wybierany', 'akademiata'); ?></div>
		</div>
	</template>

	<div id="promos" class="promos-section" style="display:none" data-hide-when-empty>
		<?php $regulamin_url = apply_filters('ata_prices_regulamin_url', 'https://chmurka.wseiz.pl/index.php/s/tsE4yJ8ftXGkXdf#pdfviewer'); ?>
		<div class="sec sec--row">
			<span><?php echo $is_en ? esc_html__('Discounts and promotions', 'akademiata') : esc_html__('Zniżki i promocje', 'akademiata'); ?></span>
			<a class="sec-link" href="<?php echo esc_url($regulamin_url); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo $is_en ? esc_html__('Terms', 'akademiata') : esc_html__('Regulamin', 'akademiata'); ?><span class="sec-link__arr" aria-hidden="true"></span>
			</a>
		</div>
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
					<button class="pc-arr" type="button" aria-label="<?php echo $is_en ? esc_attr__('Expand', 'akademiata') : esc_attr__('Rozwiń', 'akademiata'); ?>" data-promo-arr>▾</button>
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

	<div class="enr" id="enr-box" data-hide-when-empty>
		<div class="enr-title"><?php echo $is_en ? esc_html__('One-time fees on enrollment', 'akademiata') : esc_html__('Opłaty jednorazowe przy zapisie', 'akademiata'); ?></div>
		<div class="enr-items" id="enr-items">
			<div class="ei" data-enr-item="admission">
				<div class="en" data-enr-label="admission"><?php echo $is_en ? esc_html__('Recruitment fee', 'akademiata') : esc_html__('Opłata rekrutacyjna', 'akademiata'); ?></div>
				<div class="ev" data-enr-value="admission">—</div>
			</div>

			<div class="ei ei--promo" data-enr-item="entry">
				<div class="en" data-enr-label="entry"><?php echo $is_en ? esc_html__('Enrollment fee', 'akademiata') : esc_html__('Wpisowe', 'akademiata'); ?></div>
				<div class="ev" data-enr-value="entry">—</div>
				<div class="eb" data-enr-badge="entry" style="display:none">
					<span class="eb-ic" aria-hidden="true">⏰</span>
					<span data-enr-badge-text="entry"></span>
				</div>
			</div>

			<div class="ei ei--total" data-enr-item="total">
				<div class="en" data-enr-label="total"><?php echo $is_en ? esc_html__('Total on enrollment', 'akademiata') : esc_html__('Razem przy zapisie', 'akademiata'); ?></div>
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
			<?php echo $is_en ? esc_html__('More about the program →', 'akademiata') : esc_html__('Więcej o programie →', 'akademiata'); ?>
		</a>
		<a id="btn-apply" class="btn-pri" href="#" rel="noopener noreferrer"><?php echo $is_en ? esc_html__('Apply now →', 'akademiata') : esc_html__('Zapisz się →', 'akademiata'); ?></a>
	</div>

	<div class="note" id="note-bot"></div>
</div>

