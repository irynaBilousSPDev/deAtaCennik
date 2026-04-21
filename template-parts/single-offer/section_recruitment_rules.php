<?php
$acf_fields = get_query_var('acf_fields', []);
$recruitment_rules = !empty($acf_fields['recruitment_rules']) ? $acf_fields['recruitment_rules'] : [];
// Check if section data exists before rendering
if (!empty($recruitment_rules)) :
    $title = $recruitment_rules['title'] ?? '';
    $sub_title = $recruitment_rules['sub_title'];
    $sub_title = $sub_title ? $sub_title : __('to proste', 'akademiata');
    $title = $title ? $title : __('Zasady rekrutacji', 'akademiata');
    $steps = $recruitment_rules['steps'] ?? '';
    ?>
    <section id="recruitment_rules" class="section_recruitment_rules">
        <div class="container">
            <h2 class="title_section mb-5"><?php echo esc_html($title); ?></h2>
        </div>
        <?php
        $image = $recruitment_rules['image'] ?? [];

        $image_url_fallback = !empty($image['url']) ? esc_url($image['url']) : '';

        $image_url_mobile = !empty($image['sizes']['program_image_mobile']) ? esc_url($image['sizes']['program_image_mobile']) : $image_url_fallback;
        $image_url_desktop = !empty($image['sizes']['program_image']) ? esc_url($image['sizes']['program_image']) : $image_url_fallback;

        $alt_text = !empty($image['alt']) ? esc_attr($image['alt']) : __('Recruitment rules Image', 'akademiata');

        ?>
        <div class="image_bg mb-5 responsive-image" role="img"
             aria-label="<?php echo $alt_text; ?>"
             data-mobile="<?php echo $image_url_mobile; ?>"
             data-desktop="<?php echo $image_url_desktop; ?>"
             style="background-image: url('<?php echo $image_url_desktop; ?>');">
            <div class="container">
                <div class="recruitment_rules">
                    <?php if (!empty($sub_title)) : ?>
                        <h3 class="primary_color mb-5"><?php echo esc_html($sub_title); ?></h3>
                    <?php endif; ?>
                    <?php if ($steps) : ?>
                        <div class="steps">
                            <?php foreach ($steps as $key => $step) :
                                $text = $step['text'] ?? '';
                                ?>
                                <div class="step">
                                    <span class="step_number primary_color"><?php echo $key + 1; ?></span> <br>
                                    <?php if ($key === 0) : ?>
                                        <?php _e('Kliknij przycisk', 'akademiata'); ?>
                                        <?php $register_url = !empty($acf_fields['register_url']) ?
                                            $acf_fields['register_url'] : ''; ?>
                                        <a href="<?php echo $register_url; ?>" target="_blank"
                                           class="button-sing_up"><?php echo __('ZAPISZ SIĘ', 'akademiata') ?></a>
                                    <?php elseif ($key === 1): ?>
                                        <?php _e('Wypełnij formularz', 'akademiata'); ?>
                                    <?php else: ?>
                                        <?php echo $text; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        $recruitment_rules_details = !empty($acf_fields['last_step']) ? $acf_fields['last_step'] : null;

        if ($recruitment_rules_details === 'portfolio') : ?>

            <?php
            $portfolio = $acf_fields['portfolio'] ?? null;

            if (!empty($portfolio)) :

                $title_section = $portfolio['title_section'] ?? '';
                $title = $portfolio['title'] ?? '';
                $portfolio_works = $portfolio['portfolio_works'] ?? [];
                $portfolio_works_description = $portfolio['portfolio_works_description'] ?? '';
                $description = $portfolio['description'] ?? '';
                $video_title = $portfolio['video_title'] ?? '';
                $portfolio_video_placeholder = $portfolio['portfolio_video_placeholder'] ?? '';
                $video_id = !empty($portfolio['youtube_video_id']) ? esc_attr($portfolio['youtube_video_id']) : '';
                ?>

                <div class="portfolio py-5">
                    <div class="container">
                        <div class="row">
                            <div class="col-xl-2">
                                <?php if ($title_section): ?>
                                    <h2 class="title_section mb-5">
                                        <?= esc_html($title_section); ?>
                                    </h2>
                                <?php endif; ?>
                            </div>

                            <div class="col-xl-9">
                                <?php if ($title): ?>
                                    <h3 class="title px-5 mb-5">
                                        <?= esc_html($title); ?>
                                    </h3>
                                <?php endif; ?>

                                <?php if (!empty($portfolio_works)): ?>
                                    <div class="portfolio_works">
                                        <?php foreach ($portfolio_works as $item): ?>
                                            <div class="item">

                                                <?php if (!empty($item['details'])): ?>
                                                    <div class="details mb-3">
                                                        <?= wp_kses_post($item['details']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($item['images'])): ?>
                                                    <div class="images">
                                                        <?php foreach ($item['images'] as $image): ?>
                                                            <?php
                                                            $img = $image['image'] ?? null;
                                                            if (!empty($img['url'])):
                                                                $alt = $img['alt'] ?? '';
                                                                ?>
                                                                <img src="<?= esc_url($img['url']); ?>"
                                                                     alt="<?= esc_attr($alt); ?>">
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>

                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($portfolio_works_description): ?>
                                        <div class="additional mb-5">
                                            <?= wp_kses_post($portfolio_works_description); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($description): ?>
                            <div class="description">
                                <?= wp_kses_post($description); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($video_id): ?>
                            <h2 class="title my-5">
