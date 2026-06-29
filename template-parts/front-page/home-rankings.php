<?php

require_once get_template_directory() . '/configure/front-page-defaults/home-rankings/fields.php';

$acf_group = get_query_var('home_rankings');
$section = akademiata_home_rankings_fields(is_array($acf_group) ? $acf_group : null);

if (
    ($section['title'] ?? '') === ''
    && ($section['lead'] ?? '') === ''
    && empty($section['perspektywy']['stats'])
    && empty($section['ela']['stats'])
) {
    return;
}

$film = $section['film'] ?? [];
$persp = $section['perspektywy'] ?? [];
$ela = $section['ela'] ?? [];
$video_url = akademiata_rankingi_theme_video_url();
$poster = is_array($film['poster'] ?? null) ? $film['poster'] : null;
$persp_badge_fallback = akademiata_get_ranking_perspektywy_badge_image_url('both');
$ela_logo_fallback = get_template_directory_uri() . '/assets/dist/img/ela-logo.svg';

/**
 * @param array<string, mixed>|null $image
 * @param string                    $fallback_url
 * @param string                    $class
 * @param string                    $alt
 */
$render_mark = static function ($image, $fallback_url, $class, $alt) {
    if (is_array($image) && !empty($image['ID'])) {
        echo wp_get_attachment_image(
            (int) $image['ID'],
            'medium',
            false,
            [
                'class' => $class,
                'alt'   => esc_attr($image['alt'] ?: $alt),
            ]
        );
        return;
    }
    if ($fallback_url !== '') {
        printf(
            '<img class="%s" src="%s" alt="%s" loading="lazy">',
            esc_attr($class),
            esc_url($fallback_url),
            esc_attr($alt)
        );
    }
};
?>

