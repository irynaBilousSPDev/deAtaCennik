<?php

if (!defined('ABSPATH')) {
    exit;
}

function akademiata_site_daily_visitors_cookie_name() {
    return 'akademiata_site_view_sid';
}

function akademiata_site_daily_visitors_is_enabled() {
    return function_exists('akademiata_offer_daily_interest_is_globally_enabled')
        && akademiata_offer_daily_interest_is_globally_enabled();
}

function akademiata_site_daily_visitors_min_count() {
    return function_exists('akademiata_offer_daily_interest_min_count')
        ? akademiata_offer_daily_interest_min_count()
        : 2;
}

function akademiata_site_daily_visitors_count_transient_key() {
    return 'akademiata_site_daily_' . wp_date('Y-m-d');
}

/**
 * @param string $session_token
 * @return string
 */
function akademiata_site_daily_visitors_session_transient_key($session_token) {
    $hash = hash('sha256', (string) $session_token);

    return 'akademiata_site_daily_sess_'
        . wp_date('Y-m-d') . '_'
        . substr($hash, 0, 16);
}

function akademiata_site_daily_visitors_seconds_until_midnight() {
    if (function_exists('akademiata_offer_daily_interest_seconds_until_midnight')) {
        return akademiata_offer_daily_interest_seconds_until_midnight();
    }

    $timezone = wp_timezone();
    $now      = new DateTime('now', $timezone);
    $midnight = new DateTime('tomorrow', $timezone);

    return max(60, $midnight->getTimestamp() - $now->getTimestamp());
}

/**
 * @param string $session_token
 * @return bool
 */
function akademiata_site_daily_visitors_is_valid_session_token($session_token) {
    $session_token = (string) $session_token;

    return (bool) preg_match('/^[a-z0-9-]{16,64}$/i', $session_token);
}

