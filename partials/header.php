<?php
/**
 * The template for displaying header
 *
 * @package  akademiata
 */
?>

<header id="masthead" class="site-header">

    <div class="header_top_lang_nav">
        <div class="container">
            <div class="header_top_wrapper">
				<?php if ( ! wp_is_mobile() ) : ?>
                    <div class="description desktop_visible">
						<?php echo wp_kses_post( __( '<strong>od 1995 do 2024 r. </strong> jako <strong> Wyższa Szkoła Ekologii i Zarządzania w
                            Warszawie </strong>', 'akademiata' ) ); ?>
                    </div>
				<?php endif; ?>

				<?php
				$languages = apply_filters( 'wpml_active_languages', null, [
					'skip_missing' => 0,
				] );

				if ( ! empty( $languages ) ) : ?>
                    <ul class="lan_nav">
						<?php foreach ( $languages as $lang ) :
							// If no translated URL, fallback to /{lang_code}/
							$link = ! empty( $lang['url'] ) ? $lang['url'] : home_url( '/' . $lang['code'] . '/' );
							?>
                            <li class="<?php echo $lang['active'] ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url( $link ); ?>">
									<?php echo esc_html( strtoupper( $lang['code'] ) ); ?>
                                </a>
                            </li>
						<?php endforeach; ?>
                    </ul>
				<?php endif; ?>


            </div>
        </div>
    </div>
    <div class="main_top_nav">
        <div class="container">
            <div class="site_navigation_wrapper">
                <div class="logo_container">
					<?php
					$current_lang = apply_filters( 'wpml_current_language', null );

					$logo_header = get_template_directory_uri() . '/static/img/ATA_logo_main.webp';
					$logo_alt    = __( 'Logo - Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata' );

					if ( $current_lang === 'en' ) {
						$logo_header = get_template_directory_uri() . '/static/img/logo_ATA_EN_271x56_general_header.png';
						$logo_alt    = 'Logo - University of Technology and Arts, Applied Sciences in Warsaw';
					}
					?>
                    <div class="site-branding">
                        <a title="<?php _e( 'Akademia Techniczno-Artystyczna Nauk Stosowanych w Warszawie', 'akademiata' ); ?>"
                           href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">

                            <img width="250" height="100%"
                                 src="<?php echo esc_url( $logo_header ); ?>"
                                 alt="<?php echo esc_attr( $logo_alt ); ?>">
                        </a>
                    </div><!-- .site-branding -->
                    <img width="50" height="100%" alt="<?php _e( 'Polska - Logo', 'akademiata' ); ?>"
                         src="<?php echo get_template_directory_uri() ?>/static/img/poland.webp">
                </div>

				<?php if ( is_singular( array( 'bachelor', 'master', 'mba', 'postgraduate', 'courses' ) ) ) : ?>

                    <div class="mobile_visible">
                        <div class="d-flex">

                                <a href=""
                                   class="button-sing_up registration_link mr-3"><?php _e( 'ZAPISZ SIĘ', 'akademiata' ); ?></a>
                            <button class="megaMenuToggle mega-menu-button" aria-label="Toggle menu">
                                <span class="menu-icon">
                                    <span class="bar top-bar"></span>
                                    <span class="bar middle-bar"></span>
                                    <span class="bar bottom-bar"></span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="desktop_visible">
                        <nav id="offer-navigation" class="single_offer_nav">
							<?php if ( ! is_singular( [ 'courses'] ) ) : ?>
								<?php locate_template( 'partials/nav_single_offer.php', true, true ); ?>
							<?php endif; ?>

                                <a href=""
                                   class="button-sing_up registration_link"><?php _e( 'ZAPISZ SIĘ', 'akademiata' ); ?></a>


                            <button class="megaMenuToggle mega-menu-button">
                                <span class="menu-toggle-label">
                                    <?php _e( 'MENU', 'akademiata' ); ?>
                                </span>
                            </button>
                        </nav>
                    </div>

				<?php elseif ( is_singular( 'podcast-ata' ) ) : ?>

                    <div class="desktop_visible">
                        <div class="d-flex align-items-center">
                            <nav id="podcast-navigation" class="podcast-navigation" aria-label="Podcast navigation">
                                <a href="#o-czym"><?php _e( 'O czym porozmawiamy', 'akademiata' ); ?></a>
                                <a href="#goscie"><?php _e( 'Nasi goście', 'akademiata' ); ?></a>
                                <a href="#zapisz" class="button-sing_up podcast-cta"><?php _e( 'ZAPISZ SIĘ', 'akademiata' ); ?></a>
                            </nav>
                            <button class="megaMenuToggle mega-menu-button" aria-label="Toggle menu">
                                <span class="menu-icon">
                                    <span class="bar top-bar"></span>
                                    <span class="bar middle-bar"></span>
                                    <span class="bar bottom-bar"></span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="mobile_visible">
                        <div class="d-flex align-items-center">
                            <a href="#zapisz" class="button-sing_up podcast-cta mr-3"><?php _e( 'ZAPISZ SIĘ', 'akademiata' ); ?></a>
                            <button class="megaMenuToggle mega-menu-button" aria-label="Toggle menu">
                                <span class="menu-icon">
                                    <span class="bar top-bar"></span>
                                    <span class="bar middle-bar"></span>
                                    <span class="bar bottom-bar"></span>
                                </span>
                            </button>
                        </div>
                    </div>

				<?php else: ?>

                    <div class="desktop_visible">
                        <div class="d-flex align-items-center">
                            <nav id="site-navigation" class="main-navigation">
								<?php wp_nav_menu( array(
									'theme_location' => 'menu-main',
									'menu_id'        => 'menu-main'
								) ); ?>

                            </nav>
							<?php get_template_part( 'partials/button_additional_nav_front' ); ?>
                            <!--                            <button class="megaMenuToggle mega-menu-button">-->
                            <!--                                <span class="menu-toggle-label">-->
                            <!--                                    --><?php //_e('MENU', 'akademiata'); ?>
                            <!--                                </span>-->
                            <!--                            </button>-->
                            <button class="megaMenuToggle mega-menu-button" aria-label="Toggle menu">
                                <span class="menu-icon">
                                    <span class="bar top-bar"></span>
                                    <span class="bar middle-bar"></span>
                                    <span class="bar bottom-bar"></span>
                                </span>
                            </button>

                        </div>
                    </div>

                    <div class="mobile_visible">
                        <div class="d-flex align-items-center">
							<?php get_template_part( 'partials/button_additional_nav_front' ); ?>
                            <button class="megaMenuToggle mega-menu-button" aria-label="Toggle menu">
                                <span class="menu-icon">
                                    <span class="bar top-bar"></span>
                                    <span class="bar middle-bar"></span>
                                    <span class="bar bottom-bar"></span>
                                </span>
                            </button>
                        </div>
                    </div>

				<?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Mega Menu -->
    <div id="megaMenu" class="mega-menu">
        <div class="container">
            <div class="d-flex flex-column justify-content-between h-100">
                <div>
					<?php
					wp_nav_menu( [
						'theme_location' => 'mega-menu',
						'container'      => false,
						'menu_class'     => 'mega-menu-columns',
						'walker'         => new WP_Mega_Menu_Walker()
					] );
					?>
                </div>

                <div class="description my-5 text-center mobile_visible py-5">
					<?php echo wp_kses_post( __( '<strong>od 1995 do 2024 r. </strong> jako <strong> Wyższa Szkoła Ekologii i Zarządzania w
                            Warszawie </strong>', 'akademiata' ) ); ?>
                </div>

            </div>
        </div>
    </div>
</header>



