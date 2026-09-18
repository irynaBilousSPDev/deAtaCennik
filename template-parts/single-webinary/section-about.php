<?php
/**
 * Webinary — o spotkaniu.
 *
 * @package akademiata
 */

$f       = get_query_var('web_fields', array());
$badge   = trim((string) ($f['web_about_badge'] ?? ''));
$title   = trim((string) ($f['web_about_title'] ?? ''));
$content = (string) ($f['web_about_content'] ?? '');

if ($title === '' && trim(wp_strip_all_tags($content)) === '') {
	return;
}
?>

<section class="web-about" id="o-spotkaniu">
	<div class="container web-about__inner">
		<div>
			<?php if ($badge !== '') : ?>
				<span class="web-tag"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="web-about__title"><?php echo nl2br(esc_html($title)); ?></h2>
			<?php endif; ?>
		</div>
		<?php if (trim(wp_strip_all_tags($content)) !== '') : ?>
			<div class="web-about__content web-check-list">
				<?php echo akademiata_web_richtext($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
</section>
