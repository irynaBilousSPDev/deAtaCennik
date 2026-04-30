<?php
/**
 * Template Name: Prices
 *
 * Textdomain: akademiata
 */
get_header();
$current_lang = (string) apply_filters('wpml_current_language', null);
$is_en = ($current_lang === 'en');
?>

<div class="page page-prices my-5 py-3">
	<div class="container">
		<?php the_breadcrumb(); ?>
		<h1>
			<?php echo $is_en ? esc_html__('Tuition calculator', 'akademiata') : esc_html__('Kalkulator czesnego', 'akademiata'); ?>
		</h1>
		<h2 class="title_section primary_color mb-3 page-prices__subtitle">
			<?php echo $is_en ? esc_html__('Check tuition fees and apply in a few minutes.', 'akademiata') : esc_html__('Sprawdź koszt studiów i zapisz się w kilka minut.', 'akademiata'); ?>
		</h2>
		<!-- <div class="content" style="font-size: 20px; font-weight: 400; font-family: 'Lato', sans-serif;max-width: 90%;">
			Oferujemy kilka elastycznych wariantów płatności — możesz rozłożyć czesne na wygodne raty miesięczne lub zapłacić z góry za semestr 
			albo cały rok i skorzystać ze zniżki nawet do 10%. Do tego czekają na Ciebie promocje, które możesz łączyć: zapisz się wcześniej, 
			przyjdź z grupą znajomych lub skorzystaj z rabatu dla absolwentów szkół technicznych i artystycznych. Wybierz swój kierunek i sprawdź, ile dokładnie zapłacisz.
		</div> -->

		<?php
		set_query_var('prices_calculator_fixed_key', '');
		set_query_var('prices_calculator_fixed_lang', '');
		set_query_var('prices_calculator_hide_more_btn', false);
		locate_template('template-parts/prices/calculator.php', true, true);
		?>
	</div>
</div>

<?php get_footer(); ?>

