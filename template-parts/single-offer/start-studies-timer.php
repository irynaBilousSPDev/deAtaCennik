<?php
/**
 * Compact start-of-studies countdown — bachelor/master test only.
 */

if (!is_singular(array('bachelor', 'master'))) {
	return;
}

$target_date = '2026-10-01';
$timezone    = wp_timezone();
$target_dt   = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $target_date . ' 00:00:00', $timezone);
$countdown_ts = $target_dt ? $target_dt->getTimestamp() : 0;

if ($countdown_ts <= 0) {
	return;
}

$now     = new DateTimeImmutable('now', $timezone);
$seconds = max(0, $countdown_ts - $now->getTimestamp());
$days    = (int) floor($seconds / DAY_IN_SECONDS);
$hours   = (int) floor(($seconds % DAY_IN_SECONDS) / HOUR_IN_SECONDS);
$minutes = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);
$secs    = (int) ($seconds % MINUTE_IN_SECONDS);

$pad = static function ($n) {
	return str_pad((string) max(0, (int) $n), 2, '0', STR_PAD_LEFT);
};

$phrases = array(
	'start studiów',
	'pierwszego października',
);
?>
<div
	class="offer-start-timer"
	data-countdown-ts="<?php echo esc_attr((string) $countdown_ts); ?>"
	data-phrases="<?php echo esc_attr(wp_json_encode($phrases)); ?>"
	aria-label="<?php echo esc_attr__('Odliczanie do startu studiów', 'akademiata'); ?>"
>
	<p class="offer-start-timer__type" aria-live="polite">
		<span class="offer-start-timer__type-text"></span><span class="offer-start-timer__caret" aria-hidden="true"></span>
	</p>
	<div class="offer-start-timer__pill" role="timer">
		<span class="offer-start-timer__value" data-unit="days"><?php echo esc_html($pad($days)); ?></span>
		<span class="offer-start-timer__sep" aria-hidden="true">:</span>
		<span class="offer-start-timer__value" data-unit="hours"><?php echo esc_html($pad($hours)); ?></span>
		<span class="offer-start-timer__sep" aria-hidden="true">:</span>
		<span class="offer-start-timer__value" data-unit="minutes"><?php echo esc_html($pad($minutes)); ?></span>
		<span class="offer-start-timer__sep" aria-hidden="true">:</span>
		<span class="offer-start-timer__value" data-unit="seconds"><?php echo esc_html($pad($secs)); ?></span>
	</div>
</div>
