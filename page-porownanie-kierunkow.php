<?php
/**
 * Template Name: Porównanie kierunków
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/porownanie-kierunkow/fields.php';

$acf_fields = akademiata_porownanie_kierunkow_fields(get_fields());

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

$lp_render_table_cell = static function ($html) {
    if ($html === '' || $html === null) {
        return;
    }
    echo wp_kses(
        $html,
        [
            'span' => ['class' => []],
        ]
    );
};
?>

<div class="lp-page lp-porownanie-kierunkow">

    <?php
    $hero = $acf_fields['poro_hero_section'];
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
                <?php if (!empty($hero['panel_title'])) : ?>
                    <h2><?php echo esc_html($hero['panel_title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($hero['panel_rows']) && is_array($hero['panel_rows'])) : ?>
                    <div class="mini-compare">
                        <?php foreach ($hero['panel_rows'] as $row) : ?>
                            <div class="mini-row">
                                <?php if (!empty($row['title'])) : ?>
                                    <strong><?php echo esc_html($row['title']); ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($row['tag'])) : ?>
                                    <span><?php echo esc_html($row['tag']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $cards = $acf_fields['poro_cards_section'];
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
    $table = $acf_fields['poro_table_section'];
    ?>
    <section class="lp-section">
        <?php if (!empty($table['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($table['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($table['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($table['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($table['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($table['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($table['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($table['intro']); ?></p>
            <?php endif; ?>

            <?php if (!empty($table['rows']) && is_array($table['rows'])) : ?>
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html($table['header_col_1'] ?? ''); ?></th>
                                <th><?php echo esc_html($table['header_col_2'] ?? ''); ?></th>
                                <th><?php echo esc_html($table['header_col_3'] ?? ''); ?></th>
                                <th><?php echo esc_html($table['header_col_4'] ?? ''); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table['rows'] as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html($row['label'] ?? ''); ?></td>
                                    <td><?php $lp_render_table_cell($row['col_2'] ?? ''); ?></td>
                                    <td><?php $lp_render_table_cell($row['col_3'] ?? ''); ?></td>
                                    <td><?php $lp_render_table_cell($row['col_4'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($table['cta_text'])) : ?>
                <div class="cta-row cta-row--center">
                    <?php $lp_render_cta($table['cta_text'], $table['cta_url'] ?? '', 'primary'); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $decision = $acf_fields['poro_decision_section'];
    ?>
    <section class="lp-section lp-section--gray">
        <?php if (!empty($decision['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($decision['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($decision['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($decision['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($decision['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($decision['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($decision['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($decision['intro']); ?></p>
            <?php endif; ?>

            <?php if (!empty($decision['items']) && is_array($decision['items'])) : ?>
                <div class="lp-decision">
                    <?php foreach ($decision['items'] as $item) : ?>
                        <div class="lp-decision-card">
                            <?php if (!empty($item['title'])) : ?>
                                <h3><?php echo esc_html($item['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['points']) && is_array($item['points'])) : ?>
                                <ul>
                                    <?php foreach ($item['points'] as $point) :
                                        $text = is_array($point) ? ($point['text'] ?? '') : $point;
                                        if ($text === '') {
                                            continue;
                                        }
                                        ?>
                                        <li><?php echo esc_html($text); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $split = $acf_fields['poro_split_section'];
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
    $final = $acf_fields['poro_final_section'];
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
