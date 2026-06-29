<?php
/**
 * Template Name: Zasady rekrutacji
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/zasady-rekrutacji/fields.php';

$acf_fields = akademiata_zasady_rekrutacji_fields(get_fields());

/**
 * @param string $text
 * @param string $url
 * @param string $style primary|outline|small|small-blue
 * @param array<string, string> $attrs
 */
$lp_render_cta = static function ($text, $url, $style = 'primary', $attrs = []) {
    if ($text === '' || $text === null) {
        return;
    }
    $class_map = [
        'primary'    => 'cta',
        'outline'    => 'cta-outline',
        'small'      => 'small-cta',
        'small-blue' => 'small-cta small-cta--blue',
    ];
    $class = $class_map[$style] ?? 'cta';
    $href = $url !== '' && $url !== null ? $url : '#';

    $attr_html = '';
    foreach ($attrs as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $attr_html .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }

    printf(
        '<a href="%s" class="%s"%s>%s</a>',
        esc_url($href),
        esc_attr($class),
        $attr_html,
        esc_html($text)
    );
};

/**
 * @param string|null $title
 * @param string|null $text
 * @param string      $class
 */
$lp_render_callout = static function ($title, $text, $class = 'note') {
    $title = is_string($title) ? trim($title) : '';
    $text = is_string($text) ? trim($text) : '';
    if ($title === '' && $text === '') {
        return;
    }
    echo '<div class="' . esc_attr($class) . '">';
    if ($title !== '') {
        echo '<strong>' . esc_html($title) . '</strong>';
        if ($text !== '') {
            echo ' ';
        }
    }
    if ($text !== '') {
        echo esc_html($text);
    }
    echo '</div>';
};

/**
 * @param string|null $text
 */
$lp_render_multiline = static function ($text) {
    if ($text === '' || $text === null) {
        return;
    }
    echo nl2br(esc_html($text));
};

/**
 * @param array<string, mixed>|null $image
 * @param string                    $static_key hero|reassure
 * @param string                    $alt
 * @param string                    $size
 */
$lp_render_photo = static function ($image, $static_key, $alt, $size = 'large') {
    if (is_array($image) && !empty($image['ID'])) {
        echo wp_get_attachment_image(
            (int) $image['ID'],
            $size,
            false,
            [
                'alt' => esc_attr($image['alt'] ?: $alt),
            ]
        );
        return;
    }
    $url = akademiata_zasady_rekrutacji_static_image_url($static_key);
    if ($url === '') {
        return;
    }
    printf(
        '<img src="%s" alt="%s">',
        esc_url($url),
        esc_attr($alt)
    );
};

/**
 * @param array<string, mixed>|null $image
 * @param string                    $static_key
 */
$lp_has_photo = static function ($image, $static_key) {
    if (is_array($image) && !empty($image['ID'])) {
        return true;
    }
    return akademiata_zasady_rekrutacji_static_image_url($static_key) !== '';
};
?>

