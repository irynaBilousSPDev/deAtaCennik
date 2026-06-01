<?php

/**
 * Custom Miasto metabox for wpisy — bypasses WPML/tax_input save issues.
 */

/**
 * @return bool
 */
function akademiata_news_city_admin_can_save($post_id) {
    $post_id = (int) $post_id;

    if ($post_id <= 0 || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
        return false;
    }

    if (wp_is_post_revision($post_id)) {
        return false;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return false;
    }

    if (!isset($_POST['post_ID']) || (int) $_POST['post_ID'] !== $post_id) {
        return false;
    }

    if (!isset($_POST['_wpnonce'])) {
        return false;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce']));

    return (bool) wp_verify_nonce($nonce, 'update-post_' . $post_id);
}

function akademiata_news_city_admin_remove_default_metabox() {
    remove_meta_box('news_citydiv', 'post', 'side');
    remove_meta_box('tagsdiv-news_city', 'post', 'side');
}

add_action('add_meta_boxes', 'akademiata_news_city_admin_remove_default_metabox', 9999);

function akademiata_news_city_admin_register_metabox() {
    add_meta_box(
        'akademiata_news_city',
        __('Miasto', 'akademiata'),
        'akademiata_news_city_admin_render_metabox',
        'post',
        'side',
        'core'
    );
}

add_action('add_meta_boxes', 'akademiata_news_city_admin_register_metabox', 10);

function akademiata_news_city_admin_render_metabox($post) {
    $current = akademiata_get_saved_news_city_slug((int) $post->ID);
    $map     = akademiata_news_city_label_map();
    $lang    = apply_filters('wpml_current_language', 'pl');
    $choices = array(
        ''         => __('Brak (na stronie: Warszawa)', 'akademiata'),
        'warszawa' => $map['warszawa'][ $lang ] ?? $map['warszawa']['pl'],
        'wroclaw'  => $map['wroclaw'][ $lang ] ?? $map['wroclaw']['pl'],
    );

    echo '<div class="akademiata-news-city-metabox">';
    echo '<label class="screen-reader-text" for="akademiata-news-city-select">' . esc_html__('Miasto', 'akademiata') . '</label>';
    echo '<select name="akademiata_news_city_slug" id="akademiata-news-city-select" style="width:100%;max-width:100%;">';

    foreach ($choices as $slug => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($slug),
            selected($current, $slug, false),
            esc_html($label)
        );
    }

    echo '</select>';
    echo '<p class="description">' . esc_html__('Wybierz miasto i kliknij Aktualizuj.', 'akademiata') . '</p>';
    echo '</div>';
}

function akademiata_news_city_admin_capture_save($post_id, $post) {
    if (!($post instanceof WP_Post) || $post->post_type !== 'post') {
        return;
    }

    if (!akademiata_news_city_admin_can_save($post_id)) {
        return;
    }

    if (!isset($GLOBALS['akademiata_pending_news_city_slug']) || !is_array($GLOBALS['akademiata_pending_news_city_slug'])) {
        $GLOBALS['akademiata_pending_news_city_slug'] = array();
    }

    if (array_key_exists('akademiata_news_city_slug', $_POST)) {
        $slug = sanitize_title(wp_unslash($_POST['akademiata_news_city_slug']));
        $GLOBALS['akademiata_pending_news_city_slug'][ (int) $post_id ] = $slug;
        return;
    }

    if (!isset($_POST['tax_input']['news_city'])) {
        return;
    }

    $submitted = wp_unslash($_POST['tax_input']['news_city']);
    $raw_ids   = is_array($submitted) ? $submitted : array($submitted);
    $raw_ids   = array_values(array_filter(array_map('intval', $raw_ids)));
    $term_ids  = akademiata_resolve_news_city_term_ids_for_post($raw_ids, (int) $post_id);
    $slug      = '';

    if (!empty($term_ids)) {
        $term = get_term((int) $term_ids[0], 'news_city');
        if ($term && !is_wp_error($term)) {
            $slug = sanitize_title($term->slug);
        }
    }

    $GLOBALS['akademiata_pending_news_city_slug'][ (int) $post_id ] = $slug;
}

add_action('save_post', 'akademiata_news_city_admin_capture_save', 1, 2);

function akademiata_news_city_admin_apply_pending_save() {
    if (empty($GLOBALS['akademiata_pending_news_city_slug']) || !is_array($GLOBALS['akademiata_pending_news_city_slug'])) {
        return;
    }

    foreach ($GLOBALS['akademiata_pending_news_city_slug'] as $post_id => $slug) {
        akademiata_save_post_news_city_slug((int) $post_id, (string) $slug);
    }

    $GLOBALS['akademiata_pending_news_city_slug'] = array();
}

add_action('shutdown', 'akademiata_news_city_admin_apply_pending_save', 999);

function akademiata_news_city_admin_enqueue_styles($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'post') {
        return;
    }

    $css = '
        .akademiata-news-city-metabox .description { margin-top: 8px; }
        #submitpost #major-publishing-actions {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            overflow: visible;
        }
        #submitpost #delete-action,
        #submitpost #publishing-action {
            float: none !important;
            clear: both;
            text-align: left;
            line-height: 1.4;
        }
        #submitpost #publishing-action {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        #submitpost #publishing-action .spinner {
            float: none;
            margin: 0;
        }
        #submitpost .litespeed-cache-purge-btn,
        #submitpost [id*="cache"],
        #submitpost .button[id*="litespeed"] {
            display: block;
            width: 100%;
            max-width: 100%;
            margin: 0 0 4px;
            text-align: center;
            white-space: normal;
            box-sizing: border-box;
        }
    ';

    wp_register_style('akademiata-news-city-admin', false, array(), null);
    wp_enqueue_style('akademiata-news-city-admin');
    wp_add_inline_style('akademiata-news-city-admin', $css);
}

add_action('admin_enqueue_scripts', 'akademiata_news_city_admin_enqueue_styles');
