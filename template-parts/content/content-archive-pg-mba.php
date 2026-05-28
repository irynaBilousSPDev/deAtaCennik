<?php
$cities = akademiata_get_city_pg_mba_terms();

if (empty($cities)) :
    ?>
    <p><?php esc_html_e('Brak dostępnych miast.', 'akademiata'); ?></p>
    <?php
    return;
endif;

$nav_cities  = array();
$content_map = array();

foreach ($cities as $city) {
    $query = akademiata_query_posts_by_city_pg_mba($city->term_id);

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
<div class="city-tabs">
    <ul class="city-tabs__nav">
        <?php
        $first_active = true;
        foreach ($nav_cities as $city) :
            ?>
            <li id="city-<?php echo esc_attr($city->slug); ?>" class="<?php echo $first_active ? 'active' : ''; ?>">
                <a href="#city-<?php echo esc_attr($city->slug); ?>">
                    <?php echo esc_html($city->name); ?>
                </a>
            </li>
            <?php
            $first_active = false;
        endforeach;
        ?>
    </ul>

    <div class="city-tabs__content">
        <?php
        $first_active = true;
        foreach ($nav_cities as $city) :
            $pane = $content_map[ $city->slug ];
            $query = $pane['query'];
            ?>
            <div class="city-tabs__pane<?php echo $first_active ? ' active' : ''; ?>" data-city-pane="city-<?php echo esc_attr($city->slug); ?>">
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
            <?php
            $first_active = false;
        endforeach;
        ?>
    </div>
</div>