<div class="lp-page lp-zasady-rekrutacji">

    <?php
    $hero = $acf_fields['rekr_hero_section'];
    $hero_photo = $hero['hero_photo'] ?? null;
    $has_hero_photo = $lp_has_photo($hero_photo, 'hero');
    ?>
    <section class="section hero">
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
                <?php if (!empty($hero['hero_reassure'])) : ?>
                    <p class="hero-reassure"><?php echo esc_html($hero['hero_reassure']); ?></p>
                <?php endif; ?>
            </div>

            <aside class="<?php echo esc_attr('hero-media' . ($has_hero_photo ? ' hero-media--has-image' : '')); ?>">
                <?php if ($has_hero_photo) : ?>
                    <?php $lp_render_photo($hero_photo, 'hero', __('Rekrutacja ATA', 'akademiata'), 'large'); ?>
                <?php endif; ?>
                <?php if (!empty($hero['hero_tag'])) : ?>
                    <span class="hero-media__tag"><?php echo esc_html($hero['hero_tag']); ?></span>
                <?php endif; ?>
                <?php if (!empty($hero['hero_bar']) && is_array($hero['hero_bar'])) : ?>
                    <div class="hero-media__bar">
                        <?php foreach ($hero['hero_bar'] as $bar_item) : ?>
                            <div class="hm">
                                <?php if (!empty($bar_item['value'])) : ?>
                                    <b><?php echo esc_html($bar_item['value']); ?></b>
                                <?php endif; ?>
                                <?php if (!empty($bar_item['label'])) : ?>
                                    <span><?php echo esc_html($bar_item['label']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </section>

    <?php $trust = $acf_fields['rekr_trust_section']; ?>
    <?php if (!empty($trust['items']) && is_array($trust['items'])) : ?>
        <section class="trust-strip">
            <div class="container">
                <?php foreach ($trust['items'] as $item) : ?>
                    <div class="ts">
                        <?php if (!empty($item['number'])) : ?>
                            <b><?php echo esc_html($item['number']); ?></b>
                        <?php endif; ?>
                        <span>
                            <?php if (!empty($item['text'])) : ?>
                                <?php echo esc_html($item['text']); ?>
                            <?php endif; ?>
                            <?php if (!empty($item['note'])) : ?>
                                <small><?php echo esc_html($item['note']); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php $quick_nav = $acf_fields['rekr_quick_nav']; ?>
    <nav class="quick-nav" aria-label="<?php esc_attr_e('Spis treści strony', 'akademiata'); ?>">
        <div class="container">
            <div class="qn-label">
                <?php if (!empty($quick_nav['label_title'])) : ?>
                    <b><?php echo esc_html($quick_nav['label_title']); ?></b>
                <?php endif; ?>
                <?php if (!empty($quick_nav['label_text'])) : ?>
                    <span><?php echo esc_html($quick_nav['label_text']); ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($quick_nav['links']) && is_array($quick_nav['links'])) : ?>
                <div class="qn-links">
                    <?php foreach ($quick_nav['links'] as $link) :
                        $anchor = $link['anchor'] ?? '#';
                        $text = $link['text'] ?? '';
                        if ($text === '') {
                            continue;
                        }
                        ?>
                        <a href="<?php echo esc_url($anchor); ?>"><?php echo esc_html($text); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <?php $route = $acf_fields['rekr_route_section']; ?>
    <section class="section gray" id="sciezka">
        <?php if (!empty($route['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($route['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($route['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($route['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($route['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($route['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($route['intro'])) : ?>
                <p class="intro"><?php echo esc_html($route['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($route['cards']) && is_array($route['cards'])) : ?>
                <div class="route-grid">
                    <?php foreach ($route['cards'] as $card) :
                        $card_class = 'route-card';
                        if (!empty($card['is_dark'])) {
                            $card_class .= ' dark';
                        }
                        ?>
                        <article class="<?php echo esc_attr($card_class); ?>">
                            <?php if (!empty($card['title'])) : ?>
                                <h3><?php echo esc_html($card['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['text'])) : ?>
                                <p><?php echo esc_html($card['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($card['items']) && is_array($card['items'])) : ?>
                                <ul class="mini-list">
                                    <?php foreach ($card['items'] as $item) :
                                        $line = is_array($item) ? ($item['text'] ?? '') : $item;
                                        if ($line === '') {
                                            continue;
                                        }
                                        ?>
                                        <li><?php echo esc_html($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php $lp_render_cta($card['cta_text'] ?? '', $card['cta_url'] ?? '', 'small'); ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $paths = $acf_fields['rekr_paths_section'];
    $portfolio = $acf_fields['rekr_portfolio_section'];
    $gallery_images = $portfolio['gallery_images'] ?? null;
    $has_gallery = is_array($gallery_images) && count($gallery_images) > 0;
    ?>
    <section class="section" id="kierunki">
        <?php if (!empty($paths['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($paths['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($paths['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($paths['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($paths['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($paths['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($paths['intro'])) : ?>
                <p class="intro"><?php echo esc_html($paths['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($paths['cards']) && is_array($paths['cards'])) : ?>
                <div class="paths-grid">
                    <?php foreach ($paths['cards'] as $card) : ?>
                        <article class="card">
                            <?php if (!empty($card['tag'])) : ?>
                                <span class="tag"><?php echo esc_html($card['tag']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($card['title'])) : ?>
                                <h3><?php echo esc_html($card['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['text'])) : ?>
                                <p><?php echo esc_html($card['text']); ?></p>
                            <?php endif; ?>
                            <?php
                            $path_cta_style = ($card['cta_style'] ?? '') === 'blue' ? 'small-blue' : 'small';
                            $lp_render_cta($card['cta_text'] ?? '', $card['cta_url'] ?? '', $path_cta_style);
                            ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="panel portfolio-panel" id="portfolio-wymagania">
                <div class="two-col">
                    <div>
                        <?php if (!empty($portfolio['col1_eyebrow'])) : ?>
                            <div class="eyebrow"><?php echo esc_html($portfolio['col1_eyebrow']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['col1_title'])) : ?>
                            <h3><?php echo esc_html($portfolio['col1_title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['col1_text'])) : ?>
                            <p><?php echo esc_html($portfolio['col1_text']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['checklist']) && is_array($portfolio['checklist'])) : ?>
                            <ul class="checklist" style="margin-top:18px">
                                <?php foreach ($portfolio['checklist'] as $item) :
                                    $line = is_array($item) ? ($item['text'] ?? '') : $item;
                                    if ($line === '') {
                                        continue;
                                    }
                                    ?>
                                    <li><?php echo esc_html($line); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!empty($portfolio['col2_eyebrow'])) : ?>
                            <div class="eyebrow"><?php echo esc_html($portfolio['col2_eyebrow']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['col2_title'])) : ?>
                            <h3><?php echo esc_html($portfolio['col2_title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['col2_text'])) : ?>
                            <p><?php echo esc_html($portfolio['col2_text']); ?></p>
                        <?php endif; ?>
                        <div class="cta-row">
                            <?php if ($has_gallery) : ?>
                                <a
                                    class="small-cta"
                                    id="pfToggle"
                                    href="#pfGallery"
                                    aria-expanded="false"
                                    aria-controls="pfGallery"
                                    data-pf-toggle
                                    data-label-show="<?php echo esc_attr($portfolio['gallery_toggle_text'] ?? ''); ?>"
                                    data-label-hide="<?php echo esc_attr(__('Ukryj przykłady', 'akademiata')); ?>"
                                ><?php echo esc_html($portfolio['gallery_toggle_text'] ?? ''); ?></a>
                            <?php else : ?>
                                <?php
                                $lp_render_cta(
                                    $portfolio['gallery_toggle_text'] ?? '',
                                    $portfolio['gallery_toggle_url'] ?? '',
                                    'small',
                                    ['target' => '_blank', 'rel' => 'noopener noreferrer']
                                );
                                ?>
                            <?php endif; ?>
                            <?php $lp_render_cta($portfolio['gallery_link_text'] ?? '', '#terminy', 'small'); ?>
                        </div>
                    </div>
                </div>

                <?php if ($has_gallery) : ?>
                    <div class="pf-gallery" id="pfGallery" aria-hidden="true">
                        <div class="pf-gallery__inner">
                            <?php if (!empty($portfolio['gallery_bar_title']) || !empty($portfolio['gallery_bar_text'])) : ?>
                                <div class="pf-gallery__bar">
                                    <?php if (!empty($portfolio['gallery_bar_title'])) : ?>
                                        <strong><?php echo esc_html($portfolio['gallery_bar_title']); ?></strong><?php echo !empty($portfolio['gallery_bar_text']) ? ' — ' : ''; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($portfolio['gallery_bar_text'])) : ?>
                                        <?php echo esc_html($portfolio['gallery_bar_text']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="pf-grid">
                                <?php foreach ($gallery_images as $image) :
                                    if (is_array($image) && !empty($image['image']) && is_array($image['image'])) {
                                        $image = $image['image'];
                                    }
                                    if (!is_array($image)) {
                                        continue;
                                    }
                                    $img_id = !empty($image['ID']) ? (int) $image['ID'] : 0;
                                    $img_url = $image['url'] ?? ($img_id ? wp_get_attachment_image_url($img_id, 'large') : '');
                                    if ($img_url === '') {
                                        continue;
                                    }
                                    $img_alt = $image['alt'] ?? '';
                                    ?>
                                    <button class="pf-thumb" type="button" data-pf-zoom data-src="<?php echo esc_url($img_url); ?>" data-alt="<?php echo esc_attr($img_alt); ?>">
                                        <?php if ($img_id) : ?>
                                            <?php
                                            echo wp_get_attachment_image(
                                                $img_id,
                                                'medium_large',
                                                false,
                                                [
                                                    'loading' => 'lazy',
                                                    'alt'     => esc_attr($img_alt),
                                                ]
                                            );
                                            ?>
                                        <?php else : ?>
                                            <img loading="lazy" src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php $terms = $acf_fields['rekr_terms_section']; ?>
    <section class="section gray" id="terminy">
        <?php if (!empty($terms['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($terms['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($terms['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($terms['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($terms['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($terms['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($terms['intro'])) : ?>
                <p class="intro"><?php echo esc_html($terms['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($terms['term_cards']) && is_array($terms['term_cards'])) : ?>
                <div class="terms-grid">
                    <?php foreach ($terms['term_cards'] as $card) :
                        $card_class = 'term-card';
                        if (!empty($card['is_dark'])) {
                            $card_class .= ' dark';
                        }
                        ?>
                        <article class="<?php echo esc_attr($card_class); ?>">
                            <?php if (!empty($card['label'])) : ?>
                                <strong><?php echo esc_html($card['label']); ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($card['title'])) : ?>
                                <h3><?php echo esc_html($card['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['text'])) : ?>
                                <p><?php echo esc_html($card['text']); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($terms['schedule_blocks']) && is_array($terms['schedule_blocks'])) : ?>
                <div class="schedule-wrap">
                    <button aria-expanded="true" class="schedule-toggle" type="button">
                        <span><?php echo esc_html($terms['schedule_toggle_label'] ?? ''); ?></span>
                        <span>-</span>
                    </button>
                    <div class="schedule-panel open">
                        <div class="schedule-grid">
                            <?php foreach ($terms['schedule_blocks'] as $block) : ?>
                                <div class="schedule-block">
                                    <?php if (!empty($block['title'])) : ?>
                                        <h4><?php echo esc_html($block['title']); ?></h4>
                                    <?php endif; ?>
                                    <?php if (!empty($block['items']) && is_array($block['items'])) : ?>
                                        <ul>
                                            <?php foreach ($block['items'] as $item) :
                                                $line = is_array($item) ? ($item['text'] ?? '') : $item;
                                                if ($line === '') {
                                                    continue;
                                                }
                                                ?>
                                                <li><?php echo esc_html($line); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php $lp_render_callout($terms['warning_title'] ?? '', $terms['warning_text'] ?? '', 'warning'); ?>
        </div>
    </section>

    <?php
    $poland = $acf_fields['rekr_poland_section'];
    $reassure_photo = $poland['reassure_photo'] ?? null;
    $has_reassure_photo = $lp_has_photo($reassure_photo, 'reassure');
    ?>
    <section class="section" id="polska">
        <?php if (!empty($poland['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($poland['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($poland['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($poland['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($poland['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($poland['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($poland['intro'])) : ?>
                <p class="intro"><?php echo esc_html($poland['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($poland['steps']) && is_array($poland['steps'])) : ?>
                <div class="steps-grid">
                    <?php foreach ($poland['steps'] as $step) : ?>
                        <article class="step-card">
                            <?php if (!empty($step['number'])) : ?>
                                <div class="step-no"><?php echo esc_html($step['number']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($step['title'])) : ?>
                                <h3><?php echo esc_html($step['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($step['text'])) : ?>
                                <p><?php echo esc_html($step['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($step['micro'])) : ?>
                                <div class="micro"><?php echo esc_html($step['micro']); ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $lp_render_callout($poland['note_title'] ?? '', $poland['note_text'] ?? '', 'note'); ?>

            <div class="two-col" style="margin-top:22px">
                <div class="reassure-col">
                    <div class="<?php echo esc_attr('reassure-photo' . ($has_reassure_photo ? ' reassure-photo--has-image' : '')); ?>">
                        <?php if ($has_reassure_photo) : ?>
                            <?php $lp_render_photo($reassure_photo, 'reassure', __('Studenci ATA', 'akademiata'), 'medium_large'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="panel reassure-box">
                        <?php if (!empty($poland['reassure_tag'])) : ?>
                            <span class="tag"><?php echo esc_html($poland['reassure_tag']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($poland['reassure_title'])) : ?>
                            <h3><?php echo esc_html($poland['reassure_title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($poland['reassure_text'])) : ?>
                            <p><?php echo esc_html($poland['reassure_text']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel" id="przeniesienie">
                    <?php if (!empty($poland['transfer_title'])) : ?>
                        <h3><?php echo esc_html($poland['transfer_title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($poland['transfer_text'])) : ?>
                        <p><?php echo esc_html($poland['transfer_text']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($poland['transfer_items']) && is_array($poland['transfer_items'])) : ?>
                        <ul class="checklist" style="margin-top:18px">
                            <?php foreach ($poland['transfer_items'] as $item) :
                                $line = is_array($item) ? ($item['text'] ?? '') : $item;
                                if ($line === '') {
                                    continue;
                                }
                                ?>
                                <li><?php echo esc_html($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php $lp_render_callout($poland['transfer_note_title'] ?? '', $poland['transfer_note_text'] ?? '', 'note'); ?>
                    <div class="cta-row">
                        <?php $lp_render_cta($poland['transfer_cta_text'] ?? '', $poland['transfer_cta_url'] ?? '', 'small'); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php $docs_pl = $acf_fields['rekr_docs_pl_section']; ?>
    <section class="section gray" id="dokumenty-pl">
        <?php if (!empty($docs_pl['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($docs_pl['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($docs_pl['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($docs_pl['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($docs_pl['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($docs_pl['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($docs_pl['intro'])) : ?>
                <p class="intro"><?php echo esc_html($docs_pl['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($docs_pl['tabs']) && is_array($docs_pl['tabs'])) : ?>
                <div class="panel">
                    <div class="tabs" data-tabs="pl-docs">
                        <?php foreach ($docs_pl['tabs'] as $index => $tab) :
                            $panel_id = 'pl-docs-tab-' . $index;
                            ?>
                            <button
                                class="tab<?php echo $index === 0 ? ' active' : ''; ?>"
                                type="button"
                                data-target="<?php echo esc_attr($panel_id); ?>"
                            ><?php echo esc_html($tab['label'] ?? ''); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($docs_pl['tabs'] as $index => $tab) :
                        $panel_id = 'pl-docs-tab-' . $index;
                        ?>
                        <div class="tab-panel<?php echo $index === 0 ? ' active' : ''; ?>" id="<?php echo esc_attr($panel_id); ?>">
                            <?php if (!empty($tab['items']) && is_array($tab['items'])) : ?>
                                <ul class="checklist">
                                    <?php foreach ($tab['items'] as $item) :
                                        $line = is_array($item) ? ($item['text'] ?? '') : $item;
                                        if ($line === '') {
                                            continue;
                                        }
                                        ?>
                                        <li><?php echo esc_html($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php $lp_render_callout($tab['note_title'] ?? '', $tab['note_text'] ?? '', 'note'); ?>
                            <?php $lp_render_callout($tab['warning_title'] ?? '', $tab['warning_text'] ?? '', 'warning'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $foreign = $acf_fields['rekr_foreign_section']; ?>
    <section class="section dark" id="zagraniczni">
        <?php if (!empty($foreign['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($foreign['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($foreign['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($foreign['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($foreign['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($foreign['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($foreign['intro'])) : ?>
                <p class="intro"><?php echo esc_html($foreign['intro']); ?></p>
            <?php endif; ?>

            <div class="foreign-apps single">
                <div class="app-card">
                    <?php if (!empty($foreign['app_title'])) : ?>
                        <h3><?php echo esc_html($foreign['app_title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($foreign['app_text'])) : ?>
                        <p><?php echo esc_html($foreign['app_text']); ?></p>
                    <?php endif; ?>
                    <?php $lp_render_cta($foreign['app_cta_text'] ?? '', $foreign['app_cta_url'] ?? '', 'small-blue'); ?>
                </div>
            </div>

            <?php if (!empty($foreign['steps']) && is_array($foreign['steps'])) : ?>
                <div class="foreign-steps">
                    <?php foreach ($foreign['steps'] as $step) : ?>
                        <article class="step-card">
                            <?php if (!empty($step['number'])) : ?>
                                <div class="step-no"><?php echo esc_html($step['number']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($step['title'])) : ?>
                                <h3><?php echo esc_html($step['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($step['text'])) : ?>
                                <p><?php echo esc_html($step['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($step['micro'])) : ?>
                                <div class="micro"><?php echo esc_html($step['micro']); ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $foreign_docs = $acf_fields['rekr_foreign_docs_section']; ?>
    <section class="section gray" id="foreign-docs">
        <?php if (!empty($foreign_docs['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($foreign_docs['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($foreign_docs['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($foreign_docs['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($foreign_docs['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($foreign_docs['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($foreign_docs['tabs']) && is_array($foreign_docs['tabs'])) : ?>
                <div class="panel">
                    <div class="tabs" data-tabs="foreign-docs">
                        <?php foreach ($foreign_docs['tabs'] as $index => $tab) :
                            $panel_id = 'foreign-docs-tab-' . $index;
                            ?>
                            <button
                                class="tab<?php echo $index === 0 ? ' active' : ''; ?>"
                                type="button"
                                data-target="<?php echo esc_attr($panel_id); ?>"
                            ><?php echo esc_html($tab['label'] ?? ''); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($foreign_docs['tabs'] as $index => $tab) :
                        $panel_id = 'foreign-docs-tab-' . $index;
                        ?>
                        <div class="tab-panel<?php echo $index === 0 ? ' active' : ''; ?>" id="<?php echo esc_attr($panel_id); ?>">
                            <?php if (!empty($tab['items']) && is_array($tab['items'])) : ?>
                                <ul class="checklist">
                                    <?php foreach ($tab['items'] as $item) :
                                        $line = is_array($item) ? ($item['text'] ?? '') : $item;
                                        if ($line === '') {
                                            continue;
                                        }
                                        ?>
                                        <li><?php echo esc_html($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php $lp_render_callout($tab['note_title'] ?? '', $tab['note_text'] ?? '', 'note'); ?>
                            <?php $lp_render_callout($tab['warning_title'] ?? '', $tab['warning_text'] ?? '', 'warning'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $residence = $acf_fields['rekr_residence_section']; ?>
    <section class="section" id="pobyt">
        <?php if (!empty($residence['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($residence['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($residence['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($residence['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($residence['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($residence['title']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($residence['panels_two']) && is_array($residence['panels_two'])) : ?>
                <div class="two-col">
                    <?php foreach ($residence['panels_two'] as $panel) : ?>
                        <div class="panel">
                            <?php if (!empty($panel['title'])) : ?>
                                <h3><?php echo esc_html($panel['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($panel['text'])) : ?>
                                <p><?php echo esc_html($panel['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($panel['note_text'])) : ?>
                                <div class="note"><?php $lp_render_multiline($panel['note_text']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($residence['panels_three']) && is_array($residence['panels_three'])) : ?>
                <div class="three-col" style="margin-top:22px">
                    <?php foreach ($residence['panels_three'] as $panel) : ?>
                        <div class="panel">
                            <?php if (!empty($panel['title'])) : ?>
                                <h3><?php echo esc_html($panel['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($panel['text'])) : ?>
                                <p><?php echo esc_html($panel['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($residence['nawa_title']) || !empty($residence['nawa_text'])) : ?>
                <div class="panel" style="margin-top:22px">
                    <?php if (!empty($residence['nawa_title'])) : ?>
                        <h3><?php echo esc_html($residence['nawa_title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($residence['nawa_text'])) : ?>
                        <p><?php echo esc_html($residence['nawa_text']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $downloads = $acf_fields['rekr_downloads_section']; ?>
    <section class="section gray" id="pliki">
        <?php if (!empty($downloads['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($downloads['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($downloads['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($downloads['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($downloads['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($downloads['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($downloads['intro'])) : ?>
                <p class="intro"><?php echo esc_html($downloads['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($downloads['cards']) && is_array($downloads['cards'])) : ?>
                <div class="download-grid">
                    <?php foreach ($downloads['cards'] as $card) : ?>
                        <article class="download-card">
                            <?php if (!empty($card['title'])) : ?>
                                <h3><?php echo esc_html($card['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['text'])) : ?>
                                <p><?php echo esc_html($card['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($card['buttons']) && is_array($card['buttons'])) : ?>
                                <div class="file-buttons">
                                    <?php foreach ($card['buttons'] as $button) : ?>
                                        <?php
                                        $lp_render_cta(
                                            $button['text'] ?? '',
                                            $button['url'] ?? '',
                                            'small',
                                            ['target' => '_blank', 'rel' => 'noopener noreferrer']
                                        );
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <?php
                                $lp_render_cta(
                                    $card['cta_text'] ?? '',
                                    $card['cta_url'] ?? '',
                                    'small',
                                    ['target' => '_blank', 'rel' => 'noopener noreferrer']
                                );
                                ?>
                            <?php endif; ?>
                            <?php if (!empty($card['file_note'])) : ?>
                                <div class="file-note"><?php echo esc_html($card['file_note']); ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $contact = $acf_fields['rekr_contact_section']; ?>
    <section class="section blue" id="kontakt">
        <div class="container">
            <?php if (!empty($contact['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($contact['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($contact['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($contact['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($contact['cards']) && is_array($contact['cards'])) : ?>
                <div class="contact-grid">
                    <?php foreach ($contact['cards'] as $card) : ?>
                        <article class="contact-card">
                            <?php if (!empty($card['title'])) : ?>
                                <h3><?php echo esc_html($card['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['rows']) && is_array($card['rows'])) : ?>
                                <?php foreach ($card['rows'] as $row) : ?>
                                    <div class="contact-row">
                                        <?php if (!empty($row['label'])) : ?>
                                            <strong><?php echo esc_html($row['label']); ?></strong>
                                        <?php endif; ?>
                                        <?php if (!empty($row['value'])) : ?>
                                            <span><?php $lp_render_multiline($row['value'] ?? ''); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (!empty($card['hours'])) : ?>
                                <div class="hours"><?php $lp_render_multiline($card['hours'] ?? ''); ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($contact['final_title']) || !empty($contact['final_text']) || !empty($contact['final_cta_text'])) : ?>
                <div class="final-strip" style="margin-top:28px">
                    <div>
                        <?php if (!empty($contact['final_title'])) : ?>
                            <h3><?php echo esc_html($contact['final_title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($contact['final_text'])) : ?>
                            <p><?php echo esc_html($contact['final_text']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php $lp_render_cta($contact['final_cta_text'] ?? '', $contact['final_cta_url'] ?? '', 'primary'); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $faq = $acf_fields['rekr_faq_section']; ?>
    <section class="section" id="faq">
        <?php if (!empty($faq['watermark'])) : ?>
            <div class="watermark" aria-hidden="true"><?php echo esc_html($faq['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($faq['eyebrow'])) : ?>
                <div class="eyebrow"><?php echo esc_html($faq['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($faq['title'])) : ?>
                <h2 class="section-title"><?php echo esc_html($faq['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($faq['items']) && is_array($faq['items'])) : ?>
                <div class="faq">
                    <?php foreach ($faq['items'] as $item) : ?>
                        <div class="faq-item open">
                            <?php if (!empty($item['question'])) : ?>
                                <button class="faq-q" type="button">
                                    <?php echo esc_html($item['question']); ?>
                                    <span>-</span>
                                </button>
                            <?php endif; ?>
                            <?php if (!empty($item['answer'])) : ?>
                                <div class="faq-a"><?php echo esc_html($item['answer']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="pf-zoom" id="pfZoom">
        <button class="pf-zoom__close" type="button" data-pf-zoomclose aria-label="<?php esc_attr_e('Zamknij podgląd', 'akademiata'); ?>">&times;</button>
        <img id="pfZoomImg" src="" alt="">
    </div>

</div>

<script>
(function () {
    var root = document.querySelector('.lp-zasady-rekrutacji');
    if (!root) {
        return;
    }

    root.querySelectorAll('.schedule-toggle, .accordion-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var panel = toggle.nextElementSibling;
            if (!panel) {
                return;
            }
            var open = panel.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(open));
            var icon = toggle.querySelector('span:last-child');
            if (icon) {
                icon.textContent = open ? '-' : '+';
            }
        });
    });

    root.querySelectorAll('.tabs').forEach(function (tabGroup) {
        tabGroup.querySelectorAll('.tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabGroup.querySelectorAll('.tab').forEach(function (t) {
                    t.classList.remove('active');
                });
                var section = tabGroup.parentElement;
                if (!section) {
                    return;
                }
                section.querySelectorAll('.tab-panel').forEach(function (panel) {
                    panel.classList.remove('active');
                });
                tab.classList.add('active');
                var target = document.getElementById(tab.dataset.target);
                if (target) {
                    target.classList.add('active');
                }
            });
        });
    });

    root.querySelectorAll('.faq-q').forEach(function (button) {
        button.addEventListener('click', function () {
            var item = button.parentElement;
            if (!item) {
                return;
            }
            item.classList.toggle('open');
            var icon = button.querySelector('span:last-child');
            if (icon) {
                icon.textContent = item.classList.contains('open') ? '-' : '+';
            }
        });
    });

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var nav = root.querySelector('.quick-nav');
    var siteHeader = document.querySelector('.site-header');
    var scrollSpyLocked = false;
    var scrollSpyTimer = null;
    var forcedNavLink = null;
    var quickNavLinks = nav ? [].slice.call(nav.querySelectorAll('a')) : [];
    var quickNavMap = quickNavLinks.map(function (a) {
        var h = a.getAttribute('href');
        var el = h && h.charAt(0) === '#' ? document.querySelector(h) : null;
        return el ? { a: a, el: el } : null;
    }).filter(Boolean);

    function getAnchorMarker() {
        if (nav) {
            return nav.getBoundingClientRect().bottom + 12;
        }
        if (siteHeader) {
            return siteHeader.getBoundingClientRect().bottom + 12;
        }
        return 12;
    }

    function updateStickyMetrics() {
        var headerH = siteHeader ? siteHeader.offsetHeight : 0;
        if (nav) {
            nav.style.setProperty('--rekr-quick-nav-top', headerH + 'px');
        }
        var offset = headerH + (nav ? nav.offsetHeight : 0) + 16;
        root.style.setProperty('--rekr-scroll-offset', offset + 'px');
    }

    function scrollActiveLinkIntoView(activeA) {
        var container = activeA.closest('.qn-links');
        if (!container) {
            return;
        }
        var containerRect = container.getBoundingClientRect();
        var linkRect = activeA.getBoundingClientRect();
        if (linkRect.left < containerRect.left + 4) {
            container.scrollLeft -= (containerRect.left - linkRect.left) + 8;
        } else if (linkRect.right > containerRect.right - 4) {
            container.scrollLeft += (linkRect.right - containerRect.right) + 8;
        }
    }

    function setActiveLink(activeA) {
        if (!nav) {
            return;
        }
        quickNavLinks.forEach(function (a) {
            a.classList.remove('active');
        });
        if (!activeA) {
            return;
        }
        activeA.classList.add('active');
        scrollActiveLinkIntoView(activeA);
    }

    function releaseScrollSpy(activeLink) {
        scrollSpyLocked = false;
        forcedNavLink = null;
        if (activeLink) {
            var h = activeLink.getAttribute('href');
            var el = h && h.charAt(0) === '#' ? document.querySelector(h) : null;
            if (el) {
                var rect = el.getBoundingClientRect();
                var marker = getAnchorMarker();
                if (Math.abs(rect.top - marker) <= 80) {
                    setActiveLink(activeLink);
                    return;
                }
            }
        }
        onQuickNavScroll();
    }

    function lockScrollSpy(activeLink) {
        scrollSpyLocked = true;
        forcedNavLink = activeLink || null;
        clearTimeout(scrollSpyTimer);
        var finished = false;
        var behavior = reduce ? 'auto' : 'smooth';
        var waitMs = behavior === 'smooth' ? 1600 : 120;

        function finish() {
            if (finished) {
                return;
            }
            finished = true;
            clearTimeout(scrollSpyTimer);
            releaseScrollSpy(activeLink);
        }

        scrollSpyTimer = setTimeout(finish, waitMs);

        if (behavior === 'smooth' && 'onscrollend' in window) {
            window.addEventListener('scrollend', finish, { once: true });
        }
    }

    function onQuickNavScroll() {
        if (!nav || scrollSpyLocked) {
            return;
        }
        if (forcedNavLink) {
            setActiveLink(forcedNavLink);
            return;
        }
        var marker = getAnchorMarker();
        var cur = null;
        var bestTop = -Infinity;
        quickNavMap.forEach(function (m) {
            var rect = m.el.getBoundingClientRect();
            var docTop = rect.top + window.scrollY;
            if (rect.top <= marker + 2 && docTop > bestTop) {
                bestTop = docTop;
                cur = m;
            }
        });
        if (cur) {
            setActiveLink(cur.a);
        }
    }

    function scrollToAnchor(el, activeLink) {
        if (activeLink) {
            setActiveLink(activeLink);
        }
        var rect = el.getBoundingClientRect();
        var y = Math.max(0, window.scrollY + rect.top - getAnchorMarker());
        var behavior = reduce ? 'auto' : 'smooth';
        lockScrollSpy(activeLink);
        window.scrollTo({ top: y, behavior: behavior });
    }

    updateStickyMetrics();
    window.addEventListener('resize', updateStickyMetrics);

    if (nav) {
        window.addEventListener('scroll', onQuickNavScroll, { passive: true });
        onQuickNavScroll();
    }

    function flash(el) {
        if (!el || reduce) {
            return;
        }
        el.classList.remove('ata-flash');
        void el.offsetWidth;
        el.classList.add('ata-flash');
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[href^="#"]') : null;
        if (!a || !root.contains(a)) {
            return;
        }
        var h = a.getAttribute('href');
        if (!h || h.length < 2) {
            return;
        }
        var el = document.querySelector(h);
        if (!el) {
            return;
        }
        e.preventDefault();
        var activeLink = nav && nav.contains(a) ? a : null;
        scrollToAnchor(el, activeLink);
        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', h);
        } else {
            window.location.hash = h;
        }
        setTimeout(function () {
            flash(el);
        }, reduce ? 0 : 380);
    });

    window.addEventListener('hashchange', function () {
        if (!location.hash) {
            return;
        }
        var el = document.querySelector(location.hash);
        if (!el || !root.contains(el)) {
            return;
        }
        var activeLink = null;
        if (nav) {
            quickNavLinks.some(function (a) {
                if (a.getAttribute('href') === location.hash) {
                    activeLink = a;
                    return true;
                }
                return false;
            });
        }
        scrollToAnchor(el, activeLink);
        setTimeout(function () {
            flash(el);
        }, reduce ? 0 : 380);
    });

    var pfToggle = root.querySelector('[data-pf-toggle]');
    var pfGallery = root.querySelector('#pfGallery');
    if (pfToggle && pfGallery) {
        pfToggle.addEventListener('click', function (e) {
            e.preventDefault();
            var open = pfGallery.classList.toggle('is-open');
            pfGallery.setAttribute('aria-hidden', open ? 'false' : 'true');
            pfToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            var showLabel = pfToggle.getAttribute('data-label-show') || '';
            var hideLabel = pfToggle.getAttribute('data-label-hide') || '';
            pfToggle.textContent = open ? hideLabel : showLabel;
            if (open) {
                setTimeout(function () {
                    pfGallery.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 140);
            }
        });
    }

    root.querySelectorAll('[data-pf-zoom]').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var src = thumb.getAttribute('data-src');
            var alt = thumb.getAttribute('data-alt') || '';
            var zoom = document.getElementById('pfZoom');
            var img = document.getElementById('pfZoomImg');
            if (!zoom || !img || !src) {
                return;
            }
            img.src = src;
            img.alt = alt;
            zoom.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        });
    });

    function pfCloseZoom() {
        var zoom = document.getElementById('pfZoom');
        if (!zoom) {
            return;
        }
        zoom.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (ev) {
        if (ev.target.closest('[data-pf-zoomclose]') || ev.target.id === 'pfZoom') {
            pfCloseZoom();
        }
    });

    document.addEventListener('keydown', function (ev) {
        var zoom = document.getElementById('pfZoom');
        if (ev.key === 'Escape' && zoom && zoom.classList.contains('is-open')) {
            pfCloseZoom();
        }
    });
})();
</script>

<?php get_footer(); ?>