function akademiata_site_daily_visitors_get_or_set_session_token() {
    $cookie_name = akademiata_site_daily_visitors_cookie_name();

    if (
        isset($_COOKIE[ $cookie_name ])
        && akademiata_site_daily_visitors_is_valid_session_token(wp_unslash($_COOKIE[ $cookie_name ]))
    ) {
        return sanitize_text_field(wp_unslash($_COOKIE[ $cookie_name ]));
    }

    $token = wp_generate_uuid4();

    if (!headers_sent()) {
        setcookie(
            $cookie_name,
            $token,
            array(
                'expires'  => time() + DAY_IN_SECONDS,
                'path'     => COOKIEPATH ? COOKIEPATH : '/',
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
    }

    $_COOKIE[ $cookie_name ] = $token;

    return $token;
}

function akademiata_site_daily_visitors_get_count() {
    return max(0, (int) get_transient(akademiata_site_daily_visitors_count_transient_key()));
}

/**
 * @param string $session_token
 * @return int
 */
function akademiata_site_daily_visitors_register_view($session_token) {
    if (!akademiata_site_daily_visitors_is_valid_session_token($session_token)) {
        return akademiata_site_daily_visitors_get_count();
    }

    $ttl         = akademiata_site_daily_visitors_seconds_until_midnight();
    $session_key = akademiata_site_daily_visitors_session_transient_key($session_token);
    $count_key   = akademiata_site_daily_visitors_count_transient_key();

    if (false === get_transient($session_key)) {
        set_transient($session_key, 1, $ttl);
        $count = akademiata_site_daily_visitors_get_count() + 1;
        set_transient($count_key, $count, $ttl);

        return $count;
    }

    return akademiata_site_daily_visitors_get_count();
}

function akademiata_site_daily_visitors_should_track() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    if (!akademiata_site_daily_visitors_is_enabled()) {
        return false;
    }

    return true;
}

function akademiata_site_daily_visitors_track_current_view() {
    if (!akademiata_site_daily_visitors_should_track()) {
        return akademiata_site_daily_visitors_get_count();
    }

    $token = akademiata_site_daily_visitors_get_or_set_session_token();

    return akademiata_site_daily_visitors_register_view($token);
}

/**
 * Tracking runs via REST + JS so WP Rocket page cache stays valid (same pattern as offer-daily-interest).
 */
function akademiata_site_daily_visitors_enqueue_script() {
    if (!akademiata_site_daily_visitors_is_enabled()) {
        return;
    }

    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $script_path = get_template_directory() . '/assets/dist/js/siteDailyVisitors.js';
    $script_ver  = file_exists($script_path) ? filemtime($script_path) : null;

    wp_enqueue_script(
        'akademiata-site-daily-visitors',
        get_template_directory_uri() . '/assets/dist/js/siteDailyVisitors.js',
        array(),
        $script_ver,
        true
    );

    wp_localize_script(
        'akademiata-site-daily-visitors',
        'akademiataSiteDailyVisitors',
        array(
            'restUrl'  => rest_url('akademiata/v1/site-daily-visitors'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'minCount' => akademiata_site_daily_visitors_min_count(),
        )
    );
}
add_action('wp_enqueue_scripts', 'akademiata_site_daily_visitors_enqueue_script', 101);

/**
 * WP Rocket: REST endpoints must bypass page cache.
 *
 * @param string[] $uris
 * @return string[]
 */
function akademiata_site_daily_visitors_rocket_reject_uris($uris) {
    if (!is_array($uris)) {
        $uris = array();
    }

    $uris[] = '/wp-json/akademiata/v1/site-daily-visitors(?:/|$)';
    $uris[] = '/wp-json/akademiata/v1/offer-daily-interest(?:/|$)';

    return $uris;
}
add_filter('rocket_cache_reject_uri', 'akademiata_site_daily_visitors_rocket_reject_uris');

/**
 * @param string[] $excluded
 * @return string[]
 */
function akademiata_site_daily_visitors_rocket_delay_js_exclusions($excluded) {
    if (!is_array($excluded)) {
        $excluded = array();
    }

    $excluded[] = 'siteDailyVisitors';
    $excluded[] = 'homeDecisionToday';
    $excluded[] = 'offerDailyInterest';

    return $excluded;
}
add_filter('rocket_delay_js_exclusions', 'akademiata_site_daily_visitors_rocket_delay_js_exclusions');

/**
 * @param int $count
 */
function akademiata_site_daily_visitors_message($count) {
    $count = max(0, (int) $count);
    $lang  = apply_filters('wpml_current_language', null);
    $lang  = is_string($lang) && $lang !== '' ? $lang : 'pl';

    if ($lang === 'en') {
        if ($count === 1) {
            return akademiata_get_theme_lang_string('site_daily_visitors_en_one');
        }

        return sprintf(akademiata_get_theme_lang_string('site_daily_visitors_en_many'), $count);
    }

    if ($count === 1) {
        return akademiata_get_theme_lang_string('site_daily_visitors_pl_one');
    }

    $mod10  = $count % 10;
    $mod100 = $count % 100;

    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return sprintf(akademiata_get_theme_lang_string('site_daily_visitors_pl_few'), $count);
    }

    return sprintf(akademiata_get_theme_lang_string('site_daily_visitors_pl_many'), $count);
}

/**
 * @param int $count
 */
function akademiata_site_daily_visitors_message_html($count) {
    $count     = max(0, (int) $count);
    $plain     = akademiata_site_daily_visitors_message($count);
    $count_str = number_format_i18n($count);
    $highlight = '<span class="home-decision__visitor-count">' . esc_html($count_str) . '</span>';

    if (strpos($plain, $count_str) === false) {
        return esc_html($plain);
    }

    $parts = explode($count_str, $plain, 2);

    return esc_html($parts[0]) . $highlight . esc_html($parts[1] ?? '');
}

/**
 * @param int $count
 * @return array{count: int, show: bool, message: string, message_html: string}
 */
function akademiata_site_daily_visitors_payload($count) {
    $count = max(0, (int) $count);
    $min   = akademiata_site_daily_visitors_min_count();
    $show  = $count >= $min;

    return array(
        'count'        => $count,
        'show'         => $show,
        'message'      => $show ? akademiata_site_daily_visitors_message($count) : '',
        'message_html' => $show ? akademiata_site_daily_visitors_message_html($count) : '',
    );
}

function akademiata_site_daily_visitors_rest_handler(WP_REST_Request $request) {
    $session_token = sanitize_text_field((string) $request->get_param('session_token'));
    if ($session_token === '') {
        $session_token = akademiata_site_daily_visitors_get_or_set_session_token();
    }

    $count = akademiata_site_daily_visitors_register_view($session_token);

    return rest_ensure_response(akademiata_site_daily_visitors_payload($count));
}

function akademiata_site_daily_visitors_rest_permission() {
    return true;
}

function akademiata_site_daily_visitors_register_rest_route() {
    register_rest_route(
        'akademiata/v1',
        '/site-daily-visitors',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'akademiata_site_daily_visitors_rest_handler',
            'permission_callback' => 'akademiata_site_daily_visitors_rest_permission',
            'args'                => array(
                'session_token' => array(
                    'type'              => 'string',
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        )
    );
}
add_action('rest_api_init', 'akademiata_site_daily_visitors_register_rest_route');

/**
 * @param WP_REST_Response $response
 * @param WP_REST_Server   $server
 * @param WP_REST_Request  $request
 * @return WP_REST_Response
 */
function akademiata_site_daily_visitors_rest_no_cache($response, $server, $request) {
    if ($request->get_route() !== '/akademiata/v1/site-daily-visitors') {
        return $response;
    }

    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');

    return $response;
}
add_filter('rest_post_dispatch', 'akademiata_site_daily_visitors_rest_no_cache', 10, 3);
