<?php
/**
 * Template Name: O Uczelni
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/o-uczelni/fields.php';

$acf_fields = akademiata_o_uczelni_fields(get_fields());

$hero = $acf_fields['oucz_hero_section'];
$subnav = $acf_fields['oucz_subnav'];
$kim = $acf_fields['oucz_kim_section'];
$cel = $acf_fields['oucz_cel_section'];
$oferta = $acf_fields['oucz_oferta_section'];
$wspol = $acf_fields['oucz_wspolpraca_section'];
$abs = $acf_fields['oucz_absolwenci_section'];
$hist = $acf_fields['oucz_historia_section'];
$filary = $acf_fields['oucz_filary_section'];
$infra = $acf_fields['oucz_infra_section'];
$closing = $acf_fields['oucz_closing_section'];

/**
 * @param string|null $html
 */
$lp_html = static function ($html) {
	echo akademiata_o_uczelni_kses($html);
};

/**
 * @param string|null $text
 */
$lp_text = static function ($text) {
	if ($text === '' || $text === null) {
		return;
	}
	echo esc_html((string) $text);
};

/**
 * @param mixed  $image
 * @param string $fallback_url
 * @param string $class
 * @param string $alt
 */
$lp_img = static function ($image, $fallback_url, $class, $alt) {
	if (is_array($image) && !empty($image['ID'])) {
		echo wp_get_attachment_image(
			(int) $image['ID'],
			'large',
			false,
			[
				'class' => $class,
				'alt'   => esc_attr($image['alt'] ?: $alt),
				'loading' => 'lazy',
			]
		);
		return;
	}
	$url = akademiata_o_uczelni_image_url($image, $fallback_url);
	if ($url === '') {
		return;
	}
	$class_attr = $class !== '' ? ' class="' . esc_attr($class) . '"' : '';
	printf(
		'<img%s src="%s" alt="%s" loading="lazy">',
		$class_attr,
		esc_url($url),
		esc_attr($alt)
	);
};
?>

