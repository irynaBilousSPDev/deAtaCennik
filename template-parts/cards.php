<?php
$cards = get_query_var('cards', []);

if (!empty($cards)) : ?>
    <div class="cards">
        <div class="row">
            <?php foreach ($cards as $item) :
                // Ensure $item['image'] exists before accessing its keys
                $image = $item['image'] ?? [];

                // Get the original image URL as a fallback
                $fallback_image_url = !empty($image['url']) ? esc_url($image['url']) : '';

                //  Get Both Mobile & Desktop Image URLs
                $image_url_mobile = !empty($image['sizes']['card_image_mobile'])
                    ? esc_url($image['sizes']['card_image_mobile'])
                    : $fallback_image_url;

                $image_url_desktop = !empty($image['sizes']['card_image'])
                    ? esc_url($image['sizes']['card_image'])
                    : $fallback_image_url;

                // Get Alt Text (with a safe fallback)
                $image_alt = !empty($image['alt']) ? esc_attr($image['alt']) : __('Card Image', 'akademiata');

                // Get Title & Content
                $title = trim($item['title'] ?? '');
                $content = trim($item['content'] ?? '');
                ?>
                <div class="col-lg-4 mb-3">
                    <div class="card_wrapper">
                        <div class="card_item">
                            <div class="image_wrapper mb-3">
                                <div class="image_bg responsive-image" role="img"
                                     aria-label="<?php echo $image_alt; ?>"
                                     data-mobile="<?php echo esc_url($image_url_mobile); ?>"
                                     data-desktop="<?php echo esc_url($image_url_desktop); ?>"
                                     style="background-image: url('<?php echo esc_url($image_url_desktop); ?>');">
                                </div>
                            </div>
                            <div class="card_body">
                                <?php if (!empty($title)) : ?>
                                    <h3 class="title primary_color mb-3">
                                        <?php echo esc_html($title); ?>
                                    </h3>
                                <?php endif; ?>
                                <?php if (!empty($content)) : ?>
                                    <div class="content">
                                        <?php echo wp_kses_post($content); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
