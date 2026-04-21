<?php $acf_fields = get_fields();
//  Pass ACF fields to templates
set_query_var('acf_fields', $acf_fields);
$is_mobile = wp_is_mobile();

// Inside single template (in The Loop)
$post_id = get_the_ID();
$post_type = get_post_type($post_id);
// WPML-safe slugs
$wroclaw_slug = get_translated_city_slug('wroclaw', 'city_pg_mba');
$warszawa_slug = get_translated_city_slug('warszawa', 'city_pg_mba');
$online_slug = get_translated_city_slug('online', 'city_pg_mba');

$register_url = !empty($acf_fields['register_url']) ? $acf_fields['register_url'] : '';
// Define taxonomies with their labels
$top_taxonomies_with_labels = [
        'city_pg_mba' => __('Lokalizacja', 'akademiata'),
];

$payments = $acf_fields['payments'] ?? [];
$more_info = $acf_fields['more_info'] ?? '';
$full_time_price = get_field('full_time') ?: '';
$part_time_price = get_field('part_time') ?: '';

// Helper functions
function has_price_data($price_data)
{
    $columns = ['col_12_rat', 'col_semester', 'col_year', 'col_8_rat'];
    foreach ($price_data as $year_data) {
        foreach ($columns as $col) {
            if (!empty($year_data[$col])) return true;
        }
    }
    return false;
}

function get_available_columns($price_data, $columns)
{
    $available = [];
    foreach ($columns as $col_index => $col) {
        foreach ($price_data as $year_data) {
            $col_data = $year_data[$col['key']] ?? [];
            if (!empty($col_data[$col['normal']])) {
                $available[$col_index] = true;
                break;
            }
        }
    }
    return $available;
}

function render_price_row($price_data, $columns, $available_columns, $tab_key)
{
    // Currency rule: Polish-language studies => ZŁ, English-language studies => €
    $currency = ($tab_key === 'part_time') ? '€' : 'ZŁ';

    foreach ($price_data as $row) : ?>
        <tr>
            <?php foreach ($columns as $col_index => $col) :
                if (empty($available_columns[$col_index])) continue;
                $col_data = $row[$col['key']] ?? [];
                $has_promo = !empty($col_data[$col['flag']]) && in_array('promotion', $col_data[$col['flag']]);
                ?>
                <td>
                    <div class="<?php echo $has_promo ? 'old_price' : ''; ?>">
                        <?php echo esc_html($col_data[$col['normal']] ?? '') . ' ' . $currency; ?>
                    </div>
                    <?php if ($has_promo && !empty($col_data[$col['promo']])) : ?>
                        <div class="new_price">
                            <?php echo esc_html($col_data[$col['promo']]) . ' ' . $currency; ?>
                        </div>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach;
}


