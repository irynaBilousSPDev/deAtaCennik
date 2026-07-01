<div class="card_post_item">

    <div class="card_post_wrapper">
        <div class="card_post_image">
            <?php
            $city_terms = wp_get_post_terms($post->ID, 'city');
            $ranking_icon_url = akademiata_get_offer_ranking_icon_url($post->ID);

            //            etykieta_studia
            $etykieta_studia = get_field('etykieta_studia', get_the_ID());

            // Show "wkrótce" label
            if (!empty($etykieta_studia) && in_array('coming_soon_icon', (array)$etykieta_studia)) {
                echo '<span class="label-coming-soon">' . esc_html(akademiata_get_theme_lang_string('offer_label_coming_soon')) . '</span>';
            }

            // Show "nowość" label
            if (!empty($etykieta_studia) && in_array('new_icon', (array)$etykieta_studia)) {
                echo '<span class="label-new">' . esc_html(akademiata_get_theme_lang_string('offer_label_new')) . '</span>';
            }

            // Show ranking icon only if neither label is selected
            if (
                empty($etykieta_studia) ||
                (!in_array('coming_soon_icon', (array)$etykieta_studia) && !in_array('new_icon', (array)$etykieta_studia))
            ) {
                if (!empty($ranking_icon_url)) {
                    echo '<img class="ranking_icon" src="' . esc_url($ranking_icon_url) . '" alt="' . esc_attr(akademiata_get_theme_lang_string('offer_ranking_icon_alt')) . '">';
                }
            }

            ?>

            <?php
            $city_name = '';

            if (!is_wp_error($city_terms) && !empty($city_terms)) {
                $city_name = esc_html($city_terms[0]->name); // Get first city name
            }
            if ($city_name) : ?>
                <div class="city_block">
                    <img class="location_icon"
                         src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                         alt="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_location_icon_alt')); ?>">
                    <span><?php echo $city_name; ?></span>
                </div>
            <?php endif; ?>

            <!-- Temporarily hidden — restore when recruitment date block is needed again.
            <div class="price_from">
                <div class="price_from__title">--><?php //_e('Studiuj', 'akademiata'); ?><!--</div>
                <div>--><?php //_e('od Października', 'akademiata'); ?><!--
                </div>
            </div>
            -->

            <?php
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $image_size = 'specialization_card_thumb';
            $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, $image_size);

            if (!empty($thumbnail_url)) :
                ?>
                <a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>">
                    <div role="img" class="image_bg" aria-label="<?php echo esc_attr(get_the_title($post->ID)); ?>"
                         style="background-image: url(<?php echo esc_url($thumbnail_url); ?>)"></div>
                </a>
            <?php endif; ?>
        </div>
        <?php
        $terms = wp_get_post_terms($post->ID, ['degree', 'language', 'obtained_title', 'duration', 'city', 'recruitment_date']);
        ?>
        <div class="card_post_body">
            <div>
                <h2><a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="card_properties_wrapper">
                    <div class="row">

                        <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                            <?php
                            $taxonomy_titles = [
                                'degree' => akademiata_get_theme_lang_string('offer_card_degree'),
//                            'city' => akademiata_get_theme_lang_string('offer_chip_city'),
                                'language' => akademiata_get_theme_lang_string('offer_filter_language'),
                                'obtained_title' => akademiata_get_theme_lang_string('offer_filter_obtained_title'),
                                'duration' => akademiata_get_theme_lang_string('offer_filter_duration'),
                            ];

                            $grouped_terms = [];
                            foreach ($terms as $term) {
                                $grouped_terms[$term->taxonomy][] = esc_html($term->name);
                            }

                            $ordered_terms = [];
                            foreach ($taxonomy_titles as $taxonomy => $title) {
                                if (!empty($grouped_terms[$taxonomy])) {
                                    $ordered_terms[$taxonomy] = $grouped_terms[$taxonomy];
                                }
                            }
                            ?>

                            <?php foreach ($ordered_terms as $taxonomy => $names) : ?>
                                <div class="col-6 card_property">
                                    <div class="sub_title">
                                        <?php echo esc_html($taxonomy_titles[$taxonomy]); ?>:
                                    </div>
                                    <h3>
                                        <?php echo implode(', ', $names); ?>
                                    </h3>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <div class="buttons_wrapper">
                <a class="button-primary mb-3" style="min-width: 140px" href="<?php the_permalink(); ?>"><?php echo esc_html(akademiata_get_theme_lang_string('offer_card_details')); ?></a>
                <?php
                $register_url = get_field('register_url') ?: home_url();
                ?>
                <div class="button-sing_up_wrapper">
                    <a class="button-sing_up mb-3" style="width: 100%"
                       href="<?php echo esc_url($register_url); ?>">
                        <?php echo esc_html(akademiata_get_theme_lang_string('offer_card_register')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
