<?php
/**
 * Template Name: Rankingi — studia po których masz pracę
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/rankingi/fields.php';

$acf_fields = akademiata_rankingi_fields(get_fields());

/**
 * @param string      $text
 * @param string      $url
 * @param string      $style coral|out|outink
 * @param string|null $icon
 */
$lp_render_btn = static function ($text, $url, $style = 'coral', $icon = '→') {
    if ($text === '' || $text === null) {
        return;
    }
    $class = 'rank-btn rank-btn--' . $style;
    $href = ($url !== '' && $url !== null) ? $url : '#';
    printf(
        '<a href="%s" class="%s">%s%s</a>',
        esc_url($href),
        esc_attr($class),
        esc_html($text),
        $icon !== '' && $icon !== null ? ' <span>' . esc_html($icon) . '</span>' : ''
    );
};

/**
 * @param string      $title
 * @param string|null $highlight
 */
$lp_render_title_mark = static function ($title, $highlight = '') {
    akademiata_rankingi_echo_title_mark($title, $highlight);
};

/**
 * @param string      $title
 * @param string|null $emphasis
 */
$lp_render_title_em = static function ($title, $emphasis = '') {
    akademiata_rankingi_echo_title_em($title, $emphasis);
};

/**
 * @param array<string, mixed>|null $image
 * @param string                    $fallback_url
 * @param string                    $class
 * @param string                    $alt
 */
$lp_render_image = static function ($image, $fallback_url, $class, $alt) {
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
            '<img class="%s" src="%s" alt="%s">',
            esc_attr($class),
            esc_url($fallback_url),
            esc_attr($alt)
        );
    }
};
?>

