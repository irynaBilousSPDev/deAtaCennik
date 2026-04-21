<?php
$slide_id = get_the_ID();

/**
 * Images (ACF + fallbacks)
 */
$acf_image         = get_field('image_thumb', $slide_id) ?: [];
$acf_image_mobile  = get_field('image_thumb_pion', $slide_id) ?: [];

$image_url_desktop = !empty($acf_image['sizes']['product_slider_thumb'])
    ? esc_url($acf_image['sizes']['product_slider_thumb'])
    : esc_url(get_the_post_thumbnail_url($slide_id, 'product_slider_thumb'));

$image_url_mobile  = !empty($acf_image_mobile['sizes']['offer_image_mobile_pion'])
    ? esc_url($acf_image_mobile['sizes']['offer_image_mobile_pion'])
    : $image_url_desktop;

$alt_text = !empty($acf_image['alt'])
    ? esc_attr($acf_image['alt'])
    : esc_attr(get_the_title($slide_id));

/**
 * Program (category) term
 */
$categories    = get_the_terms($slide_id, 'program');
$category_name = (!empty($categories) && !is_wp_error($categories))
    ? esc_html($categories[0]->name)
    : '';

/**
 * City term (for choosing ranking icon field)
 */
$city_terms = wp_get_post_terms($slide_id, 'city');
$city_slug  = (!empty($city_terms) && !is_wp_error($city_terms)) ? $city_terms[0]->slug : '';
$city_name  = (!empty($city_terms) && !is_wp_error($city_terms)) ? esc_html($city_terms[0]->name) : '';

/**
 * Ranking icon field depends on city slug
 */
$ranking_icon_url = '';
if (!empty($categories) && !is_wp_error($categories)) {
    $category = $categories[0];
    $field_key = ($city_slug === 'wroclaw') ? 'ranking_icon_wro' : 'ranking_icon';
    $ranking_icon = get_field($field_key, 'program_' . $category->term_id) ?: [];

    if (!empty($ranking_icon['url'])) {
        $ranking_icon_url = esc_url($ranking_icon['url']);
    }
}

/**
 * Label switches (ACF: etykieta_studia on this slide)
 * Possible values (array): 'coming_soon_icon', 'new_icon'
 */
$etykieta_studia = get_field('etykieta_studia', $slide_id);
$has_coming_soon = !empty($etykieta_studia) && in_array('coming_soon_icon', (array) $etykieta_studia, true);
$has_new         = !empty($etykieta_studia) && in_array('new_icon', (array) $etykieta_studia, true);
?>
<?php if (wp_is_mobile()) : ?>
    <a title="<?php the_title(); ?> - <?php echo esc_html($category_name); ?>"
       href="<?php the_permalink(); ?>" class="button-primary image_bg " role="img"
       aria-label="<?php echo esc_attr($alt_text); ?>"
       style="background-image: url(<?php echo esc_url($image_url_mobile); ?>);">
        <?php
        // Labels: show textual labels if selected…
        if ($has_coming_soon) {
            echo '<span class="label-coming-soon">' . esc_html__('wkrótce', 'akademiata') . '</span>';
        }

        if ($has_new) {
            echo '<span class="label-new">' . esc_html__('nowość', 'akademiata') . '</span>';
        }

        // …otherwise show ranking icon if available.
        if (!$has_coming_soon && !$has_new && !empty($ranking_icon_url)) {
            echo '<img class="ranking_icon" src="' . $ranking_icon_url . '" alt="' . esc_attr__('Ranking Icon', 'akademiata') . '">';
        }
        ?>

        <?php if ($city_name) : ?>
            <div class="city_block">
                <img class="location_icon"
                     src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                     alt="<?php _e('Location Icon', 'akademiata'); ?>">
                <span><?php echo $city_name; ?></span>
            </div>
        <?php endif; ?>

        <div class="details">
            <h3 class="small_title"><?php the_title(); ?></h3>
            <?php if (!empty($category_name)) : ?>
                <!--                <div class="category_name">--><?php //echo esc_html($category_name); ?><!--</div>-->
            <?php endif; ?>
        </div>
    </a>
<?php else: ?>
    <div class="image_bg" role="img"
         aria-label="<?php echo esc_attr($alt_text); ?>"
         style="background-image: url(<?php echo esc_url($image_url_desktop); ?>);"
         loading="lazy">
        <?php
        // Labels: show textual labels if selected…
        if ($has_coming_soon) {
            echo '<span class="label-coming-soon">' . esc_html__('wkrótce', 'akademiata') . '</span>';
        }

        if ($has_new) {
            echo '<span class="label-new">' . esc_html__('nowość', 'akademiata') . '</span>';
        }

        // …otherwise show ranking icon if available.
        if (!$has_coming_soon && !$has_new && !empty($ranking_icon_url)) {
            echo '<img class="ranking_icon" src="' . $ranking_icon_url . '" alt="' . esc_attr__('Ranking Icon', 'akademiata') . '">';
        }
        ?>
        <?php if ($city_name) : ?>
            <div class="city_block">
                <img class="location_icon"
                     src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                     alt="<?php _e('Location Icon', 'akademiata'); ?>">
                <span><?php echo $city_name; ?></span>
            </div>
        <?php endif; ?>

        <div class="details">
            <h3 class="small_title"><?php the_title(); ?></h3>
            <?php if (!empty($category_name)) : ?>
                <!--                <div class="category_name">--><?php //echo esc_html($category_name); ?><!--</div>-->
            <?php endif; ?>
        </div>
        <div class="more_details text-center">
            <!--            <h3 class="small_title">--><?php //the_title(); ?><!--</h3>-->
            <a title="<?php the_title(); ?> - <?php echo esc_html($category_name); ?>"
               href="<?php the_permalink(); ?>" class="button-primary">
                <?php _e('Zobacz', 'akademiata'); ?>
            </a>
        </div>
    </div>

<?php endif; ?>
