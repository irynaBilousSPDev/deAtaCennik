<?php
/**
 * Template Name: Studiuj to, co Cię kręci
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/studiuj-to-co-cie-kreci/fields.php';

$acf_fields = akademiata_studiuj_to_co_cie_kreci_fields(get_fields());

/**
 * @param string $text
 * @param string $url
 * @param string $style primary|outline|arrow
 */
$lp_render_cta = static function ($text, $url, $style = 'primary') {
    if ($text === '' || $text === null) {
        return;
    }
    if ($style === 'arrow') {
        $href = $url !== '' && $url !== null ? $url : '#';
        printf(
            '<a href="%s" class="lp-arrow-link">%s</a>',
            esc_url($href),
            esc_html($text)
        );
        return;
    }
    $class = ($style === 'outline') ? 'cta-outline' : 'cta';
    $href = $url !== '' && $url !== null ? $url : '#';
    printf(
        '<a href="%s" class="%s">%s</a>',
        esc_url($href),
        esc_attr($class),
        esc_html($text)
    );
};
?>

<div class="lp-page lp-studiuj-to-co-cie-kreci">

    <?php
    $hero = $acf_fields['stik_hero_section'];
    $hero_photo = $hero['hero_photo'] ?? null;
    $has_hero_photo = is_array($hero_photo) && !empty($hero_photo['ID']);
    ?>
    <section class="hero">
        <?php if (!empty($hero['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($hero['watermark']); ?></div>
        <?php endif; ?>
        <div class="container hero-grid">
            <div>
                <?php if (!empty($hero['eyebrow'])) : ?>
                    <div class="eyebrow"><?php echo esc_html($hero['eyebrow']); ?></div>
                <?php endif; ?>
                <?php if (!empty($hero['title'])) : ?>
                    <h1><?php echo esc_html($hero['title']); ?></h1>
                <?php endif; ?>
                <?php if (!empty($hero['lead'])) : ?>
                    <p class="lead"><?php echo esc_html($hero['lead']); ?></p>
                <?php endif; ?>
                <?php if (!empty($hero['cta_primary_text']) || !empty($hero['cta_secondary_text'])) : ?>
                    <div class="cta-row">
                        <?php
                        $lp_render_cta($hero['cta_primary_text'] ?? '', $hero['cta_primary_url'] ?? '', 'primary');
                        $lp_render_cta($hero['cta_secondary_text'] ?? '', $hero['cta_secondary_url'] ?? '', 'outline');
                        ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($hero['facts']) && is_array($hero['facts'])) : ?>
                    <div class="quick-facts">
                        <?php foreach ($hero['facts'] as $fact) : ?>
                            <div class="fact">
                                <?php if (!empty($fact['number'])) : ?>
                                    <strong><?php echo esc_html($fact['number']); ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($fact['text'])) : ?>
                                    <span><?php echo esc_html($fact['text']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="hero-card-wrap">
                <div class="<?php echo esc_attr('hero-photo' . ($has_hero_photo ? ' hero-photo--has-image' : '')); ?>">
                    <?php if ($has_hero_photo) : ?>
                        <?php
                        echo wp_get_attachment_image(
                            (int) $hero_photo['ID'],
                            'large',
                            false,
                            [
                                'class' => 'hero-photo__img',
                                'alt'   => esc_attr($hero_photo['alt'] ?: __('Studia I stopnia', 'akademiata')),
                            ]
                        );
                        ?>
                    <?php endif; ?>
                </div>
                <div class="dots" aria-hidden="true"></div>
                <div class="hero-panel">
                    <?php if (!empty($hero['panel_title'])) : ?>
                        <h3><?php echo esc_html($hero['panel_title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($hero['panel_text'])) : ?>
                        <p><?php echo esc_html($hero['panel_text']); ?></p>
                    <?php endif; ?>
                    <?php $lp_render_cta($hero['panel_link_text'] ?? '', $hero['panel_link_url'] ?? '', 'arrow'); ?>
                </div>
            </div>
        </div>
    </section>

    <?php
    $explain = $acf_fields['stik_explain_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <?php if (!empty($explain['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($explain['watermark']); ?></div>
        <?php endif; ?>
        <div class="container lp-explain">
            <div>
                <?php if (!empty($explain['title'])) : ?>
                    <h2 class="lp-section-title"><?php echo esc_html($explain['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($explain['intro'])) : ?>
                    <p class="lp-intro"><?php echo esc_html($explain['intro']); ?></p>
                <?php endif; ?>
                <?php $lp_render_cta($explain['cta_text'] ?? '', $explain['cta_url'] ?? '', 'primary'); ?>
            </div>
            <div class="lp-explain-box">
                <?php if (!empty($explain['box_title'])) : ?>
                    <h3><?php echo esc_html($explain['box_title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($explain['box_text'])) : ?>
                    <p><?php echo esc_html($explain['box_text']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $titles = $acf_fields['stik_titles_section'];
    ?>
    <section class="lp-section">
        <div class="container">
            <?php if (!empty($titles['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($titles['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($titles['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($titles['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($titles['items']) && is_array($titles['items'])) : ?>
                <div class="lp-title-cards">
                    <?php foreach ($titles['items'] as $item) : ?>
                        <div class="lp-title-card">
                            <?php if (!empty($item['title'])) : ?>
                                <h3><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['text'])) : ?>
                                <p><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $for = $acf_fields['stik_for_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <div class="container lp-for-grid">
            <div>
                <?php if (!empty($for['title'])) : ?>
                    <h2 class="lp-section-title"><?php echo esc_html($for['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($for['intro'])) : ?>
                    <p class="lp-intro"><?php echo esc_html($for['intro']); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($for['items']) && is_array($for['items'])) : ?>
                <div class="lp-checklist">
                    <?php foreach ($for['items'] as $item) :
                        $text = is_array($item) ? ($item['text'] ?? '') : $item;
                        if ($text === '') {
                            continue;
                        }
                        ?>
                        <div class="lp-check"><?php echo esc_html($text); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $interests = $acf_fields['stik_interests_section'];
    ?>
    <section class="lp-section">
        <div class="container">
            <?php if (!empty($interests['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($interests['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($interests['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($interests['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($interests['items']) && is_array($interests['items'])) : ?>
                <div class="lp-interests">
                    <?php foreach ($interests['items'] as $item) : ?>
                        <div class="lp-interest">
                            <?php if (!empty($item['title'])) : ?>
                                <h3><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['text'])) : ?>
                                <p><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $study = $acf_fields['stik_study_section'];
    ?>
    <section class="lp-study-strip">
        <div class="container lp-study-grid">
            <div>
                <?php if (!empty($study['title'])) : ?>
                    <h2><?php echo esc_html($study['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($study['text'])) : ?>
                    <p><?php echo esc_html($study['text']); ?></p>
                <?php endif; ?>
                <?php if (!empty($study['text_secondary'])) : ?>
                    <p><?php echo esc_html($study['text_secondary']); ?></p>
                <?php endif; ?>
            </div>
            <div class="lp-explain-box lp-explain-box--light">
                <?php if (!empty($study['box_title'])) : ?>
                    <h3><?php echo esc_html($study['box_title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($study['box_text'])) : ?>
                    <p><?php echo esc_html($study['box_text']); ?></p>
                <?php endif; ?>
                <?php $lp_render_cta($study['box_link_text'] ?? '', $study['box_link_url'] ?? '', 'arrow'); ?>
            </div>
        </div>
    </section>

    <?php
    $process = $acf_fields['stik_process_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <div class="container">
            <?php if (!empty($process['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($process['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($process['items']) && is_array($process['items'])) : ?>
                <div class="lp-process">
                    <?php foreach ($process['items'] as $step) : ?>
                        <div class="lp-step">
                            <?php if (!empty($step['number'])) : ?>
                                <div class="lp-step__num"><?php echo esc_html($step['number']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($step['title'])) : ?>
                                <h3><?php echo esc_html($step['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($step['text'])) : ?>
                                <p><?php echo esc_html($step['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $faq = $acf_fields['stik_faq_section'];
    ?>
    <section class="lp-section">
        <div class="container">
            <?php if (!empty($faq['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($faq['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($faq['items']) && is_array($faq['items'])) : ?>
                <div class="lp-faq">
                    <?php foreach ($faq['items'] as $item) : ?>
                        <div class="lp-qa">
                            <?php if (!empty($item['title'])) : ?>
                                <h3><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['text'])) : ?>
                                <p><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $final = $acf_fields['stik_final_section'];
    ?>
    <section class="lp-final">
        <div class="container">
            <?php if (!empty($final['title'])) : ?>
                <h2><?php echo esc_html($final['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($final['text'])) : ?>
                <p><?php echo esc_html($final['text']); ?></p>
            <?php endif; ?>
            <?php if (!empty($final['cta_primary_text']) || !empty($final['cta_secondary_text'])) : ?>
                <div class="cta-row cta-row--center">
                    <?php
                    $lp_render_cta($final['cta_primary_text'] ?? '', $final['cta_primary_url'] ?? '', 'primary');
                    $lp_render_cta($final['cta_secondary_text'] ?? '', $final['cta_secondary_url'] ?? '', 'outline');
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php get_footer(); ?>
