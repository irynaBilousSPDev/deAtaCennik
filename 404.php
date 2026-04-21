<?php
/**
 * The template for displaying 404 pages (Not Found)
 * Place this file in your theme root as 404.php
 */
?>

<style>
    body {
        margin: 0!important;
    }
    .error-404 {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        /*background: url('*/<?php //echo get_template_directory_uri(); ?>/*/static/img/404-bg.jpg') no-repeat center center;*/
        background-size: cover;
        background-color: #fff;
        color: #F5682C;
        font-family: "Nunito Sans", serif;
    }

    .error-404 .overlay {
        /*position: absolute;*/
        /*inset: 0;*/
        /*background: rgba(0, 0, 0, 0.85);*/
    }

    .error-404 .content {
        position: relative;
        z-index: 1;
        max-width: 600px;
        font-family: "Nunito Sans", serif;
    }

    .error-404 h1 {
        font-size: 12rem;
        margin-bottom: 1rem;
        color: #F5682C;
        font-family: "Nunito Sans", serif;
    }

    .error-404 p {
        color: #000;
        font-size: 1.25rem;
        margin-bottom: 2rem;
        font-family: "Lato", serif;
    }

    .error-404 a {
        display: inline-block;
        padding: 12px 24px;
        background: #F5682C;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        transition: background 0.3s ease;
        font-family: "Nunito Sans", serif;
    }

    .error-404 a:hover {
        background: #e65c1f;
    }
</style>

<div class="error-404">
    <div class="overlay"></div>
    <div class="content">
        <h1><?php esc_html_e('404', 'your-theme'); ?></h1>
        <p><?php esc_html_e('Oops! Page not found.', 'akademiata'); ?></p>
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <?php esc_html_e('Back to Homepage', 'akademiata'); ?>
        </a>
    </div>
</div>