<div class="lp-page lp-o-uczelni">

	<div class="hero">
		<div class="wrap">
			<?php if (!empty($hero['crumbs'])) : ?>
				<div class="crumb">
					<?php
					$crumb_count = count($hero['crumbs']);
					foreach ($hero['crumbs'] as $i => $crumb) :
						$label = $crumb['label'] ?? '';
						$url = $crumb['url'] ?? '';
						if ($label === '') {
							continue;
						}
						if ($i > 0) {
							echo '<span class="sep">/</span>';
						}
						if ($url !== '' && $i < $crumb_count - 1) {
							$href = $url === '/' ? home_url('/') : $url;
							printf('<a href="%s">%s</a>', esc_url($href), esc_html($label));
						} else {
							echo esc_html($label);
						}
					endforeach;
					?>
				</div>
			<?php endif; ?>
			<h1>
				<?php $lp_text($hero['title_before'] ?? ''); ?>
				<?php if (!empty($hero['title_accent'])) : ?>
					<span class="accent"><?php $lp_text($hero['title_accent']); ?></span>
				<?php endif; ?>
			</h1>
			<?php if (!empty($hero['lead'])) : ?>
				<p class="lead"><?php $lp_text($hero['lead']); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if (!empty($subnav['links'])) : ?>
		<div class="subnav">
			<div class="wrap">
				<?php foreach ($subnav['links'] as $link) :
					$text = $link['text'] ?? '';
					$anchor = $link['anchor'] ?? '#';
					if ($text === '') {
						continue;
					}
					$class = !empty($link['highlight']) ? ' class="hl"' : '';
					printf('<a href="%s"%s>%s</a>', esc_attr($anchor), $class, esc_html($text));
				endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<section class="block" id="kim-jestesmy">
		<div class="wrap">
			<div class="km-head">
				<div>
					<?php if (!empty($kim['eyebrow'])) : ?>
						<div class="eyebrow"><?php $lp_text($kim['eyebrow']); ?></div>
					<?php endif; ?>
					<?php if (!empty($kim['title'])) : ?>
						<h2 class="title"><?php $lp_text($kim['title']); ?></h2>
					<?php endif; ?>
				</div>
				<?php
				$lp_img(
					$kim['badge_image'] ?? null,
					$kim['badge_url'] ?? '',
					'rank-badge',
					$kim['badge_alt'] ?? ''
				);
				?>
			</div>
			<div class="prose grid2">
				<div><?php $lp_html($kim['col_left'] ?? ''); ?></div>
				<div>
					<?php if (!empty($kim['highlight'])) : ?>
						<div class="highlight"><?php $lp_html($kim['highlight']); ?></div>
					<?php endif; ?>
				</div>
			</div>

			<div class="history-panel">
				<?php if (!empty($kim['history_eyebrow'])) : ?>
					<div class="eyebrow"><?php $lp_text($kim['history_eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($kim['history_title'])) : ?>
					<h2 class="title"><?php $lp_text($kim['history_title']); ?></h2>
				<?php endif; ?>
				<?php if (!empty($kim['history_lede'])) : ?>
					<p class="lede"><?php $lp_text($kim['history_lede']); ?></p>
				<?php endif; ?>
				<?php if (!empty($kim['history_steps'])) : ?>
					<div class="htl">
						<?php foreach ($kim['history_steps'] as $step) : ?>
							<div class="htl-step">
								<div class="htl-badge"><?php $lp_text($step['year'] ?? ''); ?></div>
								<div class="htl-card">
									<?php if (!empty($step['tag'])) : ?>
										<span class="tag"><?php $lp_text($step['tag']); ?></span>
									<?php endif; ?>
									<?php if (!empty($step['title'])) : ?>
										<h3><?php $lp_text($step['title']); ?></h3>
									<?php endif; ?>
									<?php if (!empty($step['text'])) : ?>
										<p><?php $lp_text($step['text']); ?></p>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="logo-section" id="logo-uczelni">
				<div class="logo-text">
					<div class="logo-hero">
						<?php if (!empty($kim['logo_title'])) : ?>
							<h2><?php $lp_text($kim['logo_title']); ?></h2>
						<?php endif; ?>
					</div>
					<?php $lp_html($kim['logo_text'] ?? ''); ?>
				</div>
				<div class="logo-visual">
					<figure>
						<?php
						$lp_img(
							$kim['logo_image'] ?? null,
							$kim['logo_image_url'] ?? '',
							'',
							$kim['logo_image_alt'] ?? ''
						);
						?>
						<?php if (!empty($kim['logo_caption'])) : ?>
							<figcaption><?php $lp_text($kim['logo_caption']); ?></figcaption>
						<?php endif; ?>
					</figure>
				</div>
			</div>
		</div>
	</section>

	<section class="block alt" id="nasz-cel">
		<div class="wrap">
			<?php if (!empty($cel['eyebrow'])) : ?>
				<div class="eyebrow"><?php $lp_text($cel['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($cel['title'])) : ?>
				<h2 class="title"><?php $lp_text($cel['title']); ?></h2>
			<?php endif; ?>
			<div class="prose grid2">
				<div><?php if (!empty($cel['col_left'])) : ?><p><?php $lp_text($cel['col_left']); ?></p><?php endif; ?></div>
				<div><?php if (!empty($cel['col_right'])) : ?><p><?php $lp_text($cel['col_right']); ?></p><?php endif; ?></div>
			</div>
		</div>
	</section>

	<section class="block" id="oferta">
		<div class="wrap">
			<?php if (!empty($oferta['eyebrow'])) : ?>
				<div class="eyebrow"><?php $lp_text($oferta['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($oferta['title'])) : ?>
				<h2 class="title"><?php $lp_text($oferta['title']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($oferta['lede'])) : ?>
				<p class="lede"><?php $lp_text($oferta['lede']); ?></p>
			<?php endif; ?>
			<?php if (!empty($oferta['campuses'])) : ?>
				<div class="campus-grid">
					<?php foreach ($oferta['campuses'] as $campus) :
						$variant = ($campus['variant'] ?? '') === 'wr' ? 'wr' : 'wa';
						?>
						<div class="campus <?php echo esc_attr($variant); ?>">
							<div class="cap">
								<span class="dot"></span>
								<h3><?php $lp_text($campus['title'] ?? ''); ?></h3>
								<?php if (!empty($campus['tag'])) : ?>
									<span class="tag"><?php $lp_text($campus['tag']); ?></span>
								<?php endif; ?>
							</div>
							<?php if (!empty($campus['programs'])) : ?>
								<ul>
									<?php foreach ($campus['programs'] as $prog) : ?>
										<li>
											<a href="<?php echo esc_url($prog['url'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer"><?php $lp_text($prog['name'] ?? ''); ?></a>
											<span><?php $lp_text($prog['meta'] ?? ''); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if (!empty($oferta['note'])) : ?>
				<div class="highlight" style="margin-top:32px">
					<p><?php $lp_text($oferta['note']); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="block alt" id="wspolpraca">
		<div class="wrap">
			<?php if (!empty($wspol['eyebrow'])) : ?>
				<div class="eyebrow"><?php $lp_text($wspol['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($wspol['title'])) : ?>
				<h2 class="title"><?php $lp_text($wspol['title']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($wspol['lede'])) : ?>
				<p class="lede"><?php $lp_html($wspol['lede']); ?></p>
			<?php endif; ?>
			<?php if (!empty($wspol['columns'])) : ?>
				<div class="coop-grid">
					<?php foreach ($wspol['columns'] as $col) : ?>
						<div class="coop-col">
							<?php if (!empty($col['title'])) : ?>
								<h4><?php $lp_text($col['title']); ?></h4>
							<?php endif; ?>
							<?php if (!empty($col['items'])) : ?>
								<ul>
									<?php foreach ($col['items'] as $item) : ?>
										<li><?php $lp_text($item['text'] ?? ''); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if (!empty($wspol['partners_eyebrow'])) : ?>
				<div class="eyebrow" style="margin-top:46px"><?php $lp_text($wspol['partners_eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($wspol['partners'])) : ?>
				<div class="logos-row">
					<?php foreach ($wspol['partners'] as $partner) :
						$url = akademiata_o_uczelni_image_url($partner['image'] ?? null, $partner['url'] ?? '');
						if ($url === '') {
							continue;
						}
						?>
						<div class="logo-chip">
							<img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($partner['alt'] ?? ''); ?>" loading="lazy">
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="block" id="absolwenci">
		<div class="wrap">
			<div class="km-head">
				<div>
					<?php if (!empty($abs['eyebrow'])) : ?>
						<div class="eyebrow"><?php $lp_text($abs['eyebrow']); ?></div>
					<?php endif; ?>
					<?php if (!empty($abs['title'])) : ?>
						<h2 class="title"><?php $lp_text($abs['title']); ?></h2>
					<?php endif; ?>
				</div>
				<?php
				$lp_img(
					$abs['badge_image'] ?? null,
					$abs['badge_url'] ?? '',
					'ela-badge',
					$abs['badge_alt'] ?? ''
				);
				?>
			</div>
			<?php if (!empty($abs['lede'])) : ?>
				<p class="lede"><?php $lp_html($abs['lede']); ?></p>
			<?php endif; ?>
			<?php if (!empty($abs['ranks'])) : ?>
				<div class="rank-grid">
					<?php foreach ($abs['ranks'] as $rank) :
						$variant = $rank['variant'] ?? '';
						$card_class = 'rank-card' . ($variant !== '' ? ' ' . $variant : '');
						?>
						<div class="<?php echo esc_attr($card_class); ?>">
							<span class="place"><?php $lp_text($rank['place'] ?? ''); ?></span>
							<?php if (!empty($rank['items'])) : ?>
								<ul>
									<?php foreach ($rank['items'] as $item) : ?>
										<li>
											<b><?php $lp_text($item['name'] ?? ''); ?></b>
											<?php if (!empty($item['meta'])) : ?>
												<em><?php $lp_text($item['meta']); ?></em>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if (!empty($abs['note'])) : ?>
				<div class="highlight" style="margin-top:32px">
					<p><?php $lp_text($abs['note']); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="block alt" id="historia">
		<div class="wrap">
			<?php if (!empty($hist['eyebrow'])) : ?>
				<div class="eyebrow"><?php $lp_text($hist['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($hist['title'])) : ?>
				<h2 class="title"><?php $lp_text($hist['title']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($hist['lede'])) : ?>
				<p class="lede"><?php $lp_text($hist['lede']); ?></p>
			<?php endif; ?>
			<?php if (!empty($hist['steps'])) : ?>
				<div class="tl">
					<?php foreach ($hist['steps'] as $step) : ?>
						<div class="tl-item">
							<div class="tl-dot"></div>
							<div class="tl-year">
								<?php $lp_text($step['year'] ?? ''); ?>
								<?php if (!empty($step['stage'])) : ?>
									<small><?php $lp_text($step['stage']); ?></small>
								<?php endif; ?>
							</div>
							<div class="tl-card">
								<div class="tl-body">
									<?php
									$lp_img(
										$step['logo'] ?? null,
										$step['logo_url'] ?? '',
										'tl-logo',
										$step['logo_alt'] ?? ''
									);
									?>
									<?php if (!empty($step['kicker'])) : ?>
										<div class="kicker"><?php $lp_text($step['kicker']); ?></div>
									<?php endif; ?>
									<?php if (!empty($step['title'])) : ?>
										<h3><?php $lp_text($step['title']); ?></h3>
									<?php endif; ?>
									<?php $lp_html($step['text'] ?? ''); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if (!empty($hist['note_head']) || !empty($hist['note_text'])) : ?>
				<div class="ata-note">
					<?php if (!empty($hist['note_head'])) : ?>
						<div class="ata-note-head"><?php $lp_text($hist['note_head']); ?></div>
					<?php endif; ?>
					<?php if (!empty($hist['note_text'])) : ?>
						<p><?php $lp_text($hist['note_text']); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="block" id="filary">
		<div class="wrap">
			<?php if (!empty($filary['eyebrow'])) : ?>
				<div class="eyebrow"><?php $lp_text($filary['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($filary['title'])) : ?>
				<h2 class="title" style="font-size:32px"><?php $lp_text($filary['title']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($filary['pillars'])) : ?>
				<div class="pillars">
					<?php foreach ($filary['pillars'] as $pillar) :
						$pclass = 'pillar ' . sanitize_html_class($pillar['variant'] ?? 'p1');
						?>
						<div class="<?php echo esc_attr($pclass); ?>">
							<span class="num"><?php $lp_text($pillar['num'] ?? ''); ?></span>
							<h3><?php $lp_text($pillar['title'] ?? ''); ?></h3>
							<p><?php $lp_text($pillar['text'] ?? ''); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="block alt" id="infrastruktura">
		<div class="wrap">
			<?php if (!empty($infra['eyebrow'])) : ?>
				<div class="eyebrow"><?php $lp_text($infra['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($infra['title'])) : ?>
				<h2 class="title"><?php $lp_text($infra['title']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($infra['lede'])) : ?>
				<p class="lede"><?php $lp_text($infra['lede']); ?></p>
			<?php endif; ?>
			<?php if (!empty($infra['buildings'])) : ?>
				<div class="infra-list">
					<?php foreach ($infra['buildings'] as $i => $building) :
						$num = $i + 1;
						$open = !empty($building['open']);
						?>
						<details class="infra-item"<?php echo $open ? ' open' : ''; ?>>
							<summary>
								<span class="infra-num"><?php echo (int) $num; ?></span>
								<span class="infra-head">
									<span class="infra-name"><?php $lp_text($building['name'] ?? ''); ?></span>
									<span class="infra-loc"><?php $lp_text($building['loc'] ?? ''); ?></span>
								</span>
								<span class="infra-chev">+</span>
							</summary>
							<div class="infra-content">
								<?php if (!empty($building['text'])) : ?>
									<p><?php $lp_text($building['text']); ?></p>
								<?php endif; ?>
								<?php if (!empty($building['facts'])) : ?>
									<ul class="infra-facts">
										<?php foreach ($building['facts'] as $fact) : ?>
											<li><?php $lp_text($fact['text'] ?? ''); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<?php if (!empty($building['gallery'])) : ?>
									<div class="infra-gallery">
										<?php foreach ($building['gallery'] as $img) :
											$url = akademiata_o_uczelni_image_url($img['image'] ?? null, $img['url'] ?? '');
											if ($url === '') {
												continue;
											}
											?>
											<img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($img['alt'] ?? ''); ?>" loading="lazy">
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<div class="closing">
		<div class="wrap">
			<h2>
				<?php $lp_text($closing['title_before'] ?? ''); ?>
				<?php if (!empty($closing['title_accent'])) : ?>
					<span class="accent"><?php $lp_text($closing['title_accent']); ?></span>
				<?php endif; ?>
				<?php if (!empty($closing['title_after'])) : ?>
					<?php echo ' '; $lp_text($closing['title_after']); ?>
				<?php endif; ?>
			</h2>
			<?php if (!empty($closing['text'])) : ?>
				<p><?php $lp_text($closing['text']); ?></p>
			<?php endif; ?>
			<?php if (!empty($closing['cta_primary_text'])) : ?>
				<a href="<?php echo esc_url($closing['cta_primary_url'] ?? '#'); ?>" class="btn"><?php $lp_text($closing['cta_primary_text']); ?></a>
			<?php endif; ?>
			<?php if (!empty($closing['cta_secondary_text'])) : ?>
				<a href="<?php echo esc_url($closing['cta_secondary_url'] ?? '#'); ?>" class="btn ghost"><?php $lp_text($closing['cta_secondary_text']); ?></a>
			<?php endif; ?>
		</div>
	</div>

</div>

<?php
get_footer();
