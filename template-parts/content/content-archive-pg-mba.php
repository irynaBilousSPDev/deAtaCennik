<?php
$archive_post_type = akademiata_get_pg_mba_archive_post_type();
$cities            = akademiata_get_city_pg_mba_terms();

if (empty($cities)) :
    ?>
    <p><?php esc_html_e('Brak dostępnych miast.', 'akademiata'); ?></p>
    <?php
    return;
endif;

$nav_cities  = array();
$content_map = array();

foreach ($cities as $city) {
    $query = akademiata_query_posts_by_city_pg_mba($city->term_id, $archive_post_type);

    if (!$query->have_posts()) {
        wp_reset_postdata();
        continue;
    }

    $nav_cities[] = $city;
    $content_map[ $city->slug ] = array(
        'city'  => $city,
        'query' => $query,
    );
}

if (empty($nav_cities)) :
    ?>
    <p><?php esc_html_e('Brak dostępnych miast.', 'akademiata'); ?></p>
    <?php
    return;
endif;
?>
<div class="city-tabs city-tabs--pg-mba-filters"
     data-pg-mba-filters
     data-archive-post-type="<?php echo esc_attr($archive_post_type); ?>">
    <ul class="city-tabs__nav">
        <li id="tab-all" data-city="" class="active">
            <a href="#tab-all"><?php esc_html_e('Wszystkie miasta', 'akademiata'); ?></a>
        </li>
        <?php foreach ($nav_cities as $city) : ?>
            <li id="city-<?php echo esc_attr($city->slug); ?>"
                data-city="<?php echo esc_attr($city->slug); ?>">
                <a href="#city-<?php echo esc_attr($city->slug); ?>">
                    <?php echo esc_html($city->name); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php get_template_part('partials/pg-mba-theme-filter'); ?>

    <div class="city-tabs__content">
        <?php foreach ($nav_cities as $city) :
            $pane = $content_map[ $city->slug ];
            $query = $pane['query'];
            ?>
            <div class="city-tabs__pane"
                 data-city-pane="city-<?php echo esc_attr($city->slug); ?>"
                 data-city-slug="<?php echo esc_attr($city->slug); ?>">
                <div class="city-tabs__body">
                    <div class="studia_cards">
                        <?php
                        while ($query->have_posts()) :
                            $query->the_post();
                            get_template_part('partials/card_post_pg_mba');
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="pg-mba-filters__no-results" hidden>
        <?php esc_html_e('Brak wyników dla wybranych filtrów.', 'akademiata'); ?>
    </p>
</div>
