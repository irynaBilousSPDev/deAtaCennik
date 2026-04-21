
<style>
    @media (max-width: 768px) {
        .city-tabs .city-tabs__nav {
            display: inline-flex!important;
            margin-top: 1rem;
        }
    }
    @media (max-width: 768px) {
        .city-tabs .city-tabs__pane {
            display: none;
        }
    }
</style>
<?php
$cities = get_terms([
    'taxonomy'   => 'city_pg_mba',
    'hide_empty' => false,
]);

if (!empty($cities) && !is_wp_error($cities)) :
    ?>
    <div class="city-tabs">
        <ul class="city-tabs__nav">
            <?php
            $first_active = true;
            foreach ($cities as $city) :
                // Check if this city has posts
                $query = new WP_Query([
                    'post_type'      => get_post_type(),
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'tax_query'      => [[
                        'taxonomy' => 'city_pg_mba',
                        'field'    => 'term_id',
                        'terms'    => $city->term_id,
                    ]],
                ]);

                if ($query->have_posts()) : ?>
                    <li class="<?= $first_active ? 'active' : ''; ?>">
                        <a href="#city-<?= esc_attr($city->slug); ?>">
                            <?= esc_html($city->name); ?>
                        </a>
                    </li>
                    <?php
                    $first_active = false;
                endif;
                wp_reset_postdata();
            endforeach;
            ?>
        </ul>

        <div class="city-tabs__content">
            <?php
            $first_active = true;
            foreach ($cities as $city) :
                // Query again for content
                $query = new WP_Query([
                    'post_type'      => get_post_type(),
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'tax_query'      => [[
                        'taxonomy' => 'city_pg_mba',
                        'field'    => 'term_id',
                        'terms'    => $city->term_id,
                    ]],
                ]);

                if ($query->have_posts()) : ?>
                    <div id="city-<?= esc_attr($city->slug); ?>" class="city-tabs__pane <?= $first_active ? 'active' : ''; ?>">

                        <div class="city-tabs__body">
                            <div class="studia_cards">
                                <?php
                                while ($query->have_posts()) : $query->the_post();
                                    get_template_part('partials/card_post_pg_mba');
                                endwhile;
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    $first_active = false;
                endif;
                wp_reset_postdata();
            endforeach;
            ?>
        </div>

    </div>
<?php else : ?>
    <p><?php _e('Brak dostępnych miast.', 'akademiata'); ?></p>
<?php endif; ?>

