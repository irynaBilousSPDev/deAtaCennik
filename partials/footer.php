<?php
/**
 * The template for displaying footer
 *
 * @package  akademiata
 */
?>
<footer id="footer" class="site-footer">
    <?php locate_template('template-parts/partner_logos_slider.php', true, true); ?>
    <div class="footer_body">
        <div class="container">
            <div class="row">
                <div class="order-1 col-md-6 col-xl-4 item d-flex align-items-center align-items-md-start ">
                    <?php
                    $footer_logo = akademiata_get_footer_logo();
                    $logo_footer = $footer_logo['url'];
                    $logo_alt    = $footer_logo['alt'];
                    ?>
                    <div class="logo_footer_wrapper">
                        <a title="<?php _e('Logo - Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata'); ?>"
                           href="<?php echo get_home_url(); ?>">
                            <img src="<?php echo esc_url( $logo_footer ); ?>"
                                 alt="<?php echo esc_attr( $logo_alt ); ?>">
                        </a>
                    </div>
                    <?php
                    // Get ACF fields from options page
                    $footer_address = get_field('footer_address', 'option');
                    $footer_phone = get_field('footer_phone', 'option');
                    $footer_mail = get_field('footer_mail', 'option');
                    $footer_description = get_field('footer_description', 'option');
                    ?>

                    <div class="address">
                        <?php if ($footer_address): ?>
                            <?php echo $footer_address; ?>
                        <?php endif; ?>

                        <?php if ($footer_phone): ?>

                            <?php echo esc_html($footer_phone); ?>
                            <br>
                        <?php endif; ?>

                        <?php if ($footer_mail): ?>
                            <a href="mailto:<?php echo esc_attr($footer_mail); ?>">
                                <?php echo esc_html($footer_mail); ?>
                            </a><br>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-3 order-xl-2 col-xl-4 item d-flex flex-row align-items-end">
                    <div class="description">
                        <?php if ($footer_description): ?>
                            <?php echo $footer_description; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-2 order-xl-3 col-md-6 col-xl-4 item footer-nav-col d-flex align-items-center align-items-md-end">
                    <?php get_template_part( 'partials/smartapply-login-link', null, array( 'variant' => 'footer' ) ); ?>

                    <nav id="site-navigation" class="main-navigation">
                        <?php wp_nav_menu(array('theme_location' => 'menu-main', 'menu_id' => 'menu-main')); ?>
                    </nav>

                    <?php wp_nav_menu(array('theme_location' => 'menu-footer', 'menu_id' => 'menu-footer')); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="date">
            © <?php
            $current_lang = apply_filters('wpml_current_language', null);
            echo ($current_lang === 'en') ? 'UTA' : 'ATA';
            ?> <?php echo date('Y'); ?>
        </div>
    </div>


</footer>
