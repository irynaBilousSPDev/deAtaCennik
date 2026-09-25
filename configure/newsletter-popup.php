<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_nl_popup_defaults() {
    return [
        'pg' => [
            'enabled'       => 0,
            'form_id'       => '',
            'badge'         => 'Newsletter ATA',
            'title'         => 'Twoje {amount} zniżki{br}już na Ciebie czeka.',
            'amount'        => '1000 zł',
            'lead'          => 'Zostaw swoje dane – skontaktujemy się z Tobą i przekażemy voucher na studia podyplomowe w ATA.',
            'thank_you'     => 'Wkrótce skontaktujemy się z Tobą telefonicznie lub mailowo i przekażemy voucher na {amount} na studia podyplomowe w ATA.',
            'promo_note'    => 'Promocja nie łączy się z innymi promocjami i rabatami. Szczegóły i warunki promocji dostępne są {link}.',
            'promo_link'    => 'TUTAJ',
            'promo_url'     => '',
            'delay'         => 8,
            'remember_days' => 14,
            'show_on'       => ['singles'],
        ],
        'mba' => [
            'enabled'       => 0,
            'form_id'       => '',
            'badge'         => 'Newsletter ATA',
            'title'         => 'Twoje {amount} zniżki{br}już na Ciebie czeka.',
            'amount'        => '2000 zł',
            'lead'          => 'Zostaw swoje dane – skontaktujemy się z Tobą i przekażemy voucher na studia MBA w ATA.',
            'thank_you'     => 'Wkrótce skontaktujemy się z Tobą telefonicznie lub mailowo i przekażemy voucher na {amount} na studia MBA w ATA.',
            'promo_note'    => 'Promocja nie łączy się z innymi promocjami i rabatami. Szczegóły i warunki promocji dostępne są {link}.',
            'promo_link'    => 'TUTAJ',
            'promo_url'     => '',
            'delay'         => 8,
            'remember_days' => 14,
            'show_on'       => ['singles'],
        ],
    ];
}

/**
 * @param string $key pg|mba
 * @return array<string, mixed>
 */
function akademiata_nl_popup_get($key) {
    $defaults_all = akademiata_nl_popup_defaults();
    if (!isset($defaults_all[$key])) {
        return [];
    }

    $defaults = $defaults_all[$key];
    $acf = akademiata_nl_popup_read_acf($key);
    if (!is_array($acf)) {
        return $defaults;
    }

    $out = $defaults;
    foreach ($defaults as $field => $default_val) {
        if (!array_key_exists($field, $acf)) {
            continue;
        }
        $val = $acf[$field];
        if ($field === 'show_on') {
            $out[$field] = (is_array($val) && $val !== []) ? array_values($val) : $default_val;
            continue;
        }
        if ($field === 'enabled') {
            $out[$field] = !empty($val) ? 1 : 0;
            continue;
        }
        if ($field === 'delay' || $field === 'remember_days') {
            $out[$field] = is_numeric($val) ? (int) $val : $default_val;
            continue;
        }
        if ($val !== '' && $val !== null) {
            $out[$field] = $val;
        }
    }

    return $out;
}

/**
 * @param string $key pg|mba
 * @return array<string, mixed>|null
 */
function akademiata_nl_popup_read_acf($key) {
    $name = 'nl_popup_' . $key;

    // Raw options first — ACF get_field() can loop with WPML on unrelated CPT singles.
    foreach (['options_' . $name, 'option_' . $name] as $option_key) {
        $raw = get_option($option_key);
        if (is_array($raw) && $raw !== []) {
            return $raw;
        }
    }

    if (function_exists('get_field')) {
        foreach (['option', 'options'] as $id) {
            $acf = get_field($name, $id);
            if (is_array($acf) && $acf !== []) {
                return $acf;
            }
        }
    }

    return null;
}

/**
 * @param string $key pg|mba
 */
