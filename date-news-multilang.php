<?php
/* Template: Date archive for language-based NEWS bases */
get_header();

$year     = (int) get_query_var('year');
$monthnum = (int) get_query_var('monthnum');
$day      = (int) get_query_var('day');
$paged    = max(1, (int) get_query_var('paged'));


if ($day && $year && $monthnum) {
    $archive_title = date_i18n('j F Y', mktime(0,0,0,$monthnum,$day,$year));
} elseif ($monthnum && $year) {
    $archive_title = date_i18n('F Y', mktime(0,0,0,$monthnum,1,$year));
} elseif ($year) {
    $archive_title = (string) $year;
} else {
    $archive_title = __('Archiwum', 'akademiata');
}

// Current language
$lang = function_exists('apply_filters') ? apply_filters('wpml_current_language', null) : '';
if (!$lang) { $lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'pl'; }


$bases = [
    'pl' => 'aktualnosci',
    'en' => 'news',
    'uk' => 'novyny',
    'ru' => 'novosti',
];
$base_slug = isset($bases[$lang]) ? $bases[$lang] : 'news';

// Category "aktualnosci" translated ID (WPML-safe)
$translated_term_id = 0;
$base_term = get_term_by('slug', 'aktualnosci', 'category');
if ($base_term && !is_wp_error($base_term)) {
    $translated_term_id = (int) $base_term->term_id;
    if (function_exists('icl_object_id')) {
        $maybe = icl_object_id($translated_term_id, 'category', true, $lang);
        if ($maybe) $translated_term_id = (int) $maybe;
    }
}

// Query
$args = [
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'cat'                 => $translated_term_id,
    'paged'               => $paged,
    'ignore_sticky_posts' => true,
    'date_query'          => [
        array_filter([
            'year'  => $year ?: null,
            'month' => $monthnum ?: null,
            'day'   => $day ?: null,
        ])
    ],
];
$query = new WP_Query($args);


$request_path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';

$request_path = preg_replace('~\/page\/\d+\/?$~', '/', $request_path);


$request_path = '/' . ltrim($request_path, '/');
$request_path = user_trailingslashit($request_path);


$lang_home = function_exists('apply_filters')
    ? apply_filters('wpml_home_url', home_url('/'), $lang)
    : home_url('/');


$base_url = rtrim($lang_home, '/') . $request_path;

// Final base with placeholder
$pagination_base = trailingslashit($base_url) . 'page/%#%/';

$archive_class_mod = 'date_' . ($year ?: 'na') . '_' . ($monthnum ?: 'na') . '_' . ($day ?: 'na');
?>
<div class="news_archive date_archive <?php echo esc_attr($archive_class_mod); ?>">
    <div class="container">
        <?php if ( function_exists('the_breadcrumb') ) { the_breadcrumb(); } ?>

        <h1 class="mb-5"><?php echo esc_html($archive_title); ?></h1>

        <?php if ($query->have_posts()) : ?>
            <div class="posts_wrapper_news">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php
                    $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                    $style = $thumb ? ' style="background-image: url(' . esc_url($thumb) . ');"' : '';
                    ?>
                    <div <?php post_class('post_news'); ?>>
                        <div class="post-image"<?php echo $style; ?>></div>
                        <div class="post-content">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        </div>
                        <a class="post-button" href="<?php the_permalink(); ?>" aria-label="<?php esc_attr_e('Read more', 'akademiata'); ?>"></a>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <?php

            if ($query->max_num_pages > 1) {
                echo paginate_links([
                    'total'     => (int) $query->max_num_pages,
                    'current'   => $paged,
                    'mid_size'  => 2,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'base'      => $pagination_base,
                    'format'    => '', // already in base
                ]);
            }
            ?>

        <?php else : ?>
            <p><?php _e('Nie znalezionо żadnych wyników', 'akademiata'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
