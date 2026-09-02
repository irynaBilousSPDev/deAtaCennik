<?php
/**
 * Virtual promotions filter for bachelor/master listings (PROMOS JSON, variant B).
 */

/**
 * Google Apps Script URL for prices JSON (keep in sync with configure/js-css.php).
 *
 * @return string
 */
function akademiata_get_prices_google_api_url() {
    return 'https://script.google.com/macros/s/AKfycby89Mt7UgeY6jKnq2YQNwumt_CBp46UVd1mbKvxqEkg_46vjGAeN-8lcL_OokQVFnAW/exec';
}

/**
 * @return string[]
 */
function akademiata_get_offer_listing_filter_keys() {
    return array_merge(akademiata_get_offer_listing_taxonomies(), array('promotions', 'promo'));
}

/**
 * PROMOS from theme prices.json (fast local fallback).
 *
 * @return array<int, array<string, mixed>>
 */
function akademiata_load_promos_from_prices_json() {
    $path = get_template_directory() . '/prices.json';
    if (!is_readable($path)) {
        return array();
    }

    $json = json_decode((string) file_get_contents($path), true);
    if (!empty($json['PROMOS']) && is_array($json['PROMOS'])) {
        return $json['PROMOS'];
    }

    return array();
}

/**
 * PROMOS from Google Apps Script (short timeout — never block listing filters for 15s).
 *
 * @param int $timeout_seconds
 * @return array<int, array<string, mixed>>
 */
function akademiata_load_promos_from_google($timeout_seconds = 3) {
    $url = akademiata_get_prices_google_api_url();
    if ($url === '') {
        return array();
    }

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => max(1, (int) $timeout_seconds),
        )
    );

    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
        return array();
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!empty($body['PROMOS']) && is_array($body['PROMOS'])) {
        return $body['PROMOS'];
    }

    return array();
}

/**
 * Extract the latest deadline date from promo short/full text.
 * Looks for DD.MM.YYYY patterns, returns the latest one found.
 *
 * @param array<string,mixed> $promo
 * @return int|null Unix timestamp end-of-day, or null if no date found.
 */
function akademiata_extract_promo_deadline($promo) {
    $text = '';
    if (!empty($promo['short'])) {
        $text .= ' ' . wp_strip_all_tags((string) $promo['short']);
    }
    if (!empty($promo['full'])) {
        $text .= ' ' . wp_strip_all_tags((string) $promo['full']);
    }

    if (!preg_match_all('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $text, $matches, PREG_SET_ORDER)) {
        return null;
    }

    $latest = null;
    foreach ($matches as $m) {
        $day   = (int) $m[1];
        $month = (int) $m[2];
        $year  = (int) $m[3];
        if ($year < 2024 || $year > 2030 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            continue;
        }
        $ts = mktime(23, 59, 59, $month, $day, $year);
        if ($ts !== false && ($latest === null || $ts > $latest)) {
            $latest = $ts;
        }
    }

    return $latest;
}

/**
 * Apply end-of-month extension rule: if deadline is end of month,
 * extend to end of next month, but never past 31 October of that year.
 *
 * @param int $deadline_ts
 * @return int Extended timestamp.
 */
function akademiata_extend_promo_deadline($deadline_ts) {
    $day       = (int) date('j', $deadline_ts);
    $month     = (int) date('n', $deadline_ts);
    $year      = (int) date('Y', $deadline_ts);
    $last_day  = (int) date('t', $deadline_ts);

    if ($day < $last_day - 1) {
        return $deadline_ts;
    }

    // Extend to end of next month.
    $next_month = $month + 1;
    $next_year  = $year;
    if ($next_month > 12) {
        $next_month = 1;
        $next_year++;
    }

    $extended = mktime(23, 59, 59, $next_month + 1, 0, $next_year);

    // Cap at 31 October same year.
    $cap = mktime(23, 59, 59, 10, 31, $year);
    if ($extended > $cap) {
        $extended = $cap;
    }

    return $extended;
}

/**
 * Check if promo has expired based on dates in its text.
 *
 * @param array<string,mixed> $promo
 * @return bool
 */
function akademiata_promo_is_expired($promo) {
    // Explicit expires field takes priority (future-proof).
    if (!empty($promo['expires'])) {
        $raw = trim((string) $promo['expires']);
        $ts  = null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            $ts = mktime(23, 59, 59, (int) $m[2], (int) $m[3], (int) $m[1]);
        } elseif (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m)) {
            $ts = mktime(23, 59, 59, (int) $m[2], (int) $m[1], (int) $m[3]);
        }
        if ($ts !== null) {
            return time() > $ts;
        }
    }

    $deadline = akademiata_extract_promo_deadline($promo);
    if ($deadline === null) {
        return false;
    }

    $effective = akademiata_extend_promo_deadline($deadline);
    return time() > $effective;
}

