<?php
/**
 * Enqueue scripts.
 */
function akademiata_enqueue_scripts()
{
    $theme_dir = get_template_directory_uri();

    // Needed by prices calculator + bootstrap plugins.
    wp_enqueue_script('jquery');

    // Fix: correct URL
    wp_enqueue_script(
        'bootstrap-script',
        'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js',
        array('jquery'),
        null,
        true
    );

    wp_enqueue_script(
        'poper',
        'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js',
        array('jquery'),
        null,
        true
    );

    // Optional: add vendors if using splitChunks
    wp_enqueue_script(
        'vendors-js',
        $theme_dir . '/assets/dist/js/vendors.js',
        array(),
        null,
        true
    );

    // Use filemtime for cache busting
    $main_js_path = get_template_directory() . '/assets/dist/js/main.js';
    $main_js_ver  = file_exists($main_js_path) ? filemtime($main_js_path) : null;

    wp_enqueue_script(
        'name-main-js',
        $theme_dir . '/assets/dist/js/main.js',
        array('vendors-js', 'jquery'),
        $main_js_ver,
        true
    );

    // Prices page: pass Google Sheets endpoint to JS.
    if (is_page_template('page-template-prices.php')) {
        wp_localize_script('name-main-js', 'akademiataPrices', [
            'googleApiUrl' => 'https://script.google.com/macros/s/AKfycby89Mt7UgeY6jKnq2YQNwumt_CBp46UVd1mbKvxqEkg_46vjGAeN-8lcL_OokQVFnAW/exec',
        ]);
    }
}
add_action('wp_enqueue_scripts', 'akademiata_enqueue_scripts', 100);


/**
 * Enqueue styles.
 */
function akademiata_enqueue_styles()
{
    // Get the theme directory URL
    $theme_dir = get_template_directory_uri();

    // Dequeue unnecessary default WordPress styles
    $styles_to_dequeue = array(
        'wp-block-library',          // Core Gutenberg block library
        'wp-block-library-theme',    // Gutenberg block theme styles
        'wc-block-style',            // WooCommerce block styles
        'global-styles',             // Global styles from WordPress
        'classic-theme-styles',      // Classic theme styles
    );
    foreach ($styles_to_dequeue as $style) {
        wp_dequeue_style($style);
    }
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css');

    // Enqueue Adobe Typekit Fonts
    wp_enqueue_style(
        'adobe-fonts',
        'https://use.typekit.net/dic8cvr.css',
        array(),
        null
    );

    // Enqueue the main stylesheet
    wp_enqueue_style(
        'name-main-css',
        $theme_dir . '/assets/dist/css/main.css',
        array(), // No dependencies
        null, // No versioning (use null or version from filemtime for cache-busting)
        'all'
    );

}

add_action('wp_enqueue_scripts', 'akademiata_enqueue_styles');
