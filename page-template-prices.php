<?php
/**
 * Template Name: Prices
 *
 * Textdomain: akademiata
 */
get_header();
?>

<div class="page page-prices my-5 py-3">
	<div class="container">
		<?php the_breadcrumb(); ?>
		<h1><?php the_title(); ?></h1>
		<h2 class="title_section primary_color mb-3" style="font-size: 30px; font-weight: 700; font-family: 'Nunito Sans', serif;">
		W ATA to Ty decydujesz, jak chcesz zaplanować wydatki na studia!
		</h2>
		<div class="content" style="font-size: 20px; font-weight: 400; font-family: 'Lato', sans-serif;max-width: 90%;">
			Oferujemy kilka elastycznych wariantów płatności — możesz rozłożyć czesne na wygodne raty miesięczne lub zapłacić z góry za semestr 
			albo cały rok i skorzystać ze zniżki nawet do 10%. Do tego czekają na Ciebie promocje, które możesz łączyć: zapisz się wcześniej, 
			przyjdź z grupą znajomych lub skorzystaj z rabatu dla absolwentów szkół technicznych i artystycznych. Wybierz swój kierunek i sprawdź, ile dokładnie zapłacisz.
		</div>

		<div id="ata-loader" class="prices-loader" role="status" aria-live="polite">
			<div class="prices-loader__spinner" aria-hidden="true"></div>
			<div class="prices-loader__text">
				<?php echo esc_html__('Ładowanie aktualnych cen...', 'akademiata'); ?>
			</div>
		</div>

		<div class="kalkulator-wse w" id="kalkulator-content">
			<script type="application/json" id="prices-i18n">
				<?php echo wp_json_encode([
					'ctaMore' => __('Więcej o programie →', 'akademiata'),
					'ctaApply' => __('Zapisz się →', 'akademiata'),
					'feeAdmission' => __('Opłata rekrutacyjna', 'akademiata'),
					'feeApplication' => __('Opłata aplikacyjna', 'akademiata'),
					'feeEntry' => __('Wpisowe', 'akademiata'),
					'feeTotal' => __('Razem przy zapisie', 'akademiata'),
				], JSON_UNESCAPED_UNICODE); ?>
			</script>

			<div class="sec">Miasto</div>
			<div class="seg" id="city-row">
				<button type="button" class="seg-btn on" data-val="wwa">Warszawa</button>
				<button type="button" class="seg-btn" data-val="wro">Wrocław</button>
			</div>

			<div class="sec">Język studiów</div>
			<div class="seg" id="lang-row">
				<button type="button" class="seg-btn on" data-val="pl">Studia w języku polskim</button>
				<button type="button" class="seg-btn" data-val="en">Studia w języku angielskim</button>
			</div>

			<div id="uaby-wrap" style="display:none">
				<div class="uaby-row" id="uaby-row">
					<div class="uaby-chk" id="uaby-chk"></div>
					<span class="uaby-lbl">Jestem obywatelem Ukrainy lub Białorusi</span>
				</div>
			</div>

			<div class="sec">Program <span class="badge" id="prog-count">—</span> 
			<span style="font-size: 14px; font-weight: 400; font-family: 'Lato', sans-serif; text-transform: lowercase; letter-spacing: 0.05em;">(wybierz swój program)</span>
			</div>
			<div class="sel-wrap">
				<select id="prog-sel"></select>
			</div>

			<div id="mode-wrap" style="display:none;margin-bottom:12px">
				<div class="sec">Forma studiów</div>
				<div class="pills" id="mode-row"></div>
			</div>

			<div id="eu-wrap" style="display:none;margin-bottom:12px">
				<div class="sec">Country group</div>
				<div class="pills" id="eu-row">
					<button type="button" class="pill on" data-val="eu">EU / CIS / Ukraine</button>
					<button type="button" class="pill" data-val="non-eu">Other countries</button>
				</div>
			</div>

			<div class="sec">Wariant płatności</div>
			<div id="plans-wrap"></div>

			<div id="promos-section" class="promos-section" style="display:none">
				<?php $regulamin_url = apply_filters( 'ata_prices_regulamin_url', '#' ); ?>
				<div class="sec sec--row">
					<span>Zniżki i promocje</span>
					<a class="sec-link" href="<?php echo esc_url( $regulamin_url ); ?>" target="_blank" rel="noopener noreferrer">
						Regulamin<span class="sec-link__arr" aria-hidden="true"></span>
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
							<button class="pc-arr" type="button" aria-label="Rozwiń" data-promo-arr>▾</button>
						</div>
						<div class="pc-body" data-promo-body style="display:none">
							<div data-promo-body-text></div>
							<div class="pc-subopts" data-promo-subopts style="display:none"></div>
						</div>
					</div>
				</template>
			</div>

			<div id="sum-box" style="display:none">
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

			<div class="enr" id="enr-box">
				<div class="enr-title">Opłaty jednorazowe przy zapisie</div>
				<div class="enr-items" id="enr-items">
					<div class="ei" data-enr-item="admission">
						<div class="en" data-enr-label="admission"><?php echo esc_html__( 'Opłata rekrutacyjna', 'akademiata' ); ?></div>
						<div class="ev" data-enr-value="admission">—</div>
					</div>

					<div class="ei ei--promo" data-enr-item="entry">
						<div class="en" data-enr-label="entry"><?php echo esc_html__( 'Wpisowe', 'akademiata' ); ?></div>
						<div class="ev" data-enr-value="entry">—</div>
						<div class="eb" data-enr-badge="entry" style="display:none">
							<span class="eb-ic" aria-hidden="true">⏰</span>
							<span data-enr-badge-text="entry"></span>
						</div>
					</div>

					<div class="ei ei--total" data-enr-item="total">
						<div class="en" data-enr-label="total"><?php echo esc_html__( 'Razem przy zapisie', 'akademiata' ); ?></div>
						<div class="ev" data-enr-value="total">—</div>
						<div class="es" data-enr-savings style="display:none"></div>
					</div>
				</div>
			</div>

			<div class="cta-row">
				<a id="btn-more" class="btn-sec" href="#"  rel="noopener noreferrer">Więcej o programie →</a>
				<a id="btn-apply" class="btn-pri" href="#" rel="noopener noreferrer">Zapisz się →</a>
			</div>

			<div class="note" id="note-bot">
				<!-- <?php echo esc_html__('Rata płatna do 10. dnia każdego miesiąca.', 'akademiata'); ?> -->
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>

