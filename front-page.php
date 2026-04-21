<?php
get_header();
$acf_fields = get_fields();// Get all ACF fields
$is_mobile = wp_is_mobile();
//$acf_fields = get_cached_acf_fields(); // Get all ACF fields from cache

?>

<?php if (wp_is_mobile()) : ?>
    <div class="mobile_visible">
        <section class="mobile_sub_header">
            <div class="mobile_nav_title mb-3">
                <?php echo _e('Wybierz', 'akademiata'); ?>
            </div>
            <nav id="site-navigation" class="main-navigation">
                <?php
                wp_nav_menu([
                    'theme_location' => 'menu-main',
                    'walker' => new Walker_Nav_With_Image(),
                    'menu_class' => 'main-menu',
                ]);
                ?>

            </nav>
        </section>
    </div>

<?php endif; ?>
<?php $main_slider = !empty($acf_fields['main_slider']) ? $acf_fields['main_slider'] : [];

// Filter: keep slides where show_slide is ON (or missing -> treat as ON)
$slides = array_values(array_filter($main_slider, function ($slide) {
	return !isset($slide['show_slide']) || (int)$slide['show_slide'] === 1;
}));
if (!empty($slides)) : ?>
    <section class="section_main_banner">
        <div class="main_slider">
            <div class="main_slider_active">
                <?php foreach ($slides as $slide) :
                    $image_url = '';

                    if (!empty($slide['image'])) {
                        if (wp_is_mobile()) {
                            //  Load mobile-optimized image
                            $image_url = !empty($slide['image']['sizes']['mobile_slider_banner'])
                                ? esc_url($slide['image']['sizes']['mobile_slider_banner'])
                                : esc_url($slide['image']['url']); // Fallback to original
                        } else {
                            //  Load desktop-optimized image
                            $image_url = !empty($slide['image']['sizes']['main_slider_banner'])
                                ? esc_url($slide['image']['sizes']['main_slider_banner'])
                                : esc_url($slide['image']['url']); // Fallback to original
                        }
                    }
                    $title = !empty($slide['title']) ? $slide['title'] : '';
                    // Ensure the button field exists within the slide
                    $button = !empty($slide['button']) ? $slide['button'] : null;
                    ?>
                    <div class="slide_item">
                        <div class="image_bg" role="img" aria-label="<?php echo $title; ?>"
                             style="background-image: url('<?php echo $image_url; ?>')">
                            <div class="details w-100">
                                <!-- Include ACF Button Component -->
                                <?php if (!empty($button)) : ?>
                                    <?php render_acf_button($button); ?>
                                <?php else : ?>
                                    <p style="color: red;">Button field is missing!</p>
                                <?php endif; ?>
                                <div class="small_title"><?php echo $title; ?></div>
                            </div>
                            <img src="<?php echo get_template_directory_uri() ?>/static/img/logo_ata_compact.webp"
                                 alt="<?php echo _e('Logo ATA compact', 'akademiata'); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$is_mobile) : ?>
                <div class="main_slider_nav_wrapper">
                    <div class="main_slider_nav">
                        <?php foreach ($slides as $slide) :
                            $image = $slide['image']["sizes"]["program_banner"];
                            $image_url = !empty($image) ? $image : '';
                            $title = !empty($slide['title']) ? $slide['title'] : '';
                            ?>
                            <div class="slide_item">
                                <div class="image_bg" role="img" aria-label="<?php echo $title; ?>"
                                     style="background-image: url('<?php echo $image_url; ?>')">
                                    <img src="<?php echo get_template_directory_uri() ?>/static/img/logo_ata_compact.webp"
                                         alt="<?php echo _e('Logo ATA compact', 'akademiata'); ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container">
                <div class="main_slider_controls"></div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php $counter = $acf_fields['counter'];
if (!empty($counter)) : ?>
    <section class="section_counter mb-5">
        <div class="container">
            <div class="counter_wrapper">
                <?php foreach ($counter as $item) : ?>
                    <div class="counter-box">
                        <div class="counter"><?php echo $item['counter']; ?></div>
                        <div class="counter-title"><?php echo $item['counter_title']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php $main_title = $acf_fields['main_title'];
