<?php

if (!defined('ABSPATH')) {
    exit;
}

function akademiata_offer_daily_interest_min_count() {
    return 2;
}

/**
 * @param int|null $post_id
 */
function akademiata_should_show_offer_daily_interest($post_id = null) {
    if (!is_singular(array('bachelor', 'master'))) {
        return false;
    }

    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return false;
    }

    if (!function_exists('get_field')) {
        return false;
    }

    return !empty(get_field('register_url', $post_id));
}

/**
 * @return string
 */
function akademiata_offer_daily_interest_lang() {
    $lang = apply_filters('wpml_current_language', null);
    return is_string($lang) && $lang !== '' ? $lang : 'pl';
}

/**
 * @param int    $post_id
 * @param string $lang
 * @return string
 */
function akademiata_offer_daily_interest_count_transient_key($post_id, $lang) {
    return 'akademiata_offer_daily_' . wp_date('Y-m-d') . '_' . (int) $post_id . '_' . sanitize_key($lang);
}

/**
 * @param int    $post_id
 * @param string $lang
 * @param string $session_token
 * @return string
 */
function akademiata_offer_daily_interest_session_transient_key($post_id, $lang, $session_token) {
    $hash = hash('sha256', (string) $session_token);

    return 'akademiata_offer_daily_sess_'
        . wp_date('Y-m-d') . '_'
        . (int) $post_id . '_'
        . sanitize_key($lang) . '_'
        . substr($hash, 0, 16);
}

/**
 * @return int
 */
function akademiata_offer_daily_interest_seconds_until_midnight() {
    $timezone = wp_timezone();
    $now      = new DateTime('now', $timezone);
    $midnight = new DateTime('tomorrow', $timezone);

    return max(60, $midnight->getTimestamp() - $now->getTimestamp());
}

/**
 * @param int $post_id
 * @return bool
 */
function akademiata_offer_daily_interest_is_valid_post($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return false;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        return false;
    }

    return in_array($post->post_type, array('bachelor', 'master'), true);
}

/**
 * @param string $session_token
 * @return bool
 */
function akademiata_offer_daily_interest_is_valid_session_token($session_token) {
    $session_token = (string) $session_token;

    return (bool) preg_match('/^[a-f0-9-]{16,64}$/i', $session_token);
}

/**
 * @param int    $post_id
 * @param string $lang
 * @return int
 */
function akademiata_offer_daily_interest_get_count($post_id, $lang) {
    return max(0, (int) get_transient(akademiata_offer_daily_interest_count_transient_key($post_id, $lang)));
}

/**
 * @param int    $post_id
 * @param string $session_token
 * @param string $lang
 * @return int
 */
function akademiata_offer_daily_interest_register_view($post_id, $session_token, $lang) {
    if (!akademiata_offer_daily_interest_is_valid_post($post_id)) {
        return 0;
    }

    if (!akademiata_offer_daily_interest_is_valid_session_token($session_token)) {
        return akademiata_offer_daily_interest_get_count($post_id, $lang);
    }

    if (!akademiata_should_show_offer_daily_interest($post_id)) {
        return 0;
    }

    $ttl         = akademiata_offer_daily_interest_seconds_until_midnight();
    $session_key = akademiata_offer_daily_interest_session_transient_key($post_id, $lang, $session_token);
    $count_key   = akademiata_offer_daily_interest_count_transient_key($post_id, $lang);

    if (false === get_transient($session_key)) {
        set_transient($session_key, 1, $ttl);
        $count = akademiata_offer_daily_interest_get_count($post_id, $lang) + 1;
        set_transient($count_key, $count, $ttl);
        return $count;
    }

    return akademiata_offer_daily_interest_get_count($post_id, $lang);
}

/**
 * @param int $count
 * @return string
 */
