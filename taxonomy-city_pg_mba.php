<?php
get_header();

// Detect post_type from query (via rewrite rules or URL)
global $wp_query;
$queried_post_type = $wp_query->get('post_type') ?? 'postgraduate';

if ($queried_post_type === 'mba') {
    $post_type = 'mba';
    $acf_slug  = 'studia-mba';
    $acf_title = __('Studia MBA', 'akademiata');
} elseif ($queried_post_type === 'courses') {
    $post_type = 'courses';
    $acf_slug  = 'kursy';
    $acf_title = __('Kursy', 'akademiata');
} else {
    $post_type = 'postgraduate';
    $acf_slug  = 'studia-podyplomowe';
    $acf_title = __('Studia Podyplomowe', 'akademiata');
}

// WPML: get current language
$current_lang = apply_filters('wpml_current_language', null);

// Get current term and translate it
$term = get_queried_object();
$translated_term_id = apply_filters('wpml_object_id', $term->term_id, 'city_pg_mba', false, $current_lang);
$term_obj = get_term($translated_term_id, 'city_pg_mba');
$term_name = $term_obj->name ?? $term->name;
$term_id = $translated_term_id;

// Get archive page content (translated)
$acf_query = new WP_Query([
    'post_type'      => 'page',
    'pagename'       => $acf_slug,
    'posts_per_page' => 1,
]);

if ($acf_query->have_posts()) {
    $acf_query->the_post();
    $acf_content = apply_filters('the_content', get_the_content());
    wp_reset_postdata();
} else {
    $acf_content = '';
}

// Override <title>
add_filter('pre_get_document_title', function () use ($acf_title, $term_name) {
    return $acf_title . ' – ' . $term_name;
});

// Override breadcrumbs
remove_action('akademiata_breadcrumbs', 'the_breadcrumb');
add_action('akademiata_breadcrumbs', function () use ($acf_title, $term_name, $acf_slug) {
    $sep = '<span class="breadcrumb-separator"> | </span>';
    echo '<div class="breadcrumbs">';
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>' . $sep;
    echo '<a href="' . esc_url(home_url("/{$acf_slug}/")) . '">' . esc_html($acf_title) . '</a>' . $sep;
    echo '<span>' . esc_html($term_name) . '</span>';
    echo '</div>';
});
?>

<section class="section_studia">
    <div class="container">
        <div class="section_header text-center">
            <h1><?= esc_html($acf_title . ' – ' . $term_name); ?></h1>
            <?php if (!empty($acf_content)) : ?>
                <div class="page-description"><?= $acf_content; ?></div>
            <?php endif; ?>
        </div>

        <div class="studia_cards">
            <?php
            $query = new WP_Query([
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'tax_query'      => [[
                    'taxonomy' => 'city_pg_mba',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ]],
            ]);

            if ($query->have_posts()) :
                while ($query->have_posts()) : $query->the_post();

                    $current_post_type = get_post_type();

                    if ($current_post_type === 'courses') {
                        get_template_part('partials/card_post_courses');
                    } elseif ($current_post_type === 'mba') {
                        get_template_part('partials/card_post_pg_mba');
                    } else {
                        get_template_part('partials/card_post_pg_mba');
                    }

                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>' . __('Brak wyników dla tego miasta.', 'akademiata') . '</p>';
            endif;
            ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
