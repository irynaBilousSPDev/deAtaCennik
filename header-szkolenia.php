<?php
/**
 * Bare header for Szkolenia singles (no site chrome).
 *
 * @package akademiata
 */
?><!DOCTYPE HTML>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<?php if (akademiata_is_production()) : ?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-NXVN3ZZHC8"></script>
		<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-NXVN3ZZHC8');</script>
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-PN9RQ2C');</script>
	<?php endif; ?>
</head>
<body <?php body_class('szkolenia-bare'); ?>>
<?php if (akademiata_is_production()) : ?>
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PN9RQ2C" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?php wp_body_open(); ?>
<main id="main" class="szkolenia-bare-main">
