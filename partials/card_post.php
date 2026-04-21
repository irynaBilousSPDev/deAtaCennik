<div class="card_post_item">

    <div class="card_post_wrapper">
        <div class="card_post_image">
            <?php
            $termsRanking = wp_get_post_terms($post->ID, ['program']);
            $city_terms = wp_get_post_terms($post->ID, 'city');
            $ranking_icon_url = '';

            if (!is_wp_error($termsRanking) && !empty($termsRanking)) {
                // Get city slug
                $city_slug = '';
                if (!is_wp_error($city_terms) && !empty($city_terms)) {
                    $city_slug = $city_terms[0]->slug;
                }

                foreach ($termsRanking as $term) {
                    $term_id = $term->term_id;

                    // If city is wroclaw, use alternate field
                    if ($city_slug === 'wroclaw') {
                        $ranking_icon = get_field('ranking_icon_wro', 'program_' . $term_id);
                    } else {
                        $ranking_icon = get_field('ranking_icon', 'program_' . $term_id);
                    }

                    if (!empty($ranking_icon) && isset($ranking_icon['url'])) {
                        $ranking_icon_url = esc_url($ranking_icon['url']);
                        break; // Use the first valid icon
                    }
                }
            }

            //            etykieta_studia
            $etykieta_studia = get_field('etykieta_studia', get_the_ID());

            // Show "wkrótce" label
            if (!empty($etykieta_studia) && in_array('coming_soon_icon', (array)$etykieta_studia)) {
                echo '<span class="label-coming-soon">' . esc_html__('wkrótce', 'akademiata') . '</span>';
            }

            // Show "nowość" label
            if (!empty($etykieta_studia) && in_array('new_icon', (array)$etykieta_studia)) {
                echo '<span class="label-new">' . esc_html__('nowość', 'akademiata') . '</span>';
            }

            // Show ranking icon only if neither label is selected
            if (
                empty($etykieta_studia) ||
                (!in_array('coming_soon_icon', (array)$etykieta_studia) && !in_array('new_icon', (array)$etykieta_studia))
            ) {
                if (!empty($ranking_icon_url)) {
                    echo '<img class="ranking_icon" src="' . esc_url($ranking_icon_url) . '" alt="' . esc_attr__('Ranking Icon', 'akademiata') . '">';
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
                         alt="<?php _e('Location Icon', 'akademiata'); ?>">
                    <span><?php echo $city_name; ?></span>
                </div>
            <?php endif; ?>

            <?php
            $current_lang  = apply_filters('wpml_current_language', null);

            // All slugs for March 2026 in all languages
//            $target_slugs = [
//                'marzec-2026',   // PL
//                'march-2026',    // EN
//                'mart-2026',     // RU
//                'berezen-2026',  // UK
//            ];
//
//            $terms = get_the_terms(get_the_ID(), 'recruitment_date');
//            $has_march_2026 = false;
//
//            if (!empty($terms) && !is_wp_error($terms)) {
//                foreach ($terms as $term) {
//                    if (in_array($term->slug, $target_slugs, true)) {
//                        $has_march_2026 = true;
//                        break;
//                    }
//                }
//            }

            // If recruitment_date has March 2026 (any language) → show "Studiuj od Marca" in ALL languages
//            if ($has_march_2026) : ?>
                <div class="price_from">
                    <div class="price_from__title"><?php _e('Studiuj', 'akademiata'); ?></div>
                    <div>
                        <?php _e('od Października', 'akademiata'); ?>
                    </div>
                </div>
            <?php
// Else → old price logic (only if not EN)
//            elseif ($current_lang !== 'en') :
//
//                $price_data = get_first_price_row_for_post(get_the_ID());
//                $currency   = 'zł';
//                $price_text = '';
//
//                if ($price_data) {
//                    $first_row = !empty($price_data['full_time'])
//                        ? $price_data['full_time'][0]
//                        : ($price_data['part_time'][0] ?? []);
//
//                    $col_data   = $first_row['col_12_rat'] ?? [];
//                    $has_promo  = !empty($col_data['add_promotion']) && in_array('promotion', $col_data['add_promotion']);
//                    $raw_price  = '';
//
//                    if ($has_promo && !empty($col_data['promotion_price'])) {
//                        $raw_price = $col_data['promotion_price'];
//                    } elseif (!empty($col_data['normal_price'])) {
//                        $raw_price = $col_data['normal_price'];
//                    }
//
//                    if (!empty($raw_price)) {
//                        $number     = floatval(str_replace([' ', ','], ['', '.'], $raw_price));
//                        $price_text = number_format($number, 0, '.', ' ') . ' ' . $currency;
//                    }
//                }
//                ?>
<!--            --><?php //endif; ?>


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
                                'degree' => __('Rodzaj studiów', 'akademiata'),
//                            'city' => __('Miasto', 'akademiata'),
                                'language' => __('Język studiów', 'akademiata'),
                                'obtained_title' => __('Uzyskany tytuł', 'akademiata'),
                                'duration' => __('Czas trwania', 'akademiata'),
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
                <a class="button-primary mb-3" style="min-width: 140px" href="<?php the_permalink(); ?>"><?php _e('SZCZEGÓŁY', 'akademiata'); ?></a>
                <?php
                $register_url = get_field('register_url') ?: home_url();
                ?>
<!--                --><?php //if ($has_march_2026) : ?>

                    <!-- ACTIVE BUTTON -->
                <div class="button-sing_up_wrapper">
                    <a class="button-sing_up mb-3" style="width: 100%"
                       href="<?php echo esc_url($register_url); ?>">
                        <?php _e('ZAPISZ SIĘ', 'akademiata'); ?>
                    </a>
                </div>

<!--                --><?php //else : ?>
<!---->
<!--                    --><?php //get_template_part('partials/button_ended'); ?>
<!---->
<!--                --><?php //endif; ?>
            </div>
        </div>
    </div>
</div>
