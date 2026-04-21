<?php

// Custom Post types & Taxonomies
function bachelor_post_type()
{
    $labels = array(
        'name' => _x("Studia I stopnia", 'Post type general name', 'akademiata'),
        'singular_name' => _x("Bachelor", 'Post type singular name', 'akademiata'),
        'add_new' => _x('Add New', 'custom'),
        'add_new_item' => _x('Add New', 'akademiata'),
        'edit_item' => _x('Edit', 'akademiata'),
        'new_item' => _x('New', 'akademiata'),
        'view_item' => _x('View', 'akademiata'),
        'search_items' => _x('Search', 'akademiata'),
        'not_found' => _x('No found', 'akademiata'),
        'not_found_in_trash' => _x('No found in Trash', 'akademiata'),
        'parent_item_colon' => _x('Parent:', 'akademiata'),
        'menu_name' => _x("Bachelor", 'Admin Menu text', 'akademiata'),
    );
    $rewrite = array(
        'slug' => 'oferta/studia-1-stopnia',
        'with_front' => true,
        'pages' => true,
        'feeds' => true,
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'description' => "Bachelor's degree specializations",
        'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'post-formats', 'custom-fields'),
        'taxonomies' => array('post_tag', 'degree', 'mode', 'obtained_title', 'promotions', 'department', 'city', 'recruitment_date'),
        'show_ui' => true,
        'show_in_menu' => true,
//        'menu_position' => 5,
        'menu_icon' => 'dashicons-awards',
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => $rewrite,
        'public' => true,
        'has_archive' => 'bachelor',
        'capability_type' => 'post'
    );
    register_post_type('bachelor', $args); // max 20 character cannot contain capital letters and spaces
}

add_action('init', 'bachelor_post_type', 0);


function master_post_type()
{
    $labels = array(
        'name' => _x("Studia II stopnia", 'Post type general name', 'akademiata'),
        'singular_name' => _x('master', 'Post type singular name', 'akademiata'),
        'add_new' => _x('Add New', 'akademiata'),
        'add_new_item' => _x('Add New masterPost', 'akademiata'),
        'edit_item' => _x('Edit masterPost', 'akademiata'),
        'new_item' => _x('New masterPost', 'akademiata'),
        'view_item' => _x('View masterPost', 'akademiata'),
        'search_items' => _x('Search masterPosts', 'akademiata'),
        'not_found' => _x('No masterPosts found', 'akademiata'),
        'not_found_in_trash' => _x('No masterPosts found in Trash', 'akademiata'),
        'parent_item_colon' => _x('Parent masterPost:', 'akademiata'),
        'menu_name' => _x("Master", 'Admin Menu text', 'akademiata'),
    );

    $rewrite = array(
        'slug' => 'oferta/studia-2-stopnia',
        'with_front' => true,
        'pages' => true,
        'feeds' => true,
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'description' => "Master's degree specializations",
        'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'post-formats', 'custom-fields'),
        'taxonomies' => array('post_tag', 'degree', 'mode', 'obtained_title', 'promotions', 'department', 'city','recruitment_date'),
        'show_ui' => true,
        'show_in_menu' => true,
//        'menu_position' => 5,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => $rewrite,
        'public' => true,
        'has_archive' => 'master',
        'capability_type' => 'post'
    );
    register_post_type('master', $args); // max 20 character cannot contain capital letters and spaces
}

add_action('init', 'master_post_type', 0);

function register_discounts_cpt()
{
    $labels = [
        'name' => __('Discounts', 'akademiata'),
        'singular_name' => __('Discount', 'akademiata'),
        'menu_name' => __('Discounts Bachelor/Master', 'akademiata'),
        'name_admin_bar' => __('Discounts Bachelor/Master', 'akademiata'),
        'add_new' => __('Add New', 'akademiata'),
        'add_new_item' => __('Add New Discount', 'akademiata'),
        'new_item' => __('New Discount', 'akademiata'),
        'edit_item' => __('Edit Discount', 'akademiata'),
        'view_item' => __('View Discount', 'akademiata'),
        'all_items' => __('All Discounts', 'akademiata'),
        'search_items' => __('Search Discounts', 'akademiata'),
        'parent_item_colon' => __('Parent Discounts:', 'akademiata'),
        'not_found' => __('No discounts found.', 'akademiata'),
        'not_found_in_trash' => __('No discounts found in Trash.', 'akademiata')
    ];

    $args = [
        'label' => __('Discounts Bachelor/Master', 'akademiata'),
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
//        'menu_position'       => 6,
        'menu_icon' => 'dashicons-tag', // WordPress Dashicon for tags
        'show_in_rest' => true, // Enable Gutenberg
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
//        'taxonomies'          => ['discount_category'], // Attach Taxonomy
        'taxonomies' => array('discount_category', 'program', 'degree', 'city'), // Attach Taxonomy
        'capability_type' => 'post',
        'rewrite' => [
            'slug' => apply_filters('wpml_translate_single_string', 'promocje', 'akademiata', 'Promocje Slug'),
        ],
    ];

    register_post_type('discounts', $args);
}