/**
 * Load calculator PROMOS (Google Apps Script = source of truth, short cache).
 * Falls back to local prices.json only when Google is unreachable.
 * Filters out expired promotions automatically.
 *
 * @return array<int, array<string, mixed>>
 */
function akademiata_get_calculator_promos() {
    static $runtime_cache = null;

    if ($runtime_cache !== null) {
        return $runtime_cache;
    }

    $transient_key = 'akademiata_calculator_promos_v5';
    $cached        = get_transient($transient_key);

    if (is_array($cached) && $cached !== array()) {
        $runtime_cache = $cached;
        return $runtime_cache;
    }

    // Google (Excel) is the only live source; prices.json is emergency fallback.
    $promos = akademiata_load_promos_from_google(5);

    if ($promos === array()) {
        $promos = akademiata_load_promos_from_prices_json();
    }

    // Filter expired promos.
    $promos = array_values(array_filter($promos, function ($promo) {
        return !akademiata_promo_is_expired($promo);
    }));

    // Short cache (15 min) so Excel changes propagate quickly.
    if ($promos !== array()) {
        set_transient($transient_key, $promos, 15 * MINUTE_IN_SECONDS);
    }

    $runtime_cache = $promos;

    return $runtime_cache;
}

/**
 * All study language tracks offered by an offer (pl and/or en).
 *
 * Bilingual posts (polski/angielski) must match PL promos when Polish is available —
 * the old single-code helper treated any English hint as EN-only.
 *
 * @param WP_Term[] $terms
 * @return string[] pl|en
 */
function akademiata_study_language_codes_from_terms(array $terms) {
    $codes = array();

    foreach ($terms as $term) {
        if (!is_object($term)) {
            continue;
        }

        $slug      = strtolower((string) $term->slug);
        $name      = strtolower((string) $term->name);
        $haystack  = $slug . ' ' . $name;
        $has_en    = (strpos($haystack, 'angiel') !== false || strpos($haystack, 'english') !== false);
        $has_pl    = (strpos($haystack, 'polsk') !== false || strpos($haystack, 'polish') !== false);

        if ($has_pl) {
            $codes[] = 'pl';
        }
        if ($has_en) {
            $codes[] = 'en';
        }
        if (!$has_pl && !$has_en) {
            $codes[] = 'pl';
        }
    }

    $codes = array_values(array_unique($codes));

    return $codes !== array() ? $codes : array( 'pl' );
}

/**
 * Primary study language for schema / display (PL when available).
 *
 * @param WP_Term[] $terms
 * @return string pl|en
 */
function akademiata_study_language_code_from_terms(array $terms) {
    $codes = akademiata_study_language_codes_from_terms($terms);

    if (in_array('pl', $codes, true)) {
        return 'pl';
    }

    return $codes[0];
}

/**
 * @param WP_Term[] $terms
 * @return string wwa|wro|uni
 */
function akademiata_city_code_from_terms(array $terms) {
    if ($terms === array()) {
        return 'uni';
    }

    $city_name = strtolower((string) $terms[0]->name);

    if (strpos($city_name, 'warszawa') !== false) {
        return 'wwa';
    }

    if (strpos($city_name, 'wroc') !== false) {
        return 'wro';
    }

    return 'uni';
}

/**
 * Map offer language taxonomy to calculator study language (pl|en).
 *
 * @param int $post_id
 * @return string pl|en
 */