?>
<section class="section_header left_space">
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
                                'mode_course' => __('FORMA ZAJĘĆ:', 'akademiata'),
                                'type_of_study_pg_mba' => __('Rodzaj studiów', 'akademiata'),
                                'duration_pg_mba' => __('Czas trwania', 'akademiata'),
                                'language_pg_mba' => __('Język', 'akademiata'),
                                'diploma_pg_mba' => __('Dokument', 'akademiata'),
                                'form_pg_mba' => __('Forma studiów', 'akademiata'),
                        ];

                        render_taxonomy_details($taxonomies_with_labels, __('oferta', 'akademiata')); ?>

                        <?php
                        $currency = apply_filters('wpml_current_language', null) === 'en' ? '€/month' : 'zł';
                        $price_text = '';

                        // Decide which dataset is used to show the "from" price
                        $source_data = [];
                        $using_part_time = false;

                        if (!empty($full_time_price) && is_array($full_time_price)) {
                            $source_data = $full_time_price;
                            $using_part_time = false;
                        } elseif (!empty($part_time_price) && is_array($part_time_price)) {
                            $source_data = $part_time_price;
                            $using_part_time = true;
                        }

                        // Currency rule consistent with tabs: PL studies => ZŁ (or 'zł' if you prefer lowercase), EN studies => €/month
                        $currency = $using_part_time ? '€/month' : 'zł';

                        $price_text = '';

                        if (!empty($source_data)) {
                            $row = $source_data[0] ?? [];
                            $col_data = $row['col_8_rat'] ?? [];

                            if (is_array($col_data)) {
                                $add_promotion = $col_data['add_promotion_rat8'] ?? [];
                                $has_promo = (is_array($add_promotion) && in_array('promotion', $add_promotion))
                                        || $add_promotion === 'promotion';

                                if ($has_promo && !empty($col_data['rat8_promotion_price'])) {
                                    $price_text = esc_html($col_data['rat8_promotion_price']) . ' ' . $currency;
                                } elseif (!empty($col_data['rat8_normal_price'])) {
                                    $price_text = esc_html($col_data['rat8_normal_price']) . ' ' . $currency;
                                }
                            }
                        }
                        ?>
                        <?php if (!empty($price_text)) : ?>
                            <div id="priseScroll" class="taxonomy_info price_from_single my-5">
                                <?php _e('CENA', 'akademiata'); ?>:
                                <strong>
                                    <?php _e('już od', 'akademiata'); ?>
                                    <?php echo $price_text; ?>
                                </strong>
                                <a href="#tuition_fees" class="primary_color">
                                    <?php _e('SPRAWDZ CENNIK', 'akademiata'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

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

<!-- Section: Dlaczego warto studiować -->
<section id="why_study" class="section_why_study_pg_mba">
    <div class="container">
        <?php
        $accordion = get_field('why_study_accordion');
        $title = get_field('why_study_title') ?: __('Dlaczego warto studiować', 'akademiata');
        $sub_title = get_field('why_study_sub_title');
        $description = get_field('why_study_description');

        if (!empty($accordion)) {
            foreach ($accordion as &$item) {
                if (is_array($item)) {
                    $item['accordion_content_template'] = 'accordion_default_content.php';
                }
            }

            set_query_var('accordion', $accordion);
            set_query_var('title', $title);
            set_query_var('sub_title', $sub_title);
            set_query_var('description', $description);

            //  Renders only this section's data
            locate_template('template-parts/accordion_universal.php', true, false);
        }
        ?>
    </div>
</section>

<!-- Section: Program i struktura studiów -->
<section id="program" class="section_study_program_pg_mba section_study_program">
    <div class="container">
        <?php
        $accordion = get_field('study_program_structure_accordion');
        $study_program_structure_title = __('Program i struktura studiów', 'akademiata');
        $sub_title = get_field('study_program_structure_sub_title');
        $description = get_field('study_program_structure_description');
        $study_program_structure_button = get_field('study_program_structure_button');
        ?>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-md-3">
            <?php if (!empty($study_program_structure_title)) : ?>
                <h2 class="title_section"><?php echo $study_program_structure_title; ?></h2>
            <?php endif; ?>
            <?php if (!empty($study_program_structure_button)) : ?>
                <span class="download_program d-none d-md-block">
                    <a href="<?php echo esc_url($study_program_structure_button); ?>"
                       class="button-orange" target="_blank" rel="noopener noreferrer">
                        <?php _e('POBIERZ PROGRAM STUDIÓW', 'akademiata'); ?>
                    </a>
                </span>
            <?php endif; ?>
        </div>

        <?php
        //        if (!empty($accordion)) {
        foreach ($accordion as &$item) {
            if (is_array($item)) {
                $item['accordion_content_template'] = 'accordion_default_content.php';
            }
        }

        //  Overwrite variables for 2nd section
        set_query_var('accordion', $accordion);
        set_query_var('title', '');
        set_query_var('sub_title', !empty($sub_title) ? $sub_title : '');
        set_query_var('description', !empty($description) ? $description : '');

        //   Render only second section's data
        locate_template('template-parts/accordion_universal.php', true, false);
        //        }
        ?>
    </div>
</section>


<?php
$show_cadre_section = (bool)get_field('show_cadre_section');
$cadre_section_title = (string)get_field('cadre_section_title');
$cadre_source = (string)get_field('cadre_source');
$cadre_groups = get_field('cadre_groups');
$manual_cadre_people = get_field('manual_cadre_people');

$cadre_section_title = !empty($cadre_section_title) ? $cadre_section_title : __('Kadra', 'akademiata');
$cadre_source = in_array($cadre_source, ['taxonomy', 'manual', 'both'], true) ? $cadre_source : 'taxonomy';

$cadre_groups = is_array($cadre_groups) ? array_map('intval', $cadre_groups) : [];
$manual_cadre_people = is_array($manual_cadre_people) ? array_map('intval', $manual_cadre_people) : [];

$people_ids = [];

if ($show_cadre_section) {
    if (in_array($cadre_source, ['taxonomy', 'both'], true) && !empty($cadre_groups)) {
        $cadre_query = new WP_Query([
                'post_type' => 'cadre',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => [
                        'menu_order' => 'ASC',
                        'title' => 'ASC',
                ],
                'order' => 'ASC',
                'fields' => 'ids',
                'suppress_filters' => false,
                'ignore_sticky_posts' => true,
                'tax_query' => [
                        [
                                'taxonomy' => 'cadre_group',
                                'field' => 'term_id',
                                'terms' => $cadre_groups,
                                'operator' => 'IN',
                        ],
                ],
        ]);

        if (!empty($cadre_query->posts)) {
            $people_ids = $cadre_query->posts;
        }

        wp_reset_postdata();
    }

    if (in_array($cadre_source, ['manual', 'both'], true) && !empty($manual_cadre_people)) {
        $people_ids = array_merge($people_ids, $manual_cadre_people);
    }

    $people_ids = array_values(array_unique(array_filter(array_map('intval', $people_ids))));
}
?>

<?php if ($show_cadre_section && !empty($people_ids)) : ?>
    <section class="pg-mba-cadre" data-cadre-section>
        <div class="container">
            <h2 class="pg-mba-cadre__title"><?php echo esc_html($cadre_section_title); ?></h2>

            <div class="pg-mba-cadre__grid">
                <?php foreach ($people_ids as $person_id) : ?>
                    <?php
                    $person_id = $person_id;

                    $name = get_the_title($person_id);
                    $role = get_field('cadre_role', $person_id);

                    $bio_acf = get_field('cadre_bio', $person_id);
                    $post_obj = get_post($person_id);
                    $bio_editor = $post_obj ? $post_obj->post_content : '';
                    $bio = !empty(trim(wp_strip_all_tags($bio_acf))) ? $bio_acf : $bio_editor;
                    $has_bio = !empty(trim(wp_strip_all_tags($bio)));

                    $thumb_id = get_post_thumbnail_id($person_id);

                    $modal_photo = get_field('cadre_modal_photo', $person_id);
                    $modal_photo_id = 0;

                    if (is_array($modal_photo) && !empty($modal_photo['ID'])) {
                        $modal_photo_id = (int)$modal_photo['ID'];
                    } elseif (!empty($modal_photo)) {
                        $modal_photo_id = (int)$modal_photo;
                    }

                    $socials = get_field('cadre_socials', $person_id);
                    $modal_id = 'cadre-modal-' . get_the_ID() . '-' . $person_id;
                    ?>
                    <div class="pg-mba-cadre__card" data-cadre-open data-target="#<?php echo esc_attr($modal_id); ?>">
                        <?php if ($thumb_id) : ?>
                            <div class="pg-mba-cadre__media">
                                <?php
                                echo wp_get_attachment_image(
                                        $thumb_id,
                                        'medium_large',
                                        false,
                                        [
                                                'class' => 'pg-mba-cadre__image',
                                                'loading' => 'lazy',
                                                'alt' => $name,
                                        ]
                                );
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="pg-mba-cadre__content">
                            <?php if (!empty($name)) : ?>
                                <div class="pg-mba-cadre__name"><?php echo esc_html($name); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($role)) : ?>
                                <div class="pg-mba-cadre__role"><?php echo esc_html($role); ?></div>
                            <?php endif; ?>

                            <?php if ($has_bio) : ?>
                                <span class="pg-mba-cadre__arrow pg-mba-cadre__button">
                                    <img
                                            src="<?php echo get_template_directory_uri(); ?>/static/img/arrow_cadre_pg_mba.svg"
                                            alt=""
                                            class="pg-mba-cadre__icon"
                                            loading="lazy"
                                    >
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($has_bio) : ?>
                            <div class="cadre-modal" id="<?php echo esc_attr($modal_id); ?>" aria-hidden="true"
                                 data-cadre-modal>
                                <div class="cadre-modal__overlay" data-cadre-close></div>

                                <div class="cadre-modal__dialog" role="dialog" aria-modal="true"
                                     aria-label="<?php echo esc_attr($name); ?>">
                                    <button class="cadre-modal__close" type="button" data-cadre-close
                                            aria-label="<?php esc_attr_e('Close', 'akademiata'); ?>">×
                                    </button>

                                    <div class="cadre-modal__content">
                                        <div class="cadre-modal__inner">
                                            <div class="cadre-modal__photo-wrap">
                                                <?php if ($modal_photo_id) : ?>
                                                    <?php
                                                    echo wp_get_attachment_image(
                                                            $modal_photo_id,
                                                            'full',
                                                            false,
                                                            [
                                                                    'class' => 'cadre-modal__photo',
                                                                    'loading' => 'lazy',
                                                                    'alt' => $name,
                                                            ]
                                                    );
                                                    ?>
                                                <?php endif; ?>

                                                <?php if (!empty($socials) && is_array($socials)) : ?>
                                                    <div class="cadre-modal__icons">
                                                        <?php foreach ($socials as $row) : ?>
                                                            <?php
                                                            $type = $row['type'] ?? '';
                                                            $url = $row['url'] ?? '';

                                                            if (!$url) {
                                                                continue;
                                                            }
                                                            ?>
                                                            <a
                                                                    class="cadre-icon cadre-icon--<?php echo esc_attr($type); ?>"
                                                                    href="<?php echo esc_url($url); ?>"
                                                                    target="_blank"
                                                                    rel="noopener"
                                                                    aria-label="<?php echo esc_attr($type); ?>"
                                                            >
                                                                <?php
                                                                switch ($type) {
                                                                    case 'linkedin':
                                                                        echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.11 1 2.5 1 4.98 2.12 4.98 3.5zM0 8h5v16H0V8zm7.5 0h4.7v2.2h.07c.65-1.2 2.25-2.47 4.63-2.47 4.95 0 5.87 3.26 5.87 7.5V24h-5V15.6c0-2-.03-4.57-2.78-4.57-2.78 0-3.2 2.17-3.2 4.42V24h-5V8z"/></svg>';
                                                                        break;

                                                                    case 'email':
                                                                        echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M0 4v16h24V4H0zm12 9L2 6h20l-10 7z"/></svg>';
                                                                        break;

                                                                    case 'website':
                                                                        echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm7.93 9h-3.18a15.7 15.7 0 00-1.38-5.01A8.03 8.03 0 0119.93 11zM12 4c1.38 1.77 2.36 4.17 2.64 7H9.36C9.64 8.17 10.62 5.77 12 4zM4.07 13h3.18c.25 1.81.8 3.53 1.63 5.01A8.03 8.03 0 014.07 13zm3.18-2H4.07a8.03 8.03 0 014.81-5.01A15.7 15.7 0 007.25 11zm4.75 9c-1.38-1.77-2.36-4.17-2.64-7h5.28c-.28 2.83-1.26 5.23-2.64 7zm3.12-1.99A15.7 15.7 0 0016.75 13h3.18a8.03 8.03 0 01-4.81 5.01z"/></svg>';
                                                                        break;

                                                                    default:
                                                                        echo esc_html(mb_substr((string)$type, 0, 1));
                                                                }
                                                                ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="cadre-modal__text">
                                                <?php if (!empty($role)) : ?>
                                                    <div class="cadre-modal__role"><?php echo esc_html($role); ?></div>
                                                <?php endif; ?>

                                                <?php if (!empty($name)) : ?>
                                                    <div class="cadre-modal__name"><?php echo esc_html($name); ?></div>
                                                <?php endif; ?>

                                                <div class="cadre-modal__bio">
                                                    <?php echo wp_kses_post($bio); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                                                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php
