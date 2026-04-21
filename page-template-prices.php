<?php
/**
 * Template Name: Prices
 *
 * Textdomain: akademiata
 */
get_header();
?>

<div class="page page-prices">
	<div class="container">
		<?php the_breadcrumb(); ?>
		<h1><?php the_title(); ?></h1>

		<div id="ata-loader" class="prices-loader" role="status" aria-live="polite">
			<div class="prices-loader__spinner" aria-hidden="true"></div>
			<div class="prices-loader__text">
				<?php echo esc_html__('Ładowanie aktualnych cen...', 'akademiata'); ?>
			</div>
		</div>

		<div class="kalkulator-wse w" id="kalkulator-content">
			<div class="sec">Miasto</div>
			<div class="seg" id="city-row">
				<button type="button" class="seg-btn on" data-val="wwa">Warszawa</button>
				<button type="button" class="seg-btn" data-val="wro">Wrocław</button>
			</div>

			<div class="sec">Język studiów</div>
			<div class="seg" id="lang-row">
				<button type="button" class="seg-btn on" data-val="pl">Studia w języku polskim</button>
				<button type="button" class="seg-btn" data-val="en">Studies in English</button>
			</div>

			<div id="uaby-wrap" style="display:none">
				<div class="uaby-row" id="uaby-row">
					<div class="uaby-chk" id="uaby-chk"></div>
					<span class="uaby-lbl">Jestem obywatelem Ukrainy lub Białorusi</span>
				</div>
			</div>

			<div class="sec">Program <span class="badge" id="prog-count">—</span></div>
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
				<div class="sec">Zniżki i promocje</div>
				<div id="promos-inner"></div>
			</div>

			<div id="sum-box" style="display:none"></div>

			<div class="enr" id="enr-box" style="display:none">
				<div class="enr-title">Opłaty jednorazowe przy zapisie</div>
				<div class="enr-items" id="enr-items"></div>
			</div>

			<div class="cta-row">
				<a id="btn-more" class="btn-sec" href="#" target="_blank" rel="noopener noreferrer">Więcej o programie →</a>
				<a id="btn-apply" class="btn-pri" href="#" target="_blank" rel="noopener noreferrer">Zapisz się →</a>
			</div>

			<div class="note" id="note-bot"></div>
		</div>
	</div>
</div>

<?php get_footer(); ?>