if (!empty($main_title)) : ?>
    <section class="home_main_title">
        <div class="container">
            <div class="title_wrapper">
                <img width="34" height="34"
                     src="<?php echo get_template_directory_uri() ?>/static/img/small_logo_ATA.webp"
                     alt="<?php _e('small logo AT', 'akademiata'); ?>">
                <h1><?php echo $main_title; ?></h1>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- offers sliders -->
<?php $post_types = $acf_fields['offers'];
if (!empty($post_types)) : ?>
    <?php
//$post_types = ['bachelor', 'master'];
    set_query_var('post_types', $post_types);
    locate_template('./template-parts/offers_sliders.php', true, true);

    ?>
<?php endif; ?>


<?php
$about_us = $acf_fields['about_us'] ?? null;

if (!empty($about_us)) :
    $title = $about_us['title'] ?? '';
    $sub_title = $about_us['sub_title'] ?? '';
    $details = $about_us['details'] ?? '';
    $image = $about_us['image'] ?? '';

    $image_url = !empty($image['url']) ? esc_url($image['url']) : '';
    $image_alt = !empty($image['alt']) ? esc_attr($image['alt']) : __('About Us Image', 'akademiata');
    ?>

    <section id="aboutUs" class="section_about_us mb-5">
        <div class="container">
            <?php if (!empty($title)) : ?>
                <h2 class="sub_title primary_color"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if (!empty($sub_title)) : ?>
                <h3 class="title_section  mb-5"><?php echo esc_html($sub_title); ?></h3>
            <?php endif; ?>

            <div class="row">
                <?php if (!empty($image_url)) : ?>
                    <div class="col-lg-6 col-xl-5 mb-5">
                        <div class="item image_bg" role="img"
                             aria-label="<?php echo $image_alt; ?>"
                             style="background-image: url('<?php echo $image_url; ?>');">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($details)) : ?>
                    <div class="col-lg-6 col-xl-7 d-flex align-items-center">
                        <div class="details">
                            <?php echo wp_kses_post($details); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php $our_students = $acf_fields['our_students'];
if (!empty($our_students)) :
    $title = $our_students['title'];
    $data_youtube_playlist = $our_students['data_youtube_playlist'];
    ?>
    <section class="section_our_students left_space gray_arrows my-5">
        <div class="container">
            <h2 class="small_title mb-5"><?php echo $title; ?></h2>
            <?php
            set_query_var('data_youtube_playlist', $data_youtube_playlist);
            locate_template('./template-parts/youtube_slider.php', true, true);
            ?>
        </div>
    </section>
<?php endif; ?>

    <!--    promo_section-->
<?php
$banners = get_field('two_column_banner');
set_query_var('two_column_banner', $banners);
locate_template('./template-parts/promo_section.php', true, true);
?>

<?php $your_interests = $acf_fields['your_interests'];
if (!empty($your_interests)) :
    $section_title = $your_interests['title'];
    $interests = $your_interests['interests'];
    ?>
    <section class="section_study_your_interests">
        <div class="container">
            <?php if (!empty($section_title)) : ?>
                <h2 class="small_title mb-5"><?php echo esc_html($section_title); ?></h2>
            <?php endif; ?>
        </div>

        <?php if (!empty($interests) && is_array($interests)) : ?>
            <div class="interests-grid">
                <?php foreach ($interests as $item) :
                    $item_title = $item['title'] ?? '';
                    $link = $item['link'] ?? [];
                    $image = $item['image'] ?? [];

                    $fallback_image_url = !empty($image['url']) ? esc_url($image['url']) : '';

                    $image_url_mobile = !empty($image['sizes']['interests_image_mobile'])
                        ? esc_url($image['sizes']['interests_image_mobile'])
                        : $fallback_image_url;

                    $image_url_desktop = !empty($image['sizes']['interests_image'])
                        ? esc_url($image['sizes']['interests_image'])
                        : $fallback_image_url;

                    $image_alt = !empty($image['alt']) ? esc_attr($image['alt']) : __('Zainteresowania zdjęcie', 'akademiata');
                    $link_url = !empty($link['url']) ? esc_url($link['url']) : '#';
                    ?>
                    <a class="interest-item" href="<?php echo $link_url; ?>"
                       title="<?php echo esc_attr($item_title); ?>">
                        <div class="image_bg responsive-image"
                             role="img"
                             aria-label="<?php echo esc_attr($image_alt); ?>"
                             data-mobile="<?php echo $image_url_mobile; ?>"
                             data-desktop="<?php echo $image_url_desktop; ?>"
                             style="background-image: url('<?php echo $image_url_desktop; ?>');">
                            <h3><?php echo esc_html($item_title); ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>



