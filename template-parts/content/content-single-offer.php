<?php $acf_fields = get_fields();
//  Pass ACF fields to templates
set_query_var('acf_fields', $acf_fields);
$is_mobile = wp_is_mobile();

$register_url = !empty($acf_fields['register_url']) ? $acf_fields['register_url'] : '';
// Define taxonomies with their labels
$top_taxonomies_with_labels = [
    'city' => __('MIASTO', 'akademiata'),
    'program' => __('KIERUNEK STUDIÓW', 'akademiata'),
];


if (is_singular(array('bachelor', 'master'))) {
    akademiata_get_offer_terms(get_the_ID());
}

$recruitment_closed = function_exists('akademiata_recruitment_is_closed') && akademiata_recruitment_is_closed(get_the_ID());
$show_register_button = !empty($register_url) && !$recruitment_closed;
?>
    <section class="section_header left_space">
        <div class="container">

            <?php if ($is_mobile) : ?>
                <div class="offer_header my-3 mobile_visible">
                    <!-- Breadcrumbs -->
                    <?php the_breadcrumb(); ?>
                    <div class="top_details">
                        <div class="row">
                            <?php
                            // Call the function to display taxonomies
                            render_taxonomy_info($top_taxonomies_with_labels);
                            ?>
                        </div>
                    </div>
                    <div class="main_title">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </div>
                    <?php get_template_part('template-parts/single-offer/daily-interest-notice'); ?>
                </div>
            <?php endif; ?>
            <div class="offer_wrapper d-flex flex-column-reverse flex-lg-row">
                <!-- Content Column -->
                <div class="col-lg-6">
                    <div class="offer_body">

                        <?php if (!$is_mobile) : ?>
                            <div class="offer_header my-3 desktop_visible">
                                <?php get_template_part('template-parts/single-offer/start-studies-timer'); ?>
                                <!-- Breadcrumbs -->
                                <?php the_breadcrumb(); ?>
                                <div class="top_details">
                                    <div class="row">
                                        <?php
                                        // Call the function to display taxonomies
                                        render_taxonomy_info($top_taxonomies_with_labels);
                                        ?>
                                    </div>
                                </div>
                                <div class="main_title">
                                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="offer_details_wrapper">
                            <?php
                            // Define taxonomies with their labels
                            $taxonomies_with_labels = [
                                'degree' => __('Rodzaj studiów', 'akademiata'),
                                'obtained_title' => __('Uzyskany tytuł', 'akademiata'),
                                'duration' => __('Czas trwania', 'akademiata'),
                                'language' => __('Język studiów', 'akademiata'),
                                'mode' => __('Forma studiów', 'akademiata'),
                            ];

                            // Call the function to display taxonomies
                            render_taxonomy_details($taxonomies_with_labels, __('oferta', 'akademiata'));
                            ?>

                            <?php
                            $logical_sync_key = trim((string) get_post_meta(get_the_ID(), 'logical_sync_key', true));
                            if ($logical_sync_key !== '') :
                                ?>
                                <div id="priseScroll" class="taxonomy_info price_from_single my-5">
                                    <div class="price-from-single__label"><?php _e('CENA', 'akademiata'); ?>:</div>
                                    <div class="price-from-single__body">
                                        <strong></strong>
                                        <a href="#tuition_fees" class="primary_color">
                                            <?php _e('SPRAWDŹ CENNIK', 'akademiata'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php if (!$recruitment_closed && $register_url !== '') : ?>
                        <a style="display: none" id="sourceLink" href="<?php echo esc_url($register_url); ?>" target="_blank"
                           class="button-sing_up offer_button"><?php _e('ZAPISZ SIĘ', 'akademiata'); ?></a>
                        <?php endif; ?>
                        <!--                        mobile nav-->
                        <?php if ($is_mobile) : ?>
                            <div class="mobile_visible">

                                <div class="d-flex justify-content-center my-5">

                                    <?php if ($recruitment_closed || $show_register_button) : ?>
                                        <?php
                                        akademiata_the_recruitment_cta(array(
                                            'url'    => $register_url,
                                            'id'     => 'offerButton',
                                            'class'  => 'button-sing_up',
                                            'target' => '_blank',
                                        ));
                                        ?>
                                    <?php else : ?>
                                        <div class="single_btn_ended">
                                            <?php get_template_part('partials/button_ended'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="fixed-bottom">
                                    <div class="menu_scrol_x">
                                        <?php get_template_part('partials/nav_single_offer', 'part'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php
                        $offer_partners = !empty($acf_fields['offer_partners']) ? $acf_fields['offer_partners'] : [];
                        set_query_var('offer_partners', $offer_partners);
                        locate_template('template-parts/single-offer/offer_partners.php', true, true);
                        ?>
                    </div>
                </div>
                <div class="col-lg-6 offer_header__media">
                    <?php if (!$is_mobile) : ?>
                        <?php get_template_part('template-parts/single-offer/daily-interest-notice'); ?>
                    <?php endif; ?>
                    <?php if ($is_mobile) : ?>
                        <?php get_template_part('template-parts/single-offer/start-studies-timer'); ?>
                    <?php endif; ?>
                    <?php if (has_post_thumbnail()) : ?>
                        <?php
                        $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                        $desktop_size = 'program_banner';
                        $mobile_size = 'specialization_card_thumb';
                        $image_url_mobile = wp_get_attachment_image_src($thumbnail_id, $mobile_size)[0] ?? '';
                        $image_url_desktop = wp_get_attachment_image_src($thumbnail_id, $desktop_size)[0] ?? '';
                        ?>

                        <div class="image_bg responsive-image" role="img"
                             data-mobile="<?php echo esc_url($image_url_mobile); ?>"
                             data-desktop="<?php echo esc_url($image_url_desktop); ?>"
                             style="background-image: url('<?php echo esc_url($image_url_desktop); ?>');">
                        </div>

                    <?php endif; ?>

                </div>
            </div>
    </section>

<?php
$sections = [
    'section_why_study',
    'section_student_testimonials',
    'section_after_studies',
    'section_after_graduation',
    'section_for_you_if',
    'section_study_program',
    'section_tuition_fees',
    'section_recruitment_rules'
];

foreach ($sections as $section) {
    get_template_part("template-parts/single-offer/{$section}");
}
?>