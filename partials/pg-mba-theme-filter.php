<?php
/**
 * Theme filter for postgraduate / MBA archives (not courses).
 * Query param: offer_theme_pg_mba=slug
 */

$post_type = get_query_var('post_type');
if (is_array($post_type)) {
    $post_type = reset($post_type);
}
if (!$post_type && is_singular(array('postgraduate', 'mba'))) {
    $post_type = get_post_type();
}
if (!in_array($post_type, array('postgraduate', 'mba'), true)) {
    return;
}

$theme_terms = get_terms(
    array(
        'taxonomy'   => 'offer_theme_pg_mba',
        'hide_empty' => true,
    )
);

if (empty($theme_terms) || is_wp_error($theme_terms)) {
    return;
}

$selected = akademiata_get_request_offer_theme_pg_mba_slugs();
?>
<div class="taxonomy-tabs pg-mba-theme-filter" data-pg-mba-theme-filter>
    <ul class="taxonomy-tabs__nav">
        <?php foreach ($theme_terms as $term) : ?>
            <li data-term="<?php echo esc_attr($term->slug); ?>"
                <?php echo in_array($term->slug, $selected, true) ? ' class="active"' : ''; ?>>
                <?php echo esc_html($term->name); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (!empty($selected)) : ?>
        <button type="button" class="pg-mba-theme-filter__clear" data-pg-mba-theme-clear>
            <?php esc_html_e('wyczyść tematy', 'akademiata'); ?>
        </button>
    <?php endif; ?>
</div>
