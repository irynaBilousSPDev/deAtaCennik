<?php
get_header();

$current_lang = apply_filters('wpml_current_language', null);

$base_page = get_page_by_path('studia-podyplomowe');
$page_id     = $base_page ? apply_filters('wpml_object_id', $base_page->ID, 'page', true, $current_lang) : 0;

$acf_page    = get_post($page_id);
$acf_content = $acf_page ? apply_filters('the_content', $acf_page->post_content) : '';
$acf_title   = $acf_page ? get_the_title($acf_page->ID) : __('Studia Podyplomowe', 'akademiata');

remove_action('akademiata_breadcrumbs', 'the_breadcrumb');
add_action('akademiata_breadcrumbs', function () use ($acf_title) {
    $sep = '<span class="breadcrumb-separator"> | </span>';
    echo '<div class="breadcrumbs">';
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>' . $sep;
    echo '<span>' . esc_html($acf_title) . '</span>';
    echo '</div>';
});

set_query_var('pg_mba_filter_post_type', 'postgraduate');
set_query_var('pg_mba_filter_title', $acf_title);
set_query_var('pg_mba_filter_content', $acf_content);
?>

<div class="page_offer page_offer--pg-mba">
    <?php get_template_part('template-parts/content/content-archive-pg-mba-filter'); ?>
</div>

<?php get_footer(); ?>