add_action('init', 'register_discounts_cpt');

function register_price_cpt()
{
    $labels = array(
        'name' => _x('Prices', 'post type general name', 'akademiata'),
        'singular_name' => _x('Price', 'post type singular name', 'akademiata'),
        'menu_name' => _x('Prices', 'admin menu', 'akademiata'),
        'name_admin_bar' => _x('Price', 'add new on admin bar', 'akademiata'),
        'add_new' => _x('Add New', 'price', 'akademiata'),
        'add_new_item' => __('Add New Price', 'akademiata'),
        'new_item' => __('New Price', 'akademiata'),
        'edit_item' => __('Edit Price', 'akademiata'),
        'view_item' => __('View Price', 'akademiata'),
        'all_items' => __('All Prices', 'akademiata'),
        'search_items' => __('Search Prices', 'akademiata'),
        'parent_item_colon' => __('Parent Prices:', 'akademiata'),
        'not_found' => __('No prices found.', 'akademiata'),
        'not_found_in_trash' => __('No prices found in Trash.', 'akademiata'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'prices'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'taxonomies' => array('price_category', 'degree', 'program', 'city'), // Attach Taxonomy
//        'show_in_rest'       => true, // enable for Gutenberg & REST API
//        'menu_position'       => 5,
        'menu_icon' => 'dashicons-money-alt',
    );

    register_post_type('price', $args);
}

add_action('init', 'register_price_cpt');


function register_youtube_shorts_cpt()
{
    $labels = array(
        'name' => __('YouTube Shorts', 'akademiata'),
        'singular_name' => __('YouTube Short', 'akademiata'),
        'menu_name' => __('YouTube Shorts', 'akademiata'),
        'name_admin_bar' => __('YouTube Short', 'akademiata'),
        'add_new' => __('Add New', 'akademiata'),
        'add_new_item' => __('Add New YouTube Short', 'akademiata'),
        'new_item' => __('New YouTube Short', 'akademiata'),
        'edit_item' => __('Edit YouTube Short', 'akademiata'),
        'view_item' => __('View YouTube Short', 'akademiata'),
        'all_items' => __('All YouTube Shorts', 'akademiata'),
        'search_items' => __('Search YouTube Shorts', 'akademiata'),
        'parent_item_colon' => __('Parent YouTube Shorts:', 'akademiata'),
        'not_found' => __('No YouTube Shorts found.', 'akademiata'),
        'not_found_in_trash' => __('No YouTube Shorts found in Trash.', 'akademiata'),
    );

    $args = array(
        'label' => __('YouTube Shorts', 'akademiata'),
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'youtube-shorts'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
//        'menu_position'       => 5,
        'menu_icon' => 'dashicons-video-alt3', // YouTube-style icon
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
//        'show_in_rest'        => true, // Enables Gutenberg editor
    );

    register_post_type('youtube_shorts', $args);
}

add_action('init', 'register_youtube_shorts_cpt');


/**
 * Create   taxonomies for the post types
 */
function wpdocs_create_study_taxonomies()
{

//     Degree - Studia
    $labels = array(
        'name' => _x('Degree', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Degree', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search degree', 'akademiata'),
        'all_items' => __('All degrees', 'akademiata'),
        'parent_item' => __('Parent degree', 'akademiata'),
        'parent_item_colon' => __('Parent degree:', 'akademiata'),
        'edit_item' => __('Edit degree', 'akademiata'),
        'update_item' => __('Update degree', 'akademiata'),
        'add_new_item' => __('Add New degree', 'akademiata'),
        'new_item_name' => __('New degree Name', 'akademiata'),
        'menu_name' => __('Degree', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'studia',
        ),

    );

    register_taxonomy('degree', array('bachelor', 'master', 'price'), $args);

    // Add new taxonomy, make it hierarchical (like categories) programs - kierunek studiow
    $labels = array(
        'name' => _x('Programs', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Program', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search Program', 'akademiata'),
        'all_items' => __('All Program', 'akademiata'),
        'parent_item' => __('Parent Program', 'akademiata'),
        'parent_item_colon' => __('Parent Program:', 'akademiata'),
        'edit_item' => __('Edit Program', 'akademiata'),
        'update_item' => __('Update Program', 'akademiata'),
        'add_new_item' => __('Add New Program', 'akademiata'),
        'new_item_name' => __('New Program Name', 'akademiata'),
        'menu_name' => __('Programs', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'kierunek',
        ),

    );

    register_taxonomy('program', array('bachelor', 'master', 'youtube_shorts', 'discounts', 'price'), $args);


    // Add new taxonomy, make it hierarchical (like categories) Study mode - Tryb
    $labels = array(
        'name' => _x('Mode', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Mode', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search Mode', 'akademiata'),
        'all_items' => __('All Mode', 'akademiata'),
        'parent_item' => __('Parent Mode', 'akademiata'),
        'parent_item_colon' => __('Parent Mode:', 'akademiata'),
        'edit_item' => __('Edit Mode', 'akademiata'),
        'update_item' => __('Update Mode', 'akademiata'),
        'add_new_item' => __('Add New Mode', 'akademiata'),
        'new_item_name' => __('New Mode Name', 'akademiata'),
        'menu_name' => __('Mode', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'tryb',
        ),

    );

    register_taxonomy('mode', array('bachelor', 'master'), $args);

    // Add new taxonomy, make it hierarchical (like categories) Obtained title - Uzyskany tytuł
    $labels = array(
        'name' => _x('Obtained title', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Obtained title', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search Obtained title', 'akademiata'),
        'all_items' => __('All Obtained title', 'akademiata'),
        'parent_item' => __('Parent Obtained title', 'akademiata'),
        'parent_item_colon' => __('Parent Obtained title:', 'akademiata'),
        'edit_item' => __('Edit Obtained title', 'akademiata'),
        'update_item' => __('Update Obtained title', 'akademiata'),
        'add_new_item' => __('Add New Obtained title', 'akademiata'),
        'new_item_name' => __('New Obtained title Name', 'akademiata'),
        'menu_name' => __('Obtained title', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'uzyskany-tytul',
        ),

    );

    register_taxonomy('obtained_title', array('bachelor', 'master'), $args);
    // Add new taxonomy, make it hierarchical (like categories) promotions
    $labels = array(
        'name' => _x('Promotions', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Promotions', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search Promotions', 'akademiata'),
        'all_items' => __('All Promotions', 'akademiata'),
        'parent_item' => __('Parent Promotions', 'akademiata'),
        'parent_item_colon' => __('Parent Promotions:', 'akademiata'),
        'edit_item' => __('Edit Promotions', 'akademiata'),
        'update_item' => __('Update Promotions', 'akademiata'),
        'add_new_item' => __('Add Promotions', 'akademiata'),
        'new_item_name' => __('New Promotions Name', 'akademiata'),
        'menu_name' => __('Promotions', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'promocje',
        ),

    );

    register_taxonomy('promotions', array('bachelor', 'master'), $args);


    // Add new taxonomy, make it hierarchical (like categories) Language
    $labels = array(
        'name' => _x('Language', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Language', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search Language', 'akademiata'),
        'all_items' => __('All Language', 'akademiata'),
        'parent_item' => __('Parent Language', 'akademiata'),
        'parent_item_colon' => __('Parent Language:', 'akademiata'),
        'edit_item' => __('Edit Language', 'akademiata'),
        'update_item' => __('Update Language', 'akademiata'),
        'add_new_item' => __('Add Language', 'akademiata'),
        'new_item_name' => __('New Language Name', 'akademiata'),
        'menu_name' => __('Language', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'jezyk',
        ),

    );

    register_taxonomy('language', array('bachelor', 'master', 'courses'), $args);

    // Add new taxonomy, make it hierarchical (like categories) Duration
    $labels = array(
        'name' => _x('Duration', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Duration', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search Duration', 'akademiata'),
        'all_items' => __('All Duration', 'akademiata'),
        'parent_item' => __('Parent Duration', 'akademiata'),
        'parent_item_colon' => __('Parent Duration:', 'akademiata'),
        'edit_item' => __('Edit Duration', 'akademiata'),
        'update_item' => __('Update Duration', 'akademiata'),
        'add_new_item' => __('Add Duration', 'akademiata'),
        'new_item_name' => __('New Duration Name', 'akademiata'),
        'menu_name' => __('Duration', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'czas-trwania',
        ),

    );

    register_taxonomy('duration', array('bachelor', 'master'), $args);
    // Add new taxonomy, make it hierarchical (like categories) Duration
    $labels = array(
        'name' => _x('Departments (Wydzialy)', 'taxonomy general name', 'akademiata'),
        'singular_name' => _x('Department', 'taxonomy singular name', 'akademiata'),
        'search_items' => __('Search department', 'akademiata'),
        'all_items' => __('All departments', 'akademiata'),
        'parent_item' => __('Parent department', 'akademiata'),
        'parent_item_colon' => __('Parent department:', 'akademiata'),
        'edit_item' => __('Edit department', 'akademiata'),
        'update_item' => __('Update department', 'akademiata'),
        'add_new_item' => __('Add department', 'akademiata'),
        'new_item_name' => __('New department Name', 'akademiata'),
        'menu_name' => __('Departments', 'akademiata'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'wydzial',
        ),

    );

    register_taxonomy('department', array('bachelor', 'master'), $args);

    // Register "City" taxonomy for 'bachelor' and 'master'
    function register_city_taxonomy()
    {
        $labels = array(
            'name' => _x('Cities', 'taxonomy general name', 'akademiata'),
            'singular_name' => _x('City', 'taxonomy singular name', 'akademiata'),
            'search_items' => __('Search Cities', 'akademiata'),
            'all_items' => __('All Cities', 'akademiata'),
            'parent_item' => __('Parent City', 'akademiata'),
            'parent_item_colon' => __('Parent City:', 'akademiata'),
            'edit_item' => __('Edit City', 'akademiata'),
            'update_item' => __('Update City', 'akademiata'),
            'add_new_item' => __('Add New City', 'akademiata'),
            'new_item_name' => __('New City Name', 'akademiata'),
            'menu_name' => __('Cities', 'akademiata'),
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'miasto'),
        );

        register_taxonomy('city', array('bachelor', 'master', 'discounts', 'price'), $args);
    }

    add_action('init', 'register_city_taxonomy');

    // Add new taxonomy, make it hierarchical (like categories) Recruitment date
    $labels = array(
        'name'              => _x('Recruitment dates', 'taxonomy general name', 'akademiata'),
        'singular_name'     => _x('Recruitment date', 'taxonomy singular name', 'akademiata'),
        'search_items'      => __('Search Recruitment dates', 'akademiata'),
        'all_items'         => __('All Recruitment dates', 'akademiata'),
        'parent_item'       => __('Parent Recruitment date', 'akademiata'),
        'parent_item_colon' => __('Parent Recruitment date:', 'akademiata'),
        'edit_item'         => __('Edit Recruitment date', 'akademiata'),
        'update_item'       => __('Update Recruitment date', 'akademiata'),
        'add_new_item'      => __('Add Recruitment date', 'akademiata'),
        'new_item_name'     => __('New Recruitment date name', 'akademiata'),
        'menu_name'         => __('Recruitment dates', 'akademiata'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug' => 'recruitment-date',
        ),
    );

    register_taxonomy('recruitment_date', array('bachelor', 'master'), $args);



}