function akademiata_nl_popup_current_cpt() {
    if (is_singular(['mba', 'postgraduate'])) {
        return (string) get_post_type();
    }
    if (is_post_type_archive('mba') || is_post_type_archive('postgraduate')) {
        return (string) get_query_var('post_type');
    }
    if (is_tax('city_pg_mba')) {
        global $wp_query;
        $type = is_object($wp_query) ? (string) $wp_query->get('post_type') : '';
        return $type !== '' ? $type : 'postgraduate';
    }
    if (!is_page()) {
        return '';
    }
    $page = get_queried_object();
    $slug = ($page && !empty($page->post_name)) ? (string) $page->post_name : '';
    if (in_array($slug, ['studia-mba', 'mba'], true)) {
        return 'mba';
    }
    if (in_array($slug, ['studia-podyplomowe', 'postgraduate', 'post-graduate'], true)) {
        return 'postgraduate';
    }
    return '';
}

/**
 * @param string $key pg|mba
 */
function akademiata_nl_popup_is_auto_context($key) {
    $cpt = $key === 'mba' ? 'mba' : 'postgraduate';

    // Not an MBA/PG surface — do not run archive/page slug detection (WPML-safe).
    if (
        !is_singular($cpt)
        && !is_post_type_archive($cpt)
        && !is_tax('city_pg_mba')
        && !is_page()
    ) {
        return false;
    }

    $config = akademiata_nl_popup_get($key);
    $show_on = is_array($config['show_on'] ?? null) ? $config['show_on'] : ['singles'];

    if (is_singular($cpt)) {
        return in_array('singles', $show_on, true);
    }

    if (!in_array('archives', $show_on, true)) {
        return false;
    }

    return akademiata_nl_popup_current_cpt() === $cpt;
}

/**
 * @param string $key pg|mba
 */
function akademiata_nl_popup_should_render($key) {
    $config = akademiata_nl_popup_get($key);
    return !empty($config['enabled']) && akademiata_nl_popup_is_auto_context($key);
}

/** True if either popup is enabled in Theme Settings (not page-context). */
function akademiata_nl_popup_any_enabled() {
    foreach (['pg', 'mba'] as $key) {
        $config = akademiata_nl_popup_get($key);
        if (!empty($config['enabled'])) {
            return true;
        }
    }
    return false;
}

/**
 * @param string      $text
 * @param string      $amount
 * @return string
 */
function akademiata_nl_popup_format_title($text, $amount) {
    $text = is_string($text) ? $text : '';
    $amount = is_string($amount) ? trim($amount) : '';
    if ($text !== '' && strpos($text, '{br}') === false && preg_match('/\sjuż na Ciebie/u', $text)) {
        $text = preg_replace('/\s+(już na Ciebie)/u', '{br}$1', $text, 1);
    }
    $safe = esc_html($text);
    if ($amount !== '' && strpos($text, '{amount}') !== false) {
        $safe = str_replace('{amount}', '<span class="nl-popup__amount">' . esc_html($amount) . '</span>', $safe);
    }
    return str_replace('{br}', '<br>', $safe);
}

/**
 * @param string $text
 * @param string $amount
 * @return string
 */
function akademiata_nl_popup_format_thanks($text, $amount) {
    $text = is_string($text) ? trim($text) : '';
    $amount = is_string($amount) ? trim($amount) : '';
    if ($text === '') {
        return '';
    }
    $safe = esc_html($text);
    if ($amount === '') {
        return $safe;
    }
    $bold = '<strong class="nl-popup__thanks-amount">' . esc_html($amount) . ' zniżki</strong>';
    if (strpos($text, '{amount}') !== false) {
        return str_replace('{amount}', $bold, $safe);
    }
    $phrase = $amount . ' zniżki';
    if (strpos($text, $phrase) !== false) {
        return str_replace(esc_html($phrase), $bold, $safe);
    }
    return str_replace(esc_html($amount), '<strong class="nl-popup__thanks-amount">' . esc_html($amount) . '</strong>', $safe);
}

/**
 * @param string $key pg|mba
 * @return string
 */
function akademiata_nl_popup_browse_url($key) {
    $cpt = $key === 'mba' ? 'mba' : 'postgraduate';
    $link = get_post_type_archive_link($cpt);
    if (is_string($link) && $link !== '') {
        return $link;
    }
    $slug = $key === 'mba' ? 'studia-mba' : 'studia-podyplomowe';
    return home_url('/' . $slug . '/');
}

/**
 * @param string $note
 * @param string $link_text
 * @param string $url
 * @return string
 */
