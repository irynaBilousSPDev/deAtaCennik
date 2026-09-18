<?php
/**
 * Szkolenia — harmonogram.
 *
 * @package akademiata
 */

$f     = get_query_var('szk_fields', array());
$badge = trim((string) ($f['szk_schedule_badge'] ?? ''));
$title = trim((string) ($f['szk_schedule_title'] ?? ''));
$items = is_array($f['szk_schedule_items'] ?? null) ? $f['szk_schedule_items'] : array();

if ($title === '' && $items === array()) {
	return;
}
?>

<section class="szk-schedule" id="harmonogram">
	<div class="container">
		<?php if ($badge !== '') : ?>
			<span class="szk-tag"><?php echo esc_html($badge); ?></span>
		<?php endif; ?>
		<?php if ($title !== '') : ?>
			<h2 class="szk-schedule__title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>

		<?php if ($items !== array()) : ?>
			<div class="szk-schedule__list">
				<?php foreach ($items as $item) :
					$time    = trim((string) ($item['time'] ?? ''));
					$i_title = trim((string) ($item['title'] ?? ''));
					$i_text  = (string) ($item['text'] ?? '');
					if ($time === '' && $i_title === '') {
						continue;
					}
					?>
					<div class="szk-schedule__item">
						<div class="szk-schedule__time"><?php echo esc_html($time); ?></div>
						<div>
							<?php if ($i_title !== '') : ?>
								<div class="szk-schedule__item-title"><?php echo esc_html($i_title); ?></div>
							<?php endif; ?>
							<?php if (trim(wp_strip_all_tags($i_text)) !== '') : ?>
								<div class="szk-schedule__item-text"><?php echo akademiata_szk_richtext($i_text); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
