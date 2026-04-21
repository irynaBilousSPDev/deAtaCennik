<?php
$acf_fields = get_query_var('acf_fields', []);
$why_study = $acf_fields['why_study'] ?? null;

if ($why_study && !empty($why_study['why_study_cards'])) :
    $section_title = $why_study['title'];
    $section_title = $section_title ? $section_title : __('Dlaczego warto studiować w ATA', 'akademiata');
    ?>
    <section id="why_study" class="section_why_study left_space py-md-5">
        <div class="container">
            <?php if ($section_title) : ?>
                <h2 class="title_section primary_color mb-5"><?php echo $section_title; ?></h2>
            <?php endif; ?>

            <div class="why_study_slider new_study_slider">
                <?php foreach ($why_study['why_study_cards'] as $card) :
                    $image = $card['image'] ?? [];

                    $image_url_fallback = $image['url'] ?? '';
                    $image_url_mobile = $image['sizes']['study_slider_image_mobile'] ?? $image_url_fallback;
                    $image_url_desktop = $image['sizes']['study_slider_image'] ?? $image_url_fallback;

                    $image_url_fallback = esc_url($image_url_fallback);
                    $image_url_mobile = esc_url($image_url_mobile);
                    $image_url_desktop = esc_url($image_url_desktop);

                    $alt_text = esc_attr($image['alt'] ?? __('Study Image', 'akademiata'));

                    $card_title = esc_html($card['title'] ?? '');
                    $card_content = wp_kses_post($card['content'] ?? '');
                    ?>

                    <div class="slider_card">
                        <div class="image_wrapper">

                            <div class="image_bg responsive-image" role="img" aria-label="<?php echo $alt_text; ?>"
                                 data-mobile="<?php echo $image_url_mobile; ?>"
                                 data-desktop="<?php echo $image_url_desktop; ?>"
                                 style="background-image: url('<?php echo $image_url_desktop; ?>');"
                                 loading="lazy">
                            </div>
                        </div>
                        <div class="card_body">

                            <?php if ($card_title) : ?>
                                <h2 class="sub_title mb-3"><?php echo $card_title; ?></h2>
                            <?php endif; ?>
                            <?php if ($card_content) : ?>
                                <p><?php echo $card_content; ?></p>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
