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
    return array_merge(akademiata_get_offer_listing_taxonomies(), array('promotions'));
}

/**
 * Load calculator PROMOS array (transient + Google URL / prices.json fallback).
 *
 * @return array<int, array<string, mixed>>
 */
function akademiata_get_calculator_promos() {
    static $runtime_cache = null;

    if ($runtime_cache !== null) {
        return $runtime_cache;
    }

    $transient_key = 'akademiata_calculator_promos_v1';
    $cached        = get_transient($transient_key);

    if (is_array($cached)) {
        $runtime_cache = $cached;
        return $runtime_cache;
    }

    $promos = array();
    $url    = akademiata_get_prices_google_api_url();

    if ($url !== '') {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 15,
            )
        );

        if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode((string) wp_remote_retrieve_body($response), true);
            if (!empty($body['PROMOS']) && is_array($body['PROMOS'])) {
                $promos = $body['PROMOS'];
            }
        }
    }

    if ($promos === array()) {
        $path = get_template_directory() . '/prices.json';
        if (is_readable($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (!empty($json['PROMOS']) && is_array($json['PROMOS'])) {
                $promos = $json['PROMOS'];
            }
        }
    }

    set_transient($transient_key, $promos, 6 * HOUR_IN_SECONDS);
    $runtime_cache = $promos;

    return $runtime_cache;
}

/**
 * Map offer language taxonomy to calculator study language (pl|en).
 *
 * @param int $post_id
 * @return string pl|en
 */
function akademiata_get_offer_study_language_code($post_id) {
    $terms = akademiata_get_offer_terms($post_id, 'language');

    foreach ($terms as $term) {
        $slug = strtolower((string) $term->slug);
        $name = strtolower((string) $term->name);

        if (
            strpos($slug, 'angiel') !== false
            || strpos($slug, 'english') !== false
            || strpos($name, 'angiel') !== false
            || strpos($name, 'english') !== false
        ) {
            return 'en';
        }
    }

    return 'pl';
}

/**
 * @param int $post_id
 * @return string wwa|wro|uni
 */
function akademiata_get_offer_city_code($post_id) {
    $terms = akademiata_get_offer_terms($post_id, 'city');

    if (!empty($terms)) {
        $city_name = strtolower((string) $terms[0]->name);

        if (strpos($city_name, 'warszawa') !== false) {
            return 'wwa';
        }

        if (strpos($city_name, 'wroc') !== false) {
            return 'wro';
        }
    }

    return 'uni';
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
 * @param int                $post_id
 * @param array<string,mixed> $promo
 * @return bool
 */
function akademiata_offer_matches_calculator_promo($post_id, array $promo) {
    $study_lng = akademiata_get_offer_study_language_code($post_id);
    $promo_lng = isset($promo['lng']) ? strtolower((string) $promo['lng']) : 'pl';

    if ($promo_lng !== $study_lng) {
        return false;
    }

    $deg       = akademiata_get_offer_degree_level($post_id);
    $promo_deg = isset($promo['deg']) ? (int) $promo['deg'] : 0;

    if ($promo_deg !== 0 && $promo_deg !== $deg) {
        return false;
    }

    $city      = akademiata_get_offer_city_code($post_id);
    $promo_cty = isset($promo['cty']) ? strtolower((string) $promo['cty']) : 'both';

    if ($promo_cty !== 'both' && $promo_cty !== $city) {
        return false;
    }

    return true;
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

        $visible[] = $promo;
    }

    return $visible;
}

/**
 * @param array<string, mixed> $form_data
 * @return string[]
 */
function akademiata_parse_selected_promotion_ids(array $form_data) {
    if (empty($form_data['promotions'])) {
        return array();
    }

    return array_values(
        array_filter(
            array_map('sanitize_title', (array) $form_data['promotions'])
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
        'update_post_term_cache' => true,
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
 * @param int[]     $post_ids
 * @param string[]  $promo_ids
 * @return int[]
 */
function akademiata_filter_offer_ids_by_promotions(array $post_ids, array $promo_ids) {
    if ($post_ids === array() || $promo_ids === array()) {
        return array();
    }

    $promos_by_id = array();
    foreach (akademiata_get_calculator_promos() as $promo) {
        if (!empty($promo['id'])) {
            $promos_by_id[ (string) $promo['id'] ] = $promo;
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

    $eligible = array();

    foreach ($post_ids as $post_id) {
        $matches_all = true;

        foreach ($selected_promos as $promo) {
            if (!akademiata_offer_matches_calculator_promo($post_id, $promo)) {
                $matches_all = false;
                break;
            }
        }

        if ($matches_all) {
            $eligible[] = (int) $post_id;
        }
    }

    return $eligible;
}

/**
 * Allowed tags for promo short HTML from calculator JSON.
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
        )
    );
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

    foreach ($candidate_ids as $post_id) {
        foreach ($promos as $promo) {
            if (empty($promo['id'])) {
                continue;
            }

            if (akademiata_offer_matches_calculator_promo($post_id, $promo)) {
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

    $base_form = akademiata_parse_offer_filter_form_data();
    unset($base_form['promotions']);
    $promo_counts = akademiata_get_promotion_filter_counts($filter_action, $base_form);
    ?>
    <div class="taxonomy_group taxonomy_group--promotions mb-3">
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
                    $promo_id    = sanitize_title((string) $promo['id']);
                    $promo_name  = isset($promo['name']) ? (string) $promo['name'] : $promo_id;
                    $promo_short = isset($promo['short'])
                        ? akademiata_get_promo_short_plain_text($promo['short'])
                        : '';
                    $promo_tag   = isset($promo['tag']) ? trim((string) $promo['tag']) : '';
                    $promo_count = isset($promo_counts[ $promo_id ]) ? (int) $promo_counts[ $promo_id ] : 0;
                    $promo_stack = isset($promo['sw']) && is_array($promo['sw']) ? $promo['sw'] : array();
                    $checked     = in_array($promo_id, $selected, true) ? 'checked' : '';
                    ?>
                    <label class="filter-promo-card">
                        <input type="checkbox"
                               class="filter-promo-card__input"
                               name="promotions[]"
                               value="<?php echo esc_attr($promo_id); ?>"
                               data-tag-label="<?php echo esc_attr($promo_name); ?>"
                               data-promo-stack="<?php echo esc_attr(wp_json_encode($promo_stack)); ?>"
                            <?php echo $checked; ?>>
                        <span class="filter-promo-card__surface">
                            <span class="filter-promo-card__chk" aria-hidden="true"></span>
                            <span class="filter-promo-card__info">
                                <span class="filter-promo-card__name"><?php echo esc_html($promo_name); ?></span>
                                <?php if ($promo_short !== '') : ?>
                                    <span class="filter-promo-card__short"><?php echo esc_html($promo_short); ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if ($promo_tag !== '') : ?>
                                <span class="filter-promo-card__tag"><?php echo esc_html($promo_tag); ?></span>
                            <?php endif; ?>
                            <span class="filter-promo-card__count"><?php echo esc_html((string) $promo_count); ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