function akademiata_offer_daily_interest_message($count) {
    $count = (int) $count;
    if ($count <= 0) {
        return '';
    }

    $lang = akademiata_offer_daily_interest_lang();

    if ($lang === 'en') {
        if ($count === 1) {
            return akademiata_get_theme_lang_string('offer_daily_interest_en_one');
        }

        return sprintf(akademiata_get_theme_lang_string('offer_daily_interest_en_many'), $count);
    }

    if ($count === 1) {
        return akademiata_get_theme_lang_string('offer_daily_interest_pl_one');
    }

    $mod10  = $count % 10;
    $mod100 = $count % 100;

    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return sprintf(akademiata_get_theme_lang_string('offer_daily_interest_pl_few'), $count);
    }

    return sprintf(akademiata_get_theme_lang_string('offer_daily_interest_pl_many'), $count);
}

/**
 * @param int $count
 * @return array<string, mixed>
 */
function akademiata_offer_daily_interest_payload($count) {
    $count    = max(0, (int) $count);
    $min      = akademiata_offer_daily_interest_min_count();
    $show     = $count >= $min;
    $message  = $show ? akademiata_offer_daily_interest_message($count) : '';

    return array(
        'count'   => $count,
        'min'     => $min,
        'show'    => $show,
        'message' => $message,
    );
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function akademiata_offer_daily_interest_rest_handler(WP_REST_Request $request) {
    $post_id = (int) $request->get_param('post_id');
    $lang    = sanitize_key((string) $request->get_param('lang'));
    if ($lang === '') {
        $lang = akademiata_offer_daily_interest_lang();
    }

    if (!akademiata_offer_daily_interest_is_valid_post($post_id)) {
        return new WP_Error('invalid_post', 'Invalid offer post.', array('status' => 400));
    }

    $session_token = sanitize_text_field((string) $request->get_param('session_token'));
    $count         = akademiata_offer_daily_interest_register_view($post_id, $session_token, $lang);

    return rest_ensure_response(akademiata_offer_daily_interest_payload($count));
}

function akademiata_offer_daily_interest_rest_permission() {
    return true;
}

function akademiata_offer_daily_interest_register_rest_route() {
    register_rest_route(
        'akademiata/v1',
        '/offer-daily-interest',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'akademiata_offer_daily_interest_rest_handler',
            'permission_callback' => 'akademiata_offer_daily_interest_rest_permission',
            'args'                => array(
                'post_id'       => array(
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ),
                'session_token' => array(
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'lang'          => array(
                    'type'              => 'string',
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        )
    );
}
add_action('rest_api_init', 'akademiata_offer_daily_interest_register_rest_route');

function akademiata_enqueue_offer_daily_interest_script() {
    if (!akademiata_should_show_offer_daily_interest()) {
        return;
    }

    $script_path = get_template_directory() . '/assets/dist/js/offerDailyInterest.js';
    $script_ver  = file_exists($script_path) ? filemtime($script_path) : null;

    wp_enqueue_script(
        'akademiata-offer-daily-interest',
        get_template_directory_uri() . '/assets/dist/js/offerDailyInterest.js',
        array(),
        $script_ver,
        true
    );

    wp_localize_script(
        'akademiata-offer-daily-interest',
        'akademiataOfferDailyInterest',
        array(
            'restUrl'       => rest_url('akademiata/v1/offer-daily-interest'),
            'nonce'         => wp_create_nonce('wp_rest'),
            'postId'        => get_the_ID(),
            'lang'          => akademiata_offer_daily_interest_lang(),
            'minCount'      => akademiata_offer_daily_interest_min_count(),
            'delayMs'       => 4000,
            'closeLabel'    => akademiata_get_theme_lang_string('offer_daily_interest_close'),
            'title'         => akademiata_get_theme_lang_string('offer_daily_interest_title'),
            'storagePrefix' => 'akademiata_offer_daily_interest',
        )
    );
}
add_action('wp_enqueue_scripts', 'akademiata_enqueue_offer_daily_interest_script', 102);
