<?php
/**
 * Recruitment closed list from the cennik tab Rekrutacja (same JSON as tuition).
 */

/**
 * @return array<int, array{lng:string,city:string,deg:int,k:string}>
 */
function akademiata_get_recruitment_closed_rules() {
    static $runtime = null;

    if ($runtime !== null) {
        return $runtime;
    }

    $transient_key = 'akademiata_recruitment_closed_v3';
    $cached        = get_transient($transient_key);

    if (is_array($cached) && array_key_exists('rules', $cached) && is_array($cached['rules'])) {
        $runtime = $cached['rules'];
        return $runtime;
    }

    $rules = akademiata_recruitment_closed_rules_from_google(20);
    $from_google = $rules !== null;
    if (!$from_google) {
        $rules = akademiata_recruitment_closed_rules_from_prices_json();
    }

    if ($from_google) {
        set_transient($transient_key, array('rules' => $rules), 15 * MINUTE_IN_SECONDS);
    }
    $runtime = $rules;

    return $runtime;
}

/**
 * @param int $timeout_seconds
 * @return array<int, array{lng:string,city:string,deg:int,k:string}>|null null when Google is unreachable
 */
function akademiata_recruitment_closed_rules_from_google($timeout_seconds = 5) {
    if (!function_exists('akademiata_get_prices_google_api_url')) {
        return null;
    }

    $url = akademiata_get_prices_google_api_url();
    if ($url === '') {
        return null;
    }

    $response = wp_remote_get(
        $url,
        array(
            'timeout'     => max(1, (int) $timeout_seconds),
            'redirection' => 5,
        )
    );

    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        return null;
    }

    return akademiata_recruitment_closed_normalize_rules($body['CLOSED'] ?? array());
}

/**
 * @return array<int, array{lng:string,city:string,deg:int,k:string}>
 */
function akademiata_recruitment_closed_rules_from_prices_json() {
    $path = get_template_directory() . '/prices.json';
    if (!is_readable($path)) {
        return array();
    }

    $json = json_decode((string) file_get_contents($path), true);
    if (!is_array($json)) {
        return array();
    }

    return akademiata_recruitment_closed_normalize_rules($json['CLOSED'] ?? array());
}

/**
 * @param mixed $rows
 * @return array<int, array{lng:string,city:string,deg:int,k:string}>
 */
function akademiata_recruitment_closed_normalize_rules($rows) {
    if (!is_array($rows)) {
        return array();
    }

    $out = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $k = akademiata_recruitment_norm($row['k'] ?? '');
        if ($k === '') {
            continue;
        }

        $lng = strtolower(trim((string) ($row['lng'] ?? 'pl')));
        if ($lng !== 'en') {
            $lng = 'pl';
        }

        $city = strtolower(trim((string) ($row['city'] ?? '')));
        if ($city !== 'wwa' && $city !== 'wro') {
            continue;
        }

        $deg = (int) ($row['deg'] ?? 0);
        if ($deg !== 1 && $deg !== 2) {
            $deg = 0;
        }

        $out[] = array(
            'lng'  => $lng,
            'city' => $city,
            'deg'  => $deg,
            'k'    => $k,
        );
    }

    return $out;
}

/**
 * @param mixed $value
 * @return string
 */
function akademiata_recruitment_norm($value) {
    $value = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $value)));
    if ($value === '') {
        return '';
    }

    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

/**
 * UK/RU follow the Polish offer. EN study programs stay on their own post.
 *
 * @param int $post_id
 * @return int
 */
function akademiata_recruitment_source_post_id($post_id) {
    $post_id = (int) $post_id;
    $post    = $post_id > 0 ? get_post($post_id) : null;
    if (!$post || !in_array($post->post_type, array('bachelor', 'master'), true)) {
        return $post_id;
    }

    $lang = apply_filters('wpml_element_language_code', null, array(
        'element_id'   => $post_id,
        'element_type' => 'post_' . $post->post_type,
    ));

    if (!is_string($lang) || $lang === '' || $lang === 'pl' || $lang === 'en') {
        return $post_id;
    }

    $source = (int) apply_filters('wpml_object_id', $post_id, $post->post_type, false, 'pl');

    return $source > 0 ? $source : $post_id;
}

/**
 * @param int $post_id
 * @return array{lngs:string[],city:string,deg:int,names:string[]}|null
 */
