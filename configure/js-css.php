<?php
/**
 * Enqueue scripts.
 */
function akademiata_enqueue_scripts()
{
    $theme_dir = get_template_directory_uri();

    wp_enqueue_script('jquery');

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

    $vendors_js_path = get_template_directory() . '/assets/dist/js/vendors.js';
    $vendors_js_ver  = file_exists($vendors_js_path) ? filemtime($vendors_js_path) : null;

    wp_enqueue_script(
        'vendors-js',
        $theme_dir . '/assets/dist/js/vendors.js',
        array(),
        $vendors_js_ver,
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

    // Prices calculator: pass Google Sheets endpoint to JS (Prices page + single offers).
    if (is_page_template('page-template-prices.php') || is_singular(['bachelor', 'master'])) {
        wp_localize_script('name-main-js', 'akademiataPrices', [
            'googleApiUrl' => 'https://script.google.com/macros/s/AKfycby89Mt7UgeY6jKnq2YQNwumt_CBp46UVd1mbKvxqEkg_46vjGAeN-8lcL_OokQVFnAW/exec',
        ]);
    }

    $offer_localize = [
        'favoriteAdd'    => akademiata_get_theme_lang_string('offer_favorite_add'),
        'favoriteRemove' => akademiata_get_theme_lang_string('offer_favorite_remove'),
        'chipFavorites'  => akademiata_get_theme_lang_string('offer_chip_favorites'),
        'favoritesScope' => akademiata_get_offer_favorites_scope(),
    ];

    if (is_page_template('page-offer.php')) {
        $offer_localize['filterNoOptions'] = akademiata_get_theme_lang_string('offer_filter_no_options');
        $offer_localize['promoActive'] = akademiata_get_theme_lang_string('offer_promo_active');
        $offer_localize['promoUnavailable'] = akademiata_get_theme_lang_string('offer_promo_unavailable');
        $offer_localize['promoExpand'] = akademiata_get_theme_lang_string('offer_promo_expand');
    }

    wp_localize_script('name-main-js', 'akademiataOffer', $offer_localize);

    wp_localize_script('name-main-js', 'akademiataRecruitment', array(
        'restUrl' => rest_url('akademiata/v1/recruitment-closed'),
    ));

    wp_localize_script('name-main-js', 'akademiataYouTube', [
        'proxyUrl' => akademiata_youtube_proxy_url(),
        'nonce'    => wp_create_nonce('wp_rest'),
    ]);

    if (is_page_template('page-rankingi.php')) {
        $lp_rankingi_js_path = get_template_directory() . '/assets/dist/js/lpRankingi.js';
        $lp_rankingi_js_ver  = file_exists($lp_rankingi_js_path) ? filemtime($lp_rankingi_js_path) : null;

        wp_enqueue_script(
            'akademiata-lp-rankingi',
            $theme_dir . '/assets/dist/js/lpRankingi.js',
            array(),
            $lp_rankingi_js_ver,
            true
        );
    }

    if (is_front_page()) {
        $home_decision_js_path = get_template_directory() . '/assets/dist/js/homeDecisionToday.js';
        $home_decision_js_ver  = file_exists($home_decision_js_path) ? filemtime($home_decision_js_path) : null;

        wp_enqueue_script(
            'akademiata-home-decision-today',
            $theme_dir . '/assets/dist/js/homeDecisionToday.js',
            array(),
            $home_decision_js_ver,
            true
        );
    }

    // Smooth anchor scrolling for Podcast ATA (some browsers/themes ignore CSS scroll-behavior).
    if (is_singular('podcast-ata')) {
        wp_add_inline_script(
            'name-main-js',
            "(function(){function s(sel){var el=document.querySelector(sel);if(!el)return;el.scrollIntoView({behavior:'smooth',block:'start'});}document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a[href^=\"#\"]'):null;if(!a)return;var href=a.getAttribute('href');if(!href||href==='#')return;try{var id=decodeURIComponent(href);}catch(_e){var id=href;}if(id==='#o-czym'||id==='#goscie'||id==='#zapisz'){e.preventDefault();s(id);}});})();",
            'after'
        );
    }

    if (is_singular('webinary')) {
        wp_add_inline_script(
            'name-main-js',
            "(function(){document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a[href=\"#zapisy\"]'):null;if(!a)return;if(!document.body.classList.contains('single-webinary'))return;var el=document.getElementById('zapisy');if(!el)return;e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'});});function btn(f){return f&&f.querySelector?f.querySelector('.wpcf7-submit'):null;}function busy(f,on){var b=btn(f);if(!b)return;if(on){if(!b.getAttribute('data-web-label'))b.setAttribute('data-web-label',b.value);b.value='Wysyłanie…';b.disabled=true;}else{b.disabled=false;b.value=b.getAttribute('data-web-label')||'Zapisz się na webinar';}}document.addEventListener('wpcf7beforesubmit',function(ev){busy(ev.target,true);});document.addEventListener('submit',function(ev){var f=ev.target;if(!f||!f.classList||!f.classList.contains('wpcf7-form'))return;if(!document.body.classList.contains('single-webinary'))return;busy(f,true);},true);['wpcf7mailsent','wpcf7mailfailed','wpcf7invalid','wpcf7spam','wpcf7failed','wpcf7aborted','wpcf7submit'].forEach(function(t){document.addEventListener(t,function(ev){busy(ev.target,false);});});})();",
            'after'
        );
    }
}
add_action('wp_enqueue_scripts', 'akademiata_enqueue_scripts', 100);


/**
 * Enqueue styles.
 */
function akademiata_enqueue_styles()
{
    $theme_dir = get_template_directory_uri();

    // Unused WP/Gutenberg styles.
    $styles_to_dequeue = array(
        'wp-block-library',
        'wp-block-library-theme',
        'wc-block-style',
        'global-styles',
        'classic-theme-styles',
    );
    foreach ($styles_to_dequeue as $style) {
        wp_dequeue_style($style);
    }
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css');

    wp_enqueue_style(
        'adobe-fonts',
        'https://use.typekit.net/dic8cvr.css',
        array(),
        null
    );

    $main_css_path = get_template_directory() . '/assets/dist/css/main.css';
    $main_css_ver  = file_exists($main_css_path) ? filemtime($main_css_path) : null;

    wp_enqueue_style(
        'name-main-css',
        $theme_dir . '/assets/dist/css/main.css',
        array(),
        $main_css_ver,
        'all'
    );

}

add_action('wp_enqueue_scripts', 'akademiata_enqueue_styles');
