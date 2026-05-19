<?php
/**
 * Cards for section_for_you_if only.
 * Styles: assets/src/scss/pages/single-offer/_for_you_if.scss
 */
$cards = get_query_var('cards', []);

if (empty($cards)) {
    return;
}

$img_sizes = '(max-width: 990px) 380px, 532px';

$for_you_strip_img_dims = static function (array $attr) {
    if (!empty($attr['class']) && str_contains($attr['class'], 'for-you-card__img')) {
        unset($attr['width'], $attr['height']);
    }
    return $attr;
};
add_filter('wp_get_attachment_image_attributes', $for_you_strip_img_dims);
?>
<div class="for-you-cards">
    <?php foreach ($cards as $item) :
        $image = $item['image'] ?? [];
        $attachment_id = !empty($image['ID']) ? (int) $image['ID'] : 0;

        $fallback_url = !empty($image['url']) ? esc_url($image['url']) : '';
        $mobile_url = !empty($image['sizes']['card_image_mobile'])
            ? esc_url($image['sizes']['card_image_mobile'])
            : $fallback_url;
        $desktop_url = !empty($image['sizes']['card_image'])
            ? esc_url($image['sizes']['card_image'])
            : $fallback_url;

        $image_alt = !empty($image['alt']) ? esc_attr($image['alt']) : __('Card Image', 'akademiata');
        $title = trim($item['title'] ?? '');
        $content = trim($item['content'] ?? '');
        ?>
        <article class="for-you-card">
            <?php if ($attachment_id || $desktop_url) : ?>
                <div class="for-you-card__media">
                    <?php if ($attachment_id) : ?>
                        <?php
                        echo wp_get_attachment_image(
                            $attachment_id,
                            'card_image',
                            false,
                            [
                                'class' => 'for-you-card__img',
                                'sizes' => $img_sizes,
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'alt' => $image_alt,
                            ]
                        );
                        ?>
                    <?php else : ?>
                        <picture>
                            <?php if ($mobile_url && $mobile_url !== $desktop_url) : ?>
                                <source media="(max-width: 990px)" srcset="<?php echo esc_url($mobile_url); ?>">
                            <?php endif; ?>
                            <img
                                class="for-you-card__img"
                                src="<?php echo esc_url($desktop_url); ?>"
                                alt="<?php echo $image_alt; ?>"
                                loading="lazy"
                                decoding="async"
                            >
                        </picture>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($title || $content) : ?>
                <div class="for-you-card__body">
                    <?php if ($title) : ?>
                        <h3 class="for-you-card__title primary_color"><?php echo esc_html($title); ?></h3>
                    <?php endif; ?>
                    <?php if ($content) : ?>
                        <div class="for-you-card__text">
                            <?php echo wp_kses_post($content); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php
remove_filter('wp_get_attachment_image_attributes', $for_you_strip_img_dims);