function akademiata_get_offer_study_language_code($post_id) {
    return akademiata_study_language_code_from_terms(akademiata_get_offer_terms($post_id, 'language'));
}

/**
 * @param int $post_id
 * @return string wwa|wro|uni
 */
function akademiata_get_offer_city_code($post_id) {
    return akademiata_city_code_from_terms(akademiata_get_offer_terms($post_id, 'city'));
}

/**
 * @return array<int, array{lng:string,lngs:string[],cty:string,deg:int}>
 */
function &akademiata_offer_promo_match_context_store() {
    static $cache = array();
    return $cache;
}

/**
 * Bulk-load lng/city/deg for promo matching (avoids N+1 term queries).
 *
 * @param int[]  $post_ids
 * @param string $filter_action filter_bachelor|filter_master|filter_posts|''
 */
function akademiata_prime_offer_promo_match_contexts(array $post_ids, $filter_action = '') {
    $cache   = &akademiata_offer_promo_match_context_store();
    $missing = array();

    foreach ($post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id > 0 && !isset($cache[ $post_id ])) {
            $missing[] = $post_id;
        }
    }

    if ($missing === array()) {
        return;
    }

    $deg_fixed = null;
    if ($filter_action === 'filter_bachelor') {
        $deg_fixed = 1;
    } elseif ($filter_action === 'filter_master') {
        $deg_fixed = 2;
    }

    $types = array();
    if ($deg_fixed === null) {
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($missing), '%d'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built from count only.
        $sql = $wpdb->prepare(
            "SELECT ID, post_type FROM {$wpdb->posts} WHERE ID IN ($placeholders)",
            $missing
        );
        foreach ((array) $wpdb->get_results($sql) as $row) {
            $types[ (int) $row->ID ] = (string) $row->post_type;
        }
    }

    $terms_by_post = array();
    $terms         = wp_get_object_terms(
        $missing,
        array('language', 'city'),
        array(
            'fields'                 => 'all_with_object_id',
            'update_term_meta_cache' => false,
        )
    );

    if (!is_wp_error($terms) && is_array($terms)) {
        foreach ($terms as $term) {
            $oid = isset($term->object_id) ? (int) $term->object_id : 0;
            if ($oid <= 0) {
                continue;
            }
            $terms_by_post[ $oid ][ $term->taxonomy ][] = $term;
        }
    }

    foreach ($missing as $post_id) {
        $lang_terms = isset($terms_by_post[ $post_id ]['language'])
            ? $terms_by_post[ $post_id ]['language']
            : array();
        $city_terms = isset($terms_by_post[ $post_id ]['city'])
            ? $terms_by_post[ $post_id ]['city']
            : array();

        if ($deg_fixed !== null) {
            $deg = $deg_fixed;
        } else {
            $pt  = isset($types[ $post_id ]) ? $types[ $post_id ] : '';
            $deg = ($pt === 'bachelor') ? 1 : (($pt === 'master') ? 2 : 0);
        }

        $lang_codes = akademiata_study_language_codes_from_terms($lang_terms);

        $cache[ $post_id ] = array(
            'lng'  => akademiata_study_language_code_from_terms($lang_terms),
            'lngs' => $lang_codes,
            'cty'  => akademiata_city_code_from_terms($city_terms),
            'deg'  => $deg,
        );
    }
}

/**
 * @param int    $post_id
 * @param string $filter_action
 * @return array{lng:string,lngs:string[],cty:string,deg:int}
 */
function akademiata_get_offer_promo_match_context($post_id, $filter_action = '') {
    $post_id = (int) $post_id;
    $cache   = &akademiata_offer_promo_match_context_store();

    if (!isset($cache[ $post_id ])) {
        akademiata_prime_offer_promo_match_contexts(array( $post_id ), $filter_action);
    }

    if (isset($cache[ $post_id ])) {
        return $cache[ $post_id ];
    }

    return array(
        'lng'  => 'pl',
        'lngs' => array( 'pl' ),
        'cty'  => 'uni',
        'deg'  => 0,
    );
}

/**
 * @param int $post_id
 * @return int 1 bachelor, 2 master, 0 other
 */
