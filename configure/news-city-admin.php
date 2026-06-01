<?php

/**
 * Custom Miasto metabox for wpisy — avoids WPML/tax_input save issues on prod.
 */

define('AKADEMIATA_NEWS_CITY_META_KEY', '_akademiata_news_city_slug');

/**
 * @param int $post_id Post ID.
 * @return string Empty, warszawa, or wroclaw (stored value only — no default).
 */
function akademiata_get_saved_news_city_slug($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
    if ($post_id <= 0) {
        return '';
    }

    $slug = get_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, true);
    $slug = sanitize_title((string) $slug);

    if (in_array($slug, array('warszawa', 'wroclaw'), true)) {
        return $slug;
    }

    $terms = get_the_terms($post_id, 'news_city');
    if (!empty($terms) && !is_wp_error($terms)) {
        $term_slug = sanitize_title($terms[0]->slug);
        if (in_array($term_slug, array('warszawa', 'wroclaw'), true)) {
            return $term_slug;
        }
    }

    return '';
}

/**
 * Persist slug to post meta and sync news_city taxonomy term.
 *
 * @param int    $post_id Post ID.
 * @param string $slug    Empty, warszawa, or wroclaw.
 */
function akademiata_save_post_news_city_slug($post_id, $slug) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return;
    }

    $slug = sanitize_title((string) $slug);
    if (!in_array($slug, array('', 'warszawa', 'wroclaw'), true)) {
        $slug = '';
    }

    update_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, $slug);

    if ($slug === '') {
        akademiata_set_post_news_city_terms($post_id, array());
        return;
    }

    $term_id = akademiata_ensure_news_city_term_id($slug, akademiata_get_post_wpml_language($post_id));
    if ($term_id > 0) {
        akademiata_set_post_news_city_terms($post_id, array($term_id));
    }
}

function akademiata_news_city_admin_remove_default_metabox() {
    remove_meta_box('news_citydiv', 'post', 'side');
}

add_action('add_meta_boxes', 'akademiata_news_city_admin_remove_default_metabox', 100);

function akademiata_news_city_admin_register_metabox() {
    add_meta_box(
        'akademiata_news_city',
        __('Miasto', 'akademiata'),
        'akademiata_news_city_admin_render_metabox',
        'post',
        'side',
        'default'
    );
}

add_action('add_meta_boxes', 'akademiata_news_city_admin_register_metabox', 101);

function akademiata_news_city_admin_render_metabox($post) {
    wp_nonce_field('akademiata_save_news_city', 'akademiata_news_city_nonce');

    $current = akademiata_get_saved_news_city_slug((int) $post->ID);
    $map     = akademiata_news_city_label_map();
    $lang    = apply_filters('wpml_current_language', 'pl');
    $choices = array(
        ''         => __('Brak (na stronie: Warszawa)', 'akademiata'),
        'warszawa' => $map['warszawa'][ $lang ] ?? $map['warszawa']['pl'],
        'wroclaw'  => $map['wroclaw'][ $lang ] ?? $map['wroclaw']['pl'],
    );

    echo '<div class="akademiata-news-city-metabox">';

    foreach ($choices as $slug => $label) {
        $id = 'akademiata-news-city-' . ($slug === '' ? 'none' : $slug);
        printf(
            '<p><label for="%1$s"><input type="radio" name="akademiata_news_city_slug" id="%1$s" value="%2$s"%3$s> %4$s</label></p>',
            esc_attr($id),
            esc_attr($slug),
            checked($current, $slug, false),
            esc_html($label)
        );
    }

    echo '</div>';
}

function akademiata_news_city_admin_save_metabox($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!($post instanceof WP_Post) || $post->post_type !== 'post' || wp_is_post_revision($post_id)) {
        return;
    }

    if (
        !isset($_POST['akademiata_news_city_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['akademiata_news_city_nonce'])), 'akademiata_save_news_city')
    ) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!array_key_exists('akademiata_news_city_slug', $_POST)) {
        return;
    }

    $slug = sanitize_title(wp_unslash($_POST['akademiata_news_city_slug']));
    akademiata_save_post_news_city_slug((int) $post_id, $slug);
}

add_action('save_post', 'akademiata_news_city_admin_save_metabox', 100, 2);

function akademiata_news_city_admin_enqueue_styles($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'post') {
        return;
    }

    $css = '
        .akademiata-news-city-metabox p { margin: 0 0 8px; }
        .akademiata-news-city-metabox label { font-weight: 400; }
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
