<?php

// MENUS
function _custom_theme_register_menu()
{
    register_nav_menus(
        array(
            'menu-main' => __('Main Menu', 'akademiata'),
            'menu-footer' => __('Footer Menu', 'akademiata'),
            'mega-menu' => __('Mega Menu', 'akademiata'),
            'lang-menu' => __('Language Menu', 'akademiata'),
        )
    );
}

add_action('init', '_custom_theme_register_menu');

class Walker_Nav_With_Image extends Walker_Nav_Menu
{
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0)
    {
        $image_url = get_field('menu_image', $item); // ACF поле до menu item
        $title = apply_filters('the_title', $item->title, $item->ID);

        $classes = implode(' ', $item->classes);
        $output .= '<li class="' . esc_attr($classes) . '">';

        $output .= '<a href="' . esc_url($item->url) . '">';

        if ($image_url) {
            $output .= '<img width="105" src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" class="menu-icon" /> ';
        }

        $output .= esc_html($title) . '</a>';
    }

    public function end_el(&$output, $item, $depth = 0, $args = [], $id = 0)
    {
        $output .= '</li>';
    }
}


function custom_setup()
{
    // Images
    add_theme_support('post-thumbnails');

    // Title tags
    add_theme_support('title-tag');

    // Custom logo
    add_theme_support('custom-logo');

    // Languages
    load_theme_textdomain('akademiata', get_template_directory() . '/languages');

    // HTML 5 - Example : deletes type="*" in scripts and style tags
    add_theme_support('html5', ['script', 'style']);

    // Remove SVG and global styles
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

    // Remove wp_footer actions which add's global inline styles
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);

    // Remove render_block filters which adds unnecessary stuff
    remove_filter('render_block', 'wp_render_duotone_support');
    remove_filter('render_block', 'wp_restore_group_inner_container');
    remove_filter('render_block', 'wp_render_layout_support_flag');

    // Remove useless WP image sizes
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');

    // Custom image sizes
    add_image_size('program_banner', 955, 900, true);
    add_image_size('specialization_card_thumb', 383, 256, true);
    add_image_size('product_slider_thumb', 400, 230, true);
    add_image_size('product_slider_thumb_mobile', 280, 350, true);
    add_image_size('main_slider_banner', 1500, 804, true);
    add_image_size('mobile_slider_banner', 350, 270, true);
    add_image_size('partner_logo', 245, 56, true);
    add_image_size('study_slider_image', 670, 440, true);
    add_image_size('study_slider_image_mobile', 300, 200, true);
    add_image_size('image_content_slider', 950, 840, true);
    add_image_size('image_content_slider_mobile', 400, 350, true);
    add_image_size('card_image', 460, 448, true);
    add_image_size('card_image_mobile', 768, 748, true);
    add_image_size('card_image_2x', 920, 896, true);
    add_image_size('program_image', 1920, 770, true);
    add_image_size('program_image_mobile', 380, 230, true);
    add_image_size('interests_image', 905, 384, true);
    add_image_size('interests_image_mobile', 350, 284, true);
    add_image_size('offer_image_mobile_pion', 260, 350, true);
    // add_image_size( '424x424', 424, 424, true );
    // add_image_size( '1920', 1920, 9999 );


}

add_action('after_setup_theme', 'custom_setup');

/**
 * WPML language codes (excl. default) — legacy per-lang logo theme_mods.
 *
 * @return array<int, string>
 */
function akademiata_logo_extra_lang_codes(): array {
	$default = akademiata_normalize_theme_lang_code(
		(string) apply_filters( 'wpml_default_language', null )
	);
	$preferred = [ 'en', 'uk', 'ru' ];
	$codes     = [];

	$languages = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );
	if ( is_array( $languages ) && $languages !== [] ) {
		foreach ( $languages as $lang ) {
			$code = akademiata_normalize_theme_lang_code( (string) ( $lang['code'] ?? '' ) );
			if ( $code !== '' && $code !== $default && ! in_array( $code, $codes, true ) ) {
				$codes[] = $code;
			}
		}
	}

	if ( $codes === [] ) {
		foreach ( $preferred as $code ) {
			if ( $code !== $default ) {
				$codes[] = $code;
			}
		}
	}

	return $codes;
}

