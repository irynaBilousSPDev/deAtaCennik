<?php
/**
 * news_city label (Warszawa default when Miasto empty).
 *
 * Args: variant — image (on thumbnail) | inline (archive footer row).
 */

$variant = isset($args['variant']) ? sanitize_key($args['variant']) : 'image';
$class   = 'city_block';

if ($variant === 'inline') {
    $class .= ' city_block--inline';
}
?>
<div class="<?php echo esc_attr($class); ?>">
    <img class="location_icon"
         src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
         alt="<?php esc_attr_e('Location Icon', 'akademiata'); ?>">
    <span><?php echo esc_html(akademiata_get_post_news_city_label()); ?></span>
</div>