// hook into the init action and call create_study_taxonomies when it fires
add_action('init', 'wpdocs_create_study_taxonomies', 0);


// Register CPT: Studia Podyplomowe
function register_postgraduate_cpt()
{
    $labels = array(
        'name' => __('Studia Podyplomowe', 'akademiata'),
        'singular_name' => __('Studia Podyplomowe', 'akademiata'),
        'menu_name' => __('Podyplomowe', 'akademiata'),
        'add_new_item' => __('Add New', 'akademiata'),
        'edit_item' => __('Edit', 'akademiata'),
        'new_item' => __('New', 'akademiata'),
        'view_item' => __('View', 'akademiata'),
        'search_items' => __('Search', 'akademiata'),
        'not_found' => __('No items found', 'akademiata'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'studia-podyplomowe',
            'with_front' => false,
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon' => 'dashicons-welcome-learn-more',
        'taxonomies' => array('type_of_study_pg_mba', 'duration_pg_mba', 'language_pg_mba', 'diploma_pg_mba', 'form_pg_mba', 'city_pg_mba'),
    );

    register_post_type('postgraduate', $args);
}

add_action('init', 'register_postgraduate_cpt');

// Register CPT: Studia MBA
function register_mba_cpt()
{
    $labels = array(
        'name' => __('Studia MBA', 'akademiata'),
        'singular_name' => __('Studia MBA', 'akademiata'),
        'menu_name' => __('MBA', 'akademiata'),
        'add_new_item' => __('Add New', 'akademiata'),
        'edit_item' => __('Edit', 'akademiata'),
        'new_item' => __('New', 'akademiata'),
        'view_item' => __('View', 'akademiata'),
        'search_items' => __('Search', 'akademiata'),
        'not_found' => __('No items found', 'akademiata'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'studia-mba',
            'with_front' => false,
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon' => 'dashicons-businessman',
        'taxonomies' => array('type_of_study_pg_mba', 'duration_pg_mba', 'language_pg_mba', 'diploma_pg_mba', 'form_pg_mba', 'city_pg_mba'),
    );

    register_post_type('mba', $args);
}

add_action('init', 'register_mba_cpt');

function register_shared_pg_mba_taxonomies()
{
    $taxonomies = [
        'type_of_study_pg_mba' => 'Rodzaj studiów',
        'duration_pg_mba' => 'Czas trwania',
        'language_pg_mba' => 'Język',
        'diploma_pg_mba' => 'Dyplom',
        'form_pg_mba' => 'Forma studiów'
    ];

    foreach ($taxonomies as $key => $label) {
        register_taxonomy($key, ['postgraduate', 'mba'], array(
            'labels' => array(
                'name' => __($label, 'akademiata'),
                'singular_name' => __($label, 'akademiata'),
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => sanitize_title($label)),
        ));
    }

    register_taxonomy('city_pg_mba', ['postgraduate', 'mba', 'courses'], array(
        'labels' => array(
            'name' => __('Miasta', 'akademiata'),
            'singular_name' => __('Miasto', 'akademiata'),
            'search_items' => __('Szukaj miasta', 'akademiata'),
            'all_items' => __('Wszystkie miasta', 'akademiata'),
            'edit_item' => __('Edytuj miasto', 'akademiata'),
            'update_item' => __('Aktualizuj miasto', 'akademiata'),
            'add_new_item' => __('Dodaj nowe miasto', 'akademiata'),
            'new_item_name' => __('Nazwa nowego miasta', 'akademiata'),
            'menu_name' => __('Miasta (Podypl/MBA/Kursy)', 'akademiata'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => false,
    ));
}

add_action('init', 'register_shared_pg_mba_taxonomies');

// Register CPT: Kursy
function register_courses_cpt()
{
    $labels = array(
        'name' => __('Courses', 'akademiata'),
        'singular_name' => __('Course', 'akademiata'),
        'menu_name' => __('Courses', 'akademiata'),
        'add_new_item' => __('Add New Course', 'akademiata'),
        'edit_item' => __('Edit Course', 'akademiata'),
        'new_item' => __('New Course', 'akademiata'),
        'view_item' => __('View Course', 'akademiata'),
        'search_items' => __('Search Courses', 'akademiata'),
        'not_found' => __('No courses found.', 'akademiata'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'kursy',
            'with_front' => false,
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon' => 'dashicons-welcome-learn-more',
        'taxonomies' => array(
            'mode_course',
            'duration_course',
            'language',
            'city_pg_mba',
            'instructor_course',
            'price_course',
            'fee_course',
        ),
    );

    register_post_type('courses', $args);
}
add_action('init', 'register_courses_cpt');

function register_courses_taxonomies() {

    // Course Type (Rodzaj kursów)
    register_taxonomy('course_type', ['courses'], array(
        'labels' => array(
            'name'              => __('Rodzaje kursów', 'akademiata'), // Заголовок у списку
            'singular_name'     => __('Rodzaj kursu', 'akademiata'),
            'search_items'      => __('Szukaj rodzaju', 'akademiata'),
            'all_items'         => __('Wszystkie rodzaje', 'akademiata'),
            'edit_item'         => __('Edytuj rodzaj', 'akademiata'),
            'update_item'       => __('Aktualizuj rodzaj', 'akademiata'),
            'add_new_item'      => __('Dodaj nowy rodzaj', 'akademiata'),
            'new_item_name'     => __('Nowy rodzaj kursu', 'akademiata'),
            'menu_name'         => __('Rodzaj kursu', 'akademiata'),
        ),
        'hierarchical'      => true, // Якщо потрібно як категорії
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'course-type'), // Slug в URL (EN)
    ));


    // Mode of Study
    register_taxonomy('mode_course', ['courses'], array(
        'labels' => array(
            'name'              => __('Modes of Study', 'akademiata'),
            'singular_name'     => __('Mode of Study', 'akademiata'),
            'search_items'      => __('Search Modes', 'akademiata'),
            'all_items'         => __('All Modes', 'akademiata'),
            'edit_item'         => __('Edit Mode', 'akademiata'),
            'update_item'       => __('Update Mode', 'akademiata'),
            'add_new_item'      => __('Add New Mode', 'akademiata'),
            'new_item_name'     => __('New Mode Name', 'akademiata'),
            'menu_name'         => __('Mode of Study', 'akademiata'),
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'mode-course'),
    ));


    // Duration
    register_taxonomy('duration_course', ['courses'], array(
        'labels' => array(
            'name'              => __('Durations', 'akademiata'),
            'singular_name'     => __('Duration', 'akademiata'),
            'search_items'      => __('Search Durations', 'akademiata'),
            'all_items'         => __('All Durations', 'akademiata'),
            'edit_item'         => __('Edit Duration', 'akademiata'),
            'update_item'       => __('Update Duration', 'akademiata'),
            'add_new_item'      => __('Add New Duration', 'akademiata'),
            'new_item_name'     => __('New Duration Name', 'akademiata'),
            'menu_name'         => __('Duration', 'akademiata'),
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'duration-course'),
    ));

    // Instructor
    register_taxonomy('instructor_course', ['courses'], array(
        'labels' => array(
            'name'              => __('Instructors', 'akademiata'),
            'singular_name'     => __('Instructor', 'akademiata'),
            'search_items'      => __('Search Instructors', 'akademiata'),
            'all_items'         => __('All Instructors', 'akademiata'),
            'edit_item'         => __('Edit Instructor', 'akademiata'),
            'update_item'       => __('Update Instructor', 'akademiata'),
            'add_new_item'      => __('Add New Instructor', 'akademiata'),
            'new_item_name'     => __('New Instructor Name', 'akademiata'),
            'menu_name'         => __('Instructor', 'akademiata'),
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'instructor-course'),
    ));

    // Price
    register_taxonomy('price_course', ['courses'], array(
        'labels' => array(
            'name'              => __('Prices', 'akademiata'),
            'singular_name'     => __('Price', 'akademiata'),
            'search_items'      => __('Search Prices', 'akademiata'),
            'all_items'         => __('All Prices', 'akademiata'),
            'edit_item'         => __('Edit Price', 'akademiata'),
            'update_item'       => __('Update Price', 'akademiata'),
            'add_new_item'      => __('Add New Price', 'akademiata'),
            'new_item_name'     => __('New Price Name', 'akademiata'),
            'menu_name'         => __('Price', 'akademiata'),
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'price-course'),
    ));

    // Administrative Fee
    register_taxonomy('fee_course', ['courses'], array(
        'labels' => array(
            'name'              => __('Administrative Fees', 'akademiata'),
            'singular_name'     => __('Administrative Fee', 'akademiata'),
            'search_items'      => __('Search Fees', 'akademiata'),
            'all_items'         => __('All Fees', 'akademiata'),
            'edit_item'         => __('Edit Fee', 'akademiata'),
            'update_item'       => __('Update Fee', 'akademiata'),
            'add_new_item'      => __('Add New Fee', 'akademiata'),
            'new_item_name'     => __('New Fee Name', 'akademiata'),
            'menu_name'         => __('Admin Fee', 'akademiata'),
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'fee-course'),
    ));
}
add_action('init', 'register_courses_taxonomies');


// Register "contact" CPT and "contact_city" taxonomy
add_action('init', function () {
    // CPT: contact
    $labels = [
        'name' => __('Contacts', 'akademiata'),
        'singular_name' => __('Contact', 'akademiata'),
        'add_new' => __('Add New', 'akademiata'),
        'add_new_item' => __('Add New Contact', 'akademiata'),
        'edit_item' => __('Edit Contact', 'akademiata'),
        'new_item' => __('New Contact', 'akademiata'),
        'view_item' => __('View Contact', 'akademiata'),
        'search_items' => __('Search Contacts', 'akademiata'),
        'not_found' => __('No contacts found', 'akademiata'),
        'not_found_in_trash' => __('No contacts found in Trash', 'akademiata'),
        'all_items' => __('All Contacts', 'akademiata'),
        'menu_name' => __('Contacts', 'akademiata'),
    ];

    register_post_type('contact', [
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'contacts', 'with_front' => false],
        'show_in_rest' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);

    // Taxonomy: contact_city
    $tax_labels = [
        'name' => __('Contact Cities', 'akademiata'),
        'singular_name' => __('Contact City', 'akademiata'),
        'search_items' => __('Search Cities', 'akademiata'),
        'all_items' => __('All Cities', 'akademiata'),
        'edit_item' => __('Edit City', 'akademiata'),
        'update_item' => __('Update City', 'akademiata'),
        'add_new_item' => __('Add New City', 'akademiata'),
        'new_item_name' => __('New City Name', 'akademiata'),
        'menu_name' => __('Cities', 'akademiata'),
    ];

    register_taxonomy('contact_city', ['contact'], [
        'labels' => $tax_labels,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'contact-city', 'with_front' => false],
        'show_in_rest' => true,
    ]);
});

/**
 * Register CPT: Cadre
 * Register Taxonomy: Cadre Groups
 */

add_action('init', function () {

    // CPT: cadre
    $labels = [
        'name'                  => __('Cadre', 'akademiata'),
        'singular_name'         => __('Cadre Member', 'akademiata'),
        'menu_name'             => __('Cadre', 'akademiata'),
        'name_admin_bar'        => __('Cadre Member', 'akademiata'),
        'add_new'               => __('Add New', 'akademiata'),
        'add_new_item'          => __('Add New Cadre Member', 'akademiata'),
        'new_item'              => __('New Cadre Member', 'akademiata'),
        'edit_item'             => __('Edit Cadre Member', 'akademiata'),
        'view_item'             => __('View Cadre Member', 'akademiata'),
        'all_items'             => __('All Cadre Members', 'akademiata'),
        'search_items'          => __('Search Cadre', 'akademiata'),
        'not_found'             => __('No cadre members found.', 'akademiata'),
        'not_found_in_trash'    => __('No cadre members found in Trash.', 'akademiata'),
    ];

    register_post_type('cadre', [
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'show_in_rest'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-id-alt',
        'supports'           => ['title', 'thumbnail', 'editor'],
        'rewrite'            => ['slug' => 'cadre'],
    ]);

    // Taxonomy: cadre_group
    $tax_labels = [
        'name'              => __('Cadre Groups', 'akademiata'),
        'singular_name'     => __('Cadre Group', 'akademiata'),
        'search_items'      => __('Search Cadre Groups', 'akademiata'),
        'all_items'         => __('All Cadre Groups', 'akademiata'),
        'edit_item'         => __('Edit Cadre Group', 'akademiata'),
        'update_item'       => __('Update Cadre Group', 'akademiata'),
        'add_new_item'      => __('Add New Cadre Group', 'akademiata'),
        'new_item_name'     => __('New Cadre Group Name', 'akademiata'),
        'menu_name'         => __('Cadre Groups', 'akademiata'),
    ];

    register_taxonomy('cadre_group', ['cadre'], [
        'hierarchical'      => true,
        'labels'            => $tax_labels,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'cadre-group'],
    ]);
});

// ==============================
// Register CPT: FAQ + Taxonomy: FAQ Topics
// ==============================

function register_faq_cpt_and_taxonomy()
{

    // ----- Register Custom Post Type: FAQ -----
    $faq_labels = [
        'name' => 'FAQ',
        'singular_name' => 'FAQ Item',
        'menu_name' => 'FAQ',
        'name_admin_bar' => 'FAQ',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New FAQ Item',
        'new_item' => 'New FAQ Item',
        'edit_item' => 'Edit FAQ Item',
        'view_item' => 'View FAQ Item',
        'all_items' => 'All FAQ Items',
        'search_items' => 'Search FAQ',
        'parent_item_colon' => 'Parent FAQ:',
        'not_found' => 'No FAQ items found.',
        'not_found_in_trash' => 'No FAQ items found in Trash.',
    ];

    $faq_args = [
        'labels' => $faq_labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-editor-help',
        'query_var' => true,
        'rewrite' => ['slug' => 'faq', 'with_front' => false],
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 20,
        'supports' => ['title', 'editor'],
        'show_in_rest' => true, // Gutenberg / API support
    ];

    register_post_type('faq', $faq_args);


    // ----- Register Taxonomy: FAQ Topics -----
    $taxonomy_labels = [
        'name' => 'Topics',
        'singular_name' => 'Topic',
        'search_items' => 'Search Topics',
        'all_items' => 'All Topics',
        'parent_item' => 'Parent Topic',
        'parent_item_colon' => 'Parent Topic:',
        'edit_item' => 'Edit Topic',
        'update_item' => 'Update Topic',
        'add_new_item' => 'Add New Topic',
        'new_item_name' => 'New Topic Name',
        'menu_name' => 'Topics',
    ];

    $taxonomy_args = [
        'hierarchical' => true, // works like categories
        'labels' => $taxonomy_labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'topics', 'with_front' => false],
        'show_in_rest' => true, // Gutenberg / API support
    ];

    register_taxonomy('faq_topics', ['faq'], $taxonomy_args);
}

add_action('init', 'register_faq_cpt_and_taxonomy');


// Add custom rewrite rules (WPML-friendly)
add_action('init', function () {
    add_rewrite_tag('%city_pg_mba%', '([^/]+)');

    // Archives
    add_rewrite_rule('^studia-podyplomowe/?$', 'index.php?post_type=postgraduate', 'top');
    add_rewrite_rule('^studia-mba/?$', 'index.php?post_type=mba', 'top');
    add_rewrite_rule('^kursy/?$', 'index.php?post_type=courses', 'top');

    // City taxonomy archive
    add_rewrite_rule('^studia-podyplomowe/([^/]+)/?$', 'index.php?taxonomy=city_pg_mba&term=$matches[1]&post_type=postgraduate', 'top');
    add_rewrite_rule('^studia-mba/([^/]+)/?$', 'index.php?taxonomy=city_pg_mba&term=$matches[1]&post_type=mba', 'top');
    add_rewrite_rule('^kursy/([^/]+)/?$', 'index.php?taxonomy=city_pg_mba&term=$matches[1]&post_type=courses', 'top');

    // Single posts (include city slug in query vars so WPML resolves correctly)
    add_rewrite_rule(
        '^studia-podyplomowe/([^/]+)/([^/]+)/?$',
        'index.php?post_type=postgraduate&city_pg_mba=$matches[1]&name=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^studia-mba/([^/]+)/([^/]+)/?$',
        'index.php?post_type=mba&city_pg_mba=$matches[1]&name=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^kursy/([^/]+)/([^/]+)/?$',
        'index.php?post_type=courses&city_pg_mba=$matches[1]&name=$matches[2]',
        'top'
    );
}, 20, 0); // Priority 20 helps avoid WPML init timing conflicts


// Force taxonomy template loading
add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query()) return;

    if (isset($query->query_vars['taxonomy'], $query->query_vars['term'], $query->query_vars['post_type']) && $query->query_vars['taxonomy'] === 'city_pg_mba') {
        $query->is_archive = false;
        $query->is_tax = true;
        $query->set('taxonomy', 'city_pg_mba');
        // Set only one post type!
        $query->set('post_type', $query->query_vars['post_type']);
    }
});

