<?php
$banners = get_query_var('two_column_banner', []);
if (!empty($banners)) : ?>
    <section class="promo_section promo_section_<?php echo count($banners) === 1 ? 'single' : 'double'; ?>">
        <?php foreach ($banners as $index => $banner) : ?>
            <div class="promo_box <?php echo count($banners) === 1 ? 'full_width' : ($index === 0 ? 'left' : 'right'); ?>"
                 style="background-image: url('<?php echo esc_url($banner['image']); ?>');">
                <div class="promo_content">
                    <h2>
                        <span><?php echo esc_html($banner['title_line_1']); ?></span><br>
                        <strong><?php echo esc_html($banner['title_line_2']); ?></strong>
                    </h2>
                    <div class="button_wrapper">
                        <?php if (!empty($banner['button_link'])) : ?>
                            <a href="<?php echo esc_url($banner['button_link']); ?>" class="promo_button">
                                <?php echo esc_html($banner['button_text']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