/**
 * @return array{url: string, alt: string, id: int}
 */
function akademiata_logo_from_attachment( int $attachment_id, string $fallback_alt = '' ): array {
	if ( $attachment_id <= 0 ) {
		return [
			'url' => '',
			'alt' => $fallback_alt,
			'id'  => 0,
		];
	}

	$url = wp_get_attachment_image_url( $attachment_id, 'full' );
	if ( ! $url ) {
		return [
			'url' => '',
			'alt' => $fallback_alt,
			'id'  => 0,
		];
	}

	$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

	return [
		'url' => $url,
		'alt' => $alt !== '' ? $alt : $fallback_alt,
		'id'  => $attachment_id,
	];
}

/**
 * Theme static fallback for header logo by language.
 */
function akademiata_header_logo_fallback_rel( string $lang ): string {
	$lang = akademiata_normalize_theme_lang_code( $lang );
	if ( $lang === 'en' ) {
		return 'static/img/logo_ATA_EN_271x56_general_header.png';
	}

	$png = get_template_directory() . '/static/img/ATA_logo_main.png';
	if ( is_readable( $png ) ) {
		return 'static/img/ATA_logo_main.png';
	}

	return 'static/img/ATA_logo_main.webp';
}

/**
 * Theme static fallback for footer logo by language.
 */
function akademiata_footer_logo_fallback_rel( string $lang ): string {
	$lang = akademiata_normalize_theme_lang_code( $lang );
	if ( $lang === 'en' ) {
		return 'static/img/logo_ATA_248x108_white_EN_footer.png';
	}

	return 'static/img/logoFooterwhite.png';
}

/**
 * Header logo for current (or given) WPML language.
 *
 * @return array{url: string, alt: string}
 */
function akademiata_get_header_logo( ?string $lang = null ): array {
	$lang = akademiata_normalize_theme_lang_code(
		$lang !== null ? $lang : (string) apply_filters( 'wpml_current_language', null )
	);
	$default = akademiata_normalize_theme_lang_code(
		(string) apply_filters( 'wpml_default_language', null )
	);

	$alt_pl = __( 'Logo - Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata' );
	$alt_en = 'Logo - University of Technology and Arts, Applied Sciences in Warsaw';
	$alt    = ( $lang === 'en' ) ? $alt_en : $alt_pl;

	// Non-PL: dedicated Customizer field (WPML admin switcher does not split custom_logo).
	if ( $lang !== $default ) {
		$from_lang = akademiata_logo_from_attachment(
			(int) get_theme_mod( 'akademiata_header_logo_' . $lang ),
			$alt
		);
		if ( $from_lang['url'] !== '' ) {
			return [
				'url' => $from_lang['url'],
				'alt' => $from_lang['alt'],
			];
		}

		$rel  = akademiata_header_logo_fallback_rel( $lang );
		$path = get_template_directory() . '/' . $rel;

		return [
			'url' => is_readable( $path ) ? get_template_directory_uri() . '/' . $rel : '',
			'alt' => $alt,
		];
	}

	$from_custom = akademiata_logo_from_attachment( (int) get_theme_mod( 'custom_logo' ), $alt );
	if ( $from_custom['url'] !== '' ) {
		return [
			'url' => $from_custom['url'],
			'alt' => $from_custom['alt'],
		];
	}

	$rel  = akademiata_header_logo_fallback_rel( $lang );
	$path = get_template_directory() . '/' . $rel;

	return [
		'url' => is_readable( $path ) ? get_template_directory_uri() . '/' . $rel : '',
		'alt' => $alt,
	];
}

/**
 * Footer logo for current (or given) WPML language.
 *
 * @return array{url: string, alt: string}
 */
