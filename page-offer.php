<?php
/* Template Name: Offer */
get_header();
?>

<div class="offer_wrapper">
    <div class="offer_content">
        <!-- Breadcrumbs -->
        <?php the_breadcrumb(); ?>

        <!-- Page Title -->
        <h1><?php echo get_the_title(); ?></h1>

        <!-- Mobile Taxonomy Buttons (Slider) -->
        <?php
        $mobile_taxonomies = [
//                'recruitment_date' => __('Rekrutacja', 'akademiata'),
                'degree' => __('Studia', 'akademiata'),
                'city' => __('Miasto', 'akademiata'),
                'program' => __('Kierunek studiów', 'akademiata'),
                'language' => __('Język studiów', 'akademiata'),
                'duration' => __('Czas trwania', 'akademiata'),
                'obtained_title' => __('Uzyskany tytuł', 'akademiata'),
                'post_tag' => __('Zainteresowania', 'akademiata'),
                'mode' => __('Forma studiów', 'akademiata'),
                'department' => __('Wydział', 'akademiata'),
        ];


        $current_page_slug = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        ?>

        <div class="mobile-taxonomy-slider">
            <?php foreach ($mobile_taxonomies as $taxonomy => $label): ?>
                <?php
                if ($taxonomy === 'degree' && !in_array($current_page_slug, ['offer', 'oferta'])) {
                    continue;
                }
                ?>
                <button class="taxonomy-filter-toggle" data-tax="<?php echo esc_attr($taxonomy); ?>">
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>


        <!-- Tags Container -->
        <?php get_template_part('partials/tags_container'); ?>
        <div id="ajax-loader" style="display: none;">
            <div class="spinner"></div>
        </div>
        <!-- Filtered Results -->
        <div id="filter-results" class="row"></div>
        <div id="no-results-message" style="display: none; text-align: center; margin: 2rem 0;">
            <?php echo __('Nie znaleziono żadnych wyników', 'akademiata'); ?>
        </div>
    </div>

    <!-- Filter Sidebar (now includes the header!) -->
    <div id="sidebar" class="filter_col">
        <!-- Mobile Filter Header now inside sidebar -->
        <div class="mobile-filter-header">
            <button class="go-back">
                <span class="go-back__icon"></span>
                <?php
                $title = get_the_title();
                $title = mb_strtolower(mb_substr($title, 0, 1)) . mb_substr($title, 1);

                echo esc_html__('Filtruj', 'akademiata') . ' ' . esc_html($title);
                ?>
            </button>
            <div class="filter_results_wrapper">
                <button class="filter_results">
                    <?php _e('Pokaż wyniki', 'akademiata'); ?></button>
            </div>
            <button class="clear-filters"><?php _e('wyczyść filtry', 'akademiata'); ?></button>
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