function akademiata_get_offer_degree_level($post_id) {
    $post_type = get_post_type($post_id);

    if ($post_type === 'bachelor') {
        return 1;
    }

    if ($post_type === 'master') {
        return 2;
    }

    return 0;
}

/**
 * Same eligibility rules as prices-calculator.js getElig().
 *
 * @param array{lng:string,cty:string,deg:int} $ctx
 * @param array<string,mixed>                  $promo
 * @return bool
 */
function akademiata_offer_context_matches_calculator_promo(array $ctx, array $promo) {
    $promo_lng = isset($promo['lng']) ? strtolower((string) $promo['lng']) : 'pl';
    $offer_lngs = !empty($ctx['lngs']) && is_array($ctx['lngs'])
        ? array_map('strval', $ctx['lngs'])
        : array( isset($ctx['lng']) ? (string) $ctx['lng'] : 'pl' );

    if (!in_array($promo_lng, $offer_lngs, true)) {
        return false;
    }

    $promo_deg = isset($promo['deg']) ? (int) $promo['deg'] : 0;

    if ($promo_deg !== 0 && $promo_deg !== (int) $ctx['deg']) {
        return false;
    }

    $promo_cty = isset($promo['cty']) ? strtolower((string) $promo['cty']) : 'both';

    if ($promo_cty !== 'both' && $promo_cty !== $ctx['cty']) {
        return false;
    }

    return true;
}

/**
 * Same eligibility rules as prices-calculator.js getElig().
 *
 * @param int                 $post_id
 * @param array<string,mixed> $promo
 * @return bool
 */
function akademiata_offer_matches_calculator_promo($post_id, array $promo) {
    return akademiata_offer_context_matches_calculator_promo(
        akademiata_get_offer_promo_match_context((int) $post_id),
        $promo
    );
}

/**
 * Promos to show in the listing filter for the current page context.
 *
 * @param string $filter_action filter_bachelor|filter_master|filter_posts
 * @return array<int, array<string, mixed>>
 */
function akademiata_get_listing_promos_for_filter($filter_action) {
    $promos    = akademiata_get_calculator_promos();
    $ui_lang   = akademiata_normalize_theme_lang_code(apply_filters('wpml_current_language', 'pl'));
    // EN site: only promos with lng=en in sheet (absolwent_en). PL/UK/RU: pl promos.
    $study_lng = ($ui_lang === 'en') ? 'en' : 'pl';

    $listing_deg = null;
    if ($filter_action === 'filter_bachelor') {
        $listing_deg = 1;
    } elseif ($filter_action === 'filter_master') {
        $listing_deg = 2;
    }

    $visible = array();

    foreach ($promos as $promo) {
        if (empty($promo['id']) || !is_array($promo)) {
            continue;
        }

        $lng = strtolower((string) ($promo['lng'] ?? 'pl'));
        if ($lng !== $study_lng) {
            continue;
        }

        $deg = (int) ($promo['deg'] ?? 0);
        if ($listing_deg !== null && $deg !== 0 && $deg !== $listing_deg) {
            continue;
        }

        if ( function_exists( 'akademiata_home_promos_localize_promo' ) ) {
            $promo = akademiata_home_promos_localize_promo( $promo, $ui_lang );
        }

        $visible[] = $promo;
    }

    return $visible;
}

/**
 * @param array<string, mixed> $form_data
 * @return string[]
 */
function akademiata_parse_selected_promotion_ids(array $form_data) {
    $raw = array();

    // Public URL param is `promo` (avoids WP taxonomy query_var conflict with `promotions`).
    // Form checkboxes still use promotions[].
    foreach (array('promo', 'promotions') as $key) {
        if (empty($form_data[ $key ])) {
            continue;
        }
        $raw = array_merge($raw, (array) $form_data[ $key ]);
    }

    if ($raw === array()) {
        return array();
    }

    return array_values(
        array_unique(
            array_filter(
                array_map('sanitize_title', $raw)
            )
        )
    );
}

/**
 * Post IDs matching taxonomy filters (no promo filter).
 *
 * @param string               $filter_action
 * @param array<string, mixed> $form_data
 * @return int[]
 */
