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
?>

<div id="ata-loader" class="prices-loader" role="status" aria-live="polite">
	<div class="prices-loader__spinner" aria-hidden="true"></div>
	<div class="prices-loader__text">
		<?php echo esc_html__('Ładowanie aktualnych cen...', 'akademiata'); ?>
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
			'ctaMore' => __('Więcej o programie →', 'akademiata'),
			'ctaApply' => __('Zapisz się →', 'akademiata'),
			'feeAdmission' => __('Opłata rekrutacyjna', 'akademiata'),
			'feeApplication' => __('Opłata aplikacyjna', 'akademiata'),
			'feeEntry' => __('Wpisowe', 'akademiata'),
			'feeTotal' => __('Razem przy zapisie', 'akademiata'),
			'emptyTitle' => __('Cennik w przygotowaniu', 'akademiata'),
			'emptyText' => __('Wkrótce udostępnimy aktualny cennik dla tego programu. Jeśli chcesz, skontaktuj się z nami — chętnie pomożemy.', 'akademiata'),
		], JSON_UNESCAPED_UNICODE); ?>
	</script>

	<div class="prices-empty" id="prices-empty" style="display:none" role="status" aria-live="polite">
		<div class="prices-empty__title" data-empty-title><?php echo esc_html__('Cennik w przygotowaniu', 'akademiata'); ?></div>
		<div class="prices-empty__text" data-empty-text><?php echo esc_html__('Wkrótce udostępnimy aktualny cennik dla tego programu. Jeśli chcesz, skontaktuj się z nami — chętnie pomożemy.', 'akademiata'); ?></div>
	</div>

	<!-- Hidden/locked rows on single offer; JS will hide if fixed-key is present -->
	<div class="sec" data-prices-row="city"><?php echo esc_html__('Miasto', 'akademiata'); ?></div>
	<div class="seg" id="city-row" data-prices-row="city">
		<button type="button" class="seg-btn on" data-val="wwa"><?php echo esc_html__('Warszawa', 'akademiata'); ?></button>
		<button type="button" class="seg-btn" data-val="wro"><?php echo esc_html__('Wrocław', 'akademiata'); ?></button>
	</div>

	<div class="sec" data-prices-row="lang"><?php echo esc_html__('Język studiów', 'akademiata'); ?></div>
	<div class="seg" id="lang-row" data-prices-row="lang">
		<button type="button" class="seg-btn on" data-val="pl"><?php echo esc_html__('Studia w języku polskim', 'akademiata'); ?></button>
		<button type="button" class="seg-btn" data-val="en"><?php echo esc_html__('Studia w języku angielskim', 'akademiata'); ?></button>
	</div>

	<div id="uaby-wrap" style="display:none">
		<div class="uaby-row" id="uaby-row">
			<div class="uaby-chk" id="uaby-chk"></div>
			<span class="uaby-lbl"><?php echo esc_html__('Jestem obywatelem Ukrainy lub Białorusi', 'akademiata'); ?></span>
		</div>
	</div>

	<div class="sec" data-prices-row="program">
		<?php echo esc_html__('Program', 'akademiata'); ?> <span class="badge" id="prog-count">—</span>
		<span style="font-size: 14px; font-weight: 400; font-family: 'Lato', sans-serif; text-transform: lowercase; letter-spacing: 0.05em;">
			(<?php echo esc_html__('wybierz swój program', 'akademiata'); ?>)
		</span>
	</div>
	<div class="sel-wrap" data-prices-row="program">
		<select id="prog-sel"></select>
	</div>

	<div id="mode-wrap" style="display:none;margin-bottom:12px">
		<div class="sec"><?php echo esc_html__('Forma studiów', 'akademiata'); ?></div>
		<div class="pills" id="mode-row"></div>
	</div>

	<div id="eu-wrap" style="display:none;margin-bottom:12px">
		<div class="sec"><?php echo esc_html__('Country group', 'akademiata'); ?></div>
		<div class="pills" id="eu-row">
			<button type="button" class="pill on" data-val="eu">EU / CIS / Ukraine</button>
			<button type="button" class="pill" data-val="non-eu">Other countries</button>
		</div>
	</div>

	<div class="sec" data-hide-when-empty><?php echo esc_html__('Wariant płatności', 'akademiata'); ?></div>
	<div id="plans-wrap" data-hide-when-empty></div>

	<div id="promos" class="promos-section" style="display:none" data-hide-when-empty>
		<?php $regulamin_url = apply_filters('ata_prices_regulamin_url', 'https://chmurka.wseiz.pl/index.php/s/tsE4yJ8ftXGkXdf#pdfviewer'); ?>
		<div class="sec sec--row">
			<span><?php echo esc_html__('Zniżki i promocje', 'akademiata'); ?></span>
			<a class="sec-link" href="<?php echo esc_url($regulamin_url); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__('Regulamin', 'akademiata'); ?><span class="sec-link__arr" aria-hidden="true"></span>
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
					<button class="pc-arr" type="button" aria-label="<?php echo esc_attr__('Rozwiń', 'akademiata'); ?>" data-promo-arr>▾</button>
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
		<div class="enr-title"><?php echo esc_html__('Opłaty jednorazowe przy zapisie', 'akademiata'); ?></div>
		<div class="enr-items" id="enr-items">
			<div class="ei" data-enr-item="admission">
				<div class="en" data-enr-label="admission"><?php echo esc_html__('Opłata rekrutacyjna', 'akademiata'); ?></div>
				<div class="ev" data-enr-value="admission">—</div>
			</div>

			<div class="ei ei--promo" data-enr-item="entry">
				<div class="en" data-enr-label="entry"><?php echo esc_html__('Wpisowe', 'akademiata'); ?></div>
				<div class="ev" data-enr-value="entry">—</div>
				<div class="eb" data-enr-badge="entry" style="display:none">
					<span class="eb-ic" aria-hidden="true">⏰</span>
					<span data-enr-badge-text="entry"></span>
				</div>
			</div>

			<div class="ei ei--total" data-enr-item="total">
				<div class="en" data-enr-label="total"><?php echo esc_html__('Razem przy zapisie', 'akademiata'); ?></div>
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
			<?php echo esc_html__('Więcej o programie →', 'akademiata'); ?>
		</a>
		<a id="btn-apply" class="btn-pri" href="#" rel="noopener noreferrer"><?php echo esc_html__('Zapisz się →', 'akademiata'); ?></a>
	</div>

	<div class="note" id="note-bot"></div>
</div>

