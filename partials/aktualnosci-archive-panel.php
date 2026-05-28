<?php
/**
 * Aktualności archive: cities + search + collapsible date filters.
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
$date_form_id  = 'aktualnosci-date-filter';
$panel_open    = ($filter_year > 0 || $filter_month > 0);

$preserve_query_args = array();
if ($search_q !== '') {
    $preserve_query_args['q'] = $search_q;
}
if ($filter_year > 0) {
    $preserve_query_args['rok'] = $filter_year;
}
if ($filter_month > 0) {
    $preserve_query_args['miesiac'] = $filter_month;
}

$see_all_url = akademiata_get_aktualnosci_page_url_with_args($preserve_query_args);

$clear_search_args = array();
if ($city_slug !== '') {
    $clear_search_args['miasto'] = $city_slug;
}
if ($filter_year > 0) {
    $clear_search_args['rok'] = $filter_year;
}
if ($filter_month > 0) {
    $clear_search_args['miesiac'] = $filter_month;
}

$clear_date_url = akademiata_get_news_archive_url_without_filters(array('rok', 'miesiac'));
?>
<div class="aktualnosci-archive-panel" data-news-archive-panel>
    <div class="aktualnosci-archive-panel__top">
        <div class="aktualnosci-archive-panel__cities">
            <?php
            get_template_part(
                'partials/aktualnosci',
                'header-actions',
                array(
                    'current_city_slug'   => $city_slug,
                    'see_all_url'         => $see_all_url,
                    'show_see_all'        => (bool) $city_slug,
                    'preserve_query_args' => $preserve_query_args,
                    'variant'             => 'archive-tabs',
                )
            );
            ?>
        </div>

        <div class="aktualnosci-archive-panel__actions">
            <form class="news-search" method="get" action="<?php echo esc_url($archive_url); ?>">
                <div class="news-search__field">
                    <span class="news-search__icon" aria-hidden="true"></span>
                    <input
                        id="news-search-input"
                        type="search"
                        name="q"
                        value="<?php echo esc_attr($search_q); ?>"
                        aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('news_search_placeholder')); ?>"
                        placeholder="<?php echo esc_attr(akademiata_get_theme_lang_string('news_search_placeholder')); ?>"
                    />
                </div>
                <button type="submit" class="news-search__submit">
                    <?php echo esc_html(akademiata_get_theme_lang_string('news_search_submit')); ?>
                </button>
                <?php if ($city_slug !== '') : ?>
                    <input type="hidden" name="miasto" value="<?php echo esc_attr($city_slug); ?>" />
                <?php endif; ?>
                <?php if ($filter_year > 0) : ?>
                    <input type="hidden" name="rok" value="<?php echo (int) $filter_year; ?>" />
                <?php endif; ?>
                <?php if ($filter_month > 0) : ?>
                    <input type="hidden" name="miesiac" value="<?php echo (int) $filter_month; ?>" />
                <?php endif; ?>
            </form>

            <button
                type="button"
                class="news-filters-toggle<?php echo $panel_open ? ' is-open' : ''; ?>"
                aria-expanded="<?php echo $panel_open ? 'true' : 'false'; ?>"
                aria-controls="aktualnosci-archive-more-filters"
                data-news-filters-toggle
                data-label-open="<?php echo esc_attr(akademiata_get_theme_lang_string('news_less_filters')); ?>"
                data-label-closed="<?php echo esc_attr(akademiata_get_theme_lang_string('news_more_filters')); ?>"
            >
                <span><?php echo esc_html($panel_open ? akademiata_get_theme_lang_string('news_less_filters') : akademiata_get_theme_lang_string('news_more_filters')); ?></span>
                <span class="news-filters-toggle__chevron" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <div
        id="aktualnosci-archive-more-filters"
        class="aktualnosci-archive-panel__more<?php echo $panel_open ? ' is-open' : ''; ?>"
        data-news-filters-panel
        <?php echo $panel_open ? '' : ' hidden'; ?>
    >
        <form id="<?php echo esc_attr($date_form_id); ?>" class="news-date-filter" method="get" action="<?php echo esc_url($archive_url); ?>" data-news-date-filter>
            <?php if ($search_q !== '') : ?>
                <input type="hidden" name="q" value="<?php echo esc_attr($search_q); ?>" />
            <?php endif; ?>
            <?php if ($city_slug !== '') : ?>
                <input type="hidden" name="miasto" value="<?php echo esc_attr($city_slug); ?>" />
            <?php endif; ?>

            <div class="news-date-filter__field">
                <label class="news-date-filter__label" for="news-filter-month">
                    <?php echo esc_html(akademiata_get_theme_lang_string('news_filter_month')); ?>
                </label>
                <div class="news-date-filter__control">
                    <select id="news-filter-month" name="miesiac" class="news-date-filter__select">
                        <option value=""><?php echo esc_html(akademiata_get_theme_lang_string('news_filter_all_months')); ?></option>
                        <?php foreach ($month_options as $num => $label) : ?>
                            <option value="<?php echo (int) $num; ?>" <?php selected($filter_month, (int) $num); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="news-date-filter__chevron" aria-hidden="true"></span>
                </div>
            </div>

            <div class="news-date-filter__field">
                <label class="news-date-filter__label" for="news-filter-year">
                    <?php echo esc_html(akademiata_get_theme_lang_string('news_filter_year')); ?>
                </label>
                <div class="news-date-filter__control">
                    <select id="news-filter-year" name="rok" class="news-date-filter__select">
                        <option value=""><?php echo esc_html(akademiata_get_theme_lang_string('news_filter_all_years')); ?></option>
                        <?php foreach ($year_options as $year) : ?>
                            <option value="<?php echo (int) $year; ?>" <?php selected($filter_year, (int) $year); ?>>
                                <?php echo (int) $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="news-date-filter__chevron" aria-hidden="true"></span>
                </div>
            </div>
        </form>
    </div>
</div>