<div class="lp-page lp-rankingi">

    <?php
    $ann = $acf_fields['rank_ann_section'];
    ?>
    <section class="rank-ann">
        <div class="rank-ann__glow" aria-hidden="true"></div>
        <div class="container rank-wrap">
            <?php if (!empty($ann['number'])) : ?>
                <div class="rank-ann__one">
                    <?php echo esc_html($ann['number']); ?>
                    <?php if (!empty($ann['number_suffix'])) : ?>
                        <b><?php echo esc_html($ann['number_suffix']); ?></b>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="rank-ann__txt">
                <?php if (!empty($ann['eyebrow'])) : ?>
                    <p class="rank-kick"><?php echo esc_html($ann['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if (!empty($ann['title'])) : ?>
                    <h2 class="rank-ann__h"><?php echo akademiata_rankingi_title_html($ann['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($ann['cities']) && is_array($ann['cities'])) : ?>
                    <div class="rank-ann__cities">
                        <?php foreach ($ann['cities'] as $city) :
                            $name = is_array($city) ? ($city['name'] ?? '') : $city;
                            if ($name === '') {
                                continue;
                            }
                            ?>
                            <span>● <?php echo esc_html($name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($ann['text']) || !empty($ann['text_suffix'])) : ?>
                    <p class="rank-ann__sub">
                        <?php if (!empty($ann['text'])) : ?>
                            <b><?php echo esc_html($ann['text']); ?></b>
                        <?php endif; ?>
                        <?php echo esc_html($ann['text_suffix'] ?? ''); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $hero = $acf_fields['rank_hero_section'];
    ?>
    <section class="rank-hero">
        <?php if (!empty($hero['watermark'])) : ?>
            <div class="rank-hero__ghost" aria-hidden="true"><?php echo esc_html($hero['watermark']); ?></div>
        <?php endif; ?>
        <div class="container rank-wrap">
            <?php if (!empty($hero['pill'])) : ?>
                <span class="rank-pill"><i aria-hidden="true"></i> <?php echo esc_html($hero['pill']); ?></span>
            <?php endif; ?>
            <?php if (!empty($hero['title'])) : ?>
                <h1 class="rank-hero__h"><?php $lp_render_title_mark($hero['title'], $hero['title_highlight'] ?? ''); ?></h1>
            <?php endif; ?>
            <?php if (!empty($hero['lead'])) : ?>
                <p class="rank-hero__lede"><?php echo esc_html($hero['lead']); ?></p>
            <?php endif; ?>
            <?php if (!empty($hero['cta_primary_text']) || !empty($hero['cta_secondary_text'])) : ?>
                <div class="rank-hero__act">
                    <?php
                    $lp_render_btn($hero['cta_primary_text'] ?? '', $hero['cta_primary_url'] ?? '', 'coral', '→');
                    $lp_render_btn($hero['cta_secondary_text'] ?? '', $hero['cta_secondary_url'] ?? '', 'out', '↓');
                    ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($hero['chips']) && is_array($hero['chips'])) : ?>
                <div class="rank-chips">
                    <?php foreach ($hero['chips'] as $chip) : ?>
                        <div class="rank-chip">
                            <?php if (!empty($chip['value'])) : ?>
                                <b><?php echo wp_kses($chip['value'], ['sup' => []]); ?></b>
                            <?php endif; ?>
                            <?php if (!empty($chip['label'])) : ?>
                                <span><?php echo esc_html($chip['label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $beats = $acf_fields['rank_beats_section'];
    ?>
    <section class="rank-beats">
        <div class="container rank-wrap">
            <?php if (!empty($beats['title']) || !empty($beats['title_emphasis'])) : ?>
                <h2 class="rank-beats__h rank-reveal">
                    <?php $lp_render_title_em($beats['title'] ?? '', $beats['title_emphasis'] ?? ''); ?>
                </h2>
            <?php endif; ?>
            <?php if (!empty($beats['intro'])) : ?>
                <p class="rank-beats__sub rank-reveal"><?php echo esc_html($beats['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($beats['steps']) && is_array($beats['steps'])) : ?>
                <div class="rank-steps">
                    <?php foreach ($beats['steps'] as $step) : ?>
                        <div class="rank-step rank-reveal">
                            <?php if (!empty($step['step_label'])) : ?>
                                <div class="rank-step__n"><?php echo esc_html($step['step_label']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($step['title'])) : ?>
                                <h3><?php echo akademiata_rankingi_title_html($step['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($step['text'])) : ?>
                                <p><?php echo esc_html($step['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($step['big_stat'])) : ?>
                                <div class="rank-step__big"><?php echo wp_kses($step['big_stat'], ['sup' => []]); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $persp = $acf_fields['rank_perspektywy_section'];
    $persp_id = !empty($persp['section_id']) ? $persp['section_id'] : 'perspektywy';
    $persp_badge_fallback = akademiata_get_ranking_perspektywy_badge_image_url('both');
    ?>
    <section class="rank-sec" id="<?php echo esc_attr($persp_id); ?>">
        <div class="container rank-wrap">
            <div class="rank-sec__head rank-reveal">
                <div>
                    <?php if (!empty($persp['eyebrow'])) : ?>
                        <p class="rank-kick"><?php echo esc_html($persp['eyebrow']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($persp['title']) || !empty($persp['title_emphasis'])) : ?>
                        <h2 class="rank-sec__h">
                            <?php akademiata_rankingi_echo_title_em($persp['title'] ?? '', $persp['title_emphasis'] ?? ''); ?>
                        </h2>
                    <?php endif; ?>
                    <?php if (!empty($persp['intro'])) : ?>
                        <p class="rank-sec__p"><?php echo esc_html($persp['intro']); ?></p>
                    <?php endif; ?>
                </div>
                <?php
                $lp_render_image(
                    $persp['badge_image'] ?? null,
                    $persp_badge_fallback,
                    'rank-mark-badge',
                    __('Ranking Uczelni Zawodowych Perspektywy 2026', 'akademiata')
                );
                ?>
            </div>
            <?php if (!empty($persp['stats']) && is_array($persp['stats'])) : ?>
                <div class="rank-rstats">
                    <?php foreach ($persp['stats'] as $stat) : ?>
                        <div class="rank-rstat rank-reveal">
                            <?php if (isset($stat['value']) && $stat['value'] !== '') : ?>
                                <b data-count="<?php echo esc_attr($stat['value']); ?>">0</b>
                            <?php endif; ?>
                            <?php if (!empty($stat['label'])) : ?>
                                <span><?php echo esc_html($stat['label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $ela = $acf_fields['rank_ela_section'];
    $ela_id = !empty($ela['section_id']) ? $ela['section_id'] : 'ela';
    $ela_logo_fallback = get_template_directory_uri() . '/assets/dist/img/ela-logo.svg';
    ?>
    <section class="rank-sec rank-sec--tint" id="<?php echo esc_attr($ela_id); ?>">
        <div class="container rank-wrap">
            <div class="rank-sec__head rank-reveal">
                <div>
                    <?php if (!empty($ela['eyebrow'])) : ?>
                        <p class="rank-kick"><?php echo esc_html($ela['eyebrow']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($ela['title']) || !empty($ela['title_emphasis'])) : ?>
                        <h2 class="rank-sec__h">
                            <?php akademiata_rankingi_echo_title_em($ela['title'] ?? '', $ela['title_emphasis'] ?? ''); ?>
                        </h2>
                    <?php endif; ?>
                    <?php if (!empty($ela['intro'])) : ?>
                        <p class="rank-sec__p"><?php echo esc_html($ela['intro']); ?></p>
                    <?php endif; ?>
                </div>
                <?php
                $lp_render_image(
                    $ela['logo'] ?? null,
                    $ela_logo_fallback,
                    'rank-mark-ela',
                    __('ELA — Ekonomiczne Losy Absolwentów', 'akademiata')
                );
                ?>
            </div>
            <?php if (!empty($ela['stats']) && is_array($ela['stats'])) : ?>
                <div class="rank-rstats rank-rstats--spaced">
                    <?php foreach ($ela['stats'] as $stat) : ?>
                        <div class="rank-rstat rank-reveal">
                            <?php if (isset($stat['value']) && $stat['value'] !== '') : ?>
                                <b data-count="<?php echo esc_attr($stat['value']); ?>">0</b>
                            <?php endif; ?>
                            <?php if (!empty($stat['label'])) : ?>
                                <span><?php echo esc_html($stat['label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($ela['cards_title'])) : ?>
                <p class="rank-kick rank-reveal rank-kick--cards"><?php echo akademiata_rankingi_title_html($ela['cards_title']); ?></p>
            <?php endif; ?>
            <?php if (!empty($ela['cards']) && is_array($ela['cards'])) : ?>
                <div class="rank-cards rank-reveal">
                    <?php foreach ($ela['cards'] as $card) : ?>
                        <article class="rank-card">
                            <?php if (!empty($card['badge_main'])) : ?>
                                <div class="rank-card__badge">
                                    <?php echo esc_html($card['badge_main']); ?>
                                    <?php if (!empty($card['badge_sub'])) : ?>
                                        <span><?php echo esc_html($card['badge_sub']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($card['name'])) : ?>
                                <h3 class="rank-card__name"><?php echo akademiata_rankingi_title_html($card['name']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['level'])) : ?>
                                <p class="rank-card__lvl"><?php echo esc_html($card['level']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($card['description'])) : ?>
                                <p class="rank-card__desc"><?php echo esc_html($card['description']); ?></p>
                            <?php endif; ?>
                            <div class="rank-card__data">
                                <?php if (!empty($card['salary'])) : ?>
                                    <div><span><?php echo esc_html($card['salary']); ?></span><?php esc_html_e('zarobki/mies.', 'akademiata'); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($card['time_to_job'])) : ?>
                                    <div><span><?php echo esc_html($card['time_to_job']); ?></span><?php esc_html_e('do pracy', 'akademiata'); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($card['unemployment'])) : ?>
                                    <div><span><?php echo esc_html($card['unemployment']); ?></span><?php esc_html_e('bezrobocie', 'akademiata'); ?></div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $film = $acf_fields['rank_film_section'];
    $video_url = akademiata_rankingi_theme_video_url();
    $poster = is_array($film['poster'] ?? null) ? $film['poster'] : null;
    ?>
    <section class="rank-film">
        <div class="container rank-wrap">
            <div class="rank-film__media rank-reveal">
                <?php if ($video_url !== '') : ?>
                    <video
                        class="rank-video"
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
                        class="rank-video rank-video--poster"
                        src="<?php echo esc_url($poster['url']); ?>"
                        alt="<?php echo esc_attr($poster['alt'] ?: __('ATA Mistrzem Świata', 'akademiata')); ?>"
                    >
                <?php endif; ?>
            </div>
            <div class="rank-reveal">
                <?php if (!empty($film['eyebrow'])) : ?>
                    <p class="rank-kick"><?php echo esc_html($film['eyebrow']); ?></p>
                <?php endif; ?>
                <?php if (!empty($film['title'])) : ?>
                    <h2><?php echo akademiata_rankingi_title_html($film['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($film['text'])) : ?>
                    <p><?php echo esc_html($film['text']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $cta = $acf_fields['rank_cta_section'];
    $cta_id = !empty($cta['section_id']) ? $cta['section_id'] : 'rekrutacja';
    ?>
    <section class="rank-cta" id="<?php echo esc_attr($cta_id); ?>">
        <div class="container rank-wrap">
            <?php if (!empty($cta['title'])) : ?>
                <h2><?php echo akademiata_rankingi_title_html($cta['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($cta['text'])) : ?>
                <p><?php echo esc_html($cta['text']); ?></p>
            <?php endif; ?>
            <?php if (!empty($cta['cta_primary_text']) || !empty($cta['cta_secondary_text'])) : ?>
                <div class="rank-cta__act">
                    <?php
                    $lp_render_btn($cta['cta_primary_text'] ?? '', $cta['cta_primary_url'] ?? '', 'coral', '→');
                    $lp_render_btn($cta['cta_secondary_text'] ?? '', $cta['cta_secondary_url'] ?? '', 'out', '→');
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $sources = $acf_fields['rank_sources_section'];
    ?>
    <?php if (!empty($sources['text'])) : ?>
        <div class="rank-src">
            <div class="container rank-wrap">
                <?php echo esc_html($sources['text']); ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
