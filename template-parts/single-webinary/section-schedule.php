<?php
/**
 * Webinary — harmonogram.
 *
 * @package akademiata
 */

$f     = get_query_var('web_fields', array());
$badge = trim((string) ($f['web_schedule_badge'] ?? ''));
$title = trim((string) ($f['web_schedule_title'] ?? ''));
$items = is_array($f['web_schedule_items'] ?? null) ? $f['web_schedule_items'] : array();

if ($title === '' && $items === array()) {
	return;
}
?>

<section class="web-schedule" id="harmonogram">
	<div class="container">
		<?php if ($badge !== '') : ?>
			<span class="web-tag"><?php echo esc_html($badge); ?></span>
		<?php endif; ?>
		<?php if ($title !== '') : ?>
			<h2 class="web-schedule__title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>

		<?php if ($items !== array()) : ?>
			<div class="web-schedule__list">
				<?php foreach ($items as $item) :
					$time    = trim((string) ($item['time'] ?? ''));
					$i_title = trim((string) ($item['title'] ?? ''));
					$i_text  = (string) ($item['text'] ?? '');
					if ($time === '' && $i_title === '') {
						continue;
					}
					?>
					<div class="web-schedule__item">
						<div class="web-schedule__time"><?php echo esc_html($time); ?></div>
						<div>
							<?php if ($i_title !== '') : ?>
								<div class="web-schedule__item-title"><?php echo esc_html($i_title); ?></div>
							<?php endif; ?>
							<?php if (trim(wp_strip_all_tags($i_text)) !== '') : ?>
								<div class="web-schedule__item-text"><?php echo akademiata_web_richtext($i_text); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
