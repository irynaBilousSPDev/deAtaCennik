<?php
/**
 * Contact Form 7 tweaks (theme-level).
 *
 * Keep CF7 markup unwrapped for pixel-perfect layouts.
 */

/**
 * Resolve CF7 form post ID from numeric ID or shortcode hash (e.g. 2dea2cf).
 */
function akademiata_cf7_form_id_from_ref($ref) {
    $ref = trim((string) $ref);
    if ($ref === '' || !function_exists('wpcf7_contact_form')) {
        return 0;
    }

    if (ctype_digit($ref)) {
        return (int) $ref;
    }

    if (function_exists('wpcf7_get_contact_form_by_hash')) {
        $form = wpcf7_get_contact_form_by_hash($ref);
        if ($form && method_exists($form, 'id')) {
            return (int) $form->id();
        }
    }

    return 0;
}

/**
 * Thank-you page slug => CF7 form (for mail_2 body text).
 */
function akademiata_open_day_thank_you_form_map() {
    return [
        'dziekujemy-wroclaw'  => 26382,
        'dziekujemy-warszawa' => 26494,
        'dziekujemy-za-rejestracje-na-dzien-otwarty-studiow-podyplomowych-w-ata-w-warszawie' => akademiata_cf7_form_id_from_ref('2dea2cf'),
    ];
}

/**
 * Open Day CF7 forms: ensure redirect wrapper class (initCf7Redirect).
 */
function akademiata_cf7_open_day_html_classes($properties, $contact_form) {
    if (!$contact_form || !method_exists($contact_form, 'title')) {
        return $properties;
    }

    $class_by_title = [
        'Open Day Form WRO'                              => 'cf7-open-day-wro',
        'Open Day Form Warszawa'                         => 'cf7-open-day-warszawa',
        'Open Day Form Warszawa STUDIA PODYPLOMOWE'      => 'cf7-open-day-studia-podyplomowe',
    ];

    $title = (string) $contact_form->title();
    if (!isset($class_by_title[$title])) {
        return $properties;
    }

    $class = $class_by_title[$title];
    $additional = (string) ($properties['additional_settings'] ?? '');

    if (preg_match('/html_class:\s*(.+)/i', $additional, $matches)) {
        $existing = trim($matches[1]);
        if (strpos($existing, $class) === false) {
            $properties['additional_settings'] = preg_replace(
                '/html_class:\s*.+/i',
                'html_class: ' . trim($existing . ' ' . $class),
                $additional,
                1
            );
        }
        return $properties;
    }

    $properties['additional_settings'] = trim($additional . "\nhtml_class: " . $class);
    return $properties;
}

add_filter('wpcf7_contact_form_properties', 'akademiata_cf7_open_day_html_classes', 5, 2);

add_action('wp_enqueue_scripts', function () {
    if (!wp_script_is('name-main-js', 'registered')) {
        return;
    }

    $pg_form_id = akademiata_cf7_form_id_from_ref('2dea2cf');
    $by_form_id = [];

    if ($pg_form_id) {
        $by_form_id[$pg_form_id] = home_url('/dzien-otwarty-studia-podyplomowe/dziekujemy-za-rejestracje-na-dzien-otwarty-studiow-podyplomowych-w-ata-w-warszawie/');
    }

    wp_localize_script('name-main-js', 'akademiataCf7Redirects', [
        'byFormId' => $by_form_id,
    ]);
}, 100);

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

