<?php
/**
 * Active filter pills, reset link, results count (below panel).
 *
 * Args: city_slug, active_city_term, filter_year, filter_month, search_q, found_posts.
 */

$city_slug        = isset($args['city_slug']) ? sanitize_title((string) $args['city_slug']) : '';
$active_city_term = !empty($args['active_city_term']) && $args['active_city_term'] instanceof WP_Term
    ? $args['active_city_term']
    : null;
$filter_year      = isset($args['filter_year']) ? (int) $args['filter_year'] : 0;
$filter_month     = isset($args['filter_month']) ? (int) $args['filter_month'] : 0;
$search_q         = isset($args['search_q']) ? (string) $args['search_q'] : '';
$found_posts      = isset($args['found_posts']) ? (int) $args['found_posts'] : 0;

$has_city_filter = ($city_slug !== '');
$has_date_filter = ($filter_year > 0 || $filter_month > 0);
$has_search      = ($search_q !== '');
$has_any_filter  = ($has_city_filter || $has_date_filter || $has_search);

$date_label = '';
if ($filter_month > 0 && isset(akademiata_get_news_archive_month_options()[ $filter_month ])) {
    $date_label = akademiata_get_news_archive_month_options()[ $filter_month ];
    if ($filter_year > 0) {
        $date_label .= ' ' . $filter_year;
    }
} elseif ($filter_year > 0) {
    $date_label = (string) $filter_year;
}

$remove_city_url = akademiata_get_news_archive_url_without_filters(array('miasto'));
$remove_date_url = akademiata_get_news_archive_url_without_filters(array('rok', 'miesiac'));
$remove_search_url = akademiata_get_news_archive_url_without_filters(array('q'));
$clear_all_url = akademiata_get_aktualnosci_page_url();
?>
<div class="news-archive-status">
    <?php if ($has_any_filter) : ?>
        <div class="news-archive-status__tags">
            <?php if ($has_city_filter) : ?>
                <?php
                $filter_city_label = $active_city_term
                    ? akademiata_get_news_city_display_name($active_city_term)
                    : akademiata_get_post_news_city_label(0);
                ?>
                <a class="news-archive-filter-tag" href="<?php echo esc_url($remove_city_url); ?>">
                    <span><?php echo esc_html(sprintf(akademiata_get_theme_lang_string('news_active_filter'), $filter_city_label)); ?></span>
                    <span class="news-archive-filter-tag__remove" aria-hidden="true"></span>
                </a>
            <?php endif; ?>

            <?php if ($has_date_filter && $date_label !== '') : ?>
                <a class="news-archive-filter-tag news-archive-filter-tag--date" href="<?php echo esc_url($remove_date_url); ?>">
                    <span class="news-archive-filter-tag__calendar" aria-hidden="true"></span>
                    <span><?php echo esc_html($date_label); ?></span>
                    <span class="news-archive-filter-tag__remove" aria-hidden="true"></span>
                </a>
            <?php endif; ?>

            <?php if ($has_search) : ?>
                <a class="news-archive-filter-tag" href="<?php echo esc_url($remove_search_url); ?>">
                    <span><?php echo esc_html(sprintf(akademiata_get_theme_lang_string('news_results_for'), $search_q)); ?></span>
                    <span class="news-archive-filter-tag__remove" aria-hidden="true"></span>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="news-archive-status__meta">
        <?php if ($has_any_filter) : ?>
            <a class="news-archive-status__reset" href="<?php echo esc_url($clear_all_url); ?>">
                <span class="news-archive-status__reset-icon" aria-hidden="true"></span>
                <?php echo esc_html(akademiata_get_theme_lang_string('news_clear_all_filters')); ?>
            </a>
            <span class="news-archive-status__divider" aria-hidden="true"></span>
        <?php endif; ?>
        <p class="news-archive-status__count">
            <?php echo esc_html(sprintf(akademiata_get_theme_lang_string('news_results_count'), $found_posts)); ?>
        </p>
    </div>
</div>
