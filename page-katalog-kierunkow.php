<?php
/**
 * Template Name: Katalog kierunków
 */

get_header();

require_once get_template_directory() . '/configure/lp-defaults/katalog-kierunkow/fields.php';
require_once get_template_directory() . '/configure/lp-defaults/katalog-kierunkow/svg.php';

$acf_fields = akademiata_katalog_kierunkow_fields(get_fields());

/**
 * @param string $text
 * @param string $url
 * @param string $style primary|outline|small|small-outline
 */
$lp_render_cta = static function ($text, $url, $style = 'primary') {
    if ($text === '' || $text === null) {
        return;
    }
    $class_map = [
        'primary' => 'cta',
        'outline' => 'cta-outline',
        'small' => 'small-cta',
        'small-outline' => 'small-outline',
    ];
    $class = $class_map[$style] ?? 'cta';
    $href = $url !== '' && $url !== null ? $url : '#';
    printf(
        '<a href="%s" class="%s">%s</a>',
        esc_url($href),
        esc_attr($class),
        esc_html($text)
    );
};

/**
 * @param array<string, mixed> $course
 */
$lp_render_course_visual = static function ($course) {
    $variant = $course['visual_variant'] ?? 'arch';
    $image = $course['image'] ?? null;
    $has_image = is_array($image) && !empty($image['ID']);
    $class = 'lp-course-visual lp-course-visual--' . esc_attr($variant);
    if ($has_image) {
        $class .= ' lp-course-visual--has-image';
    }
    echo '<div class="' . esc_attr($class) . '">';
    if (!empty($course['location'])) {
        echo '<span class="lp-location">' . esc_html($course['location']) . '</span>';
    }
    if ($has_image) {
        echo wp_get_attachment_image(
            (int) $image['ID'],
            'medium_large',
            false,
            [
                'class' => 'lp-course-visual__img',
                'alt'   => esc_attr($image['alt'] ?: ($course['title'] ?? __('Kierunek', 'akademiata'))),
            ]
        );
    } else {
        echo '<span class="lp-course-visual__svg" aria-hidden="true">';
        akademiata_katalog_kierunkow_course_svg($course['visual_svg'] ?? 'arch_1');
        echo '</span>';
    }
    echo '</div>';
};
?>

