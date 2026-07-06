<?php
/* Template Name: Offer */

$offer_filter_action = akademiata_get_offer_filter_action();
$offer_initial_limit = akademiata_offer_listing_initial_count();
$offer_form_data     = akademiata_parse_offer_filter_form_data();
$offer_initial_query = new WP_Query(
    akademiata_get_offer_listing_query_args($offer_filter_action, $offer_form_data, 0, $offer_initial_limit)
);
$offer_initial_count = (int) $offer_initial_query->post_count;
$offer_has_more      = $offer_initial_count >= $offer_initial_limit;

get_header();
?>

<div class="offer_wrapper offer_wrapper--offer-page">
    <div class="offer_content">
        <!-- Breadcrumbs -->
        <?php the_breadcrumb(); ?>

        <!-- Page Title -->
        <div class="offer_page_header">
            <h1><?php echo get_the_title(); ?></h1>
            <?php if (akademiata_should_show_ranking_perspektywy_badge()) :
                set_query_var('ranking_badge_context', 'offer-header');
                get_template_part('template-parts/single-offer/ranking-perspektywy-badge');
            endif; ?>
        </div>

        <?php get_template_part('partials/offer-mobile-toolbar'); ?>

        <?php get_template_part('partials/tags_container'); ?>

        <div id="ajax-loader" style="display: none;">
            <div class="spinner"></div>
        </div>
        <!-- Filtered Results -->
        <div id="filter-results"
             class="row filter-results--grid"
             data-initial-count="<?php echo esc_attr((string) $offer_initial_count); ?>"
             data-next-offset="<?php echo esc_attr((string) $offer_initial_count); ?>"
             data-has-more="<?php echo $offer_has_more ? '1' : '0'; ?>">
            <?php
            if ($offer_initial_query->have_posts()) :
                while ($offer_initial_query->have_posts()) :
                    $offer_initial_query->the_post();
                    get_template_part('./partials/card_post');
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
        <?php if ($offer_has_more) : ?>
            <div id="filter-load-sentinel" class="filter-load-sentinel" aria-hidden="true"></div>
        <?php endif; ?>
        <div id="no-results-message"
             style="<?php echo $offer_initial_count === 0 ? 'text-align: center; margin: 2rem 0;' : 'display: none; text-align: center; margin: 2rem 0;'; ?>">
            <?php echo esc_html(akademiata_get_theme_lang_string('offer_no_results')); ?>
        </div>
    </div>

    <?php get_template_part('partials/offer-mobile-dropdown'); ?>

    <!-- Filter Sidebar (now includes the header!) -->
    <div id="sidebar" class="filter_col">
        <!-- Mobile Filter Header now inside sidebar -->
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

        <!-- Tags Container -->
        <?php get_template_part('partials/tags_container'); ?>

        <div id="scroller-anchor"></div>
        <?php get_template_part('partials/filter_side'); ?>
    </div>

    <!-- Overlay for mobile close -->
    <div class="filter-overlay"></div>
</div>

<?php get_footer(); ?>