function akademiata_nl_popup_format_promo($note, $link_text, $url) {
    $note = is_string($note) ? trim($note) : '';
    if ($note === '') {
        return '';
    }
    $safe = esc_html($note);
    $link_text = is_string($link_text) ? trim($link_text) : '';
    $url = is_string($url) ? trim($url) : '';
    $label = $link_text !== '' ? esc_html($link_text) : '';
    $link = ($url !== '' && $label !== '')
        ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>'
        : $label;
    if ($link !== '' && strpos($note, '{link}') !== false) {
        return str_replace('{link}', $link, $safe);
    }
    if ($link !== '' && strpos($note, '{link}') === false) {
        return $safe . ' ' . $link;
    }
    return str_replace('{link}', '', $safe);
}

/**
 * @param int $form_id
 * @return string
 */
function akademiata_nl_popup_cf7_html($form_id) {
    $form_id = (int) $form_id;
    if ($form_id <= 0 || !function_exists('wpcf7_contact_form')) {
        return '';
    }
    $cf7 = wpcf7_contact_form($form_id);
    if (!$cf7) {
        return '';
    }
    $hash = method_exists($cf7, 'hash') ? $cf7->hash() : '';
    $id_attr = $hash ?: $form_id;
    $title = method_exists($cf7, 'title') ? $cf7->title() : '';

    add_filter('wpcf7_autop_or_shortcode', '__return_false');
    add_filter('wpcf7_autop', '__return_false');

    return do_shortcode(
        '[contact-form-7 id="' . esc_attr((string) $id_attr) . '"'
        . ($title !== '' ? ' title="' . esc_attr($title) . '"' : '')
        . ']'
    );
}

function akademiata_nl_popup_render() {
    if (is_admin()) {
        return;
    }

    if (
        !is_singular(['mba', 'postgraduate'])
        && !is_post_type_archive(['mba', 'postgraduate'])
        && !is_tax('city_pg_mba')
        && !is_page()
    ) {
        return;
    }

    if (!akademiata_nl_popup_any_enabled()) {
        return;
    }

    foreach (['pg', 'mba'] as $key) {
        if (!akademiata_nl_popup_should_render($key)) {
            continue;
        }
        $config = akademiata_nl_popup_get($key);
        $form_html = akademiata_nl_popup_cf7_html((int) ($config['form_id'] ?? 0));
        $auto = akademiata_nl_popup_is_auto_context($key);
        set_query_var('nl_popup_key', $key);
        set_query_var('nl_popup_config', $config);
        set_query_var('nl_popup_form', $form_html);
        set_query_var('nl_popup_auto', $auto);
        get_template_part('template-parts/newsletter-popup');
    }
}
add_action('wp_footer', 'akademiata_nl_popup_render', 5);

function akademiata_enqueue_newsletter_popup_script() {
    if (is_admin()) {
        return;
    }

    // MBA/PG popups only — skip unrelated singles (webinary + WPML/ACF loop).
    if (
        !is_singular(['mba', 'postgraduate'])
        && !is_post_type_archive(['mba', 'postgraduate'])
        && !is_tax('city_pg_mba')
        && !is_page()
    ) {
        return;
    }

    if (!akademiata_nl_popup_any_enabled()) {
        return;
    }

    $script_path = get_template_directory() . '/assets/dist/js/newsletterPopup.js';
    $script_ver  = file_exists($script_path) ? filemtime($script_path) : null;

    wp_enqueue_script(
        'akademiata-newsletter-popup',
        get_template_directory_uri() . '/assets/dist/js/newsletterPopup.js',
        array(),
        $script_ver,
        true
    );
}
add_action('wp_enqueue_scripts', 'akademiata_enqueue_newsletter_popup_script', 102);

/**
 * @param string[] $excluded
 * @return string[]
 */
function akademiata_nl_popup_rocket_delay_js_exclusions($excluded) {
    if (!is_array($excluded)) {
        $excluded = array();
    }
    $excluded[] = 'newsletterPopup';
    $excluded[] = 'contact-form-7';
    return $excluded;
}
add_filter('rocket_delay_js_exclusions', 'akademiata_nl_popup_rocket_delay_js_exclusions');
