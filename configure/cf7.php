<?php
/**
 * Contact Form 7 tweaks (theme-level).
 *
 * Keep CF7 markup unwrapped for pixel-perfect layouts.
 */

add_action('wp', function () {
    if (!is_singular('podcast-ata')) {
        return;
    }

    // Disable CF7 auto-formatting (<p> and <br />) for this template.
    add_filter('wpcf7_autop_or_shortcode', '__return_false');
    add_filter('wpcf7_autop', '__return_false');
}, 20);

