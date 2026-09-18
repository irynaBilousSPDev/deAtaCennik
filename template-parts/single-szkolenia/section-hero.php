<?php
/**
 * Szkolenia — hero + details card (Main.dc.html).
 *
 * @package akademiata
 */

$f     = get_query_var('szk_fields', array());
$title = (string) get_query_var('szk_hero_title', get_the_title());

$badge      = trim((string) ($f['szk_hero_badge'] ?? ''));
$subtitle   = trim((string) ($f['szk_hero_subtitle'] ?? ''));
$text       = (string) ($f['szk_hero_text'] ?? '');
$cta        = trim((string) ($f['szk_hero_cta_label'] ?? ''));
$cta_note   = trim((string) ($f['szk_hero_cta_note'] ?? ''));
$det_label  = trim((string) ($f['szk_details_label'] ?? ''));
$date       = trim((string) ($f['szk_details_date'] ?? ''));
$date_note  = trim((string) ($f['szk_details_date_note'] ?? ''));
$time       = trim((string) ($f['szk_details_time'] ?? ''));
$time_note  = trim((string) ($f['szk_details_time_note'] ?? ''));
$place      = trim((string) ($f['szk_details_place'] ?? ''));
$place_note = trim((string) ($f['szk_details_place_note'] ?? ''));
// Speaker mini-card: same photo/name/role as section Prowadząca.
$sp_photo = $f['szk_speaker_photo'] ?? null;
$sp_line  = trim((string) ($f['szk_speaker_name'] ?? ''));
$sp_sub   = trim((string) ($f['szk_speaker_role'] ?? ''));

$has_details = ($date !== '' || $time !== '' || $place !== '' || $sp_line !== '' || !empty($sp_photo['url']));
?>

<section class="szk-hero" id="szk-top">
	<div class="container">
		<div class="szk-hero__grid">
			<div>
				<?php if ($badge !== '') : ?>
					<span class="szk-tag"><?php echo esc_html($badge); ?></span>
				<?php endif; ?>
				<?php if ($title !== '') : ?>
					<h1 class="szk-hero__title szk-serif"><?php echo nl2br(esc_html($title)); ?></h1>
				<?php endif; ?>
				<?php if ($subtitle !== '') : ?>
					<p class="szk-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
				<?php endif; ?>
				<?php if (trim(wp_strip_all_tags($text)) !== '') : ?>
					<div class="szk-hero__text">
						<?php echo akademiata_szk_richtext($text); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
				<?php if ($cta !== '') : ?>
					<div class="szk-hero__cta-row">
						<a class="szk-btn" href="#zapisy">
							<?php echo esc_html($cta); ?>
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="#F7F5EF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
						</a>
						<?php if ($cta_note !== '') : ?>
							<span class="szk-hero__cta-note"><?php echo esc_html($cta_note); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ($has_details) : ?>
				<aside class="szk-card szk-details" aria-label="<?php echo esc_attr($det_label ?: __('Szczegóły spotkania', 'akademiata')); ?>">
					<svg class="szk-details__deco" width="220" height="220" viewBox="0 0 220 220" fill="none" aria-hidden="true">
						<circle cx="110" cy="110" r="90" stroke="#2F4A3C" stroke-width="1" opacity="0.25"></circle>
						<circle cx="110" cy="110" r="60" stroke="#C1693E" stroke-width="1" opacity="0.3"></circle>
						<path d="M40 150 C70 100, 100 180, 140 110 S 200 60, 210 40" stroke="#2F4A3C" stroke-width="1.4" opacity="0.4" fill="none"></path>
					</svg>
					<div class="szk-details__inner">
						<?php if ($det_label !== '') : ?>
							<div class="szk-details__label"><?php echo esc_html($det_label); ?></div>
						<?php endif; ?>

						<?php if ($date !== '') : ?>
							<div class="szk-details__row">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="13" rx="2" stroke="#1F2420" stroke-width="1.4"></rect><path d="M3 8H17M7 2.5V5.5M13 2.5V5.5" stroke="#1F2420" stroke-width="1.4" stroke-linecap="round"></path></svg>
								<div>
									<div class="szk-details__main"><?php echo esc_html($date); ?></div>
									<?php if ($date_note !== '') : ?>
										<div class="szk-details__sub"><?php echo esc_html($date_note); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ($time !== '') : ?>
							<div class="szk-details__row">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.2" stroke="#1F2420" stroke-width="1.4"></circle><path d="M10 6V10L12.6 11.6" stroke="#1F2420" stroke-width="1.4" stroke-linecap="round"></path></svg>
								<div>
									<div class="szk-details__main"><?php echo esc_html($time); ?></div>
									<?php if ($time_note !== '') : ?>
										<div class="szk-details__sub"><?php echo esc_html($time_note); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ($place !== '') : ?>
							<div class="szk-details__row">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="2" stroke="#1F2420" stroke-width="1.4"></rect><path d="M3 6L10 11L17 6" stroke="#1F2420" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								<div>
									<div class="szk-details__main"><?php echo esc_html($place); ?></div>
									<?php if ($place_note !== '') : ?>
										<div class="szk-details__sub"><?php echo esc_html($place_note); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ($sp_line !== '' || !empty($sp_photo['url'])) : ?>
							<div class="szk-details__hr"></div>
							<div class="szk-details__speaker">
								<?php if (!empty($sp_photo['url'])) : ?>
									<img class="szk-details__avatar" src="<?php echo esc_url($sp_photo['url']); ?>" alt="<?php echo esc_attr($sp_photo['alt'] ?? ''); ?>" width="44" height="44" loading="lazy">
								<?php else : ?>
									<div class="szk-details__avatar-ph" aria-hidden="true">zdjęcie</div>
								<?php endif; ?>
								<div>
									<?php if ($sp_line !== '') : ?>
										<div class="szk-details__speaker-name"><?php echo esc_html($sp_line); ?></div>
									<?php endif; ?>
									<?php if ($sp_sub !== '') : ?>
										<div class="szk-details__speaker-sub"><?php echo esc_html($sp_sub); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</aside>
			<?php endif; ?>
		</div>
	</div>
</section>
