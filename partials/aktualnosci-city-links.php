<?php
/**
 * news_city filter pills + optional inline "Zobacz wszystkie".
 *
 * Args: current_city_slug, see_all_url, show_see_all.
 */

$current_city_slug     = isset($args['current_city_slug']) ? sanitize_title((string) $args['current_city_slug']) : '';
$see_all_url           = !empty($args['see_all_url']) ? $args['see_all_url'] : '';
$show_see_all          = !empty($args['show_see_all']);
$variant               = isset($args['variant']) ? sanitize_key((string) $args['variant']) : 'default';
$preserve_query_args   = isset($args['preserve_query_args']) && is_array($args['preserve_query_args'])
    ? $args['preserve_query_args']
    : array();
$cities                = akademiata_get_news_city_terms();
$is_archive_tabs       = ($variant === 'archive-tabs');
$is_archive_variant    = ($variant === 'archive' || $is_archive_tabs);
$pin_icon_url          = get_template_directory_uri() . '/static/img/icon_location.png';
$see_all_is_active     = $is_archive_variant && $current_city_slug === '';

if (empty($cities) && !($show_see_all && $see_all_url !== '')) {
    return;
}
?>
<div class="aktualnosci-header-toolbar">
    <?php if (!empty($cities)) : ?>
        <nav class="aktualnosci-city-links<?php echo $is_archive_tabs ? ' aktualnosci-city-links--tabs' : ($is_archive_variant ? ' aktualnosci-city-links--archive' : ''); ?>" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('news_filter_city_nav')); ?>">
            <?php
            foreach ($cities as $city) :
                $is_active = ($current_city_slug === $city->slug);
                $city_url  = akademiata_get_aktualnosci_page_url_with_args(
                    array_merge(
                        $preserve_query_args,
                        array('miasto' => $city->slug)
                    )
                );
                ?>
                <a class="aktualnosci-city-link<?php echo $is_active ? ' is-active' : ''; ?><?php echo $is_archive_tabs ? ' aktualnosci-city-link--tab' : ($is_archive_variant ? ' aktualnosci-city-link--archive' : ''); ?>"
                   href="<?php echo esc_url($city_url); ?>">
                    <?php if ($is_archive_variant && !$is_archive_tabs && $is_active) : ?>
                        <img class="aktualnosci-city-link__pin" src="<?php echo esc_url($pin_icon_url); ?>" alt="" width="14" height="14" />
                    <?php endif; ?>
                    <span><?php echo esc_html(akademiata_get_news_city_display_name($city)); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($show_see_all && $see_all_url !== '') : ?>
        <a class="see-all-link<?php echo $is_archive_tabs ? ' see-all-link--tab' : ($is_archive_variant ? ' see-all-link--archive' : ''); ?><?php echo $see_all_is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url($see_all_url); ?>"<?php echo $see_all_is_active ? ' aria-current="true"' : ''; ?>>
            <?php echo esc_html(akademiata_get_theme_lang_string('see_all_news')); ?>
        </a>
    <?php endif; ?>
</div>