<section id="aboutUs" class="home-rank mb-5" aria-labelledby="home-rank-title">
    <div class="home-rank__inner container">
        <?php if (!empty($section['eyebrow'])) : ?>
            <p class="home-rank__eyebrow"><?php echo esc_html($section['eyebrow']); ?></p>
        <?php endif; ?>
        <?php if (!empty($section['title'])) : ?>
            <h2 class="home-rank__title" id="home-rank-title"><?php echo esc_html($section['title']); ?></h2>
        <?php endif; ?>
        <?php if (!empty($section['lead'])) : ?>
            <p class="home-rank__lede"><?php echo esc_html($section['lead']); ?></p>
        <?php endif; ?>

        <div class="home-rank__cols">
            <div class="home-rank__media">
                <?php if (!empty($film['eyebrow'])) : ?>
                    <p class="home-rank__media-eyebrow"><?php echo esc_html($film['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if ($video_url !== '') : ?>
                    <video
                        class="home-rank__video"
                        controls
                        autoplay
                        muted
                        loop
                        playsinline
                        <?php if ($poster && !empty($poster['url'])) : ?>
                            poster="<?php echo esc_url($poster['url']); ?>"
                        <?php endif; ?>
                    >
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    </video>
                <?php elseif ($poster && !empty($poster['url'])) : ?>
                    <img
                        class="home-rank__video home-rank__video--poster"
                        src="<?php echo esc_url($poster['url']); ?>"
                        alt="<?php echo esc_attr($poster['alt'] ?: ($film['title'] ?? __('ATA Mistrzem Świata', 'akademiata'))); ?>"
                        loading="lazy"
                    >
                <?php endif; ?>
                <?php if (!empty($film['title'])) : ?>
                    <p class="home-rank__media-cap"><?php echo esc_html($film['title']); ?></p>
                <?php endif; ?>
                <?php if (!empty($film['subtitle'])) : ?>
                    <p class="home-rank__media-sub"><?php echo esc_html($film['subtitle']); ?></p>
                <?php endif; ?>
            </div>

            <div class="home-rank__stacks">
                <?php if (!empty($persp['stats']) && is_array($persp['stats'])) : ?>
                    <div class="home-rank__block">
                        <div class="home-rank__block-head">
                            <div class="home-rank__block-txt">
                                <?php if (!empty($persp['label'])) : ?>
                                    <b><?php echo esc_html($persp['label']); ?></b>
                                <?php endif; ?>
                                <?php if (!empty($persp['label_sub'])) : ?>
                                    <span><?php echo esc_html($persp['label_sub']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $render_mark(
                                $persp['badge'] ?? null,
                                $persp_badge_fallback,
                                'home-rank__mark home-rank__mark--badge',
                                __('Ranking Uczelni Zawodowych Perspektywy 2026', 'akademiata')
                            );
                            ?>
                        </div>
                        <div class="home-rank__grid">
                            <?php foreach ($persp['stats'] as $stat) :
                                $value = $stat['value'] ?? '';
                                if ($value === '') {
                                    continue;
                                }
                                $suffix = $stat['value_suffix'] ?? '';
                                ?>
                                <div class="home-rank__stat">
                                    <span class="home-rank__stat-num">
                                        <?php echo esc_html($value); ?>
                                        <?php if ($suffix === '•') : ?>
                                            <sup>•</sup>
                                        <?php elseif ($suffix !== '') : ?>
                                            <span class="home-rank__stat-unit"><?php echo esc_html($suffix); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="home-rank__stat-label">
                                        <?php if (!empty($stat['label_bold'])) : ?>
                                            <b><?php echo esc_html($stat['label_bold']); ?></b><br>
                                        <?php endif; ?>
                                        <?php if (!empty($stat['label'])) : ?>
                                            <?php echo esc_html($stat['label']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ela['stats']) && is_array($ela['stats'])) : ?>
                    <div class="home-rank__block">
                        <div class="home-rank__block-head">
                            <div class="home-rank__block-txt">
                                <?php if (!empty($ela['label'])) : ?>
                                    <b><?php echo esc_html($ela['label']); ?></b>
                                <?php endif; ?>
                                <?php if (!empty($ela['label_sub'])) : ?>
                                    <span><?php echo esc_html($ela['label_sub']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $render_mark(
                                $ela['logo'] ?? null,
                                $ela_logo_fallback,
                                'home-rank__mark home-rank__mark--ela',
                                __('ELA — Ekonomiczne Losy Absolwentów', 'akademiata')
                            );
                            ?>
                        </div>
                        <div class="home-rank__grid">
                            <?php foreach ($ela['stats'] as $stat) :
                                $value = $stat['value'] ?? '';
                                if ($value === '') {
                                    continue;
                                }
                                $suffix = $stat['value_suffix'] ?? '';
                                ?>
                                <div class="home-rank__stat">
                                    <span class="home-rank__stat-num">
                                        <?php echo esc_html($value); ?>
                                        <?php if ($suffix === '•') : ?>
                                            <sup>•</sup>
                                        <?php elseif ($suffix !== '') : ?>
                                            <span class="home-rank__stat-unit"><?php echo esc_html($suffix); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="home-rank__stat-label">
                                        <?php if (!empty($stat['label_bold'])) : ?>
                                            <b><?php echo esc_html($stat['label_bold']); ?></b><br>
                                        <?php endif; ?>
                                        <?php if (!empty($stat['label'])) : ?>
                                            <?php echo esc_html($stat['label']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($section['sources']) || !empty($section['cta_text'])) : ?>
            <div class="home-rank__foot">
                <?php if (!empty($section['sources'])) : ?>
                    <p class="home-rank__src"><?php echo esc_html($section['sources']); ?></p>
                <?php endif; ?>
                <?php if (!empty($section['cta_text'])) : ?>
                    <?php
                    $cta_url = !empty($section['cta_url']) ? $section['cta_url'] : home_url('/ata-rankingi/');
                    ?>
                    <a class="home-rank__cta" href="<?php echo esc_url($cta_url); ?>">
                        <?php echo esc_html($section['cta_text']); ?> <span aria-hidden="true">→</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
