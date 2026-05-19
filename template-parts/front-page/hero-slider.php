<?php
/**
 * Homepage hero slider — ACF main_slider.
 *
 * @package akademiata
 */

$slides = get_query_var('hero_slider_slides', []);

if (empty($slides) || !is_array($slides)) {
    return;
}

$slide_count = count($slides);
$logo_compact = get_template_directory_uri() . '/static/img/logo_ata_compact.webp';
?>

<section class="hero-slider" data-slide-count="<?php echo esc_attr((string) $slide_count); ?>">
    <div class="hero-slider__viewport">
        <div class="swiper hero-slider__swiper">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $slide_index => $slide) :
                    $urls = akademiata_hero_slide_image_urls($slide);
                    $image_desktop = $urls['desktop'];
                    $image_mobile = $urls['mobile'] ?: $image_desktop;
                    $image = is_array($slide['image'] ?? null) ? $slide['image'] : [];
                    $title = !empty($slide['title']) ? (string) $slide['title'] : '';
                    $alt = !empty($image['alt']) ? (string) $image['alt'] : $title;
                    $button = !empty($slide['button']) ? $slide['button'] : null;
                    $button_group = (is_array($button) && !empty($button['button'])) ? $button['button'] : [];
                    $slide_href = !empty($button_group['button_link']) ? esc_url($button_group['button_link']) : '';
                    $slide_target = !empty($button_group['button_target']) ? '_blank' : '_self';
                    $img_width = (int) ($image['sizes']['main_slider_banner-width'] ?? $image['width'] ?? 1500);
                    $img_height = (int) ($image['sizes']['main_slider_banner-height'] ?? $image['height'] ?? 804);
                    $is_first_slide = ($slide_index === 0);
                    ?>
                    <div class="swiper-slide<?php echo $slide_href ? ' hero-slider__slide--linked' : ''; ?>"
                        <?php if ($slide_href) : ?>
                            data-href="<?php echo $slide_href; ?>"
                            data-target="<?php echo esc_attr($slide_target); ?>"
                        <?php endif; ?>>
                        <article class="hero-slider__slide">
                            <div class="hero-slider__media">
                                <picture>
                                    <?php if ($image_mobile !== $image_desktop) : ?>
                                        <source media="(max-width: 767px)" srcset="<?php echo esc_url($image_mobile); ?>">
                                    <?php endif; ?>
                                    <img
                                        class="hero-slider__image"
                                        src="<?php echo esc_url($image_desktop); ?>"
                                        alt="<?php echo esc_attr($alt); ?>"
                                        width="<?php echo esc_attr((string) $img_width); ?>"
                                        height="<?php echo esc_attr((string) $img_height); ?>"
                                        decoding="async"
                                        <?php if ($is_first_slide) : ?>
                                            fetchpriority="high"
                                        <?php elseif ($slide_index === 1) : ?>
                                            loading="eager"
                                        <?php else : ?>
                                            loading="lazy"
                                        <?php endif; ?>
                                    >
                                </picture>
                                <div class="hero-slider__overlay">
                                    <div class="hero-slider__footer">
                                        <?php if ($button) : ?>
                                            <div class="hero-slider__cta">
                                                <?php render_acf_button($button); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($title !== '') : ?>
                                            <p class="hero-slider__title"><?php echo esc_html($title); ?></p>
                                        <?php endif; ?>
                                        <img
                                            class="hero-slider__logo"
                                            src="<?php echo esc_url($logo_compact); ?>"
                                            alt="<?php echo esc_attr__('Logo ATA', 'akademiata'); ?>"
                                            width="48"
                                            height="48"
                                            decoding="async"
                                            <?php echo $is_first_slide ? 'loading="eager"' : 'loading="lazy"'; ?>
                                        >
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="hero-slider__controls">
        <div class="hero-slider__dots" role="tablist"
             aria-label="<?php esc_attr_e('Nawigacja slidera', 'akademiata'); ?>"></div>
        <button
            type="button"
            class="hero-slider__autoplay is-playing"
            aria-pressed="false"
            data-label-pause="<?php esc_attr_e('Wstrzymaj automatyczne przewijanie', 'akademiata'); ?>"
            data-label-play="<?php esc_attr_e('Wznów automatyczne przewijanie', 'akademiata'); ?>"
            aria-label="<?php esc_attr_e('Wstrzymaj automatyczne przewijanie', 'akademiata'); ?>"
            <?php echo $slide_count < 2 ? ' hidden="hidden"' : ''; ?>
        >
            <span class="hero-slider__autoplay-icon hero-slider__autoplay-icon--pause" aria-hidden="true"></span>
            <span class="hero-slider__autoplay-icon hero-slider__autoplay-icon--play" aria-hidden="true" hidden></span>
        </button>
    </div>
</section>