<?php
// Get translated 'aktualnosci' category ID
$category_id = 0;

if (function_exists('icl_object_id')) {
    $default_term = get_term_by('slug', 'aktualnosci', 'category');
    if ($default_term && !is_wp_error($default_term)) {
        $current_lang = apply_filters('wpml_current_language', null);
        $translated_term_id = apply_filters('wpml_object_id', $default_term->term_id, 'category', true, $current_lang);
        if (!empty($translated_term_id)) {
            $category_id = $translated_term_id;
        }
    }
}

// Fallback if WPML is not active
if (!$category_id) {
    $default_term = get_term_by('slug', 'aktualnosci', 'category');
    if ($default_term && !is_wp_error($default_term)) {
        $category_id = $default_term->term_id;
    }
}

// Build query only if category ID found
$aktualnosci = new WP_Query([
    'posts_per_page' => 6,
    'post_status' => 'publish',
    'post_type' => 'post',
    'cat' => $category_id, // filter by category ID!
]);

if ($aktualnosci->have_posts()) :
    $index = 0;
    ?>
    <?php
// Get ID of the "Aktualnosci" page (in default language)
    $page_slug = 'aktualnosci';
    $page = get_page_by_path($page_slug);

    if ($page) {
        // Get current language
        $lang = apply_filters('wpml_current_language', null);

        // Get translated page ID
        $translated_id = apply_filters('wpml_object_id', $page->ID, 'page', false, $lang);

        // Get URL of translated page
        $page_url = get_permalink($translated_id);
    }
    ?>
    <section class="section_aktualnosci mb-5">
        <div class="container">
            <div class="aktualnosci-header-row">
                <h2 class="small_title"><?php _e('AKTUALNOŚCI', 'akademiata'); ?></h2>
                <a class="see-all-link" href="<?php echo esc_url($page_url); ?>">
                    <?php _e('Zobacz wszystkie', 'akademiata'); ?>
                </a>
            </div>

            <div class="front-aktualnosci-grid">
                <?php while ($aktualnosci->have_posts()) :
                $aktualnosci->the_post(); ?>
                <?php if ($index === 0): ?>
                    <div class="aktualnosci-post aktualnosci-first">
                        <a href="<?php the_permalink(); ?>">
                            <div class="post-image"
                                 style="background-image: url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>');">
                                <div class="post-title-overlay"><?php the_title(); ?></div>
                            </div>
                        </a>
                    </div>
                <?php elseif ($index === 1): ?>
                    <div class="aktualnosci-post aktualnosci-second">
                        <a href="<?php the_permalink(); ?>">
                            <div class="post-image"
                                 style="background-image: url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium_large'); ?>');">
                                <div class="post-title-overlay"><?php the_title(); ?></div>
                            </div>
                        </a>
                    </div>
                <?php elseif ($index === 2): ?>
                <div class="aktualnosci-post aktualnosci-list">
                    <ul>
                        <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                        <?php else: ?>
                            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                        <?php endif; ?>
                        <?php $index++; ?>
                        <?php endwhile; ?>
                    </ul>
                </div> <!-- close .aktualnosci-list -->
            </div> <!-- close .front-aktualnosci-grid -->
        </div> <!-- close .container -->
    </section>

    <?php
    wp_reset_postdata();
endif;
?>

<?php
get_footer();