// Pretty permalinks for single posts (WPML-correct)
add_filter('post_type_link', function ($post_link, $post) {

    if (!in_array($post->post_type, ['postgraduate', 'mba', 'courses'], true)) {
        return $post_link;
    }

    $terms = wp_get_object_terms($post->ID, 'city_pg_mba');
    if (empty($terms) || is_wp_error($terms)) {
        return $post_link;
    }

    $term = $terms[0];

    // Detect language of THIS post (not current page)
    $post_lang = null;
    if (function_exists('apply_filters')) {
        $post_lang = apply_filters('wpml_element_language_code', null, [
            'element_id'   => $post->ID,
            'element_type' => 'post_' . $post->post_type,
        ]);
    }
    if (empty($post_lang) && defined('ICL_LANGUAGE_CODE')) {
        $post_lang = ICL_LANGUAGE_CODE; // fallback
    }

    // Translate city term into the post language
    if (!empty($post_lang) && defined('ICL_SITEPRESS_VERSION')) {
        $term_id = apply_filters('wpml_object_id', $term->term_id, 'city_pg_mba', true, $post_lang);
        if ($term_id) {
            $translated = get_term($term_id, 'city_pg_mba');
            if ($translated && !is_wp_error($translated)) {
                $term = $translated;
            }
        }
    }

    // Base slug (keep your current logic)
    if ($post->post_type === 'mba') {
        $base = 'studia-mba';
    } elseif ($post->post_type === 'postgraduate') {
        $base = 'studia-podyplomowe';
    } else {
        $base = 'kursy';
    }

    $post_slug = get_post_field('post_name', $post->ID);

    // IMPORTANT: build relative URL first
    $relative = "/{$base}/{$term->slug}/{$post_slug}/";
    $url = home_url($relative);

    // IMPORTANT: do NOT force ICL_LANGUAGE_CODE here
    // WPML will format URL correctly when permalink is requested in that language context.
    // If you want, you may apply wpml_permalink using $post_lang (not current page lang):
    if (!empty($post_lang) && defined('ICL_SITEPRESS_VERSION')) {
        $url = apply_filters('wpml_permalink', $url, $post_lang);
    }

    return $url;

}, 10, 2);



