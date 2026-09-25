<?php
/**
 * Fix WPML language switcher links on news date archives like /aktualnosci/2025/04/.
 *
 * Without this, WPML may generate /en/2025/04/ instead of /en/news/2025/04/.
 */

add_filter('wpml_ls_languages', function ($languages) {
    if (empty($languages) || !is_array($languages)) {
        return $languages;
    }

    if (!is_date()) {
        return $languages;
    }

    $year     = (int) get_query_var('year');
    $monthnum = (int) get_query_var('monthnum');
    $day      = (int) get_query_var('day');

    if ($year <= 0) {
        return $languages;
    }

    $bases = array(
        'pl' => 'aktualnosci',
        'en' => 'news',
        'uk' => 'novyny',
        'ru' => 'novosti',
    );

    $request_path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $request_path = '/' . ltrim($request_path, '/');

    $is_news_date_archive = false;
    foreach ($bases as $base_slug) {
        if (preg_match('~/' . preg_quote($base_slug, '~') . '/\d{4}(?:/\d{2})?(?:/\d{2})?/?$~', $request_path)) {
            $is_news_date_archive = true;
            break;
        }
    }

    if (!$is_news_date_archive) {
        return $languages;
    }

    foreach ($languages as $code => $lang_data) {
        $lang_home = apply_filters('wpml_home_url', home_url('/'), $code);
        $base_slug = $bases[ $code ] ?? $bases['en'];

        $path = $base_slug . '/' . sprintf('%04d', $year) . '/';
        if ($monthnum > 0) {
            $path .= sprintf('%02d', $monthnum) . '/';
        }
        if ($day > 0) {
            $path .= sprintf('%02d', $day) . '/';
        }

        $languages[ $code ]['url'] = trailingslashit($lang_home) . $path;
    }

    return $languages;
});

add_filter('wpml_active_languages', function ($languages) {
    if (empty($languages) || !is_array($languages)) {
        return $languages;
    }

    if (!is_date()) {
        return $languages;
    }

    $year     = (int) get_query_var('year');
    $monthnum = (int) get_query_var('monthnum');
    $day      = (int) get_query_var('day');

    if ($year <= 0) {
        return $languages;
    }

    $bases = array(
        'pl' => 'aktualnosci',
        'en' => 'news',
        'uk' => 'novyny',
        'ru' => 'novosti',
    );

    $request_path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $request_path = '/' . ltrim($request_path, '/');

    $is_news_date_archive = false;
    foreach ($bases as $base_slug) {
        if (preg_match('~/' . preg_quote($base_slug, '~') . '/\d{4}(?:/\d{2})?(?:/\d{2})?/?$~', $request_path)) {
            $is_news_date_archive = true;
            break;
        }
    }

    if (!$is_news_date_archive) {
        return $languages;
    }

    foreach ($languages as $code => $lang_data) {
        $lang_home = apply_filters('wpml_home_url', home_url('/'), $code);
        $base_slug = $bases[ $code ] ?? $bases['en'];

        $path = $base_slug . '/' . sprintf('%04d', $year) . '/';
        if ($monthnum > 0) {
            $path .= sprintf('%02d', $monthnum) . '/';
        }
        if ($day > 0) {
            $path .= sprintf('%02d', $day) . '/';
        }

        $languages[ $code ]['url'] = trailingslashit($lang_home) . $path;
    }

    return $languages;
});

/**
 * Webinary singles: after WP core updates, WPML can hang in wp_head while
 * resolving language URLs / filtered queries (hreflang, adjacent posts).
 * CPT stays fully translatable; switcher uses skip_missing until translations exist.
 */
add_action('template_redirect', function () {
	if (!is_singular('webinary')) {
		return;
	}

	add_filter('wpml_seo_head_langs', '__return_false');
	add_filter('wpml_hreflangs', '__return_empty_array');

	remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
	remove_action('wp_head', 'wp_shortlink_wp_head', 10);

	global $sitepress;
	if ($sitepress && is_object($sitepress)) {
		remove_action('wp_head', array($sitepress, 'head_langs'));
	}

	// Drop any remaining WPML callbacks still hooked to wp_head for this request.
	global $wp_filter;
	if (!empty($wp_filter['wp_head']) && $wp_filter['wp_head'] instanceof WP_Hook) {
		foreach ($wp_filter['wp_head']->callbacks as $priority => $callbacks) {
			foreach ($callbacks as $id => $cb) {
				if (empty($cb['function'])) {
					continue;
				}
				$fn = $cb['function'];
				$label = '';
				if (is_string($fn)) {
					$label = $fn;
				} elseif (is_array($fn)) {
					if (is_object($fn[0])) {
						$label = get_class($fn[0]);
					} elseif (is_string($fn[0])) {
						$label = $fn[0];
					}
				}
				if ($label !== '' && preg_match('/wpml|sitepress|WPML_/i', $label)) {
					unset($wp_filter['wp_head']->callbacks[ $priority ][ $id ]);
				}
			}
		}
	}
}, 0);
