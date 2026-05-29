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
 * Capture the episode date on submission so the mail tag [podcast-episode-date]
 * and CFDB7 receive it. CF7 submits via AJAX (page-scoped filters do not run),
 * so derive the value server-side from `_wpcf7_container_post` — the ID of the
 * post the form was embedded in — and read it straight from the episode's ACF.
 * Falls back to the posted hidden value if present.
 */
function akademiata_cf7_capture_podcast_episode_date($posted_data) {
    $date = '';

    $container_post = isset($_POST['_wpcf7_container_post']) ? absint($_POST['_wpcf7_container_post']) : 0;
    if ($container_post && function_exists('get_field') && get_post_type($container_post) === 'podcast-ata') {
        $date = (string) get_field('episode_datetime', $container_post);
    }

    if ($date === '' && isset($_POST['podcast-episode-date'])) {
        $date = sanitize_text_field(wp_unslash($_POST['podcast-episode-date']));
    }

    if ($date !== '') {
        $posted_data['podcast-episode-date'] = $date;
    }

    return $posted_data;
}

add_filter('wpcf7_posted_data', 'akademiata_cf7_capture_podcast_episode_date');

