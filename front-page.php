<?php
$acf_fields = get_fields();

$hero_slider_slides = akademiata_get_hero_slider_slides($acf_fields['main_slider'] ?? []);
akademiata_preload_main_slider_image($hero_slider_slides);

get_header();
//$acf_fields = get_cached_acf_fields(); // Get all ACF fields from cache

?>

<?php // Always render — wp_is_mobile() fails under WP Rocket (desktop cache for phones). ?>
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

<?php
set_query_var('hero_slider_slides', $hero_slider_slides);
get_template_part('template-parts/front-page/hero-slider');

$counter = $acf_fields['counter'];
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
                <h2><?php echo $main_title; ?></h2>
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
set_query_var('home_rankings', $acf_fields['home_rankings'] ?? null);
get_template_part('template-parts/front-page/home-rankings');
?>


<?php $our_students = $acf_fields['our_students'];
if (!empty($our_students)) :
    $title = $our_students['title'];
    $data_youtube_playlist = akademiata_normalize_youtube_playlist_id($our_students['data_youtube_playlist'] ?? '');
    ?>
    <section class="section_our_students left_space gray_arrows my-5">
        <div class="container">
            <h2 class="small_title mb-5"><?php echo esc_html($title); ?></h2>
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
$aktualnosci_cat_id = akademiata_get_aktualnosci_category_term_id();

if ($aktualnosci_cat_id > 0) {
    $aktualnosci = new WP_Query(
        array(
            'posts_per_page' => 6,
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'cat'            => $aktualnosci_cat_id,
            'lang'           => apply_filters('wpml_current_language', null),
        )
    );

    get_template_part(
        'partials/section',
        'aktualnosci-grid',
        array(
            'query'       => $aktualnosci,
            'see_all_url' => akademiata_get_aktualnosci_page_url(),
        )
    );
}
?>

<?php
get_footer();
