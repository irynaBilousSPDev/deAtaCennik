<?php
$term_slug = get_query_var('term_slug', '');
$data_city = get_query_var('data_city', 'all');
?>
<div class="course-card card_post_item pg_mba_card" data-term="<?= esc_attr($term_slug); ?>"
     data-city="<?= esc_attr($data_city); ?>">
    <div class="card_post_wrapper">
        <div class="card_post_image">
            <?php

            // City name
            $city_terms = wp_get_post_terms(get_the_ID(), 'city_pg_mba');
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
            // Thumbnail
            $thumbnail_url = wp_get_attachment_image_url(get_post_thumbnail_id(get_the_ID()), 'specialization_card_thumb');
            if ($thumbnail_url) :
                ?>
                <a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>">
                    <div class="image_bg" role="img" aria-label="<?= esc_attr(get_the_title()); ?>"
                         style="background-image: url(<?= esc_url($thumbnail_url); ?>)"></div>
                </a>
            <?php endif; ?>
        </div>

        <div class="card_post_body">
            <div>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                <div class="card_properties_wrapper">
                    <div class="row">
                        <?php
                        $taxonomy_titles = [
                            'mode_course' => __('Forma zajęć', 'akademiata'),
                            'language' => __('Język', 'akademiata'),
                            'duration_course' => __('Czas trwania', 'akademiata'),
                            'instructor_course' => __('Prowadzący', 'akademiata'),
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
                <?php $register_url = get_field('register_url'); ?>

                <a class="button-sing_up"
                   href="<?php echo esc_url($register_url); ?>"><?php _e('ZAPISZ SIĘ', 'akademiata'); ?></a>

            </div>
        </div>
    </div>
</div>
