<?php
/**
 * Szkolenia single — zapisy + CF7.
 *
 * @package akademiata
 */

$f           = get_query_var('szk_fields', array());
$form_output = (string) get_query_var('szk_form_output', '');
$badge       = trim((string) ($f['szk_signup_badge'] ?? ''));
$title       = trim((string) ($f['szk_signup_title'] ?? ''));
$text        = trim((string) ($f['szk_signup_text'] ?? ''));
$note        = trim((string) ($f['szk_signup_form_note'] ?? ''));
$perks       = is_array($f['szk_signup_perks'] ?? null) ? $f['szk_signup_perks'] : array();

if ($title === '' && $form_output === '') {
	return;
}
?>

<section class="szk-signup" id="zapisy">
	<div class="container szk-signup__inner">
		<div class="szk-signup__copy">
			<?php if ($badge !== '') : ?>
				<span class="szk-badge"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="szk-section-title"><?php echo esc_html($title); ?></h2>
			<?php endif; ?>
			<?php if ($text !== '') : ?>
				<p class="szk-signup__text"><?php echo esc_html($text); ?></p>
			<?php endif; ?>
			<?php if ($perks !== array()) : ?>
				<ul class="szk-signup__perks">
					<?php foreach ($perks as $perk) :
						$p = trim((string) ($perk['text'] ?? ''));
						if ($p === '') {
							continue;
						}
						?>
						<li>
							<span class="szk-signup__check" aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="m8 12.5 2.5 2.5 5.5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<?php echo esc_html($p); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="szk-signup__form-wrap">
			<?php if ($form_output !== '') : ?>
				<div class="szk-signup__form">
					<?php echo $form_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — CF7 HTML ?>
				</div>
				<?php if ($note !== '') : ?>
					<p class="szk-signup__form-note"><?php echo esc_html($note); ?></p>
				<?php endif; ?>
			<?php else : ?>
				<div class="szk-signup__form szk-signup__form--empty">
					<p><?php esc_html_e('Wybierz formularz Contact Form 7 w polach szkolenia.', 'akademiata'); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
