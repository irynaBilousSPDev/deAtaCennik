<?php

// CPT TAXONOMY

include('configure/cpt-taxonomy.php');

// Utilities

include('configure/utilities.php');

// WPML: fix language switcher links for news date archives
include('configure/wpml-news-date-switcher.php');

include('configure/offer-pricing.php');

// ajax filter

include('configure/ajax_filters.php');

// CONFIG

include('configure/configure.php');

// JAVASCRIPT & CSS

include('configure/js-css.php');

// Contact Form 7 tweaks
include('configure/cf7.php');

// SHORTCODES

include('configure/shortcodes.php');

// BREADCRUMBS

include( 'configure/breadcrumbs-functions.php' );

// duplicate CPT posts

include('configure/duplicate-cpt-posts.php');

// ACF

include('configure/acf.php');

// load-cpt-slider

include('configure/load-cpt-slider.php');

// mega menu walker

include('configure/class-wp-mega-menu-walker.php');


// generate_keys_bch_mst

include('configure/generate_keys_bch_mst.php');


//WPML
//include('configure/wpml-string.php');

// HOOKS ADMIN

if (is_admin()) {
    include('configure/admin.php');
    include('configure/news-city-admin.php');
}