$sub_title = __('Opłaty za studia', 'akademiata');
$section_title = __('W ATA to Ty decydujesz, jak chcesz zaplanować wydatki na studia!', 'akademiata');
$table_price_title = __('Elastyczne płatności dla Twojej wygody', 'akademiata');
?>
<section id="tuition_fees" class="section_tuition_fees">
    <div class="container">
        <h2 class="small_title primary_color mb-3"><?php echo esc_html($sub_title); ?></h2>
        <h3 class="title_section col-xl-10 p-0 mb-3"><?php echo esc_html($section_title); ?></h3>
    </div>

    <div class="container">
        <div class="small_container py-md-5 py-3">
            <?php if (!empty($payments)) : ?>
                <?php foreach ($payments as $key => $item) :
                    $title = $item['title'] ?? '';
                    $price = $item['price'] ?? '';
                    $currency = $item['currency'] ?? '';
                    $promotion = $item['promotion'] ?? [];
                    $description = $item['description'] ?? '';
                    ?>
                    <div class="row payments_item mb-5">
                        <div class="small_title d-flex align-items-center mr-5">
                            <?php
                            if ($title) {
                                echo esc_html($title);
                            } elseif (in_array($key, [0, 1])) {
                                $label = $key === 0 ? __('Opłata rekrutacyjna', 'akademiata') : __('Wpisowe', 'akademiata');
                                $suffix = __('(opłata jednorazowa)', 'akademiata');
                                echo wp_kses_post($label . '<br>' . $suffix);
                            }
                            ?>
                        </div>

                        <?php if ($price) : ?>
                            <div class="d-flex align-items-center mr-5">
                                <div class="normal_price <?php echo in_array('promotion', $promotion) ? 'promotion' : ''; ?>">
                                    <?php echo esc_html($price . ' ' . $currency); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('promotion', $promotion)) : ?>
                            <div class="d-flex align-items-center mr-5">
                                <div class="description">
                                    <?php echo $description ? wp_kses_post($description) :
                                            __('Zapisz się do końca miesiąca', 'akademiata') . ' ' .
                                            ($key === 0 ? __('- zapłacisz 0 zł opłaty rekrutacyjnej', 'akademiata') : __('- zapłacisz 0 zł wpisowego', 'akademiata')); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($more_info)) : ?>
            <div class="description py-3 mb-md-5 mb-3">
                <?php echo wp_kses_post($more_info); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="table_header small_container">
        <div class="container">
            <?php if (!empty($table_price_title)) : ?>
                <h4 class="small_title pb-3 mb-md-5 mb-3">
                    <?php echo esc_html($table_price_title); ?>
                </h4>
            <?php endif; ?>
        </div>
    </div>

    <div class="price_table">
        <div class="container">
            <div class="small_container">
                <?php
                $columns = [
                        ['key' => 'col_12_rat', 'normal' => 'normal_price', 'promo' => 'promotion_price', 'flag' => 'add_promotion'],
                        ['key' => 'col_semester', 'normal' => 'semester_normal_price', 'promo' => 'semester_promotion_price', 'flag' => 'add_promotion_semester'],
                        ['key' => 'col_year', 'normal' => 'year_normal_price', 'promo' => 'year_promotion_price', 'flag' => 'add_promotion_year'],
                        ['key' => 'col_8_rat', 'normal' => 'rat8_normal_price', 'promo' => 'rat8_promotion_price', 'flag' => 'add_promotion_rat8'],
                        ['key' => 'col_6_rat', 'normal' => 'rat6_normal_price', 'promo' => 'rat6_promotion_price', 'flag' => 'add_promotion_rat6'],
                        ['key' => 'col_9_rat', 'normal' => 'rat9_normal_price', 'promo' => 'rat9_promotion_price', 'flag' => 'add_promotion_rat9'],
                ];

                // Read custom tab labels from current post (fallback to defaults)
                $label_full_time = trim((string)get_field('tab_label_full_time'));
                $label_part_time = trim((string)get_field('tab_label_part_time'));

                $label_full_time = $label_full_time !== '' ? $label_full_time : __('STUDIA W JĘZYKU POLSKIM', 'akademiata');
                $label_part_time = $label_part_time !== '' ? $label_part_time : __('STUDIA W JĘZYKU ANGIELSKIM', 'akademiata');

                $tabs = [
                        'full_time' => [
                                'label' => esc_html($label_full_time),
                                'data' => $full_time_price,
                        ],
                        'part_time' => [
                                'label' => esc_html($label_part_time),
                                'data' => $part_time_price,
                        ],
                ];

                $tabs = array_filter($tabs, fn($tab) => has_price_data($tab['data']));
                ?>

                <div class="tabs_container pb-md-5 mb-md-5 mb-3">
                    <?php if (!empty($tabs)) : ?>
                        <div class="tabs-header">
                            <?php $first = true; ?>
                            <?php foreach ($tabs as $key => $tab) : ?>
                                <div class="tab<?php echo $first ? ' active' : ''; ?>"
                                     data-tab="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($tab['label']); ?>
                                </div>
                                <?php $first = false; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="tabs-content">
                            <?php $first = true; ?>
                            <?php foreach ($tabs as $key => $tab) :
                                $available_columns = get_available_columns($tab['data'], $columns); ?>
                                <div id="<?php echo esc_attr($key); ?>"
                                     class="tab-content<?php echo $first ? ' active' : ''; ?>">
                                    <table>
                                        <thead>
                                        <tr>
                                            <?php
                                            // Get ACF fields (options page or post, depending on your setup)
                                            $title_first_column = get_field('title_first_column') ?: __('1 RATA', 'akademiata');
                                            $title_second_column = get_field('title_second_column') ?: __('2 RATY', 'akademiata');
                                            $title_third_column = get_field('title_third_column') ?: __('4 RATY', 'akademiata');
                                            $title_fourth_column = get_field('title_fourth_column') ?: __('8 RAT', 'akademiata');
                                            $title_fifth_column = get_field('title_fifth_column') ?: __('6 RAT', 'akademiata');
                                            $title_sixth_column = get_field('title_sixth_column') ?: __('9 RAT', 'akademiata');
                                            ?>

                                            <?php if (!empty($available_columns[0])) : ?>
                                                <th><span><?php echo $title_first_column; ?></span></th>
                                            <?php endif; ?>
                                            <?php if (!empty($available_columns[1])) : ?>
                                                <th><span><?php echo $title_second_column; ?></span></th>
                                            <?php endif; ?>
                                            <?php if (!empty($available_columns[2])) : ?>
                                                <th><span><?php echo $title_third_column; ?></span></th>
                                            <?php endif; ?>
                                            <?php if (!empty($available_columns[3])) : ?>
                                                <th><span><?php echo $title_fourth_column; ?></span></th>
                                            <?php endif; ?>
                                            <?php if (!empty($available_columns[4])) : ?>
                                                <th><span><?php echo $title_fifth_column; ?></span></th>
                                            <?php endif; ?>
                                            <?php if (!empty($available_columns[5])) : ?>
                                                <th><span><?php echo $title_sixth_column; ?></span></th>
                                            <?php endif; ?>

                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php render_price_row($tab['data'], $columns, $available_columns, $key); ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php $first = false; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php _e('Brak dostępnych cenników w tym momencie.', 'akademiata'); ?></p>
                    <?php endif; ?>
                </div>
                <?php
                $current_lang = apply_filters('wpml_current_language', null);

                if ($current_lang !== 'en') :
                    ?>
                    <?php
