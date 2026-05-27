<?php
/**
 * news_city filter pills.
 *
 * Args: current_city_slug (optional).
 */

$current_city_slug = isset($args['current_city_slug']) ? sanitize_title((string) $args['current_city_slug']) : '';
$cities            = akademiata_get_news_city_terms();

if (empty($cities)) {
    return;
}
?>
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