function akademiata_get_footer_logo( ?string $lang = null ): array {
	$lang = akademiata_normalize_theme_lang_code(
		$lang !== null ? $lang : (string) apply_filters( 'wpml_current_language', null )
	);
	$default = akademiata_normalize_theme_lang_code(
		(string) apply_filters( 'wpml_default_language', null )
	);

	$alt_pl = __( 'Logo - Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata' );
	$alt_en = 'Logo - University of Technology and Arts, Applied Sciences in Warsaw';
	$alt    = ( $lang === 'en' ) ? $alt_en : $alt_pl;

	if ( $lang !== $default ) {
		$from_lang = akademiata_logo_from_attachment(
			(int) get_theme_mod( 'akademiata_footer_logo_' . $lang ),
			$alt
		);
		if ( $from_lang['url'] !== '' ) {
			return [
				'url' => $from_lang['url'],
				'alt' => $from_lang['alt'],
			];
		}

		$rel  = akademiata_footer_logo_fallback_rel( $lang );
		$path = get_template_directory() . '/' . $rel;

		return [
			'url' => is_readable( $path ) ? get_template_directory_uri() . '/' . $rel : '',
			'alt' => $alt,
		];
	}

	$from_custom = akademiata_logo_from_attachment( (int) get_theme_mod( 'akademiata_footer_logo' ), $alt );
	if ( $from_custom['url'] !== '' ) {
		return [
			'url' => $from_custom['url'],
			'alt' => $from_custom['alt'],
		];
	}

	$rel  = akademiata_footer_logo_fallback_rel( $lang );
	$path = get_template_directory() . '/' . $rel;

	return [
		'url' => is_readable( $path ) ? get_template_directory_uri() . '/' . $rel : '',
		'alt' => $alt,
	];
}

/**
 * Appearance → Customize → Logotypy (języki) — PL + EN/UK/RU.
 * WPML language switcher does not give separate Customizer logos; use these fields.
 */
function akademiata_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'akademiata_logos',
		[
			'title'       => __( 'Logotypy (języki)', 'akademiata' ),
			'description' => __( 'Tu ustawiasz osobne logo dla EN / UK / RU. Przełącznik WPML w pasku admina nie zmienia pola „Logo” w Tożsamości witryny — zawsze jest jedno (PL).', 'akademiata' ),
			'priority'    => 21,
		]
	);

	if ( $wp_customize->get_control( 'custom_logo' ) ) {
		$control = $wp_customize->get_control( 'custom_logo' );
		$control->section     = 'akademiata_logos';
		$control->label       = __( 'Logo nagłówka — polski (PL)', 'akademiata' );
		$control->description = __( 'Tylko wersja polska strony.', 'akademiata' );
		$control->priority    = 10;
	}

	$wp_customize->add_setting(
		'akademiata_footer_logo',
		[
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'default'           => 0,
		]
	);

	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'akademiata_footer_logo',
			[
				'label'       => __( 'Logo stopki — polski (PL)', 'akademiata' ),
				'description' => __( 'Opcjonalnie. Puste = domyślne logo stopki z motywu.', 'akademiata' ),
				'section'     => 'akademiata_logos',
				'mime_type'   => 'image',
				'priority'    => 11,
			]
		)
	);

	$lang_labels = [
		'en' => __( 'angielski (EN)', 'akademiata' ),
		'uk' => __( 'ukraiński (UK)', 'akademiata' ),
		'ru' => __( 'rosyjski (RU)', 'akademiata' ),
	];

	$priority = 20;
	foreach ( akademiata_logo_extra_lang_codes() as $code ) {
		$lang_label = $lang_labels[ $code ] ?? strtoupper( $code );

		$header_setting = 'akademiata_header_logo_' . $code;
		$wp_customize->add_setting(
			$header_setting,
			[
				'type'              => 'theme_mod',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
				'default'           => 0,
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				$header_setting,
				[
					/* translators: %s: language label */
					'label'       => sprintf( __( 'Logo nagłówka — %s', 'akademiata' ), $lang_label ),
					'description' => __( 'Wybierz obrazek i Opublikuj. Puste = domyślne logo EN z motywu.', 'akademiata' ),
					'section'     => 'akademiata_logos',
					'mime_type'   => 'image',
					'priority'    => $priority++,
				]
			)
		);

		$footer_setting = 'akademiata_footer_logo_' . $code;
		$wp_customize->add_setting(
			$footer_setting,
			[
				'type'              => 'theme_mod',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
				'default'           => 0,
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				$footer_setting,
				[
					/* translators: %s: language label */
					'label'       => sprintf( __( 'Logo stopki — %s', 'akademiata' ), $lang_label ),
					'description' => __( 'Opcjonalnie. Puste = domyślne logo stopki dla tego języka.', 'akademiata' ),
					'section'     => 'akademiata_logos',
					'mime_type'   => 'image',
					'priority'    => $priority++,
				]
			)
		);
	}
}

