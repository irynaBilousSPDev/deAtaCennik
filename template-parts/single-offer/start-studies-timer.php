<?php
/**
 * Compact start-of-studies countdown — bachelor/master.
 */

if (!function_exists('akademiata_offer_start_timer_should_show') || !akademiata_offer_start_timer_should_show()) {
	return;
}

$countdown_ts = akademiata_offer_start_timer_countdown_ts();
if ($countdown_ts <= 0) {
	return;
}

$pairs = akademiata_offer_start_timer_get_pairs_for_display();
if (empty($pairs)) {
	return;
}

$timezone = wp_timezone();
$now      = new DateTimeImmutable('now', $timezone);
$seconds  = max(0, $countdown_ts - $now->getTimestamp());
$days     = (int) floor($seconds / DAY_IN_SECONDS);
$hours    = (int) floor(($seconds % DAY_IN_SECONDS) / HOUR_IN_SECONDS);
$minutes  = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);
$secs     = (int) ($seconds % MINUTE_IN_SECONDS);

$pad = static function ($n) {
	return str_pad((string) max(0, (int) $n), 2, '0', STR_PAD_LEFT);
};
?>
<div
	class="offer-start-timer"
	data-countdown-ts="<?php echo esc_attr((string) $countdown_ts); ?>"
	data-pair-count="<?php echo esc_attr((string) count($pairs)); ?>"
	aria-label="<?php echo esc_attr__('Odliczanie do startu studiów', 'akademiata'); ?>"
>
	<div class="offer-start-timer__stack">
		<div class="offer-start-timer__line offer-start-timer__line--top" aria-hidden="true">
			<div class="offer-start-timer__reel" data-reel="top">
				<?php foreach ($pairs as $pair) : ?>
					<span class="offer-start-timer__word"><?php echo esc_html($pair[0]); ?></span>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="offer-start-timer__pill" role="timer">
			<span class="offer-start-timer__value" data-unit="days"><?php echo esc_html($pad($days)); ?></span>
			<span class="offer-start-timer__sep" aria-hidden="true">:</span>
			<span class="offer-start-timer__value" data-unit="hours"><?php echo esc_html($pad($hours)); ?></span>
			<span class="offer-start-timer__sep" aria-hidden="true">:</span>
			<span class="offer-start-timer__value" data-unit="minutes"><?php echo esc_html($pad($minutes)); ?></span>
			<span class="offer-start-timer__sep" aria-hidden="true">:</span>
			<span class="offer-start-timer__value" data-unit="seconds"><?php echo esc_html($pad($secs)); ?></span>
		</div>

		<div class="offer-start-timer__line offer-start-timer__line--bottom" aria-hidden="true">
			<div class="offer-start-timer__reel" data-reel="bottom">
				<?php foreach ($pairs as $pair) : ?>
					<span class="offer-start-timer__word"><?php echo esc_html($pair[1]); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
