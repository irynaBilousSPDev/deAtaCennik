<?php
$terms_args = [
    'taxonomy'   => 'exam_city',
    'hide_empty' => false,
];

// WPML: only current language terms
if (defined('ICL_LANGUAGE_CODE')) {
    $terms_args['lang'] = ICL_LANGUAGE_CODE;
}

$cities = get_terms($terms_args);

if (!empty($cities) && !is_wp_error($cities)) :

    $cities_with_posts = [];

    foreach ($cities as $city) {
        $ids = get_posts([
            'post_type'           => 'exams',
            'post_status'         => 'publish',
            'posts_per_page'      => 1,
            'fields'              => 'ids',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
            'tax_query'           => [
                [
                    'taxonomy' => 'exam_city',
                    'field'    => 'term_id',
                    'terms'    => $city->term_id,
                ],
            ],
        ]);

        if (!empty($ids)) {
            $cities_with_posts[] = $city;
        }
    }
    ?>

    <div class="city-tabs city-tabs--exams">
        <ul class="city-tabs__nav">
            <li class="active">
                <a href="#tab-all"><?= __('Wszystkie miasta', 'akademiata'); ?></a>
            </li>

            <?php foreach ($cities_with_posts as $city) : ?>
                <li>
                    <a href="#city-<?= esc_attr($city->slug); ?>">
                        <?= esc_html($city->name); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="city-tabs__content">
            <!-- All cities -->
            <div id="tab-all" class="city-tabs__pane active">
                <div class="city-tabs__accordion">
                    <?= __('All cities', 'akademiata'); ?> <span class="accordion-icon">+</span>
                </div>

                <div class="city-tabs__body">
                    <div class="studia_cards exams_cards">
                        <?php
                        $query_all = new WP_Query([
                            'post_type'      => 'exams',
                            'posts_per_page' => -1,
                            'post_status'    => 'publish',
                        ]);

                        if ($query_all->have_posts()) :
                            while ($query_all->have_posts()) : $query_all->the_post();
                                get_template_part('partials/card_post_exams');
                            endwhile;
                            wp_reset_postdata();
                        else :
                            echo '<p>' . __('No results.', 'akademiata') . '</p>';
                        endif;
                        ?>
                    </div>
                </div>
            </div>

            <!-- City panes -->
            <?php foreach ($cities_with_posts as $city) : ?>
                <div id="city-<?= esc_attr($city->slug); ?>" class="city-tabs__pane">
                    <div class="city-tabs__accordion">
                        <?= esc_html($city->name); ?> <span class="accordion-icon">+</span>
                    </div>

                    <div class="city-tabs__body">
                        <div class="studia_cards exams_cards">
                            <?php
                            $query_city = new WP_Query([
                                'post_type'      => 'exams',
                                'posts_per_page' => -1,
                                'post_status'    => 'publish',
                                'tax_query'      => [
                                    [
                                        'taxonomy' => 'exam_city',
                                        'field'    => 'term_id',
                                        'terms'    => $city->term_id,
                                    ],
                                ],
                            ]);

                            if ($query_city->have_posts()) :
                                while ($query_city->have_posts()) : $query_city->the_post();
                                    get_template_part('partials/card_post_exams');
                                endwhile;
                                wp_reset_postdata();
                            else :
                                echo '<p>' . __('No results.', 'akademiata') . '</p>';
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

<?php else : ?>
    <p><?php _e('No cities available.', 'akademiata'); ?></p>
<?php endif; ?>
