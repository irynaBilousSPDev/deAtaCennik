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

/**
 * Podcast pages: append a hidden field carrying the episode date so the value
 * is stored as a CF7 form-tag (and saved by CFDB7 for later filtering).
 */
function akademiata_cf7_inject_podcast_episode_date($properties, $contact_form) {
    if (!is_singular('podcast-ata')) {
        return $properties;
    }

    if (isset($properties['form']) && strpos($properties['form'], 'podcast-episode-date') === false) {
        $properties['form'] .= "\n[hidden podcast-episode-date]";
    }

    return $properties;
}

add_filter('wpcf7_contact_form_properties', 'akademiata_cf7_inject_podcast_episode_date', 10, 2);

/**
 * Fill the injected hidden field with the current episode's date/time
 * (same value as the "pill-filled" badge in the hero).
 */
function akademiata_cf7_fill_podcast_episode_date($tag) {
    if (!($tag instanceof WPCF7_FormTag) || $tag->name !== 'podcast-episode-date') {
        return $tag;
    }

    if (!is_singular('podcast-ata')) {
        return $tag;
    }

    $date = function_exists('get_field') ? get_field('episode_datetime', get_the_ID()) : '';

    if (!empty($date)) {
        $tag->values     = array($date);
        $tag->raw_values = array($date);
    }

    return $tag;
}

add_filter('wpcf7_form_tag', 'akademiata_cf7_fill_podcast_episode_date', 10, 1);

/**
 * Capture the episode date on submission. CF7 submits via AJAX where the
 * page-scoped filters above do not run, so the field is not re-scanned. The
 * hidden input was still posted by the browser — read it from $_POST so the
 * value reaches both the mail tag [podcast-episode-date] and CFDB7.
 */
function akademiata_cf7_capture_podcast_episode_date($posted_data) {
    if (isset($_POST['podcast-episode-date'])) {
        $posted_data['podcast-episode-date'] = sanitize_text_field(wp_unslash($_POST['podcast-episode-date']));
    }

    return $posted_data;
}

add_filter('wpcf7_posted_data', 'akademiata_cf7_capture_podcast_episode_date');

