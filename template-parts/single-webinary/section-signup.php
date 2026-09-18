<?php
/**
 * Webinary — zapisy + CF7.
 *
 * @package akademiata
 */

$f           = get_query_var('web_fields', array());
$form_output = (string) get_query_var('web_form_output', '');
$badge       = trim((string) ($f['web_signup_badge'] ?? ''));
$title       = trim((string) ($f['web_signup_title'] ?? ''));
$text        = (string) ($f['web_signup_text'] ?? '');
$note        = trim((string) ($f['web_signup_form_note'] ?? ''));

if ($title === '' && $form_output === '') {
	return;
}
?>

<section class="web-signup" id="zapisy">
	<div class="container web-signup__inner">
		<div>
			<?php if ($badge !== '') : ?>
				<span class="web-tag"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="web-signup__title"><?php echo esc_html($title); ?></h2>
			<?php endif; ?>
			<?php if (trim(wp_strip_all_tags($text)) !== '') : ?>
				<div class="web-signup__text web-check-list">
					<?php echo akademiata_web_richtext($text); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<div>
			<?php if ($form_output !== '') : ?>
				<div class="web-card web-signup__form">
					<?php echo $form_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ($note !== '') : ?>
						<p class="web-signup__form-note"><?php echo esc_html($note); ?></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="web-card web-signup__form web-signup__form--empty">
					<p><?php esc_html_e('Wybierz formularz Contact Form 7 w polach webinaru.', 'akademiata'); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
