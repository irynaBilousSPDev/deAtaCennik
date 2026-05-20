<?php
/**
 * Bank transfer info for PG/MBA by city.
 *
 * Expects: wroclaw_slug, warszawa_slug, online_slug (query vars or globals from parent).
 *
 * @package akademiata
 */

$post_id = get_the_ID();
$post_type = get_post_type($post_id);
$wroclaw_slug = get_query_var('pg_mba_wroclaw_slug', '');
$warszawa_slug = get_query_var('pg_mba_warszawa_slug', '');
$online_slug = get_query_var('pg_mba_online_slug', '');

if (apply_filters('wpml_current_language', null) === 'en') {
    return;
}

$pay_wroclaw = get_field('info_for_pay_wroclaw', 'option');
$account_number_wroclaw = get_field('account_number_wroclaw', 'option');

if (!in_array($post_type, ['mba', 'postgraduate'], true)) {
    return;
}

if (has_term($warszawa_slug, 'city_pg_mba', $post_id)) {
    ?>
    <div class="description">
        <?php echo wp_kses_post($pay_wroclaw); ?>
        <strong><?php esc_html_e('Nr rachunku', 'akademiata'); ?>
            <span class="copy_account_number"><?php echo esc_html($account_number_wroclaw); ?></span></strong>
    </div>
    <?php
    return;
}

if (has_term($wroclaw_slug, 'city_pg_mba', $post_id) || has_term($online_slug, 'city_pg_mba', $post_id)) {
    ?>
    <div class="description">
        <?php echo wp_kses_post($pay_wroclaw); ?>
        <strong>
            <?php esc_html_e('Nr rachunku', 'akademiata'); ?>
            <span class="copy_account_number"><?php echo esc_html($account_number_wroclaw); ?></span>
        </strong>
    </div>
    <?php
}
