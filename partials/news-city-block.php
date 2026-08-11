<?php
/**
 * news_city label (Warszawa default when Miasto empty).
 * Hidden when the wpis is tagged with both cities.
 *
 * Args: variant — image (on thumbnail) | inline (archive footer row).
 */

$post_id = get_the_ID();
if (function_exists('akademiata_post_has_multiple_news_cities') && akademiata_post_has_multiple_news_cities($post_id)) {
	return;
}

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