//                    $pay_warsaw = get_field('info_for_pay_warsaw', 'option');

                    $pay_wroclaw = get_field('info_for_pay_wroclaw', 'option');
                    $pay_warsaw = $pay_wroclaw;

//                    $account_number_warsaw = get_field('account_number_warsaw', 'option');

                    $account_number_wroclaw = get_field('account_number_wroclaw', 'option');
                    $account_number_warsaw = $account_number_wroclaw;

                    if (in_array($post_type, ['mba', 'postgraduate'], true) && has_term($warszawa_slug, 'city_pg_mba', $post_id)) : ?>
                        <div class="description">
                            <?php echo $pay_warsaw; ?>
                            <strong><?php _e('Nr rachunku', 'akademiata'); ?>
                                <span class="copy_account_number"><?php echo $account_number_warsaw; ?></span></strong>
                        </div>
                    <?php elseif (
                            in_array($post_type, ['mba', 'postgraduate'], true)
                            && (
                                    has_term($wroclaw_slug, 'city_pg_mba', $post_id)
                                    || has_term($online_slug, 'city_pg_mba', $post_id)
                            )
                    ) : ?>
                        <div class="description">
                            <?php echo $pay_wroclaw; ?>
                            <strong>
                                <?php _e('Nr rachunku', 'akademiata'); ?>
                                <span class="copy_account_number"><?php echo $account_number_wroclaw; ?></span></strong>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>