function akademiata_recruitment_post_context($post_id) {
    static $cache = array();

    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return null;
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    $source_id = akademiata_recruitment_source_post_id($post_id);
    $post      = get_post($source_id);
    if (!$post || !in_array($post->post_type, array('bachelor', 'master'), true)) {
        $cache[$post_id] = null;
        return null;
    }

    $lngs = array('pl');
    if (function_exists('akademiata_study_language_codes_from_terms') && function_exists('akademiata_get_offer_terms')) {
        $codes = akademiata_study_language_codes_from_terms(akademiata_get_offer_terms($source_id, 'language'));
        if ($codes !== array()) {
            $lngs = $codes;
        }
    }

    $city = function_exists('akademiata_get_offer_city_code')
        ? akademiata_get_offer_city_code($source_id)
        : 'uni';

    $names = array();
    $terms = get_the_terms($source_id, 'program');
    if (is_array($terms)) {
        foreach ($terms as $term) {
            $names[] = $term->name;
            if (!empty($term->parent)) {
                $parent = get_term((int) $term->parent, 'program');
                if ($parent && !is_wp_error($parent)) {
                    $names[] = $parent->name;
                }
            }
        }
    }

    $names[] = $post->post_title;

    if (function_exists('akademiata_find_bachelor_master_price_row')) {
        $key = trim((string) get_post_meta($source_id, 'logical_sync_key', true));
        $row = $key !== '' ? akademiata_find_bachelor_master_price_row($key) : null;
        if (is_array($row) && !empty($row['k'])) {
            $names[] = (string) $row['k'];
        }
    }

    $normalized = array();
    foreach ($names as $name) {
        $norm = akademiata_recruitment_norm($name);
        if ($norm !== '') {
            $normalized[$norm] = $name;
        }
    }

    $cache[$post_id] = array(
        'lngs'  => array_values(array_unique($lngs)),
        'city'  => $city,
        'deg'   => $post->post_type === 'master' ? 2 : 1,
        'names' => array_keys($normalized),
        'raw'   => array_values($normalized),
    );

    return $cache[$post_id];
}

/**
 * @param int $post_id
 * @return bool
 */
function akademiata_recruitment_is_closed($post_id) {
    $ctx = akademiata_recruitment_post_context($post_id);
    if ($ctx === null || $ctx['names'] === array() || ($ctx['city'] !== 'wwa' && $ctx['city'] !== 'wro')) {
        return false;
    }

    foreach (akademiata_get_recruitment_closed_rules() as $rule) {
        if (!in_array($rule['lng'], $ctx['lngs'], true)) {
            continue;
        }
        if ($rule['city'] !== $ctx['city']) {
            continue;
        }
        if ($rule['deg'] !== 0 && $rule['deg'] !== $ctx['deg']) {
            continue;
        }
        if (in_array($rule['k'], $ctx['names'], true)) {
            return true;
        }
    }

    return false;
}

/**
 * @param int $post_id
 * @return string
 */
function akademiata_recruitment_data_attrs($post_id) {
    $ctx = akademiata_recruitment_post_context($post_id);
    if ($ctx === null) {
        return '';
    }

    return sprintf(
        ' data-rekr-lng="%s" data-rekr-city="%s" data-rekr-deg="%d" data-rekr-k="%s"',
        esc_attr(implode(',', $ctx['lngs'])),
        esc_attr($ctx['city']),
        (int) $ctx['deg'],
        esc_attr(implode('|', $ctx['raw']))
    );
}

/**
 * Signup control. Closed bachelor/master renders a non-link with the closed label.
 *
 * @param array<string, mixed> $args post_id, url, label, class, id, target, hidden
 */
function akademiata_the_recruitment_cta($args = array()) {
    $post_id = (int) ($args['post_id'] ?? get_the_ID());
    $url     = trim((string) ($args['url'] ?? ''));
    $label   = (string) ($args['label'] ?? __('ZAPISZ SIĘ', 'akademiata'));
    $class   = trim((string) ($args['class'] ?? 'button-sing_up'));
    $id      = trim((string) ($args['id'] ?? ''));
    $target  = trim((string) ($args['target'] ?? ''));
    $hidden  = !empty($args['hidden']);
    $closed  = akademiata_recruitment_is_closed($post_id);
    $attrs   = akademiata_recruitment_data_attrs($post_id);
    $id_attr = $id !== '' ? ' id="' . esc_attr($id) . '"' : '';
    $style   = $hidden ? ' style="display:none"' : '';

    if ($closed) {
        printf(
            '<span%s class="%s is-recruitment-closed" aria-disabled="true"%s%s>%s</span>',
            $id_attr,
            esc_attr($class),
            $attrs,
            $style,
            esc_html(akademiata_get_theme_lang_string('recruitment_closed'))
        );
        return;
    }

    $target_attr = $target !== '' ? ' target="' . esc_attr($target) . '" rel="noopener noreferrer"' : '';

    printf(
        '<a%s href="%s" class="%s"%s%s%s>%s</a>',
        $id_attr,
        esc_url($url),
        esc_attr($class),
        $target_attr,
        $attrs,
        $style,
        esc_html($label)
    );
}

function akademiata_recruitment_closed_rest() {
    return array(
        'label' => akademiata_get_theme_lang_string('recruitment_closed'),
        'rules' => akademiata_get_recruitment_closed_rules(),
    );
}

add_action('rest_api_init', function () {
    register_rest_route('akademiata/v1', '/recruitment-closed', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'akademiata_recruitment_closed_rest',
    ));
});

/**
 * @param string[] $uris
 * @return string[]
 */
function akademiata_recruitment_closed_rocket_reject_uris($uris) {
    if (!is_array($uris)) {
        $uris = array();
    }

    $uris[] = '/wp-json/akademiata/v1/recruitment-closed(?:/|$)';

    return $uris;
}
add_filter('rocket_cache_reject_uri', 'akademiata_recruitment_closed_rocket_reject_uris');