function akademiata_get_offer_listing_candidate_ids($filter_action, array $form_data) {
    $args = array(
        'post_type'              => akademiata_get_post_types_for_offer_filter_action($filter_action),
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'lang'                   => apply_filters('wpml_current_language', null),
    );

    $tax_query = array('relation' => 'AND');

    foreach (akademiata_get_offer_listing_taxonomies() as $taxonomy) {
        if (empty($form_data[ $taxonomy ])) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $form_data[ $taxonomy ],
            'operator' => 'IN',
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);
    $ids   = array_map('intval', (array) $query->posts);
    wp_reset_postdata();

    return $ids;
}

/**
 * @param array<string, mixed> $promo
 * @return string[]
 */
function akademiata_get_promo_stack_ids(array $promo) {
    if (empty($promo['sw']) || !is_array($promo['sw'])) {
        return array();
    }

    return array_values(
        array_filter(
            array_map('strval', $promo['sw']),
            function ($id) {
                return $id !== '' && $id !== '—' && $id !== '-';
            }
        )
    );
}

/**
 * Mirror prices-calculator.js canSel() for one promo vs already selected IDs.
 *
 * @param array<string, mixed> $promo
 * @param string[]             $selected_ids Other selected promo IDs (excluding $promo itself).
 * @return bool
 */
function akademiata_promo_can_select_with_ids(array $promo, array $selected_ids) {
    $sw = isset($promo['sw']) && is_array($promo['sw']) ? $promo['sw'] : array();

    foreach ($selected_ids as $oid) {
        $oid = (string) $oid;
        if ($oid === '') {
            continue;
        }
        if (!in_array($oid, $sw, true)) {
            return false;
        }
    }

    return true;
}

/**
 * @param array<int, array<string, mixed>> $selected_promos
 * @return bool
 */
function akademiata_selected_promos_are_stackable(array $selected_promos) {
    if (count($selected_promos) <= 1) {
        return true;
    }

    $ids = array();
    foreach ($selected_promos as $promo) {
        if (empty($promo['id'])) {
            continue;
        }
        $ids[] = (string) $promo['id'];
    }

    foreach ($selected_promos as $promo) {
        if (empty($promo['id'])) {
            continue;
        }

        $pid    = (string) $promo['id'];
        $others = array_values(
            array_filter(
                $ids,
                function ($id) use ($pid) {
                    return $id !== $pid;
                }
            )
        );

        if (!akademiata_promo_can_select_with_ids($promo, $others)) {
            return false;
        }
    }

    return true;
}

/**
 * @param int[]  $post_ids
 * @param string[] $promo_ids
 * @param string $filter_action filter_bachelor|filter_master|filter_posts|''
 * @return int[]
 */
function akademiata_filter_offer_ids_by_promotions(array $post_ids, array $promo_ids, $filter_action = '') {
    if ($post_ids === array() || $promo_ids === array()) {
        return array();
    }

    $promos_by_id = array();
    foreach (akademiata_get_calculator_promos() as $promo) {
        if (empty($promo['id']) || !is_array($promo)) {
            continue;
        }

        $raw  = (string) $promo['id'];
        $slug = sanitize_title($raw);
        $promos_by_id[ $raw ] = $promo;
        if ($slug !== '' && $slug !== $raw) {
            $promos_by_id[ $slug ] = $promo;
        }
    }

    $selected_promos = array();
    foreach ($promo_ids as $promo_id) {
        if (isset($promos_by_id[ $promo_id ])) {
            $selected_promos[] = $promos_by_id[ $promo_id ];
        }
    }

    if ($selected_promos === array()) {
        return array();
    }

    if (!akademiata_selected_promos_are_stackable($selected_promos)) {
        return array();
    }

    akademiata_prime_offer_promo_match_contexts($post_ids, $filter_action);
    $cache    = &akademiata_offer_promo_match_context_store();
    $eligible = array();

    foreach ($post_ids as $post_id) {
        $post_id = (int) $post_id;
        $ctx     = isset($cache[ $post_id ])
            ? $cache[ $post_id ]
            : array(
                'lng'  => 'pl',
                'lngs' => array( 'pl' ),
                'cty'  => 'uni',
                'deg'  => 0,
            );

        $matches_all = true;
        foreach ($selected_promos as $promo) {
            if (!akademiata_offer_context_matches_calculator_promo($ctx, $promo)) {
                $matches_all = false;
                break;
            }
        }

        if ($matches_all) {
            $eligible[] = $post_id;
        }
    }

    return $eligible;
}

