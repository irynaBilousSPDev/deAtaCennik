<?php
/**
 * Szkolenia — zapisy + CF7.
 *
 * @package akademiata
 */

$f           = get_query_var('szk_fields', array());
$form_output = (string) get_query_var('szk_form_output', '');
$badge       = trim((string) ($f['szk_signup_badge'] ?? ''));
$title       = trim((string) ($f['szk_signup_title'] ?? ''));
$text        = (string) ($f['szk_signup_text'] ?? '');
$note        = trim((string) ($f['szk_signup_form_note'] ?? ''));

if ($title === '' && $form_output === '') {
	return;
}
?>

<section class="szk-signup" id="zapisy">
	<div class="container szk-signup__inner">
		<div>
			<?php if ($badge !== '') : ?>
				<span class="szk-tag"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="szk-signup__title"><?php echo esc_html($title); ?></h2>
			<?php endif; ?>
			<?php if (trim(wp_strip_all_tags($text)) !== '') : ?>
				<div class="szk-signup__text szk-check-list">
					<?php echo akademiata_szk_richtext($text); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<div>
			<?php if ($form_output !== '') : ?>
				<div class="szk-card szk-signup__form">
					<?php echo $form_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ($note !== '') : ?>
						<p class="szk-signup__form-note"><?php echo esc_html($note); ?></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="szk-card szk-signup__form szk-signup__form--empty">
					<p><?php esc_html_e('Wybierz formularz Contact Form 7 w polach szkolenia.', 'akademiata'); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
