<?php
/**
 * Szkolenia single — o spotkaniu.
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
		<div class="szk-about__left">
			<?php if ($badge !== '') : ?>
				<span class="szk-badge"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h2 class="szk-section-title"><?php echo nl2br(esc_html($title)); ?></h2>
			<?php endif; ?>
		</div>
		<?php if (trim(wp_strip_all_tags($content)) !== '') : ?>
			<div class="szk-about__right szk-prose">
				<?php echo wp_kses_post($content); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
