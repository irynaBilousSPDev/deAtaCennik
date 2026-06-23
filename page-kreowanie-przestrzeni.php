<?php
/**
 * Template Name: Kreowanie przestrzeni
 */

get_header();

$acf_fields = get_fields() ?: [];

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
?>

<div class="lp-page lp-kreowanie-przestrzeni">

    <?php
    $hero = $acf_fields['kreo_hero_section'] ?? [];
    if (!empty($hero)) :
        ?>
        <section class="hero">
            <?php if (!empty($hero['watermark'])) : ?>
                <div class="watermark" aria-hidden="true"><?php echo esc_html($hero['watermark']); ?></div>
            <?php endif; ?>
            <div class="container grid">
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

                    <?php if (!empty($hero['chips']) && is_array($hero['chips'])) : ?>
                        <div class="chips">
                            <?php foreach ($hero['chips'] as $chip) :
                                $chip_class = !empty($chip['is_orange']) ? 'chip orange' : 'chip';
                                ?>
                                <span class="<?php echo esc_attr($chip_class); ?>"><?php echo esc_html($chip['text'] ?? ''); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="visual">
                    <?php
                    $city_window_image = $hero['city_window_image'] ?? null;
                    $has_city_window_image = is_array($city_window_image) && !empty($city_window_image['ID']);
                    ?>
                    <div class="visual-card">
                        <div class="<?php echo esc_attr('city-window' . ($has_city_window_image ? ' city-window--has-image' : '')); ?>"<?php echo $has_city_window_image ? '' : ' aria-hidden="true"'; ?>>
                            <?php if ($has_city_window_image) : ?>
                                <?php
                                echo wp_get_attachment_image(
                                    (int) $city_window_image['ID'],
                                    'large',
                                    false,
                                    [
                                        'class' => 'city-window__img',
                                        'alt'   => esc_attr($city_window_image['alt'] ?: __('Kreowanie przestrzeni', 'akademiata')),
                                    ]
                                );
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="building" aria-hidden="true"></div>
                        <div class="floorplan" aria-hidden="true">
                            <svg viewBox="0 0 340 224">
                                <rect x="18" y="18" width="304" height="188" rx="10" fill="#f7f8fb" stroke="#20253d" stroke-width="2"></rect>
                                <path d="M52 52h92v58H52zM164 52h124v58H164zM52 130h70v46H52zM142 130h146v46H142z" fill="#fff" stroke="#20253d" stroke-width="2"></path>
                                <path d="M88 110v20M218 110v20M122 153h20" stroke="#F5682C" stroke-width="4"></path>
                                <circle cx="260" cy="150" r="12" fill="#7cbf88" opacity=".65"></circle>
                                <path d="M38 194h264" stroke="#0F83FF" stroke-width="4" stroke-linecap="round"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="dots" aria-hidden="true"></div>
                    <?php if (!empty($hero['floating_title']) || !empty($hero['floating_text'])) : ?>
                        <div class="floating">
                            <?php if (!empty($hero['floating_title'])) : ?>
                                <h2><?php echo esc_html($hero['floating_title']); ?></h2>
                            <?php endif; ?>
                            <?php if (!empty($hero['floating_text'])) : ?>
                                <p><?php echo esc_html($hero['floating_text']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($hero['city_tag'])) : ?>
                        <div class="city-tag"><?php echo esc_html($hero['city_tag']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $cards = $acf_fields['kreo_cards_section'] ?? [];
    if (!empty($cards)) :
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
                    <div class="lp-cards">
                        <?php foreach ($cards['items'] as $item) : ?>
                            <article class="lp-card">
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
    <?php endif; ?>

    <?php
    $split = $acf_fields['kreo_split_section'] ?? [];
    if (!empty($split)) :
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

                <div class="lp-list-box">
                    <?php if (!empty($split['list_title'])) : ?>
                        <h2 class="lp-section-title"><?php echo esc_html($split['list_title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($split['list_items']) && is_array($split['list_items'])) : ?>
                        <ul class="lp-list">
                            <?php foreach ($split['list_items'] as $list_item) :
                                $text = is_array($list_item) ? ($list_item['text'] ?? '') : $list_item;
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
    <?php endif; ?>

    <?php
    $proof = $acf_fields['kreo_proof_section'] ?? [];
    if (!empty($proof)) :
        ?>
        <section class="lp-section lp-section--gray">
            <?php if (!empty($proof['watermark'])) : ?>
                <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($proof['watermark']); ?></div>
            <?php endif; ?>
            <div class="container">
                <?php if (!empty($proof['eyebrow'])) : ?>
                    <div class="lp-eyebrow"><?php echo esc_html($proof['eyebrow']); ?></div>
                <?php endif; ?>
                <?php if (!empty($proof['title'])) : ?>
                    <h2 class="lp-section-title"><?php echo esc_html($proof['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($proof['intro'])) : ?>
                    <p class="lp-intro"><?php echo esc_html($proof['intro']); ?></p>
                <?php endif; ?>

                <?php if (!empty($proof['items']) && is_array($proof['items'])) : ?>
                    <div class="lp-proof-grid">
                        <?php foreach ($proof['items'] as $item) : ?>
                            <div class="lp-proof">
                                <?php if (!empty($item['number'])) : ?>
                                    <div class="lp-proof__num"><?php echo esc_html($item['number']); ?></div>
                                <?php endif; ?>
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
    <?php endif; ?>

    <?php
    $events = $acf_fields['kreo_events_section'] ?? [];
    if (!empty($events)) :
        ?>
        <section class="lp-section">
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
                <?php if (!empty($events['intro'])) : ?>
                    <p class="lp-intro"><?php echo esc_html($events['intro']); ?></p>
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
    <?php endif; ?>

    <?php
    $quiz = $acf_fields['kreo_quiz_section'] ?? [];
    if (!empty($quiz)) :
        ?>
        <section class="lp-section lp-section--gray">
            <div class="container">
                <div class="lp-quiz">
                    <div>
                        <?php if (!empty($quiz['eyebrow'])) : ?>
                            <div class="lp-eyebrow"><?php echo esc_html($quiz['eyebrow']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($quiz['title'])) : ?>
                            <h2><?php echo esc_html($quiz['title']); ?></h2>
                        <?php endif; ?>
                        <?php if (!empty($quiz['text'])) : ?>
                            <p><?php echo esc_html($quiz['text']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quiz['cta_primary_text']) || !empty($quiz['cta_secondary_text'])) : ?>
                            <div class="cta-row">
                                <?php
                                $lp_render_cta($quiz['cta_primary_text'] ?? '', $quiz['cta_primary_url'] ?? '', 'primary');
                                $lp_render_cta($quiz['cta_secondary_text'] ?? '', $quiz['cta_secondary_url'] ?? '', 'outline');
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="lp-quiz-card">
                        <?php if (!empty($quiz['card_title'])) : ?>
                            <h3><?php echo esc_html($quiz['card_title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($quiz['card_points']) && is_array($quiz['card_points'])) : ?>
                            <ol>
                                <?php foreach ($quiz['card_points'] as $point) :
                                    $text = is_array($point) ? ($point['text'] ?? '') : $point;
                                    if ($text === '') {
                                        continue;
                                    }
                                    ?>
                                    <li><?php echo esc_html($text); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $final = $acf_fields['kreo_final_section'] ?? [];
    if (!empty($final)) :
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
    <?php endif; ?>

</div>

<?php get_footer(); ?>