<div class="lp-page lp-katalog-kierunkow">

    <?php
    $hero = $acf_fields['kato_hero_section'];
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
                <?php if (!empty($hero['stats']) && is_array($hero['stats'])) : ?>
                    <div class="quick-stats">
                        <?php foreach ($hero['stats'] as $stat) : ?>
                            <div class="stat">
                                <?php if (!empty($stat['number'])) : ?>
                                    <strong><?php echo esc_html($stat['number']); ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($stat['text'])) : ?>
                                    <span><?php echo esc_html($stat['text']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $catalog = $acf_fields['kato_catalog_section'];
    $course_count = !empty($catalog['courses']) && is_array($catalog['courses']) ? count($catalog['courses']) : 0;
    ?>
    <section class="lp-section lp-section--gray lp-catalog" id="katalog">
        <?php if (!empty($catalog['watermark'])) : ?>
            <div class="lp-watermark" aria-hidden="true"><?php echo esc_html($catalog['watermark']); ?></div>
        <?php endif; ?>
        <div class="container">
            <?php if (!empty($catalog['eyebrow'])) : ?>
                <div class="lp-eyebrow"><?php echo esc_html($catalog['eyebrow']); ?></div>
            <?php endif; ?>
            <?php if (!empty($catalog['title'])) : ?>
                <h2 class="lp-section-title"><?php echo esc_html($catalog['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($catalog['intro'])) : ?>
                <p class="lp-intro"><?php echo esc_html($catalog['intro']); ?></p>
            <?php endif; ?>

            <div class="lp-filter-panel">
                <div class="lp-filter-head">
                    <div>
                        <?php if (!empty($catalog['filter_title'])) : ?>
                            <h2><?php echo esc_html($catalog['filter_title']); ?></h2>
                        <?php endif; ?>
                        <?php if (!empty($catalog['filter_text'])) : ?>
                            <p><?php echo esc_html($catalog['filter_text']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="lp-result-count" data-suffix="<?php echo esc_attr($catalog['result_count_suffix'] ?? ''); ?>">
                        <?php echo esc_html($course_count . ' ' . ($catalog['result_count_suffix'] ?? '')); ?>
                    </div>
                </div>

                <div class="lp-filters">
                    <?php if (!empty($catalog['city_filters']) && is_array($catalog['city_filters'])) : ?>
                        <div class="lp-filter-group" data-filter-group="city">
                            <span class="lp-filter-title"><?php esc_html_e('Miasto', 'akademiata'); ?></span>
                            <?php foreach ($catalog['city_filters'] as $index => $filter) :
                                $slug = $filter['slug'] ?? '';
                                if ($slug === '') {
                                    continue;
                                }
                                $is_active = $index === 0;
                                ?>
                                <button type="button" class="lp-filter-btn<?php echo $is_active ? ' is-active' : ''; ?>" data-filter-group="city" data-filter-value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($filter['label'] ?? $slug); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($catalog['area_filters']) && is_array($catalog['area_filters'])) : ?>
                        <div class="lp-filter-group" data-filter-group="area">
                            <span class="lp-filter-title"><?php esc_html_e('Obszar', 'akademiata'); ?></span>
                            <?php foreach ($catalog['area_filters'] as $index => $filter) :
                                $slug = $filter['slug'] ?? '';
                                if ($slug === '') {
                                    continue;
                                }
                                $is_active = $index === 0;
                                ?>
                                <button type="button" class="lp-filter-btn<?php echo $is_active ? ' is-active' : ''; ?>" data-filter-group="area" data-filter-value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($filter['label'] ?? $slug); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($catalog['courses']) && is_array($catalog['courses'])) : ?>
                <div class="lp-catalog-grid">
                    <?php foreach ($catalog['courses'] as $course) : ?>
                        <article class="lp-course-card" data-city="<?php echo esc_attr($course['city'] ?? ''); ?>" data-area="<?php echo esc_attr($course['area'] ?? ''); ?>">
                            <?php $lp_render_course_visual($course); ?>
                            <div class="lp-course-body">
                                <?php if (!empty($course['course_type'])) : ?>
                                    <span class="lp-course-type"><?php echo esc_html($course['course_type']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($course['title'])) : ?>
                                    <h3><?php echo esc_html($course['title']); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($course['text'])) : ?>
                                    <p><?php echo esc_html($course['text']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($course['meta']) && is_array($course['meta'])) : ?>
                                    <div class="lp-meta">
                                        <?php foreach ($course['meta'] as $meta_row) :
                                            $label = is_array($meta_row) ? ($meta_row['label'] ?? '') : $meta_row;
                                            if ($label === '') {
                                                continue;
                                            }
                                            ?>
                                            <span><?php echo esc_html($label); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="lp-actions">
                                    <?php $lp_render_cta(__('Szczegóły', 'akademiata'), $course['details_url'] ?? '', 'small-outline'); ?>
                                    <?php $lp_render_cta(__('Zapisz się', 'akademiata'), $course['register_url'] ?? '', 'small'); ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="lp-no-results" hidden>
                <?php if (!empty($catalog['no_results_title'])) : ?>
                    <h3><?php echo esc_html($catalog['no_results_title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($catalog['no_results_text'])) : ?>
                    <p><?php echo esc_html($catalog['no_results_text']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $final = $acf_fields['kato_final_section'];
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

<script>
(function () {
    var catalog = document.querySelector('.lp-katalog-kierunkow .lp-catalog');
    if (!catalog) {
        return;
    }

    var cards = catalog.querySelectorAll('.lp-course-card');
    var buttons = catalog.querySelectorAll('.lp-filter-btn');
    var countEl = catalog.querySelector('.lp-result-count');
    var noResults = catalog.querySelector('.lp-no-results');
    var suffix = countEl ? (countEl.getAttribute('data-suffix') || '') : '';
    var filters = { city: 'all', area: 'all' };

    function updateCatalog() {
        var visible = 0;
        cards.forEach(function (card) {
            var matchCity = filters.city === 'all' || card.getAttribute('data-city') === filters.city;
            var matchArea = filters.area === 'all' || card.getAttribute('data-area') === filters.area;
            var show = matchCity && matchArea;
            card.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (countEl) {
            countEl.textContent = visible + (suffix ? ' ' + suffix : '');
        }
        if (noResults) {
            noResults.hidden = visible > 0;
        }
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var group = button.getAttribute('data-filter-group');
            var value = button.getAttribute('data-filter-value');
            if (!group || !value) {
                return;
            }

            filters[group] = value;
            catalog.querySelectorAll('.lp-filter-btn[data-filter-group="' + group + '"]').forEach(function (btn) {
                btn.classList.toggle('is-active', btn === button);
            });
            updateCatalog();
        });
    });

    updateCatalog();
})();
</script>

<?php get_footer(); ?>
