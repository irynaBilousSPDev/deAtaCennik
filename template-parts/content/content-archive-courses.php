<?php
$cities = get_terms([
    'taxonomy' => 'city_pg_mba',
    'hide_empty' => false,
]);

$filter_taxonomy = 'course_type';
$terms = get_terms([
    'taxonomy' => $filter_taxonomy,
    'hide_empty' => false,
]);

if (!empty($cities) && !is_wp_error($cities)) :
    ?>

    <div class="city-tabs">
        <ul class="city-tabs__nav">
            <!-- Reset tab: Wszystkie miasta -->
            <li class="active">
                <a href="#tab-all"><?= __('Wszystkie miasta', 'akademiata'); ?></a>
            </li>

            <?php foreach ($cities as $i => $city) : ?>
                <?php
                // Check if this city has at least ONE course
                $city_query = new WP_Query([
                    'post_type'      => get_post_type(), // current CPT
                    'posts_per_page' => 1,
                    'tax_query'      => [
                        [
                            'taxonomy' => 'city_pg_mba',
                            'field'    => 'term_id',
                            'terms'    => $city->term_id,
                        ],
                    ],
                ]);
                ?>

                <?php if ($city_query->have_posts()) : ?>
                    <li>
                        <a href="#city-<?= esc_attr($city->slug); ?>">
                            <?= esc_html($city->name); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            <?php endforeach; ?>
        </ul>

        <div class="city-tabs__content">
            <!-- Tab: Wszystkie miasta -->
            <div id="tab-all" class="city-tabs__pane active">
                <div class="city-tabs__accordion">
                    <?= __('Wszystkie', 'akademiata'); ?> <span class="accordion-icon">+</span>
                </div>
                <div class="city-tabs__body">
                    <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                        <div class="taxonomy-tabs" data-city="all" data-taxonomy="<?= esc_attr($filter_taxonomy); ?>">
                            <ul class="taxonomy-tabs__nav">
                                <?php foreach ($terms as $term) : ?>
                                    <li data-term="<?= esc_attr($term->slug); ?>">
                                        <?= esc_html($term->name); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="taxonomy-tabs__content studia_cards">
                                <?php
                                $query = new WP_Query([
                                    'post_type' => get_post_type(),
                                    'posts_per_page' => -1,
                                ]);

                                if ($query->have_posts()) :
                                    while ($query->have_posts()) : $query->the_post();
                                        $taxonomy_terms = get_the_terms(get_the_ID(), $filter_taxonomy);
                                        $term_slug = (!empty($taxonomy_terms) && !is_wp_error($taxonomy_terms)) ? $taxonomy_terms[0]->slug : 'no-term';

                                        set_query_var('term_slug', $term_slug);
                                        set_query_var('data_city', 'all');

                                        get_template_part('partials/card_post_courses');

                                        ?>

                                    <?php
                                    endwhile;
                                    wp_reset_postdata();
                                else :
                                    echo '<p>' . __('Brak wyników.', 'akademiata') . '</p>';
                                endif;
                                ?>
                                <p class="no-taxonomy-results" style="display:none">
                                    <?= __('Brak wyników dla wybranej opcji.', 'akademiata'); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Individual city tabs -->
            <?php foreach ($cities as $city) : ?>
                <div id="city-<?= esc_attr($city->slug); ?>" class="city-tabs__pane">
                    <div class="city-tabs__accordion">
                        <?= esc_html($city->name); ?> <span class="accordion-icon">+</span>
                    </div>

                    <div class="city-tabs__body">
                        <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                            <div class="taxonomy-tabs" data-city="<?= esc_attr($city->slug); ?>"
                                 data-taxonomy="<?= esc_attr($filter_taxonomy); ?>">
                                <ul class="taxonomy-tabs__nav">
                                    <?php foreach ($terms as $term) : ?>
                                        <li data-term="<?= esc_attr($term->slug); ?>">
                                            <?= esc_html($term->name); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="taxonomy-tabs__content studia_cards">
                                    <?php
                                    $query = new WP_Query([
                                        'post_type' => get_post_type(),
                                        'posts_per_page' => -1,
                                        'tax_query' => [[
                                            'taxonomy' => 'city_pg_mba',
                                            'field' => 'term_id',
                                            'terms' => $city->term_id,
                                        ]],
                                    ]);

                                    if ($query->have_posts()) :
                                        while ($query->have_posts()) : $query->the_post();
                                            $taxonomy_terms = get_the_terms(get_the_ID(), $filter_taxonomy);
                                            $term_slug = (!empty($taxonomy_terms) && !is_wp_error($taxonomy_terms)) ? $taxonomy_terms[0]->slug : 'no-term';

                                            set_query_var('term_slug', $term_slug);
                                            set_query_var('data_city', 'all');

                                            get_template_part('partials/card_post_courses'); ?>
                                        <?php
                                        endwhile;
                                        wp_reset_postdata();
                                    else :
                                        echo '<p>' . __('Brak wyników', 'akademiata') . '</p>';
                                    endif;
                                    ?>
                                    <p class="no-taxonomy-results" style="display:none">
                                        <?= __('Brak wyników dla wybranej opcji.', 'akademiata'); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else : ?>
    <p><?php _e('Brak dostępnych miast.', 'akademiata'); ?></p>
<?php endif; ?>
