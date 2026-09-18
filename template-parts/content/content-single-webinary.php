<?php
/**
 * Single webinar — landing sections from ACF + CF7 signup.
 *
 * @package akademiata
 */

$acf = function_exists('get_fields') ? get_fields() : array();
if (!is_array($acf)) {
	$acf = array();
}
set_query_var('acf_fields', $acf);
set_query_var('web_fields', $acf);

$hero_title = trim((string) ($acf['web_hero_title'] ?? ''));
if ($hero_title === '') {
	$hero_title = get_the_title();
}

$form_output = '';
$form_id     = !empty($acf['web_signup_form_id']) ? (int) $acf['web_signup_form_id'] : 0;
if ($form_id > 0 && function_exists('wpcf7_contact_form')) {
	$cf7 = wpcf7_contact_form($form_id);
	if ($cf7) {
		$hash     = method_exists($cf7, 'hash') ? $cf7->hash() : '';
		$id_attr  = $hash ?: (string) $form_id;
		$title    = method_exists($cf7, 'title') ? $cf7->title() : '';
		$form_output = do_shortcode(
			'[contact-form-7 id="' . esc_attr($id_attr) . '"'
			. ($title !== '' ? ' title="' . esc_attr($title) . '"' : '')
			. ']'
		);
	}
}
set_query_var('web_hero_title', $hero_title);
set_query_var('web_form_output', $form_output);
?>

<div class="webinary-single">
	<?php get_template_part('template-parts/single-webinary/section', 'hero'); ?>
	<?php get_template_part('template-parts/single-webinary/section', 'about'); ?>
	<?php get_template_part('template-parts/single-webinary/section', 'audience'); ?>
	<?php get_template_part('template-parts/single-webinary/section', 'speaker'); ?>
	<?php get_template_part('template-parts/single-webinary/section', 'schedule'); ?>
	<?php get_template_part('template-parts/single-webinary/section', 'signup'); ?>
</div>