</section>


<section id="discounts" class="section_discounts_pg_mba">
    <div class="container">
        <?php
        $accordion = get_field('discounts_accordion');
        $title = get_field('discounts_title') ?: __('Zniżki', 'akademiata');
        $sub_title = get_field('discounts_sub_title');
        $description = get_field('discounts_description');

        if (!empty($accordion)) {
            foreach ($accordion as &$item) {
                if (is_array($item)) {
                    $item['accordion_content_template'] = 'accordion_default_content.php';
                }
            }

            set_query_var('accordion', $accordion);
            set_query_var('title', $title);
            set_query_var('sub_title', !empty($sub_title) ? $sub_title : '');
            set_query_var('description', !empty($description) ? $description : '');

            locate_template('template-parts/accordion_universal.php', true, false);
        }
        ?>
    </div>
</section>

<section id="recruitment_rules" class="section_admission_rules_pg_mba">
    <div class="container">
        <?php
        $accordion = get_field('admission_rules_accordion');
        $title = get_field('admission_rules_title') ?: __('Zasady rekrutacji', 'akademiata');
        $sub_title = get_field('admission_rules_sub_title');
        $description = get_field('admission_rules_description');

        if (!empty($accordion)) {
            foreach ($accordion as &$item) {
                if (is_array($item)) {
                    $item['accordion_content_template'] = 'accordion_default_content.php';
                }
            }

            set_query_var('accordion', $accordion);
            set_query_var('title', $title);
            set_query_var('sub_title', !empty($sub_title) ? $sub_title : '');
            set_query_var('description', !empty($description) ? $description : '');

            locate_template('template-parts/accordion_universal.php', true, false);
        }
        ?>
    </div>