/**
 * Sanitize promo HTML (short / full) from calculator JSON.
 *
 * @param string $html
 * @return string
 */
function akademiata_sanitize_promo_short_html($html) {
    return wp_kses(
        (string) $html,
        array(
            'strong' => array(),
            'b'      => array(),
            'br'     => array(),
            'em'     => array(),
            'p'      => array(),
        )
    );
}

/**
 * Convert promo text (HTML / markdown-ish) to safe display HTML.
 *
 * @param string $text
 * @return string
 */
function akademiata_format_promo_display_html($text) {
    $raw = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $raw = str_replace(array("\r\n", "\r"), "\n", $raw);

    // If source is plain text with **bold**, convert before escaping.
    if (strpos($raw, '<') === false) {
        $escaped = esc_html($raw);
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $escaped);
        $escaped = nl2br($escaped);
        return akademiata_sanitize_promo_short_html($escaped);
    }

    return akademiata_sanitize_promo_short_html(nl2br($raw));
}

/**
 * Plain one-line promo short for filter cards.
 *
 * @param string $html
 * @param int    $max
 * @return string
 */
function akademiata_get_promo_short_plain_text($html, $max = 72) {
    $text = wp_strip_all_tags((string) $html);
    $text = preg_replace('/\s+/u', ' ', trim($text));

    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && mb_strlen($text) > $max) {
        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }

    if (strlen($text) > $max) {
        return rtrim(substr($text, 0, $max - 1)) . '…';
    }

    return $text;
}

/**
 * Count eligible offers per promo for the current listing context.
 *
 * @param string               $filter_action
 * @param array<string, mixed> $base_form_data Taxonomy filters without promotions.
 * @return array<string, int>
 */
function akademiata_get_promotion_filter_counts($filter_action, array $base_form_data = array()) {
    $candidate_ids = akademiata_get_offer_listing_candidate_ids($filter_action, $base_form_data);
    $promos        = akademiata_get_listing_promos_for_filter($filter_action);
    $counts        = array();

    foreach ($promos as $promo) {
        if (empty($promo['id'])) {
            continue;
        }

        $promo_id = (string) $promo['id'];
        $counts[ $promo_id ] = 0;
    }

    akademiata_prime_offer_promo_match_contexts($candidate_ids, $filter_action);
    $cache = &akademiata_offer_promo_match_context_store();

    foreach ($candidate_ids as $post_id) {
        $post_id = (int) $post_id;
        $ctx     = isset($cache[ $post_id ])
            ? $cache[ $post_id ]
            : array(
                'lng'  => 'pl',
                'lngs' => array( 'pl' ),
                'cty'  => 'uni',
                'deg'  => 0,
            );

        foreach ($promos as $promo) {
            if (empty($promo['id'])) {
                continue;
            }

            if (akademiata_offer_context_matches_calculator_promo($ctx, $promo)) {
                $counts[ (string) $promo['id'] ]++;
            }
        }
    }

    return $counts;
}

/**
 * @return string HTML (escaped inner text only in caller).
 */
function akademiata_get_promotions_filter_badge_html() {
    return '<span class="filter-promotions-badge" aria-hidden="true">%</span>';
}

/**
 * Temporary Zarządzanie PL tuition campaign (until 31 Oct 2026).
 */
function akademiata_is_zarzadzanie_price_override_active() {
    return wp_date('Y-m-d') <= '2026-10-31';
}

/**
 * Program taxonomy slugs that trigger Zarządzanie promo block on listing.
 *
 * @return string[]
 */
function akademiata_get_zarzadzanie_program_filter_slugs() {
    return array('zarzadzanie', 'wroclaw-zarzadzanie');
}

