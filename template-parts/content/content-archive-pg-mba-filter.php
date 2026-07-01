<?php
/**
 * Offer-style filter layout for PG/MBA archives.
 */

$post_type = get_query_var('pg_mba_filter_post_type');
if (!$post_type) {
    $post_type = akademiata_get_pg_mba_archive_post_type();
}

$filter_title = get_query_var('pg_mba_filter_title');
if (!$filter_title) {
    $filter_title = $post_type === 'mba'
        ? __('Studia MBA', 'akademiata')
        : __('Studia Podyplomowe', 'akademiata');
}

$mobile_taxonomies = akademiata_get_pg_mba_filter_taxonomies();
$page_description  = get_query_var('pg_mba_filter_content');
?>
<div class="offer_wrapper offer_wrapper--pg-mba">
    <div class="offer_content">
        <?php
        if (has_action('akademiata_breadcrumbs')) {
            do_action('akademiata_breadcrumbs');
        } elseif (function_exists('the_breadcrumb')) {
            the_breadcrumb();
        }
        ?>

        <h1><?php echo esc_html($filter_title); ?></h1>

        <?php if (!empty($page_description)) : ?>
            <div class="page-description"><?php echo $page_description; ?></div>
        <?php endif; ?>

        <div class="mobile-taxonomy-slider">
            <?php foreach ($mobile_taxonomies as $taxonomy => $label) : ?>
                <button class="taxonomy-filter-toggle" data-tax="<?php echo esc_attr($taxonomy); ?>">
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php get_template_part('partials/tags_container'); ?>

        <div id="ajax-loader" style="display: none;">
            <div class="spinner"></div>
        </div>

        <?php get_template_part('partials/offer-view-toggle'); ?>
        <div id="filter-results" class="row filter-results--grid"></div>

        <div id="no-results-message" style="display: none; text-align: center; margin: 2rem 0;">
            <?php echo esc_html(akademiata_get_theme_lang_string('pg_mba_no_filter_results')); ?>
        </div>
    </div>

    <div id="sidebar" class="filter_col">
        <div class="mobile-filter-header">
            <button class="go-back" type="button">
                <span class="go-back__icon"></span>
                <?php
                $title_lower = mb_strtolower(mb_substr($filter_title, 0, 1)) . mb_substr($filter_title, 1);
                echo esc_html__('Filtruj', 'akademiata') . ' ' . esc_html($title_lower);
                ?>
            </button>
            <div class="filter_results_wrapper">
                <button class="filter_results" type="button">
                    <?php esc_html_e('Pokaż wyniki', 'akademiata'); ?>
                </button>
            </div>
            <button class="clear-filters" type="button"><?php esc_html_e('wyczyść filtry', 'akademiata'); ?></button>
        </div>

        <?php get_template_part('partials/tags_container'); ?>

        <div id="scroller-anchor"></div>
        <?php get_template_part('partials/filter_side_pg_mba'); ?>
    </div>

    <div class="filter-overlay"></div>
</div>