<!--                                --><?php //= esc_html($video_title); ?>
                                <?php _e('Więcej o portfolio dowiesz się z filmu:', 'akademiata'); ?>
                            </h2>

                            <div class="portfolio_video">
                                <div class="youtube_video"
                                     data-hover="false"
                                     data-controls="true"
                                     data-mute="true"
                                     data-youtube-start-play-video="4"
                                     data-youtube-placeholder="<?= esc_url($portfolio_video_placeholder); ?>"
                                     data-youtube-id="<?= $video_id; ?>">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>


        <?php elseif ($recruitment_rules_details === 'exam'): ?>
            <?php
            $exam = $acf_fields['exam'] ?? null;

            if (!empty($exam)) :
                $title = $exam['title'] ?? '';
                $sub_title = $exam['sub_title'] ?? '';
                $content = $exam['content'] ?? '';
                $details = $exam['details'] ?? '';
                $kurs = $exam['kurs'] ?? null;
                ?>

                <?php if ($title || $sub_title || $content || $details): ?>
                <div class="exam py-5">
                    <div class="container">
                        <?php if ($title): ?>
                            <h2 class="title_section mb-5">
                                <?= esc_html($title); ?>
                            </h2>
                        <?php endif; ?>

                        <?php if ($sub_title): ?>
                            <h3 class="title mb-5">
                                <?= esc_html($sub_title); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if ($content): ?>
                            <div class="content mb-5">
                                <?= wp_kses_post($content); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($details): ?>
                            <div class="details mb-5">
                                <?= wp_kses_post($details); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

                <?php
                if (!empty($kurs)) :
                    $title = $kurs['title'] ?? '';
                    $content = $kurs['content'] ?? '';
                    $image = $kurs['image'] ?? null;
                    $button = $kurs['button'] ?? null;
                    $video_id = !empty($kurs['youtube_video_id']) ? esc_attr($kurs['youtube_video_id']) : '';
                    ?>
                    <div class="kurs mb-5">
                        <div class="container">
                            <?php if ($title): ?>
                                <h2 class="title_section mb-5">
                                    <?= esc_html($title); ?>
                                </h2>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($content): ?>
                                        <div class="content mb-5">
                                            <?= wp_kses_post($content); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($button)): ?>
                                        <div class="details mb-5">
                                            <?php render_acf_button($button); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <div class="image_wrapper">
                                        <?php if ($video_id): ?>
                                            <div class="youtube_video" data-hover="false"
                                                 data-controls="true"
                                                 data-mute="true"
                                                 data-youtube-start-play-video="4"
                                                 data-youtube-placeholder=""
                                                 data-youtube-id="<?= $video_id; ?>"></div>
                                        <?php elseif (!empty($image) && !empty($image['sizes']['study_slider_image'])): ?>
                                            <img src="<?php echo esc_url($image['sizes']['study_slider_image']); ?>"
                                                 alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                                                 width="100%"
                                                 height="100%">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>


            <?php endif; ?>


        <?php endif; ?>


    </section>


<?php endif; ?>