</section>

<?php

/**
 * Helper: Get translated city slug in current language
 */
function get_translated_city_slug($slug, $taxonomy)
{
    $term = get_term_by('slug', $slug, $taxonomy);
    if ($term && !is_wp_error($term)) {
        $mapped_id = function_exists('apply_filters')
                ? apply_filters('wpml_object_id', (int)$term->term_id, $taxonomy, true)
                : (int)$term->term_id;

        if ($mapped_id) {
            $mapped_term = get_term($mapped_id, $taxonomy);
            if ($mapped_term && !is_wp_error($mapped_term)) {
                return $mapped_term->slug;
            }
        }
    }
    return $slug; // fallback
}

// Resolve which options page to read from
$options_post_id = null;

if ($post_type === 'postgraduate' && has_term($wroclaw_slug, 'city_pg_mba', $post_id)) {
    $options_post_id = 'contact_postgraduate';

} elseif ($post_type === 'mba' && has_term($wroclaw_slug, 'city_pg_mba', $post_id)) {
    $options_post_id = 'contact_mba';

} elseif (in_array($post_type, ['mba', 'postgraduate'], true) && has_term($warszawa_slug, 'city_pg_mba', $post_id)) {
    $options_post_id = 'contact_warsaw';
} elseif (in_array($post_type, ['mba', 'postgraduate'], true) && has_term($online_slug, 'city_pg_mba', $post_id)) {
    $options_post_id = 'contact_postgraduate';
}
//        else {
//            $options_post_id = 'contact_warsaw';
//        }