/**
 * Offer listing: sheet promos unavailable when Zarządzanie is selected (PL/UK/RU UI).
 */
function akademiata_offer_listing_zarzadzanie_promos_blocked() {
    if (!akademiata_is_zarzadzanie_price_override_active()) {
        return false;
    }

    $ui_lang = apply_filters('wpml_current_language', null);
    if ($ui_lang === 'en') {
        return false;
    }

    $selected = akademiata_get_selected_filter_terms_from_request(
        'program',
        akademiata_get_offer_listing_filter_keys()
    );

    return (bool) array_intersect(akademiata_get_zarzadzanie_program_filter_slugs(), $selected);
}

/**
 * Render promotions filter group (after Miasto in filter sidebar).
 *
 * @param string|null $filter_action
 */
function akademiata_render_offer_promotions_filter_group($filter_action = null) {
    if ($filter_action === null) {
        $filter_action = akademiata_get_offer_filter_action();
    }

    $promos = akademiata_get_listing_promos_for_filter($filter_action);

    if ($promos === array()) {
        return;
    }

    $selected = akademiata_get_selected_filter_terms_from_request(
        'promotions',
        akademiata_get_offer_listing_filter_keys()
    );

    $zarzadzanie_blocked = akademiata_offer_listing_zarzadzanie_promos_blocked();
    if ($zarzadzanie_blocked) {
        $selected = array();
    }

    ?>
    <div class="taxonomy_group taxonomy_group--promotions mb-3<?php echo $zarzadzanie_blocked ? ' is-zarzadzanie-blocked' : ''; ?>">
        <h2 class="filter_accordion_header filter_accordion_header--promotions" data-tax="promotions">
            <span class="filter_accordion_header__label">
                <?php echo akademiata_get_promotions_filter_badge_html(); ?>
                <?php echo esc_html(akademiata_get_theme_lang_string('offer_filter_promotions')); ?>
            </span>
            <div class="arrow-open-close" aria-hidden="true"></div>
        </h2>
        <div class="accordion-content">
            <div class="labels_list filter-promo-cards">
                <?php foreach ($promos as $promo) :
                    $promo_id     = sanitize_title((string) $promo['id']);
                    $promo_name   = isset($promo['name']) ? (string) $promo['name'] : $promo_id;
                    $promo_short  = isset($promo['short'])
                        ? akademiata_get_promo_short_plain_text($promo['short'], 160)
                        : '';
                    $promo_tag    = isset($promo['tag']) ? trim((string) $promo['tag']) : '';
                    $promo_full   = isset($promo['full'])
                        ? akademiata_strip_promo_tag_from_full(
                            akademiata_format_promo_display_html($promo['full']),
                            $promo_tag
                        )
                        : '';
                    $promo_stack  = isset($promo['sw']) && is_array($promo['sw']) ? $promo['sw'] : array();
                    $checked      = !$zarzadzanie_blocked && in_array($promo_id, $selected, true) ? 'checked' : '';
                    $card_class   = 'filter-promo-card' . ($zarzadzanie_blocked ? ' is-disabled' : '');
                    ?>
                    <label class="<?php echo esc_attr($card_class); ?>">
                        <input type="checkbox"
                               class="filter-promo-card__input"
                               name="promotions[]"
                               value="<?php echo esc_attr($promo_id); ?>"
                               data-tag-label="<?php echo esc_attr($promo_name); ?>"
                               data-promo-name="<?php echo esc_attr($promo_name); ?>"
                               data-promo-short="<?php echo esc_attr($promo_short); ?>"
                               data-promo-full="<?php echo esc_attr($promo_full); ?>"
                               data-promo-tag="<?php echo esc_attr($promo_tag); ?>"
                               data-promo-stack="<?php echo esc_attr(wp_json_encode($promo_stack)); ?>"
                            <?php disabled($zarzadzanie_blocked); ?>
                            <?php echo $checked; ?>>
                        <span class="filter-promo-card__name"><?php echo esc_html($promo_name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Strip tag/discount duplicate lines from promo full HTML.
 *
 * @param string $full_html
 * @param string $tag
 * @return string
 */
function akademiata_strip_promo_tag_from_full($full_html, $tag) {
    $html = (string) $full_html;
    $tag  = trim(wp_strip_all_tags((string) $tag));

    if ($html === '' || $tag === '') {
        return $html;
    }

    $normalized_tag = preg_replace('/\s+/u', ' ', $tag);
    $parts          = preg_split('/(?:<br\s*\/?>|\n)+/iu', $html);
    $kept           = array();

    foreach ((array) $parts as $part) {
        $plain = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($part)));
        if ($plain === '' || $plain === $normalized_tag) {
            continue;
        }

        $plain_len = function_exists('mb_strlen') ? mb_strlen($plain) : strlen($plain);
        $tag_len   = function_exists('mb_strlen') ? mb_strlen($normalized_tag) : strlen($normalized_tag);
        $pos       = function_exists('mb_stripos')
            ? mb_stripos($plain, $normalized_tag)
            : stripos($plain, $normalized_tag);

        if ($pos !== false && $plain_len <= $tag_len + 8) {
            continue;
        }
        $kept[] = $part;
    }

    return akademiata_sanitize_promo_short_html(implode('<br>', $kept));
}

