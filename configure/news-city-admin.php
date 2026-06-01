<?php

/**
 * Persist news_city checkbox saves (Classic editor) + admin UI fixes.
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

    return !empty($_POST['post_ID']) && (int) $_POST['post_ID'] === $post_id;
}

/**
 * @return int[]
 */
function akademiata_news_city_admin_read_submitted_term_ids() {
    if (isset($_POST['akademiata_news_city_term_ids'])) {
        $raw = sanitize_text_field(wp_unslash($_POST['akademiata_news_city_term_ids']));
        if ($raw !== '') {
            return array_values(array_filter(array_map('intval', explode(',', $raw))));
        }
        return array();
    }

    if (!array_key_exists('news_city', $_POST['tax_input'] ?? array())) {
        return null;
    }

    $submitted = wp_unslash($_POST['tax_input']['news_city']);
    if ($submitted === '' || $submitted === array('')) {
        return array();
    }

    $raw_ids = is_array($submitted) ? $submitted : array($submitted);

    return array_values(array_filter(array_map('intval', $raw_ids)));
}

function akademiata_news_city_admin_capture_save($post_id, $post) {
    if (!($post instanceof WP_Post) || $post->post_type !== 'post') {
        return;
    }

    if (!akademiata_news_city_admin_can_save($post_id)) {
        return;
    }

    $raw_ids = akademiata_news_city_admin_read_submitted_term_ids();
    if ($raw_ids === null) {
        return;
    }

    if (!isset($GLOBALS['akademiata_pending_news_city_ids']) || !is_array($GLOBALS['akademiata_pending_news_city_ids'])) {
        $GLOBALS['akademiata_pending_news_city_ids'] = array();
    }

    $GLOBALS['akademiata_pending_news_city_ids'][ (int) $post_id ] = $raw_ids;
}

add_action('save_post', 'akademiata_news_city_admin_capture_save', 1, 2);

function akademiata_news_city_admin_apply_pending_save() {
    if (!isset($GLOBALS['akademiata_pending_news_city_ids']) || !is_array($GLOBALS['akademiata_pending_news_city_ids'])) {
        $GLOBALS['akademiata_pending_news_city_ids'] = array();
    }

    if (empty($GLOBALS['akademiata_pending_news_city_ids']) && !empty($_POST['post_ID']) && is_admin()) {
        $post_id = (int) $_POST['post_ID'];
        if ($post_id > 0 && akademiata_news_city_admin_can_save($post_id)) {
            $raw_ids = akademiata_news_city_admin_read_submitted_term_ids();
            if ($raw_ids !== null) {
                $GLOBALS['akademiata_pending_news_city_ids'][ $post_id ] = $raw_ids;
            }
        }
    }

    foreach ($GLOBALS['akademiata_pending_news_city_ids'] as $post_id => $raw_ids) {
        akademiata_save_post_news_city_from_term_ids((int) $post_id, (array) $raw_ids);
    }

    $GLOBALS['akademiata_pending_news_city_ids'] = array();
}

add_action('shutdown', 'akademiata_news_city_admin_apply_pending_save', 99999);

/**
 * Keep News cities checkboxes checked after save (map stored slug → visible term ID).
 */
function akademiata_news_city_admin_object_terms($terms, $object_ids, $taxonomies, $args) {
    unset($args);

    if (!is_admin() || !in_array('news_city', (array) $taxonomies, true)) {
        return $terms;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->base, array('post'), true)) {
        return $terms;
    }

    $post_id = (int) ($object_ids[0] ?? 0);
    if ($post_id <= 0) {
        return $terms;
    }

    $slug = get_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, true);
    $slug = sanitize_title((string) $slug);

    if ($slug === '' && !empty($terms) && !is_wp_error($terms)) {
        $slug = sanitize_title($terms[0]->slug);
    }

    if (!in_array($slug, array('warszawa', 'wroclaw'), true)) {
        return array();
    }

    $display = get_term_by('slug', $slug, 'news_city');
    if (!$display || is_wp_error($display)) {
        return $terms;
    }

    if (function_exists('apply_filters')) {
        $lang = apply_filters('wpml_current_language', null);
        $tid  = (int) apply_filters('wpml_object_id', (int) $display->term_id, 'news_city', false, $lang);
        if ($tid > 0) {
            $translated = get_term($tid, 'news_city');
            if ($translated && !is_wp_error($translated)) {
                $display = $translated;
            }
        }
    }

    return array($display);
}

add_filter('wp_get_object_terms', 'akademiata_news_city_admin_object_terms', 20, 4);

function akademiata_news_city_admin_footer_script($post) {
    if (!($post instanceof WP_Post) || $post->post_type !== 'post') {
        return;
    }
    ?>
    <input type="hidden" id="akademiata-news-city-term-ids" name="akademiata_news_city_term_ids" value="" />
    <script>
    (function ($) {
        var $form = $('#post');
        if (!$form.length) {
            return;
        }
        $form.on('submit', function () {
            var ids = [];
            $('#news_citydiv input[type="checkbox"]:checked').each(function () {
                ids.push(this.value);
            });
            $('#akademiata-news-city-term-ids').val(ids.join(','));
        });
    })(jQuery);
    </script>
    <?php
}

add_action('edit_form_after_title', 'akademiata_news_city_admin_footer_script');

function akademiata_news_city_admin_enqueue_styles($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'post') {
        return;
    }

    $css = '
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