$section_title = __('KONTAKT', 'akademiata');
?>

<section id="contact" class="section_contact_pg_mba">
    <div class="container">
        <!--        --><?php //if (
        //            in_array($post_type, ['mba', 'postgraduate'], true) &&
        //            (has_term($warszawa_slug, 'city_pg_mba', $post_id) || has_term($wroclaw_slug, 'city_pg_mba', $post_id))
        //        ) : ?>
        <h2 class="title_section col-xl-10 p-0 mb-3"><?php echo esc_html($section_title); ?></h2>
        <!--        --><?php //endif; ?>

        <?php

        // Pull the ACF group only if we resolved an options page
        $contact = $options_post_id ? (get_field('contact_content', $options_post_id) ?: []) : [];

        if (!empty($contact)) :
            // Extract fields with safe defaults
            $title_position = isset($contact['title']) ? wp_kses_post($contact['title']) : '';
            $address_html = isset($contact['address']) ? wp_kses_post($contact['address']) : '';
            $phones = !empty($contact['phones']) ? $contact['phones'] : [];
            $email_value = isset($contact['email'])
                    ? sanitize_email($contact['email'])
                    : '';

            $email_value_warsaw_postgraduate = isset($contact['email_warsaw_postgraduate'])
                    ? sanitize_email($contact['email_warsaw_postgraduate'])
                    : '';

            $email_value_warsaw_mba = isset($contact['email_warsaw_mba'])
                    ? sanitize_email($contact['email_warsaw_mba'])
                    : '';


            $working_hours = !empty($contact['working_hours']) ? $contact['working_hours'] : [];
            $staff = !empty($contact['staff']) ? $contact['staff'] : [];

            $theme_dir = esc_url(get_template_directory_uri());
            ?>
            <div class="contact_content w-100 mw-100">

                <div class="contact_top mb-5">
                    <div class="contact_columns">

                        <div class="contact_info">
                            <?php if ($title_position) : ?>
                                <h3 class="title_position"><strong><?php echo $title_position; ?></strong></h3>
                            <?php endif; ?>

                            <?php if ($address_html) : ?>
                                <div class="address"><?php echo $address_html; ?></div>
                            <?php endif; ?>

                            <?php if (!empty($phones)) : ?>
                                <div class="contact_row">
                                    <div class="icon">
                                        <img src="<?php echo $theme_dir; ?>/static/img/icon_contact_page_ATA_phone.png"
                                             alt="<?php esc_attr_e('Phone', 'akademiata'); ?>">
                                    </div>
                                    <div class="text">
                                        <?php
                                        foreach ($phones as $row) {
                                            if (empty($row['number'])) {
                                                continue;
                                            }
                                            $num_raw = preg_replace('/\s+/', '', $row['number']);
                                            $num_href = 'tel:' . esc_attr($num_raw);
                                            $num_label = esc_html($row['number']);
                                            echo '<a href="' . $num_href . '">' . $num_label . '</a><br>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php

                            $final_email = '';

                            if (has_term($warszawa_slug, 'city_pg_mba', $post_id)) {

                                if ($post_type === 'postgraduate') {
                                    $final_email = $email_value_warsaw_postgraduate;
                                }

                                if ($post_type === 'mba') {
                                    $final_email = $email_value_warsaw_mba;
                                }

                            } else {
                                // Wrocław + Online →  email
                                $final_email = $email_value;
                            }


                            if ($final_email) : ?>
                                <div class="contact_row">
                                    <div class="icon">
                                        <img src="<?php echo $theme_dir; ?>/static/img/icon_contact_page_ATA_mail.png"
                                             alt="<?php esc_attr_e('Email', 'akademiata'); ?>">
                                    </div>
                                    <div class="text">
                                        <a href="mailto:<?php echo esc_attr($final_email); ?>">
                                            <?php echo esc_html($final_email); ?>
                                        </a><br>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($working_hours)) : ?>
                            <div class="contact_hours">
                                <div class="contact_hours_wrapper">
                                    <h3 class="small_title"><?php esc_html_e('godziny pracy:', 'akademiata'); ?></h3>
                                    <ul>
                                        <?php
                                        foreach ($working_hours as $row) {
                                            $day = !empty($row['day']) ? esc_html($row['day']) : '';
                                            $time = !empty($row['time']) ? esc_html($row['time']) : '';
                                            if (!$day && !$time) {
                                                continue;
                                            }
                                            echo '<li>' . ($day ? $day . ': ' : '') . ($time ? '<strong>' . $time . '</strong>' : '') . '</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <?php if (!empty($staff)) : ?>
                    <div class="staff_pg_mba">
                        <div class="staff__grid">
                            <?php foreach ($staff as $person) :
                                $name = !empty($person['name']) ? esc_html($person['name']) : '';
                                $role = !empty($person['role']) ? esc_html($person['role']) : '';
                                $email = !empty($person['email']) ? sanitize_email($person['email']) : '';
                                $photo = !empty($person['photo']) ? $person['photo'] : [];

                                // Resolve photo URL + alt
                                $photo_url = '';
                                $photo_alt = '';
                                if (is_array($photo)) {
                                    $photo_url = !empty($photo['url']) ? esc_url($photo['url']) : '';
                                    $photo_alt = !empty($photo['alt']) ? esc_attr($photo['alt']) : ($name ?: '');
                                } elseif (is_int($photo)) {
                                    $photo_url = esc_url(wp_get_attachment_image_url($photo, 'large'));
                                    $photo_alt = esc_attr(get_post_meta($photo, '_wp_attachment_image_alt', true));
                                }
                                ?>
                                <article class="staff-card">
                                    <?php if ($photo_url) : ?>
                                        <figure class="staff-card__photo">
                                            <img src="<?php echo $photo_url; ?>" alt="<?php echo $photo_alt; ?>">
                                        </figure>
                                    <?php endif; ?>

                                    <div class="staff-card__body">
                                        <?php if ($name) : ?>
                                            <h3 class="staff-card__name"><?php echo $name; ?></h3>
                                        <?php endif; ?>

                                        <?php if ($role) : ?>
                                            <p class="staff-card__role"><?php echo $role; ?></p>
                                        <?php endif; ?>

                                        <?php if ($email) : ?>
                                            <a class="staff-card__email"
                                               href="mailto:<?php echo antispambot($email); ?>">
                                                <img src="<?php echo $theme_dir; ?>/static/img/icon_contact_page_ATA_mail.png"
                                                     alt="<?php esc_attr_e('Email', 'akademiata'); ?>"
                                                     class="staff-card__email-icon">
                                                <span><?php echo esc_html(antispambot($email)); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</section>

