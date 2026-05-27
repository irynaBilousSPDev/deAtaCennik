<?php
/**
 * City links + "Zobacz wszystkie" for aktualności sections/archives.
 *
 * @package akademiata
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
    <?php if (!empty($cities)) : ?>
        <nav class="aktualnosci-city-links" aria-label="<?php esc_attr_e('Filtruj po mieście', 'akademiata'); ?>">
            <?php
            foreach ($cities as $city) :
                $is_active = ($current_city_slug === $city->slug);
                $city_url  = akademiata_get_aktualnosci_page_url_with_args(
                    array('miasto' => $city->slug)
                );
                ?>
                <a class="aktualnosci-city-link<?php echo $is_active ? ' is-active' : ''; ?>"
                   href="<?php echo esc_url($city_url); ?>">
                    <?php echo esc_html($city->name); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($show_see_all && $see_all_url !== '') : ?>
        <a class="see-all-link" href="<?php echo esc_url($see_all_url); ?>">
            <?php esc_html_e('Zobacz wszystkie', 'akademiata'); ?>
        </a>
    <?php endif; ?>
</div>