/**
 * Empty promo info panel (filled by JS / SSR selected state).
 */
function akademiata_render_offer_promo_info_panel() {
    $filter_action = akademiata_get_offer_filter_action();
    $promos        = akademiata_get_listing_promos_for_filter($filter_action);
    if ($promos === array()) {
        return;
    }

    $selected = akademiata_get_selected_filter_terms_from_request(
        'promotions',
        akademiata_get_offer_listing_filter_keys()
    );

    $items = array();
    foreach ($promos as $promo) {
        $promo_id = sanitize_title((string) ($promo['id'] ?? ''));
        if ($promo_id === '' || !in_array($promo_id, $selected, true)) {
            continue;
        }
        $tag  = isset($promo['tag']) ? trim((string) $promo['tag']) : '';
        $full = isset($promo['full']) ? akademiata_format_promo_display_html($promo['full']) : '';
        $full = akademiata_strip_promo_tag_from_full($full, $tag);
        $items[] = array(
            'id'    => $promo_id,
            'name'  => isset($promo['name']) ? (string) $promo['name'] : $promo_id,
            'short' => isset($promo['short']) ? akademiata_get_promo_short_plain_text($promo['short'], 180) : '',
            'full'  => $full,
            'tag'   => $tag,
        );
    }

    $hidden = empty($items) ? ' is-empty' : '';
    $expand_label = akademiata_get_theme_lang_string('offer_promo_expand');
    ?>
    <div id="offer-promo-info" class="offer-promo-info<?php echo esc_attr($hidden); ?>" aria-live="polite">
        <?php foreach ($items as $item) :
            $has_full = $item['full'] !== '';
            ?>
            <div class="offer-promo-info__item<?php echo $has_full ? '' : ' offer-promo-info__item--no-body'; ?>"
                 data-promo-id="<?php echo esc_attr($item['id']); ?>">
                <button type="button"
                        class="offer-promo-info__toggle"
                        aria-expanded="false"
                    <?php echo $has_full ? '' : ' disabled'; ?>
                        aria-label="<?php echo esc_attr($expand_label); ?>">
                    <span class="offer-promo-info__main">
                        <strong class="offer-promo-info__name"><?php echo esc_html($item['name']); ?></strong>
                        <?php if ($item['short'] !== '') : ?>
                            <span class="offer-promo-info__short"><?php echo esc_html($item['short']); ?></span>
                        <?php endif; ?>
                    </span>
                    <?php if ($item['tag'] !== '') : ?>
                        <span class="offer-promo-info__tag"><?php echo esc_html($item['tag']); ?></span>
                    <?php endif; ?>
                    <?php if ($has_full) : ?>
                        <span class="offer-promo-info__arr" aria-hidden="true">▾</span>
                    <?php endif; ?>
                </button>
                <?php if ($has_full) : ?>
                    <div class="offer-promo-info__body" hidden>
                        <?php echo $item['full']; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