add_action( 'customize_register', 'akademiata_customize_register' );

// remove default image sizes to avoid overcharging server - comment line if you need size
function remove_default_image_sizes($sizes)
{
    unset($sizes['large']);
    unset($sizes['medium']);
    unset($sizes['medium_large']);
    return $sizes;
}

add_filter('intermediate_image_sizes_advanced', 'remove_default_image_sizes');

// disabling big image sizes scaled
add_filter('big_image_size_threshold', '__return_false');

// Move Yoast to bottom
function yoasttobottom()
{
    return 'low';
}

add_filter('wpseo_metabox_prio', 'yoasttobottom');

// Remove WP Emoji
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

// delete wp-embed.js from footer
function my_deregister_scripts()
{
    wp_deregister_script('wp-embed');
}

add_action('wp_footer', 'my_deregister_scripts');

// delete jquery migrate
function dequeue_jquery_migrate(&$scripts)
{
    if (!is_admin()) {
        $scripts->remove('jquery');
        $scripts->add('jquery', 'https://code.jquery.com/jquery-3.6.1.min.js', null, null, true);
    }
}

add_filter('wp_default_scripts', 'dequeue_jquery_migrate');

function enqueue_fancybox_scripts()
{
    wp_enqueue_style('fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css');
    wp_enqueue_script('fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js', [], null, true);
}

add_action('wp_enqueue_scripts', 'enqueue_fancybox_scripts');


// add SVG to allowed file uploads
function add_file_types_to_uploads($mime_types)
{
    $mime_types['svg'] = 'image/svg+xml';

    return $mime_types;
}

add_action('upload_mimes', 'add_file_types_to_uploads', 1, 1);

//disable update emails
add_filter('auto_plugin_update_send_email', '__return_false');
add_filter('auto_theme_update_send_email', '__return_false');

function clean_wp_nav_menu_classes($classes, $item, $args, $depth)
{
    return []; // Remove all classes
}

add_filter('nav_menu_css_class', 'clean_wp_nav_menu_classes', 10, 4);

if (function_exists('acf_add_options_page')) {

    // Add the main options page
    acf_add_options_page(array(
        'page_title' => 'Theme Settings',       // Page title shown at the top of the screen
        'menu_title' => 'Theme Settings',       // Title shown in the WordPress admin menu
        'menu_slug' => 'theme-general-settings', // Unique slug for the page
        'capability' => 'edit_posts',           // Capability required to view this page
        'redirect' => false                   // Disable automatic redirection to the first subpage
    ));

    // Add a subpage for header settings
    acf_add_options_sub_page(array(
        'page_title' => 'Header Settings',      // Page title
        'menu_title' => 'Header',               // Menu label
        'parent_slug' => 'theme-general-settings'// Parent page slug
    ));

    // Add a subpage for footer settings
    acf_add_options_sub_page(array(
        'page_title' => 'Footer Settings',      // Page title
        'menu_title' => 'Footer',               // Menu label
        'parent_slug' => 'theme-general-settings'// Parent page slug
    ));

    // Add a subpage for Contact Postgraduate
    acf_add_options_sub_page(array(
        'page_title' => 'Contact-Postgraduate Wroclaw',
        'menu_title' => 'Contact-Postgraduate Wroclaw',
        'parent_slug' => 'theme-general-settings',
        'menu_slug' => 'contact_postgraduate',
        'post_id' => 'contact_postgraduate',
    ));

// Add a subpage for Contact MBA
    acf_add_options_sub_page(array(
        'page_title' => 'Contact-MBA Wroclaw',
        'menu_title' => 'Contact-MBA Wroclaw',
        'parent_slug' => 'theme-general-settings',
        'menu_slug' => 'contact_mba',
        'post_id' => 'contact_mba',
    ));

// Add a subpage for Contact Postgraduate and MBA in Warsaw
    acf_add_options_sub_page(array(
        'page_title' => 'Contact-Postgraduate and MBA Warsaw',
        'menu_title' => 'Contact-Postgraduate and MBA Warsaw',
        'parent_slug' => 'theme-general-settings',
        'menu_slug' => 'contact_warsaw',
        'post_id' => 'contact_warsaw',
    ));

}


