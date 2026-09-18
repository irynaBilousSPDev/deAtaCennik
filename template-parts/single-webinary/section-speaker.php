<?php
/**
 * Webinary — prowadząca.
 *
 * @package akademiata
 */

$f     = get_query_var('web_fields', array());
$badge = trim((string) ($f['web_speaker_badge'] ?? ''));
$name  = trim((string) ($f['web_speaker_name'] ?? ''));
$role  = trim((string) ($f['web_speaker_role'] ?? ''));
$bio   = (string) ($f['web_speaker_bio'] ?? '');
$photo = $f['web_speaker_photo'] ?? null;

if ($name === '' && trim(wp_strip_all_tags($bio)) === '') {
	return;
}
?>

<section class="web-speaker" id="prowadzaca">
	<div class="container web-speaker__inner">
		<div class="web-speaker__photo">
			<?php if (!empty($photo['url'])) : ?>
				<img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?: $name); ?>" loading="lazy">
			<?php else : ?>
				<div class="web-speaker__photo-ph" aria-hidden="true"><?php echo esc_html($name !== '' ? ('[zdjęcie: ' . $name . ']') : '—'); ?></div>
			<?php endif; ?>
		</div>
		<div>
			<?php if ($badge !== '') : ?>
				<span class="web-speaker__badge"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($name !== '') : ?>
				<h2 class="web-speaker__name"><?php echo esc_html($name); ?></h2>
			<?php endif; ?>
			<?php if ($role !== '') : ?>
				<p class="web-speaker__role"><?php echo esc_html($role); ?></p>
			<?php endif; ?>
			<?php if (trim(wp_strip_all_tags($bio)) !== '') : ?>
				<div class="web-speaker__bio">
					<?php echo akademiata_web_richtext($bio); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
