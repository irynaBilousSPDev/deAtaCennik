<?php

if (!defined('ABSPATH')) {
    exit;
}

function akademiata_offer_daily_interest_min_count() {
    return akademiata_is_production() ? 2 : 1;
}

function akademiata_offer_daily_interest_cookie_name() {
    return 'akademiata_offer_view_sid';
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
 * Stable counter group for WPML translation sets (same specialization, all languages).
 *
 * @param int $post_id
 * @return string
 */
function akademiata_offer_daily_interest_group_key($post_id) {
    static $cache = array();

    $post_id = (int) $post_id;
    if (isset($cache[ $post_id ])) {
        return $cache[ $post_id ];
    }

    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, array('bachelor', 'master'), true)) {
        $cache[ $post_id ] = 'offer_post_' . $post_id;

        return $cache[ $post_id ];
    }

    $element_type = 'post_' . $post->post_type;
    $trid         = apply_filters('wpml_element_trid', null, $post_id, $element_type);
    if ($trid) {
        $cache[ $post_id ] = sanitize_key($post->post_type . '_trid_' . (int) $trid);

        return $cache[ $post_id ];
    }

    $cache[ $post_id ] = sanitize_key($post->post_type . '_post_' . $post_id);

    return $cache[ $post_id ];
}

/**
 * @param int $post_id
 * @return string
 */
function akademiata_offer_daily_interest_count_transient_key($post_id) {
    return 'akademiata_offer_daily_' . wp_date('Y-m-d') . '_' . akademiata_offer_daily_interest_group_key($post_id);
}

/**
 * @param int    $post_id
 * @param string $session_token
 * @return string
 */
function akademiata_offer_daily_interest_session_transient_key($post_id, $session_token) {
    $hash = hash('sha256', (string) $session_token);

    return 'akademiata_offer_daily_sess_'
        . wp_date('Y-m-d') . '_'
        . akademiata_offer_daily_interest_group_key($post_id) . '_'
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

    return (bool) preg_match('/^[a-z0-9-]{16,64}$/i', $session_token);
}

/**
 * @return string
 */
function akademiata_offer_daily_interest_get_or_set_session_token() {
    $cookie_name = akademiata_offer_daily_interest_cookie_name();

    if (
        !empty($_COOKIE[ $cookie_name ])
        && akademiata_offer_daily_interest_is_valid_session_token(wp_unslash($_COOKIE[ $cookie_name ]))
    ) {
        return sanitize_text_field(wp_unslash($_COOKIE[ $cookie_name ]));
    }

    if (function_exists('wp_generate_uuid4')) {
        $token = wp_generate_uuid4();
    } else {
        $token = 'sess-' . bin2hex(random_bytes(16));
    }

    if (!headers_sent()) {
        setcookie(
            $cookie_name,
            $token,
            time() + DAY_IN_SECONDS,
            COOKIEPATH ? COOKIEPATH : '/',
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }

    $_COOKIE[ $cookie_name ] = $token;

    return $token;
}

/**
 * @param int $post_id
 * @return int
 */
function akademiata_offer_daily_interest_get_count($post_id) {
    return max(0, (int) get_transient(akademiata_offer_daily_interest_count_transient_key($post_id)));
}

/**
 * @param int         $post_id
 * @param string      $session_token
 * @param string|null $lang Unused; kept for REST compatibility. Message language uses current locale.
 * @return int
 */
function akademiata_offer_daily_interest_register_view($post_id, $session_token, $lang = null) {
    if (!akademiata_offer_daily_interest_is_valid_post($post_id)) {
        return 0;
    }

    if (!akademiata_offer_daily_interest_is_valid_session_token($session_token)) {
        return akademiata_offer_daily_interest_get_count($post_id);
    }

    if (!akademiata_should_show_offer_daily_interest($post_id)) {
        return 0;
    }

    $ttl         = akademiata_offer_daily_interest_seconds_until_midnight();
    $session_key = akademiata_offer_daily_interest_session_transient_key($post_id, $session_token);
    $count_key   = akademiata_offer_daily_interest_count_transient_key($post_id);

    if (false === get_transient($session_key)) {
        set_transient($session_key, 1, $ttl);
        $count = akademiata_offer_daily_interest_get_count($post_id) + 1;
        set_transient($count_key, $count, $ttl);

        return $count;
    }

    return akademiata_offer_daily_interest_get_count($post_id);
}

/**
 * @param int|null $post_id
 * @return array<string, mixed>
 */
function akademiata_offer_daily_interest_track_current_view($post_id = null) {
    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    $post_id = (int) $post_id;
    $lang    = akademiata_offer_daily_interest_lang();
    $token   = akademiata_offer_daily_interest_get_or_set_session_token();
    $count   = akademiata_offer_daily_interest_register_view($post_id, $token, $lang);

    return akademiata_offer_daily_interest_payload($count);
}

/**
 * @param int $count
 * @return string
 */
function akademiata_offer_daily_interest_message_html($count) {
    $count = (int) $count;
    if ($count <= 0) {
        return '';
    }

    $plain     = akademiata_offer_daily_interest_message($count);
    $count_str = (string) $count;
    $escaped   = esc_html($plain);
    $highlight = '<span class="offer-daily-interest__count">' . esc_html($count_str) . '</span>';

    return preg_replace(
        '/' . preg_quote($count_str, '/') . '/',
        $highlight,
        $escaped,
        1
    );
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
    $count = max(0, (int) $count);
    $min   = akademiata_offer_daily_interest_min_count();
    $show  = $count >= $min;

    return array(
        'count'   => $count,
        'min'     => $min,
        'show'    => $show,
        'message' => $show ? akademiata_offer_daily_interest_message($count) : '',
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
    if ($session_token === '') {
        $session_token = akademiata_offer_daily_interest_get_or_set_session_token();
    }

    $count = akademiata_offer_daily_interest_register_view($post_id, $session_token, $lang);

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
                    'required'          => false,
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
            'postId'        => get_the_ID(),
            'groupKey'      => akademiata_offer_daily_interest_group_key(get_the_ID()),
            'closeLabel'    => akademiata_get_theme_lang_string('offer_daily_interest_close'),
            'storagePrefix' => 'akademiata_offer_daily_interest',
        )
    );
}
add_action('wp_enqueue_scripts', 'akademiata_enqueue_offer_daily_interest_script', 102);
