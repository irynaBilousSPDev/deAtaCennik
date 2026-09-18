<?php
/**
 * Szkolenia single — hero + details card.
 *
 * @package akademiata
 */

$f     = get_query_var('szk_fields', array());
$title = (string) get_query_var('szk_hero_title', get_the_title());

$badge     = trim((string) ($f['szk_hero_badge'] ?? ''));
$subtitle  = trim((string) ($f['szk_hero_subtitle'] ?? ''));
$text      = trim((string) ($f['szk_hero_text'] ?? ''));
$cta       = trim((string) ($f['szk_hero_cta_label'] ?? ''));
$cta_note  = trim((string) ($f['szk_hero_cta_note'] ?? ''));
$det_label = trim((string) ($f['szk_details_label'] ?? ''));
$date      = trim((string) ($f['szk_details_date'] ?? ''));
$time      = trim((string) ($f['szk_details_time'] ?? ''));
$time_note = trim((string) ($f['szk_details_time_note'] ?? ''));
$place     = trim((string) ($f['szk_details_place'] ?? ''));
$sp_line   = trim((string) ($f['szk_details_speaker_line'] ?? ''));
$sp_sub    = trim((string) ($f['szk_details_speaker_sub'] ?? ''));
$sp_photo  = $f['szk_details_speaker_photo'] ?? null;

$has_details = ($date !== '' || $time !== '' || $place !== '' || $sp_line !== '');
?>

<section class="szk-hero" id="szk-top">
	<div class="container szk-hero__inner">
		<div class="szk-hero__main">
			<?php if ($badge !== '') : ?>
				<span class="szk-badge"><?php echo esc_html($badge); ?></span>
			<?php endif; ?>
			<?php if ($title !== '') : ?>
				<h1 class="szk-hero__title"><?php echo esc_html($title); ?></h1>
			<?php endif; ?>
			<?php if ($subtitle !== '') : ?>
				<p class="szk-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
			<?php endif; ?>
			<?php if ($text !== '') : ?>
				<p class="szk-hero__text"><?php echo esc_html($text); ?></p>
			<?php endif; ?>
			<?php if ($cta !== '') : ?>
				<div class="szk-hero__cta-row">
					<a class="szk-btn" href="#zapisy"><?php echo esc_html($cta); ?></a>
					<?php if ($cta_note !== '') : ?>
						<span class="szk-hero__cta-note"><?php echo esc_html($cta_note); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ($has_details) : ?>
			<aside class="szk-details-card" aria-label="<?php echo esc_attr($det_label ?: __('Szczegóły spotkania', 'akademiata')); ?>">
				<?php if ($det_label !== '') : ?>
					<div class="szk-details-card__label"><?php echo esc_html($det_label); ?></div>
				<?php endif; ?>
				<ul class="szk-details-card__list">
					<?php if ($date !== '') : ?>
						<li>
							<span class="szk-details-card__ico" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M3 9h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
							</span>
							<span><?php echo esc_html($date); ?></span>
						</li>
					<?php endif; ?>
					<?php if ($time !== '') : ?>
						<li>
							<span class="szk-details-card__ico" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6"/><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
							</span>
							<span>
								<?php echo esc_html($time); ?>
								<?php if ($time_note !== '') : ?>
									<small><?php echo esc_html($time_note); ?></small>
								<?php endif; ?>
							</span>
						</li>
					<?php endif; ?>
					<?php if ($place !== '') : ?>
						<li>
							<span class="szk-details-card__ico" aria-hidden="true">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 6.5h16v11H4v-11Z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php echo esc_html($place); ?></span>
						</li>
					<?php endif; ?>
				</ul>
				<?php if ($sp_line !== '' || !empty($sp_photo['url'])) : ?>
					<div class="szk-details-card__speaker">
						<?php if (!empty($sp_photo['url'])) : ?>
							<img src="<?php echo esc_url($sp_photo['url']); ?>" alt="<?php echo esc_attr($sp_photo['alt'] ?? ''); ?>" width="48" height="48" loading="lazy">
						<?php endif; ?>
						<div>
							<?php if ($sp_line !== '') : ?>
								<strong><?php echo esc_html($sp_line); ?></strong>
							<?php endif; ?>
							<?php if ($sp_sub !== '') : ?>
								<span><?php echo esc_html($sp_sub); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</aside>
		<?php endif; ?>
	</div>
</section>
