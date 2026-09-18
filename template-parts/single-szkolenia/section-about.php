<?php
/**
 * Szkolenia — o spotkaniu.
 *
 * @package akademiata
 */

$f       = get_query_var('szk_fields', array());
$badge   = trim((string) ($f['szk_about_badge'] ?? ''));
$title   = trim((string) ($f['szk_about_title'] ?? ''));
$content = (string) ($f['szk_about_content'] ?? '');

if ($title === '' && trim(wp_strip_all_tags($content)) === '') {
	return;
}
?>

<section class="szk-about" id="o-spotkaniu">
	<div class="container szk-about__inner">
		<div>
			<?php if ($badge !== '') : ?>
				<span class="szk-tag"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="szk-about__title"><?php echo nl2br(esc_html($title)); ?></h2>
			<?php endif; ?>
		</div>
		<?php if (trim(wp_strip_all_tags($content)) !== '') : ?>
			<div class="szk-about__content szk-check-list">
				<?php echo akademiata_szk_richtext($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
</section>
