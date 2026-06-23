<?php
/**
 * Template Name: Projekty i pracownie
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/projekty-i-pracownie/fields.php';

$acf_fields = akademiata_projekty_i_pracownie_fields(get_fields());

/**
 * @param string $text
 * @param string $url
 * @param string $style primary|outline
 */
$lp_render_cta = static function ($text, $url, $style = 'primary') {
    if ($text === '' || $text === null) {
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

/**
 * @param string $variant orange|green|dark
 */
$lp_render_gallery_thumb = static function ($variant) {
    $class = 'lp-thumb';
    if (in_array($variant, ['orange', 'green', 'dark'], true)) {
        $class .= ' lp-thumb--' . $variant;
    }
    echo '<div class="' . esc_attr($class) . '" aria-hidden="true">';
    if ($variant === 'green') {
        ?>
        <svg viewBox="0 0 400 170">
            <circle cx="94" cy="82" r="42" fill="#74b983" opacity=".45"></circle>
            <circle cx="160" cy="72" r="35" fill="#74b983" opacity=".34"></circle>
            <rect x="210" y="42" width="110" height="72" rx="12" fill="#fff"></rect>
            <path d="M52 132c76-43 145-42 244 0" stroke="#20253d" stroke-width="6" fill="none"></path>
        </svg>
        <?php
    } elseif ($variant === 'dark') {
        ?>
        <svg viewBox="0 0 400 170">
            <rect x="60" y="46" width="86" height="88" rx="10" fill="rgba(255,255,255,.86)"></rect>
            <rect x="164" y="30" width="62" height="104" rx="10" fill="rgba(255,255,255,.72)"></rect>
            <rect x="246" y="62" width="92" height="72" rx="10" fill="rgba(255,255,255,.60)"></rect>
            <path d="M50 142h300" stroke="#ff5a28" stroke-width="7" stroke-linecap="round"></path>
        </svg>
        <?php
    } else {
        ?>
        <svg viewBox="0 0 400 170">
            <rect x="42" y="38" width="95" height="82" rx="10" fill="#fff"></rect>
            <rect x="154" y="22" width="92" height="115" rx="10" fill="#fff"></rect>
            <rect x="262" y="52" width="74" height="70" rx="10" fill="#fff"></rect>
            <path d="M24 138h344" stroke="#ff5a28" stroke-width="7" stroke-linecap="round"></path>
        </svg>
        <?php
    }
    echo '</div>';
};
?>

<div class="lp-page lp-projekty-i-pracownie">

    <?php
    $hero = $acf_fields['prop_hero_section'];
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
            </div>

            <div class="hero-panel">
                <div class="studio-mock" aria-hidden="true">
                    <?php if (!empty($hero['tool_tags']) && is_array($hero['tool_tags'])) : ?>
                        <div class="tool-tags">
                            <?php foreach ($hero['tool_tags'] as $tag) :
                                $text = is_array($tag) ? ($tag['text'] ?? '') : $tag;
                                if ($text === '') {
                                    continue;
                                }
                                ?>
                                <span><?php echo esc_html($text); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="model"></div>
                    <div class="drawing">
                        <svg viewBox="0 0 245 165">
                            <rect x="14" y="14" width="217" height="137" rx="8" fill="#f7f8fb" stroke="#20253d" stroke-width="2"></rect>
                            <path d="M38 48h78v43H38zM132 48h75v43h-75zM38 105h58v30H38zM112 105h95v30h-95z" fill="#fff" stroke="#20253d" stroke-width="2"></path>
                            <path d="M68 91v14M158 91v14M96 119h16" stroke="#F5682C" stroke-width="4"></path>
                            <circle cx="190" cy="120" r="9" fill="#74b983" opacity=".72"></circle>
                        </svg>
                    </div>
                    <?php if (!empty($hero['city_tag'])) : ?>
                        <div class="city-tag"><?php echo esc_html($hero['city_tag']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php
    $cards = $acf_fields['prop_cards_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <?php if (!empty($cards['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($cards['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($cards['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($cards['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($cards['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($cards['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($cards['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($cards['intro']); ?></p>
            <?php endif; ?>

            <?php if (!empty($cards['items']) && is_array($cards['items'])) : ?>
                <div class="lp-cards lp-cards--4">
                    <?php foreach ($cards['items'] as $item) : ?>
                        <article class="lp-card">
                            <?php if (!empty($item['label'])) : ?>
                                <span class="lp-label"><?php echo esc_html($item['label']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['title'])) : ?>
                                <h3><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['text'])) : ?>
                                <p><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['meta']) && is_array($item['meta'])) : ?>
                                <div class="lp-meta">
                                    <?php foreach ($item['meta'] as $meta_row) :
                                        $label = is_array($meta_row) ? ($meta_row['label'] ?? '') : $meta_row;
                                        if ($label === '') {
                                            continue;
                                        }
                                        ?>
                                        <span><?php echo esc_html($label); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $split = $acf_fields['prop_split_section'];
    ?>
    <section class="lp-section">
        <?php if (!empty($split['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($split['watermark']); ?></div>
        <?php endif; ?>
        <div class="container lp-split">
            <div class="lp-dark-box" <?php echo !empty($split['dark_watermark']) ? 'data-watermark="' . esc_attr($split['dark_watermark']) . '"' : ''; ?>>
                <?php if (!empty($split['dark_title'])) : ?>
                    <h2><?php echo esc_html($split['dark_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($split['dark_text'])) : ?>
                    <p><?php echo esc_html($split['dark_text']); ?></p>
                <?php endif; ?>
                <?php if (!empty($split['dark_cta_text'])) : ?>
                    <div class="cta-row">
                        <?php $lp_render_cta($split['dark_cta_text'], $split['dark_cta_url'] ?? '', 'primary'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lp-light-box">
                <?php if (!empty($split['tips_title'])) : ?>
                    <h2><?php echo esc_html($split['tips_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($split['tips_items']) && is_array($split['tips_items'])) : ?>
                    <ul class="lp-tips">
                        <?php foreach ($split['tips_items'] as $tip) :
                            $text = is_array($tip) ? ($tip['text'] ?? '') : $tip;
                            if ($text === '') {
                                continue;
                            }
                            ?>
                            <li><?php echo esc_html($text); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $gallery = $acf_fields['prop_gallery_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <?php if (!empty($gallery['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($gallery['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($gallery['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($gallery['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($gallery['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($gallery['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($gallery['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($gallery['intro']); ?></p>
            <?php endif; ?>

            <?php if (!empty($gallery['items']) && is_array($gallery['items'])) : ?>
                <div class="lp-gallery">
                    <?php foreach ($gallery['items'] as $project) : ?>
                        <article class="lp-project">
                            <?php $lp_render_gallery_thumb($project['thumb_variant'] ?? 'orange'); ?>
                            <div class="lp-project__body">
                                <?php if (!empty($project['tag'])) : ?>
                                    <span class="lp-tag"><?php echo esc_html($project['tag']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($project['title'])) : ?>
                                    <h3><?php echo esc_html($project['title']); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($project['text'])) : ?>
                                    <p><?php echo esc_html($project['text']); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $process = $acf_fields['prop_process_section'];
    ?>
    <section class="lp-section">
        <?php if (!empty($process['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($process['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($process['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($process['eyebrow']); ?></div>
            <?php endif; ?>
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
    $events = $acf_fields['prop_events_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <?php if (!empty($events['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($events['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($events['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($events['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($events['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($events['title']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($events['items']) && is_array($events['items'])) : ?>
                <div class="lp-events">
                    <?php foreach ($events['items'] as $event) : ?>
                        <article class="lp-event">
                            <?php if (!empty($event['tag'])) : ?>
                                <span class="lp-tag"><?php echo esc_html($event['tag']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($event['title'])) : ?>
                                <h3><?php echo esc_html($event['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($event['text'])) : ?>
                                <p><?php echo esc_html($event['text']); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $final = $acf_fields['prop_final_section'];
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
