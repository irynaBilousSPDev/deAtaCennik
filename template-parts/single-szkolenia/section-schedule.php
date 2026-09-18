<?php
/**
 * Szkolenia single — harmonogram.
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
			<span class="szk-badge"><?php echo esc_html($badge); ?></span>
		<?php endif; ?>
		<?php if ($title !== '') : ?>
			<h2 class="szk-section-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>

		<?php if ($items !== array()) : ?>
			<ol class="szk-schedule__list">
				<?php foreach ($items as $item) :
					$time  = trim((string) ($item['time'] ?? ''));
					$i_title = trim((string) ($item['title'] ?? ''));
					$i_text  = trim((string) ($item['text'] ?? ''));
					if ($time === '' && $i_title === '') {
						continue;
					}
					?>
					<li class="szk-schedule__item">
						<div class="szk-schedule__time"><?php echo esc_html($time); ?></div>
						<div class="szk-schedule__body">
							<?php if ($i_title !== '') : ?>
								<strong><?php echo esc_html($i_title); ?></strong>
							<?php endif; ?>
							<?php if ($i_text !== '') : ?>
								<p><?php echo esc_html($i_text); ?></p>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
</section>
