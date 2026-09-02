<?php
$offer_theme_terms = wp_get_post_terms($post->ID, 'offer_theme_pg_mba');
$offer_theme_slugs = array();
$city_pg_mba_terms = wp_get_post_terms($post->ID, 'city_pg_mba');
$city_slug         = '';
$city_name         = '';

if (!is_wp_error($offer_theme_terms) && !empty($offer_theme_terms)) {
    $offer_theme_slugs = wp_list_pluck($offer_theme_terms, 'slug');
}
if (!is_wp_error($city_pg_mba_terms) && !empty($city_pg_mba_terms)) {
    $city_slug = $city_pg_mba_terms[0]->slug;
    $city_name = esc_html($city_pg_mba_terms[0]->name);
}

$termsRanking = wp_get_post_terms($post->ID, array('program'));
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
?>
<div class="card_post_item pg_mba_card"
     data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
     data-city="<?php echo esc_attr($city_slug); ?>"
     data-post-type="<?php echo esc_attr(get_post_type($post->ID)); ?>"
     data-offer-theme="<?php echo esc_attr(implode(',', $offer_theme_slugs)); ?>">
    <div class="card_post_wrapper">
        <div class="card_post_image">
            <?php if ($ranking_icon_url) : ?>
                <img class="ranking_icon ranking_icon--overlay"
                     src="<?php echo $ranking_icon_url; ?>"
                     alt="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_ranking_icon_alt')); ?>">
            <?php endif; ?>

            <?php if ($city_name) : ?>
                <div class="city_block city_block--overlay">
                    <img class="location_icon"
                         src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                         alt="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_location_icon_alt')); ?>">
                    <span><?php echo $city_name; ?></span>
                </div>
            <?php endif; ?>

            <?php
            $thumbnail_url = wp_get_attachment_image_url(get_post_thumbnail_id($post->ID), 'specialization_card_thumb');
            if ($thumbnail_url) :
                ?>
                <a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>">
                    <div class="image_bg" role="img" aria-label="<?php echo esc_attr(get_the_title()); ?>"
                         style="background-image: url(<?php echo esc_url($thumbnail_url); ?>)"></div>
                </a>
            <?php endif; ?>

            <button type="button"
                    class="pg-mba-favorite-btn"
                    data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
                    data-post-type="<?php echo esc_attr(get_post_type($post->ID)); ?>"
                    aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('offer_favorite_add')); ?>"
                    aria-pressed="false">
                <svg class="pg-mba-favorite-btn__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            </button>
        </div>

        <div class="card_post_body" style="display: flex;flex-direction: column;justify-content: space-between">
            <div>
                <?php if ($city_name) : ?>
                    <div class="city_block city_block--inline">
                        <img class="location_icon"
                             src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                             alt=""
                             aria-hidden="true">
                        <span><?php echo $city_name; ?></span>
                    </div>
                <?php endif; ?>
                <h2 class="mb-3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                <div class="card_properties_wrapper">
                    <div class="row">
                        <?php
                        $taxonomy_titles = array(
                            'language_pg_mba' => __('Język', 'akademiata'),
                            'duration_pg_mba' => __('Czas trwania', 'akademiata'),
                        );

                        $all_terms = wp_get_post_terms($post->ID, array_keys($taxonomy_titles));
                        $grouped_terms = array();

                        foreach ($all_terms as $term) {
                            $grouped_terms[ $term->taxonomy ][] = esc_html($term->name);
                        }

                        foreach ($taxonomy_titles as $taxonomy => $label) {
                            if (!empty($grouped_terms[ $taxonomy ])) :
                                ?>
                                <div class="col-6 card_property">
                                    <div class="sub_title"><?php echo $label; ?>:</div>
                                    <h3><?php echo implode(', ', $grouped_terms[ $taxonomy ]); ?></h3>
                                </div>
                            <?php
                            endif;
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
