<?php
/**
 * City links + optional "Zobacz wszystkie" (archive / inline layout).
 *
 * Args: current_city_slug, see_all_url, show_see_all (default true).
 */

$current_city_slug = isset($args['current_city_slug']) ? sanitize_title((string) $args['current_city_slug']) : '';
$see_all_url       = !empty($args['see_all_url']) ? $args['see_all_url'] : akademiata_get_aktualnosci_page_url();
$show_see_all      = !isset($args['show_see_all']) || $args['show_see_all'];
$cities            = akademiata_get_news_city_terms();

if (empty($cities) && (!$show_see_all || $see_all_url === '')) {
    return;
}
?>
<div class="aktualnosci-header-actions">
    <?php
    get_template_part(
        'partials/aktualnosci',
        'city-links',
        array('current_city_slug' => $current_city_slug)
    );
    ?>

    <?php if ($show_see_all && $see_all_url !== '') : ?>
        <a class="see-all-link" href="<?php echo esc_url($see_all_url); ?>">
            <?php esc_html_e('Zobacz wszystkie', 'akademiata'); ?>
        </a>
    <?php endif; ?>
</div>
