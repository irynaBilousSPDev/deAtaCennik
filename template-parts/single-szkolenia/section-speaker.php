<?php
/**
 * Szkolenia single — prowadząca.
 *
 * @package akademiata
 */

$f      = get_query_var('szk_fields', array());
$badge  = trim((string) ($f['szk_speaker_badge'] ?? ''));
$name   = trim((string) ($f['szk_speaker_name'] ?? ''));
$role   = trim((string) ($f['szk_speaker_role'] ?? ''));
$bio    = (string) ($f['szk_speaker_bio'] ?? '');
$photo  = $f['szk_speaker_photo'] ?? null;

if ($name === '' && trim(wp_strip_all_tags($bio)) === '') {
	return;
}
?>

<section class="szk-speaker" id="prowadzaca">
	<div class="container szk-speaker__inner">
		<div class="szk-speaker__photo">
			<?php if (!empty($photo['url'])) : ?>
				<img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?: $name); ?>" loading="lazy">
			<?php else : ?>
				<div class="szk-speaker__photo-ph" aria-hidden="true"><?php echo esc_html($name !== '' ? $name : '—'); ?></div>
			<?php endif; ?>
		</div>
		<div class="szk-speaker__content">
			<?php if ($badge !== '') : ?>
				<span class="szk-badge szk-badge--on-dark"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($name !== '') : ?>
				<h2 class="szk-speaker__name"><?php echo esc_html($name); ?></h2>
			<?php endif; ?>
			<?php if ($role !== '') : ?>
				<p class="szk-speaker__role"><?php echo esc_html($role); ?></p>
			<?php endif; ?>
			<?php if (trim(wp_strip_all_tags($bio)) !== '') : ?>
				<div class="szk-speaker__bio szk-prose">
					<?php echo wp_kses_post($bio); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