// Maps language code → base slug for NEWS archives
function ata_news_base_by_lang($lang)
{
    $map = [
        'pl' => 'aktualnosci',
        'en' => 'news',
        'uk' => 'novyny',
        'ru' => 'novosti',
    ];
    return $map[$lang] ?? 'news';
}

/**
 * Build month archive URL like:
 *  PL (default, no prefix if configured) -> /aktualnosci/YYYY/MM/
 *  EN (dir prefix)                       -> /en/news/YYYY/MM/
 * Uses WPML home per language so it matches your WPML URL mode.
 */
function ata_news_month_link_lang($year, $month, $lang = '')
{
    $year = (int)$year;
    $month = sprintf('%02d', (int)$month);

    if (!$lang) {
        if (function_exists('apply_filters')) {
            $lang = apply_filters('wpml_current_language', null);
        }
        if (!$lang && defined('ICL_LANGUAGE_CODE')) {
            $lang = ICL_LANGUAGE_CODE;
        }
        if (!$lang) {
            $lang = 'pl';
        }
    }

    $lang_home = function_exists('apply_filters')
        ? apply_filters('wpml_home_url', home_url('/'), $lang)
        : home_url('/');

    $base = ata_news_base_by_lang($lang);

    // Ensure trailing slash on home, then append base/YYYY/MM/
    $url = trailingslashit($lang_home) . trailingslashit($base) . $year . '/' . $month . '/';
    return $url;
}


add_action('init', function () {

    add_rewrite_tag('%ata_date%', '1');


    add_rewrite_tag('%year%', '([0-9]{4})');
    add_rewrite_tag('%monthnum%', '([0-9]{2})');
    add_rewrite_tag('%day%', '([0-9]{2})');
    add_rewrite_tag('%paged%', '([0-9]+)');

    $bases = ['aktualnosci', 'news', 'novyny', 'novosti'];
    $bases = array_unique(array_filter($bases));

    foreach ($bases as $base) {
        // YYYY/MM
        add_rewrite_rule(
            '^' . $base . '/([0-9]{4})/([0-9]{2})/?$',
            'index.php?ata_date=1&year=$matches[1]&monthnum=$matches[2]',
            'top'
        );
        // YYYY/MM/page/N
        add_rewrite_rule(
            '^' . $base . '/([0-9]{4})/([0-9]{2})/page/([0-9]+)/?$',
            'index.php?ata_date=1&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]',
            'top'
        );
        // YYYY/MM/DD
        add_rewrite_rule(
            '^' . $base . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$',
            'index.php?ata_date=1&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]',
            'top'
        );
        // YYYY/MM/DD/page/N
        add_rewrite_rule(
            '^' . $base . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/page/([0-9]+)/?$',
            'index.php?ata_date=1&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]',
            'top'
        );
    }
});


add_filter('template_include', function ($template) {
    if (get_query_var('ata_date')) {
        if ($custom = locate_template('date-news-multilang.php')) return $custom;
    }
    return $template;
});


add_filter( 'wpseo_title', function ( $title ) {

	if ( ! is_post_type_archive() ) {
		return $title;
	}

	// Map CPT archive => page slug you use as "base page"
	$map = [
		'postgraduate' => 'studia-podyplomowe',
		'mba'          => 'studia-mba',
		'courses'      => 'kursy',
		'exams'        => 'egzaminy',
	];

	$pt = get_query_var( 'post_type' );

	// If multiple post types returned as array
	if ( is_array( $pt ) ) {
		$pt = $pt[0] ?? '';
	}

	if ( empty( $pt ) || empty( $map[ $pt ] ) ) {
		return $title;
	}

	$current_lang = apply_filters( 'wpml_current_language', null );

	$base_page = get_page_by_path( $map[ $pt ] );
	$page_id   = $base_page ? apply_filters( 'wpml_object_id', $base_page->ID, 'page', true, $current_lang ) : 0;

	if ( ! $page_id ) {
		return $title;
	}

	$page_title = get_the_title( $page_id );
	if ( empty( $page_title ) ) {
		return $title;
	}

	return $page_title . ' - ' . get_bloginfo( 'name' );
}, 9999 );
