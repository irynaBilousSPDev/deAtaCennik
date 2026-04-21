<?php
$acf_fields = get_query_var('acf_fields', []);
$study_program = $acf_fields['study_program'] ?? [];
$program_info = $acf_fields['program_info'] ?? [];
$subjects_study = $acf_fields['subjects_study'] ?? [];

?>

<section id="program" class="section_study_program">
    <?php if (!empty($study_program)) :
        $title = $study_program['title'];
        $title = $title ? $title : __('Program i struktura studiów', 'akademiata');
        $sub_title = $study_program['sub_title'];
        $sub_title = $sub_title ? $sub_title : __('praktyczny profil kształcenia', 'akademiata');
        $program_percentages = $study_program['program_percentages'] ?? '';
        $button = !empty($study_program['button']) ? $study_program['button'] : null;
        ?>
        <div class="container">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-md-3">
                <h2 class="title_section  mb-md-3">
                    <?php echo esc_html($title); ?>
                </h2>
                <?php if (!empty($button['button']['button_link'])) : ?>
                    <span class="download_program d-none d-md-block">
                    <a href="<?php echo $button['button']['button_link']; ?>" class="button-orange" target="_blank"
                       rel="\&quot;noopener" noreferrer\"="">
                   <?php _e('POBIERZ PROGRAM STUDIÓW', 'akademiata') ?>
                    </a>
                </span>
                <?php endif; ?>
            </div>
            <h3 class="small_title primary_color pb-md-5 mb-5">
                <?php echo esc_html($sub_title); ?>
            </h3>

            <?php if (!empty($program_percentages)) : ?>
            <div class="program_percentages py-md-5 my-5">
                <div class="row">
                    <?php
                    // Define Default Titles
                    $titles = [
                        __('Umiejętności praktyczne', 'akademiata'),
                        __('Wiedza teoretyczna', 'akademiata'),
                        __('Praktyki zawodowe', 'akademiata'),
                        __('Nudne zajęcia', 'akademiata'),
                    ];
                    foreach ($program_percentages as $index => $item) :
                        // Use provided title or fallback from $titles
                        $title = !empty(trim($item['title'])) ? $item['title'] : ($titles[$index] ?? '');
//                        $title = $titles[$index];

                        // Ensure percentage exists
                        $percent = $item['percent'] ?? '';
                        ?>
                        <?php if (!empty($percent)) : ?>
                        <div class="col-6 col-xl-3 mb-5">
                            <div class="item text-center">
                                <h3 class="sub_title"><?php echo esc_html($title); ?></h3>
                                <div class="number"><?php echo esc_html($percent); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php endforeach;; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($button['button']['button_link'])) : ?>
                    <span class="download_program d-block d-md-none d-flex justify-content-center">
                    <a href="<?php echo $button['button']['button_link']; ?>" class="button-orange" target="_blank"
                       rel="\&quot;noopener" noreferrer\"="">
                   <?php _e('POBIERZ PROGRAM STUDIÓW', 'akademiata') ?>
                    </a>
                </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($program_info)) :

        $image = $program_info['image'] ?? [];

        $image_url_fallback = !empty($image['url']) ? esc_url($image['url']) : '';

        $image_url_mobile = !empty($image['sizes']['program_image_mobile']) ? esc_url($image['sizes']['program_image_mobile']) : $image_url_fallback;
        $image_url_desktop = !empty($image['sizes']['program_image']) ? esc_url($image['sizes']['program_image']) : $image_url_fallback;

        $alt_text = !empty($image['alt']) ? esc_attr($image['alt']) : __('Program Image', 'akademiata');

        ?>
        <div class="program_info">
            <div class="image_bg responsive-image" role="img"
                 aria-label="<?php echo esc_attr($alt_text); ?>"
                 data-mobile="<?php echo esc_url($image_url_mobile); ?>"
                 data-desktop="<?php echo esc_url($image_url_desktop); ?>"
                 style="background-image: url('<?php echo esc_url($image_url_desktop); ?>');">

                <div class="container">
                    <?php
                    $post_id = get_the_ID();

                    // Get ECTS value (fallback to empty string if not set)
                    $ects = isset($program_info['ects']) ? esc_html($program_info['ects']) : '';

                    // Define multiple taxonomies to retrieve
                    $taxonomies = [
                        'program' => __('Kierunek studiów', 'akademiata'),
                        'duration' => __('Czas trwania', 'akademiata'),
                        'obtained_title' => __('Uzyskany tytuł zawodowy', 'akademiata'),
                    ];

                    $counter = 0; // Counter to track iteration order
                    ?>

                    <div class="program_info_details">
                        <?php foreach ($taxonomies as $taxonomy => $label) :
                            $terms = get_the_terms($post_id, $taxonomy);
                            if (!empty($terms) && !is_wp_error($terms)) :
                                // Show first taxonomy
                                ?>
                                <div class="item text-center">
                                    <h3 class="small_title"><?php echo esc_html($label); ?></h3>
                                    <h4 class="small_title">
                                        <?php echo esc_html(implode(', ', wp_list_pluck($terms, 'name'))); ?>
                                    </h4>
                                </div>
                                <?php
                                // Show ECTS section as second element
                                if ($counter === 0 && !empty($ects)) : ?>
                                    <div class="item text-center">
                                        <h3 class="small_title"><?php esc_html_e('Punkty ECTS', 'akademiata'); ?></h3>
                                        <h4 class="small_title">
                                            <?php echo esc_html($ects) . ' ' . esc_html__('', 'akademiata'); ?>
                                        </h4>
                                    </div>
                                <?php endif;
                                $counter++;
                            endif;
                        endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($subjects_study)) :
        $title = $subjects_study['title'] ?? '';
        $accordion = $subjects_study['subjects_study_accordion'] ?? []; // Fixed Variable Reference
        ?>

        <div class="section_subjects_study py-5 my-5">
            <div class="container">

                <?php if (!empty($title)) : ?>
                    <h2 class="sub_title mb-5">
                        <?php echo esc_html($title); ?>
                    </h2>
                <?php endif; ?>

                <?php if (!empty($accordion)) : ?>
                    <?php
                    set_query_var('subjects_study_accordion', $accordion);
                    get_template_part('template-parts/accordion'); //  Removed Unnecessary `./`
                    ?>
                <?php endif; ?>

            </div>
        </div>

    <?php endif; ?>


</section>
