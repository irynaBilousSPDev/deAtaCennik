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

    // Prices calculator: pass Google Sheets endpoint to JS (Prices page + single offers).
    if (is_page_template('page-template-prices.php') || is_singular(['bachelor', 'master'])) {
        wp_localize_script('name-main-js', 'akademiataPrices', [
            'googleApiUrl' => 'https://script.google.com/macros/s/AKfycby89Mt7UgeY6jKnq2YQNwumt_CBp46UVd1mbKvxqEkg_46vjGAeN-8lcL_OokQVFnAW/exec',
        ]);
    }

    // Smooth anchor scrolling for Podcast ATA (some browsers/themes ignore CSS scroll-behavior).
    if (is_singular('podcast-ata')) {
        wp_add_inline_script(
            'name-main-js',
            "(function(){function s(sel){var el=document.querySelector(sel);if(!el)return;el.scrollIntoView({behavior:'smooth',block:'start'});}document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a[href^=\"#\"]'):null;if(!a)return;var href=a.getAttribute('href');if(!href||href==='#')return;try{var id=decodeURIComponent(href);}catch(_e){var id=href;}if(id==='#o-czym'||id==='#goscie'||id==='#zapisz'){e.preventDefault();s(id);}});})();",
            'after'
        );

        // Hero sticker "realistic signups" counter: persistent + random growth.
        wp_add_inline_script(
            'name-main-js',
            "(function(){var page=document.body&&document.body.classList&&document.body.classList.contains('single-podcast-ata');if(!page)return;var el=document.querySelector('.hero-sticker-text');if(!el)return;var textNode=(function(){for(var i=0;i<el.childNodes.length;i++){var n=el.childNodes[i];if(n.nodeType===3&&String(n.textContent||'').trim())return n;}return null;})();function parseCount(){var t=(el.textContent||'').replace(/\\s+/g,' ').trim();var m=t.match(/(\\d+)/);return m?parseInt(m[1],10):0;}function renderCount(v){var base=String(v)+' zapisanych';if(textNode){textNode.textContent='\\n                        '+base+'\\n                        ';}else{var small=el.querySelector('small');el.innerHTML='';el.appendChild(document.createTextNode(base+' '));if(small)el.appendChild(small);}el.setAttribute('data-count',String(v));}function randInt(a,b){return Math.floor(Math.random()*(b-a+1))+a;}function choice(arr){return arr[Math.floor(Math.random()*arr.length)];}var STORAGE_KEY='ata_podcast_signups_v1';var now=Date.now();var initial=parseCount();if(!initial||initial<1)initial=randInt(280,360);var state=null;try{state=JSON.parse(localStorage.getItem(STORAGE_KEY)||'null');}catch(_e){state=null;}if(!state||typeof state!=='object'){state={count:initial,nextAt:now+choice([15,30,120,1440])*60*1000};}if(typeof state.count!=='number'||state.count<1)state.count=initial;if(typeof state.nextAt!=='number'||state.nextAt<now-7*24*60*60*1000)state.nextAt=now+choice([15,30,120,1440])*60*1000;function save(){try{localStorage.setItem(STORAGE_KEY,JSON.stringify(state));}catch(_e){}}function maybeTick(){var n=Date.now();if(n>=state.nextAt){state.count=state.count+randInt(1,15);state.nextAt=n+choice([15,30,120,1440])*60*1000;save();renderCount(state.count);} }renderCount(state.count);save();maybeTick();setInterval(maybeTick,30000);})();",
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
