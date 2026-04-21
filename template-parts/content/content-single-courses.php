<?php
/* Template for single kursy (Courses) */

$acf_fields = get_fields();
set_query_var('acf_fields', $acf_fields);
$is_mobile = wp_is_mobile();

$register_url = !empty($acf_fields['register_url']) ? $acf_fields['register_url'] : '';

// Taxonomy to show on top
$top_taxonomies_with_labels = [
    'city_pg_mba' => __('MIASTO', 'akademiata'),
];

 

// Render taxonomy details with conditional column layout

if (!function_exists('render_course_taxonomy_details')) {
    function render_course_taxonomy_details($taxonomies_with_labels)
    {
        echo '<div class="offer_details_wrapper">';

        foreach ($taxonomies_with_labels as $taxonomy => $label) {
            $terms = get_the_terms(get_the_ID(), $taxonomy);

            if (!empty($terms) && !is_wp_error($terms)) {
                echo '<div class="taxonomy_info">';
                echo '<div class="row">';
                echo '<div class="col-5 col-md-4 item">' . esc_html($label) . ':</div>';
                echo '<div class="col-7 col-md-8 item">';

                // Escape each term name and join with <br>
                $term_names = array_map('esc_html', wp_list_pluck($terms, 'name'));
                // If taxonomy is 'language', join with comma and space
                if ($taxonomy === 'language') {
                    echo implode(', ', $term_names);
                } else {
                    // Default: join with <br>
                    echo implode('<br>', $term_names);
                }

                echo '</div></div></div>';
            }
        }

        echo '</div>';
    }
}
?>

<section class="section_header left_space mb-5">
    <div class="container">

        <?php if ($is_mobile) : ?>
            <div class="offer_header my-3 mobile_visible">
                <?php the_breadcrumb(); ?>
                <div class="top_details">
                    <div class="row">
                        <?php render_taxonomy_info($top_taxonomies_with_labels); ?>
                    </div>
                </div>
                <div class="main_title">
                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="offer_wrapper d-flex flex-column-reverse flex-lg-row">
            <div class="col-lg-6">
                <div class="offer_body">

                    <?php if (!$is_mobile) : ?>
                        <div class="offer_header my-3 desktop_visible">
                            <?php the_breadcrumb(); ?>
                            <div class="top_details">
                                <div class="row">
                                    <?php render_taxonomy_info($top_taxonomies_with_labels); ?>
                                </div>
                            </div>
                            <div class="main_title">
                                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="offer_details_wrapper">
                        <?php
                        $taxonomies_with_labels = [
                            'mode_course' => __('Forma zajęć', 'akademiata'),
                            'duration_course' => __('CZAS TRWANIA', 'akademiata'),
                            'language' => __('JĘZYK', 'akademiata'),
                            'instructor_course' => __('PROWADZĄCY', 'akademiata'),
                            'price_course' => __('KOSZT', 'akademiata'),
                            'fee_course' => __('OPŁATA ADMINISTRACYJNA', 'akademiata'),
                        ];

                        render_course_taxonomy_details($taxonomies_with_labels);
                        ?>
                    </div>

                    <?php if (!empty($register_url)) : ?>
                        <a style="display: none" id="sourceLink" href="<?= esc_url($register_url); ?>" target="_blank"
                           class="button-sing_up offer_button"><?php _e('ZAPISZ SIĘ', 'akademiata'); ?></a>
                    <?php endif; ?>

                    <?php if ($is_mobile && !empty($register_url)) : ?>
                        <div class="mobile_visible">
                            <div class="d-flex justify-content-center my-5">
                                <a id="offerButton" href="<?= esc_url($register_url); ?>" target="_blank"
                                   class="button-sing_up"><?php _e('ZAPISZ SIĘ', 'akademiata'); ?></a>
                            </div>

                            <div class="fixed-bottom">
                                <div class="menu_scrol_x">
                                    <?php get_template_part('partials/nav_single_offer', 'part'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    $offer_partners = $acf_fields['offer_partners'] ?? [];
                    set_query_var('offer_partners', $offer_partners);
                    locate_template('template-parts/single-offer/offer_partners.php', true, true);
                    ?>
                </div>
            </div>

            <div class="col-lg-6">
                <?php if (has_post_thumbnail()) :
                    $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                    $desktop_size = 'program_banner';
                    $mobile_size = 'specialization_card_thumb';
                    $image_url_mobile = wp_get_attachment_image_src($thumbnail_id, $mobile_size)[0] ?? '';
                    $image_url_desktop = wp_get_attachment_image_src($thumbnail_id, $desktop_size)[0] ?? '';
                    ?>
                    <div class="image_bg responsive-image" role="img"
                         data-mobile="<?= esc_url($image_url_mobile); ?>"
                         data-desktop="<?= esc_url($image_url_desktop); ?>"
                         style="background-image: url('<?= esc_url($image_url_desktop); ?>');">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="single_course_body">

    <div class="container">
        <?php
        $accordion_universal = get_field('course_accordion');
        $title = get_field('course_main_title');
        $sub_title = get_field('course_sub_title');
        $description = get_field('course_description');

        if (!empty($accordion_universal)) {
            // Inject default template path for each accordion item
            foreach ($accordion_universal as &$item) {
                if (is_array($item)) {
                    $item['accordion_content_template'] = 'accordion_default_content.php';
                }
            }

            // Pass data to template
            set_query_var('accordion', $accordion_universal);
            set_query_var('title', $title);
            set_query_var('sub_title', $sub_title);
            set_query_var('description', $description);

            locate_template('template-parts/accordion_universal.php', true, true);
        }
        ?>
    </div>
</section>
