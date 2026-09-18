<?php
/**
 * Szkolenia single — dla kogo (cards).
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
?>

<section class="szk-audience" id="dla-kogo">
	<div class="container">
		<?php if ($badge !== '') : ?>
			<span class="szk-badge"><?php echo esc_html($badge); ?></span>
		<?php endif; ?>
		<?php if ($title !== '') : ?>
			<h2 class="szk-section-title szk-audience__title"><?php echo nl2br(esc_html($title)); ?></h2>
		<?php endif; ?>

		<?php if ($cards !== array()) : ?>
			<div class="szk-audience__grid">
				<?php foreach ($cards as $card) :
					$c_title = trim((string) ($card['title'] ?? ''));
					$c_text  = trim((string) ($card['text'] ?? ''));
					$icon    = $card['icon'] ?? null;
					if ($c_title === '' && $c_text === '') {
						continue;
					}
					?>
					<article class="szk-audience-card">
						<?php if (!empty($icon['url'])) : ?>
							<img class="szk-audience-card__icon" src="<?php echo esc_url($icon['url']); ?>" alt="" width="40" height="40" loading="lazy">
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
