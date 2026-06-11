<?php
/**
 * Theme filter for postgraduate / MBA archives (not courses).
 * Query param: offer_theme_pg_mba=slug
 */

$post_type = akademiata_get_pg_mba_archive_post_type();

$theme_terms = akademiata_get_offer_theme_pg_mba_terms_for_post_type($post_type);

if (empty($theme_terms)) {
    return;
}

?>
<div class="taxonomy-tabs pg-mba-theme-filter" data-pg-mba-theme-filter>
    <ul class="taxonomy-tabs__nav">
        <?php foreach ($theme_terms as $term) : ?>
            <li data-term="<?php echo esc_attr($term->slug); ?>">
                <?php echo esc_html($term->name); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
