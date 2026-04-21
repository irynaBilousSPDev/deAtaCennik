<?php
get_header();

// WPML language detection
$current_lang = apply_filters('wpml_current_language', null);

// Get page by path (not deprecated) + WPML translation
$page = get_page_by_path('studia-mba', OBJECT, 'page');
$page_id = $page ? apply_filters('wpml_object_id', $page->ID, 'page', true, $current_lang) : 0;

// Load translated content
$acf_page = get_post($page_id);
$acf_content = $acf_page ? apply_filters('the_content', $acf_page->post_content) : '';
$acf_title = $acf_page ? get_the_title($acf_page->ID) : __('Studia MBA', 'akademiata');

// Override <title>
add_filter('pre_get_document_title', function () use ($acf_title) {
    return $acf_title;
});

// Override breadcrumbs
remove_action('akademiata_breadcrumbs', 'the_breadcrumb');
add_action('akademiata_breadcrumbs', function () use ($acf_title) {
    $sep = '<span class="breadcrumb-separator"> | </span>';
    echo '<div class="breadcrumbs">';
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>' . $sep;
    echo '<span>' . esc_html($acf_title) . '</span>';
    echo '</div>';
});
?>

<section class="section_studia">
    <div class="container">
        <div class="section_header text-center">
            <h1><?= esc_html($acf_title); ?></h1>
            <?php if (!empty($acf_content)) : ?>
                <div class="page-description"><?= $acf_content; ?></div>
            <?php endif; ?>
        </div>

        <?php get_template_part('template-parts/content/content-archive-pg-mba'); ?>
    </div>
</section>

<?php get_footer(); ?>