add_filter('template_include', function ($template) {

    if (is_singular()) {
        return $template;
    }

    if (is_tax('city_pg_mba')) {
        $pt = get_query_var('post_type');

        if (in_array($pt, ['mba', 'postgraduate', 'courses'], true)) {
            $new_template = get_template_directory() . '/taxonomy-city_pg_mba.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
    }

    return $template;
}, 99);


/**
 * CPT: Exams + Taxonomies (PRICE, EXAM DATE, LOCATION, CITY)
 */

// Register CPT: Exams
function register_exams_cpt()
{
    $labels = array(
        'name' => __('Exams', 'akademiata'),
        'singular_name' => __('Exam', 'akademiata'),
        'menu_name' => __('Exams', 'akademiata'),
        'add_new_item' => __('Add New Exam', 'akademiata'),
        'edit_item' => __('Edit Exam', 'akademiata'),
        'new_item' => __('New Exam', 'akademiata'),
        'view_item' => __('View Exam', 'akademiata'),
        'search_items' => __('Search Exams', 'akademiata'),
        'not_found' => __('No exams found.', 'akademiata'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'egzaminy',
            'with_front' => false,
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon' => 'dashicons-welcome-write-blog',
        'taxonomies' => array(
            'exam_price',     // PRICE
            'exam_date',      // EXAM DATE
            'exam_location',  // LOCATION
            'exam_city',      // CITY
        ),
        'show_in_rest' => true,
    );

    register_post_type('exams', $args);
}

add_action('init', 'register_exams_cpt');


// Taxonomy: PRICE
function register_exam_price_taxonomy()
{
    register_taxonomy('exam_price', array('exams'), array(
        'labels' => array(
            'name' => __('Prices', 'akademiata'),
            'singular_name' => __('Price', 'akademiata'),
            'menu_name' => __('PRICE', 'akademiata'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'exam-price', 'with_front' => false),
        'show_in_rest' => true,
    ));
}

add_action('init', 'register_exam_price_taxonomy');


// Taxonomy: EXAM DATE
function register_exam_date_taxonomy()
{
    register_taxonomy('exam_date', array('exams'), array(
        'labels' => array(
            'name' => __('Exam Dates', 'akademiata'),
            'singular_name' => __('Exam Date', 'akademiata'),
            'menu_name' => __('EXAM DATE', 'akademiata'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'exam-date', 'with_front' => false),
        'show_in_rest' => true,
    ));
}

add_action('init', 'register_exam_date_taxonomy');


// Taxonomy: LOCATION
function register_exam_location_taxonomy()
{
    register_taxonomy('exam_location', array('exams'), array(
        'labels' => array(
            'name' => __('Locations', 'akademiata'),
            'singular_name' => __('Location', 'akademiata'),
            'menu_name' => __('LOCATION', 'akademiata'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'exam-location', 'with_front' => false),
        'show_in_rest' => true,
    ));
}

add_action('init', 'register_exam_location_taxonomy');


// Taxonomy: CITY
function register_exam_city_taxonomy()
{
    register_taxonomy('exam_city', array('exams'), array(
        'labels' => array(
            'name' => __('Cities', 'akademiata'),
            'singular_name' => __('City', 'akademiata'),
            'menu_name' => __('CITY', 'akademiata'),
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'exam-city', 'with_front' => false),
        'show_in_rest' => true,
    ));
}

add_action('init', 'register_exam_city_taxonomy');


