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
$text        = trim((string) ($f['szk_signup_text'] ?? ''));
$note        = trim((string) ($f['szk_signup_form_note'] ?? ''));
$perks       = is_array($f['szk_signup_perks'] ?? null) ? $f['szk_signup_perks'] : array();

if ($title === '' && $form_output === '') {
	return;
}
?>

<section class="szk-signup" id="zapisy">
	<div class="szk-wrap szk-signup__inner">
		<div>
			<?php if ($badge !== '') : ?>
				<span class="szk-tag"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="szk-signup__title"><?php echo esc_html($title); ?></h2>
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
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="M3 9L7 13L15 4" stroke="#2F4A3C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
							<span><?php echo esc_html($p); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
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
