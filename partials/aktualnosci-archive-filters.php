<?php
/**
 * Aktualności archive: city pills (left) + month/year (right).
 *
 * Args: archive_url, city_slug, filter_year, filter_month, search_q.
 */

$archive_url   = !empty($args['archive_url']) ? $args['archive_url'] : akademiata_get_aktualnosci_page_url();
$city_slug     = isset($args['city_slug']) ? sanitize_title((string) $args['city_slug']) : '';
$filter_year   = isset($args['filter_year']) ? (int) $args['filter_year'] : 0;
$filter_month  = isset($args['filter_month']) ? (int) $args['filter_month'] : 0;
$search_q      = isset($args['search_q']) ? (string) $args['search_q'] : '';
$month_options = akademiata_get_news_archive_month_options();
$year_options  = akademiata_get_news_archive_year_options();

$see_all_args = array();
if ($search_q !== '') {
    $see_all_args['q'] = $search_q;
}
if ($filter_year > 0) {
    $see_all_args['rok'] = $filter_year;
}
if ($filter_month > 0) {
    $see_all_args['miesiac'] = $filter_month;
}
$see_all_url = akademiata_get_aktualnosci_page_url_with_args($see_all_args);
?>
<div class="aktualnosci-archive-filters">
    <div class="aktualnosci-archive-filters__cities aktualnosci-city-filter">
        <?php
        get_template_part(
            'partials/aktualnosci',
            'header-actions',
            array(
                'current_city_slug' => $city_slug,
                'see_all_url'       => $see_all_url,
                'show_see_all'      => (bool) $city_slug,
            )
        );
        ?>
    </div>

    <form class="aktualnosci-archive-filters__date news-date-filter" method="get" action="<?php echo esc_url($archive_url); ?>">
        <?php if ($search_q !== '') : ?>
            <input type="hidden" name="q" value="<?php echo esc_attr($search_q); ?>" />
        <?php endif; ?>
        <?php if ($city_slug !== '') : ?>
            <input type="hidden" name="miasto" value="<?php echo esc_attr($city_slug); ?>" />
        <?php endif; ?>

        <label class="screen-reader-text" for="news-filter-month">
            <?php echo esc_html(akademiata_get_theme_lang_string('news_filter_month')); ?>
        </label>
        <select id="news-filter-month" name="miesiac" class="news-date-filter__select">
            <option value=""><?php echo esc_html(akademiata_get_theme_lang_string('news_filter_all_months')); ?></option>
            <?php foreach ($month_options as $num => $label) : ?>
                <option value="<?php echo (int) $num; ?>" <?php selected($filter_month, (int) $num); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="screen-reader-text" for="news-filter-year">
            <?php echo esc_html(akademiata_get_theme_lang_string('news_filter_year')); ?>
        </label>
        <select id="news-filter-year" name="rok" class="news-date-filter__select">
            <option value=""><?php echo esc_html(akademiata_get_theme_lang_string('news_filter_all_years')); ?></option>
            <?php foreach ($year_options as $year) : ?>
                <option value="<?php echo (int) $year; ?>" <?php selected($filter_year, (int) $year); ?>>
                    <?php echo (int) $year; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="news-date-filter__submit">
            <?php echo esc_html(akademiata_get_theme_lang_string('news_filter_apply')); ?>
        </button>
    </form>
</div>
