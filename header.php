<?php
/**
 * The header.
 *
 * This is the template that displays all of the <head> section and everything up until content.
 *
 * @package  akademiata
 */

?>
<!DOCTYPE HTML>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Permissions-Policy" content="compute-pressure=()">
    <!--or add to .htaccess-->
    <!--    Header always set Permissions-Policy "compute-pressure=()"-->
    <?php wp_head(); ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-NXVN3ZZHC8"></script>
    <script>   window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());
        gtag('config', 'G-NXVN3ZZHC8'); </script>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-PN9RQ2C');</script>
    <!-- End Google Tag Manager -->

    <script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid="7d62c502-b225-4caf-86f4-ecff5cd1b119" data-blockingmode="auto" type="text/javascript"></script>
</head>
<body <?php body_class(); ?>>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PN9RQ2C"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php wp_body_open(); ?>
<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Skip to content', 'akademiata'); ?></a>

    <?php locate_template('partials/header.php', true, true); ?>

    <div id="content" class="site-content">
        <div id="primary" class="content-area">
            <main id="main" class="site-main">