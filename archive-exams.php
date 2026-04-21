<?php
/**
 * archive-exams.php
 * Archive page for CPT: exams
 * City filter is inside template-part: content-archive-exams (tabs only)
 */

get_header();

// WPML current language
$current_lang = apply_filters('wpml_current_language', null);

// Base page by slug "exams" (create a page with this slug in PL, WPML will translate it)
$base_page = get_page_by_path('egzaminy');

// Get translated ID (important for WPML)
$page_id = $base_page ? apply_filters('wpml_object_id', $base_page->ID, 'page', true, $current_lang) : 0;

// Load content + title from translated page
$acf_page    = $page_id ? get_post($page_id) : null;
$acf_content = $acf_page ? apply_filters('the_content', $acf_page->post_content) : '';
$acf_title   = $acf_page ? get_the_title($acf_page->ID) : __('Exams', 'akademiata');

// Breadcrumb override
remove_action('akademiata_breadcrumbs', 'the_breadcrumb');
add_action('akademiata_breadcrumbs', function () use ($acf_title) {
    $sep = '<span class="breadcrumb-separator"> | </span>';
    echo '<div class="breadcrumbs">';
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>' . $sep;
    echo '<span>' . esc_html($acf_title) . '</span>';
    echo '</div>';
});
?>

<section class="section_exams">
    <div class="container">
        <?php the_breadcrumb(); ?>
        <div class="section_header text-center">
            <h1><?= esc_html($acf_title); ?></h1>

            <?php if (!empty($acf_content)) : ?>
                <div class="page-description"><?= $acf_content; ?></div>
            <?php endif; ?>
        </div>

        <?php get_template_part('template-parts/content/content-archive-exams'); ?>
    </div>
</section>

<?php get_footer(); ?>
