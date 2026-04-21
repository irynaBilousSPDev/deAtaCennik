<div class="card_post_item pg_mba_card">
    <div class="card_post_wrapper">
        <div class="card_post_image">

            <?php
            // Ranking icon from program taxonomy
            $termsRanking = wp_get_post_terms($post->ID, ['program']);
            $ranking_icon_url = '';

            if (!is_wp_error($termsRanking) && !empty($termsRanking)) {
                foreach ($termsRanking as $term) {
                    $ranking_icon = get_field('ranking_icon', 'program_' . $term->term_id);
                    if (!empty($ranking_icon['url'])) {
                        $ranking_icon_url = esc_url($ranking_icon['url']);
                        break;
                    }
                }
            }

            if ($ranking_icon_url) :
                echo '<img class="ranking_icon" src="' . $ranking_icon_url . '" alt="Ranking Icon">';
            endif;

            // City name from city_pg_mba
            $city_terms = wp_get_post_terms($post->ID, 'city_pg_mba');
            $city_name = (!is_wp_error($city_terms) && !empty($city_terms)) ? esc_html($city_terms[0]->name) : '';
            ?>

            <?php if ($city_name) : ?>
                <div class="city_block">
                    <img class="location_icon"
                         src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                         alt="<?php _e('Location Icon', 'akademiata'); ?>">
                    <span><?php echo $city_name; ?></span>
                </div>
            <?php endif; ?>
            <?php

            $full_time_price = get_field('full_time') ?: '';
            $part_time_price = get_field('part_time') ?: '';
            $currency = apply_filters('wpml_current_language', null) === 'en' ? '€/month' : 'zł/mies.';
            $price_text = '';

            $source_data = [];

            if (!empty($full_time_price) && is_array($full_time_price)) {
                $source_data = $full_time_price;
            } elseif (!empty($part_time_price) && is_array($part_time_price)) {
                $source_data = $part_time_price;
            }

            if (!empty($source_data)) {
                $row = $source_data[0] ?? [];
                $col_data = $row['col_8_rat'] ?? [];

                if (is_array($col_data)) {
                    $add_promotion = $col_data['add_promotion_rat8'] ?? [];
                    $has_promo = (is_array($add_promotion) && in_array('promotion', $add_promotion))
                        || $add_promotion === 'promotion';

                    if ($has_promo && !empty($col_data['rat8_promotion_price'])) {
                        $price_text = esc_html($col_data['rat8_promotion_price']) . ' ' . $currency;
                    } elseif (!empty($col_data['rat8_normal_price'])) {
                        $price_text = esc_html($col_data['rat8_normal_price']) . ' ' . $currency;
                    }
                }
            }
            ?>
<!--            --><?php //if (!empty($price_text)) : ?>
<!--                <div class="price_from">-->
<!--                    <div class="price_from__title">--><?php //_e('już od', 'akademiata'); ?><!--</div>-->
<!--                    <div>-->
<!--                        --><?php //echo $price_text; ?>
<!--                    </div>-->
<!--                </div>-->
<!--            --><?php //endif; ?>

            <?php
            // Thumbnail
            $thumbnail_url = wp_get_attachment_image_url(get_post_thumbnail_id($post->ID), 'specialization_card_thumb');
            if ($thumbnail_url) :
                ?>
                <a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>">
                    <div class="image_bg" role="img" aria-label="<?= esc_attr(get_the_title()); ?>"
                         style="background-image: url(<?= esc_url($thumbnail_url); ?>)"></div>
                </a>
            <?php endif; ?>
        </div>

        <div class="card_post_body" style="display: flex;flex-direction: column;justify-content: space-between">
            <div>
                <h2 class="mb-3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                <div class="card_properties_wrapper">
                    <div class="row">
                        <?php
                        $taxonomy_titles = [
//                            'type_of_study_pg_mba' => __('Rodzaj studiów', 'akademiata'),

                            'language_pg_mba' => __('Język', 'akademiata'),
                            'duration_pg_mba' => __('Czas trwania', 'akademiata'),
//                            'diploma_pg_mba' => __('Dyplom', 'akademiata'),
//                            'form_pg_mba'          => __('Forma studiów', 'akademiata'),


                        ];

                        $all_terms = wp_get_post_terms($post->ID, array_keys($taxonomy_titles));
                        $grouped_terms = [];

                        foreach ($all_terms as $term) {
                            $grouped_terms[$term->taxonomy][] = esc_html($term->name);
                        }

                        foreach ($taxonomy_titles as $taxonomy => $label) {
                            if (!empty($grouped_terms[$taxonomy])) :
                                ?>
                                <div class="col-6 card_property">
                                    <div class="sub_title"><?php echo $label; ?>:</div>
                                    <h3><?php echo implode(', ', $grouped_terms[$taxonomy]); ?></h3>
                                </div>
                            <?php endif;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="buttons_wrapper">
                <a class="button-primary" href="<?php the_permalink(); ?>"><?php _e('SZCZEGÓŁY', 'akademiata'); ?></a>
                <?php
                $register_url = get_field('register_url') ?: home_url();
                ?>
                <a class="button-sing_up"
                   href="<?php echo esc_url($register_url); ?>"><?php _e('ZAPISZ SIĘ', 'akademiata'); ?></a>
            </div>
        </div>
    </div>
</div>
