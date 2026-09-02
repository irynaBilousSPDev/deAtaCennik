<?php
/**
 * Offer-style filter layout for PG/MBA archives (separate templates from page-offer.php).
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

$page_description = get_query_var('pg_mba_filter_content');

$pg_mba_form_data     = akademiata_parse_pg_mba_filter_form_data();
$pg_mba_initial_query = new WP_Query(
    akademiata_get_pg_mba_listing_query_args($post_type, $pg_mba_form_data)
);
$pg_mba_initial_count = (int) $pg_mba_initial_query->post_count;
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

        <div class="offer_page_header">
            <h1><?php echo esc_html($filter_title); ?></h1>
        </div>

        <?php if (!empty($page_description)) : ?>
            <div class="page-description"><?php echo $page_description; ?></div>
        <?php endif; ?>

        <?php get_template_part('partials/pg-mba-mobile-toolbar'); ?>

        <div id="ajax-loader" style="display: none;">
            <div class="spinner"></div>
        </div>

        <div id="filter-results"
             class="row filter-results--grid"
             data-initial-count="<?php echo esc_attr((string) $pg_mba_initial_count); ?>">
            <?php
            if ($pg_mba_initial_query->have_posts()) :
                while ($pg_mba_initial_query->have_posts()) :
                    $pg_mba_initial_query->the_post();
                    get_template_part('partials/card_post_pg_mba');
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>

        <div id="no-results-message"
             style="<?php echo $pg_mba_initial_count === 0 ? 'text-align: center; margin: 2rem 0;' : 'display: none; text-align: center; margin: 2rem 0;'; ?>">
            <?php echo esc_html(akademiata_get_theme_lang_string('pg_mba_no_filter_results')); ?>
        </div>
    </div>

    <?php get_template_part('partials/pg-mba-mobile-dropdown'); ?>

    <div id="sidebar" class="filter_col">
        <div class="mobile-filter-header">
            <button class="go-back" type="button" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_filter_back')); ?>">
                <span class="go-back__icon"></span>
            </button>
            <h2 class="mobile-filter-header__title"><?php echo esc_html(akademiata_get_theme_lang_string('offer_filter_panel_title')); ?></h2>
            <button type="button" class="clear-filters"><?php echo esc_html(akademiata_get_theme_lang_string('offer_clear_filters')); ?></button>
        </div>
        <div class="filter_results_wrapper">
            <button class="filter_results" type="button">
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_show_results')); ?>
            </button>
        </div>

        <?php get_template_part('partials/tags_container'); ?>

        <div id="scroller-anchor"></div>
        <?php get_template_part('partials/filter_side_pg_mba'); ?>
    </div>

    <div class="filter-overlay"></div>
</div>
