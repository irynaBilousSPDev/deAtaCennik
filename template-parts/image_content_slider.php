<?php
$image_content_slider = get_query_var('image_content_slider', []);
if (!empty($image_content_slider)): ?>
    <div class="image_content_slider" style="position: relative">
        <div class="row">
            <div class="col-lg-6">
                <div class="slider_for_images">
                    <?php foreach ($image_content_slider as $item) :
                        // Ensure $item['image'] exists before accessing its keys
                        $image = $item['image'] ?? [];

                        // Get the original image URL as a fallback
                        $fallback_image_url = !empty($image['url']) ? esc_url($image['url']) : '';

                        // Get Both Mobile & Desktop Image URLs
                        $image_url_mobile = !empty($image['sizes']['image_content_slider_mobile'])
                            ? esc_url($image['sizes']['image_content_slider_mobile'])
                            : $fallback_image_url;

                        $image_url_desktop = !empty($image['sizes']['image_content_slider'])
                            ? esc_url($image['sizes']['image_content_slider'])
                            : $fallback_image_url;

                        //  Get Alt Text (with a safe fallback)
                        $image_alt = !empty($image['alt']) ? esc_attr($image['alt']) : __('Card Image', 'akademiata');
                        ?>

                        <!-- Display the Image with Responsive Data -->
                        <div class="image_bg responsive-image" role="img"
                             aria-label="<?php echo $image_alt; ?>"
                             data-mobile="<?php echo esc_url($image_url_mobile); ?>"
                             data-desktop="<?php echo esc_url($image_url_desktop); ?>"
                             style="background-image: url('<?php echo esc_url($image_url_desktop); ?>');">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6 d-flex flex-column-reverse flex-lg-column justify-content-center">
                <div class="slider_nav_content">
                    <?php foreach ($image_content_slider as $item) :
                        $title = $item['title'] ?? '';
                        $content = $item['content'] ?? '';
                        ?>
                        <div class="slider_content_wrapper">
                            <div class="slider_content_container">
                                <?php if (!empty($title)) : ?>
                                    <h3 class="title primary_color mb-5">
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
                    <?php endforeach; ?>
                </div>
                <div class="controls_wrapper"></div>
            </div>
        </div>
    </div>
<?php endif; ?>