<?php
/**
 * Prevent WPML recursion on webinary singles.
 * CPT rewrite slug `/webinary/` collides with a Page of the same slug; WPML then
 * spins in permalink / language-URL resolution until max_execution_time (HTTP 500).
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return bool
 */
function akademiata_is_webinary_singular() {
	return function_exists('is_singular') && is_singular('webinary');
}

/**
 * Shared nesting depth for post_type_link guards.
 *
 * @param int|null $delta
 * @return int
 */
function akademiata_webinary_permalink_depth($delta = null) {
	static $depth = 0;
	if ($delta !== null) {
		$depth = max(0, $depth + (int) $delta);
	}
	return $depth;
}

/**
 * Drop WPML hreflang on webinary singles (same recursion path as the language switcher).
 */
function akademiata_webinary_disable_wpml_head_langs() {
	if (!akademiata_is_webinary_singular()) {
		return;
	}

	global $sitepress;
	if ($sitepress && is_object($sitepress)) {
		remove_action('wp_head', array($sitepress, 'head_langs'));
		remove_action('wp_head', array($sitepress, 'meta_generator_tag'));
	}

	add_filter('wpml_hreflangs', 'akademiata_webinary_hreflangs_current_only', 1);
	add_filter('wpml_seo_head_langs', '__return_false', 1);
}
add_action('template_redirect', 'akademiata_webinary_disable_wpml_head_langs', 0);
add_action('wp', 'akademiata_webinary_disable_wpml_head_langs', 0);

/**
 * @param array<string, string>|mixed $hreflangs
 * @return array<string, string>
 */
function akademiata_webinary_hreflangs_current_only($hreflangs) {
	if (!akademiata_is_webinary_singular()) {
		return is_array($hreflangs) ? $hreflangs : array();
	}

	$lang = apply_filters('wpml_current_language', 'pl');
	if (!is_string($lang) || $lang === '') {
		$lang = 'pl';
	}

	$url = get_permalink();
	if (!is_string($url) || $url === '') {
		return array();
	}

	return array($lang => $url);
}

/**
 * Enter permalink filter chain for webinary.
 *
 * @param string  $post_link
 * @param WP_Post $post
 * @return string
 */
function akademiata_webinary_permalink_enter($post_link, $post) {
	if (!($post instanceof WP_Post) || $post->post_type !== 'webinary') {
		return $post_link;
	}

	if (akademiata_webinary_permalink_depth() > 0) {
		$slug = $post->post_name !== '' ? $post->post_name : (string) $post->ID;
		return home_url('/webinary/' . $slug . '/');
	}

	akademiata_webinary_permalink_depth(1);
	return $post_link;
}
add_filter('post_type_link', 'akademiata_webinary_permalink_enter', 0, 2);

/**
 * Leave permalink filter chain; force a plain URL if still nested after WPML hooks.
 *
 * @param string  $post_link
 * @param WP_Post $post
 * @return string
 */
function akademiata_webinary_permalink_leave($post_link, $post) {
	if (!($post instanceof WP_Post) || $post->post_type !== 'webinary') {
		return $post_link;
	}

	if (akademiata_webinary_permalink_depth() > 1) {
		$slug = $post->post_name !== '' ? $post->post_name : (string) $post->ID;
		$post_link = home_url('/webinary/' . $slug . '/');
	}

	akademiata_webinary_permalink_depth(-1);
	return $post_link;
}
add_filter('post_type_link', 'akademiata_webinary_permalink_leave', 9999, 2);

/**
 * After WPML builds language links, replace unsafe /webinary/ URLs for other langs.
 *
 * @param array<string, mixed> $languages
 * @return array<string, mixed>
 */
function akademiata_webinary_active_languages($languages) {
	if (!akademiata_is_webinary_singular() || !is_array($languages) || $languages === array()) {
		return $languages;
	}

	$current = apply_filters('wpml_current_language', 'pl');

	foreach ($languages as $code => $lang) {
		if (!is_array($lang) || !empty($lang['active'])) {
			continue;
		}

		$missing = !empty($lang['missing']);
		$url     = isset($lang['url']) ? (string) $lang['url'] : '';
		if ($missing || $url === '' || strpos($url, '/webinary/') !== false) {
			$lang_home = apply_filters('wpml_home_url', home_url('/'), $code);
			$languages[ $code ]['url'] = trailingslashit(is_string($lang_home) ? $lang_home : home_url('/'));
		}
	}

	unset($current);
	return $languages;
}
add_filter('wpml_active_languages', 'akademiata_webinary_active_languages', 5);
add_filter('wpml_ls_languages', 'akademiata_webinary_active_languages', 5);
