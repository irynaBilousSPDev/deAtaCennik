<?php
/**
 * Szkolenia — dla kogo.
 *
 * @package akademiata
 */

$f     = get_query_var('szk_fields', array());
$badge = trim((string) ($f['szk_audience_badge'] ?? ''));
$title = trim((string) ($f['szk_audience_title'] ?? ''));
$cards = is_array($f['szk_audience_cards'] ?? null) ? $f['szk_audience_cards'] : array();

if ($title === '' && $cards === array()) {
	return;
}

$fallback_icons = array(
	'<svg class="szk-audience-card__svg" width="26" height="26" viewBox="0 0 26 26" fill="none" aria-hidden="true"><path d="M4 21L13 4L22 21H4Z" stroke="#2F4A3C" stroke-width="1.5" stroke-linejoin="round"></path><path d="M9 21L13 12L17 21" stroke="#2F4A3C" stroke-width="1.5" stroke-linejoin="round"></path></svg>',
	'<svg class="szk-audience-card__svg" width="26" height="26" viewBox="0 0 26 26" fill="none" aria-hidden="true"><rect x="4" y="9" width="18" height="12" rx="1.5" stroke="#2F4A3C" stroke-width="1.5"></rect><path d="M9 9V6a4 4 0 018 0v3" stroke="#2F4A3C" stroke-width="1.5"></path></svg>',
	'<svg class="szk-audience-card__svg" width="26" height="26" viewBox="0 0 26 26" fill="none" aria-hidden="true"><path d="M13 22C13 22 5 17.5 5 10.5C5 6.9 7.9 4 11.5 4C12.5 4 13 4.7 13 4.7C13 4.7 13.5 4 14.5 4C18.1 4 21 6.9 21 10.5C21 17.5 13 22 13 22Z" stroke="#2F4A3C" stroke-width="1.5" stroke-linejoin="round"></path></svg>',
);
?>

<section class="szk-audience" id="dla-kogo">
	<div class="container">
		<?php if ($badge !== '') : ?>
			<span class="szk-tag"><?php echo esc_html($badge); ?></span>
		<?php endif; ?>
		<?php if ($title !== '') : ?>
			<h2 class="szk-audience__title"><?php echo nl2br(esc_html($title)); ?></h2>
		<?php endif; ?>

		<?php if ($cards !== array()) : ?>
			<div class="szk-audience__grid">
				<?php foreach ($cards as $i => $card) :
					$c_title = trim((string) ($card['title'] ?? ''));
					$c_text  = trim((string) ($card['text'] ?? ''));
					$icon    = $card['icon'] ?? null;
					if ($c_title === '' && $c_text === '') {
						continue;
					}
					?>
					<article class="szk-card szk-audience-card">
						<?php if (!empty($icon['url'])) : ?>
							<img class="szk-audience-card__icon" src="<?php echo esc_url($icon['url']); ?>" alt="" width="26" height="26" loading="lazy">
						<?php else : ?>
							<?php echo $fallback_icons[ $i % count($fallback_icons) ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
						<?php if ($c_title !== '') : ?>
							<h3><?php echo esc_html($c_title); ?></h3>
						<?php endif; ?>
						<?php if ($c_text !== '') : ?>
							<p><?php echo esc_html($c_text); ?></p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
