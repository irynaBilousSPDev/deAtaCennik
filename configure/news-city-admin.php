<?php

/**
 * Persist news_city checkbox saves (Classic editor) + admin UI fixes.
 */

/**
 * @return bool
 */
function akademiata_news_city_admin_is_inline_save_request($post_id) {
    return (
        defined('DOING_AJAX')
        && DOING_AJAX
        && isset($_POST['action'], $_POST['post_ID'])
        && $_POST['action'] === 'inline-save'
        && (int) $_POST['post_ID'] === (int) $post_id
        && isset($_POST['_inline_edit'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_inline_edit'])), 'inline-post-save')
    );
}

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

    if (empty($_POST['post_ID']) || (int) $_POST['post_ID'] !== $post_id) {
        return false;
    }

    if (akademiata_news_city_admin_is_inline_save_request($post_id)) {
        return true;
    }

    // Full editor save — core already verified the request before save_post.
    return true;
}

/**
 * @return array<string, WP_Term>
 */
function akademiata_news_city_admin_checkbox_terms() {
    $choices = array();

    foreach (array('warszawa', 'wroclaw') as $slug) {
        $term = function_exists('akademiata_get_news_city_term_by_slug')
            ? akademiata_get_news_city_term_by_slug($slug)
            : get_term_by('slug', $slug, 'news_city');

        if ($term && !is_wp_error($term)) {
            $choices[ $slug ] = $term;
        }
    }

    return $choices;
}

/**
 * @return int[]|null
 */
function akademiata_news_city_admin_read_submitted_term_ids($post_id = 0) {
    $post_id = (int) $post_id;

    if (isset($_POST['akademiata_news_city_term_ids'])) {
        $raw = sanitize_text_field(wp_unslash($_POST['akademiata_news_city_term_ids']));
        if ($raw !== '') {
            return array_values(array_filter(array_map('intval', explode(',', $raw))));
        }
        return array();
    }

    if (!array_key_exists('news_city', $_POST['tax_input'] ?? array())) {
        if ($post_id > 0 && akademiata_news_city_admin_is_inline_save_request($post_id)) {
            return array();
        }
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

    $raw_ids = akademiata_news_city_admin_read_submitted_term_ids((int) $post_id);
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
            $raw_ids = akademiata_news_city_admin_read_submitted_term_ids($post_id);
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
 * @return bool
 */
function akademiata_news_city_admin_is_posts_context() {
    if (!is_admin()) {
        return false;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'post') {
        return false;
    }

    return in_array($screen->base, array('post', 'edit'), true);
}

/**
 * @param WP_Term[] $terms
 * @return WP_Term[]
 */
function akademiata_news_city_admin_translate_term_for_display($term) {
    if (!$term || is_wp_error($term)) {
        return $term;
    }

    if (function_exists('apply_filters')) {
        $lang = apply_filters('wpml_current_language', null);
        $tid  = (int) apply_filters('wpml_object_id', (int) $term->term_id, 'news_city', false, $lang);
        if ($tid > 0) {
            $translated = get_term($tid, 'news_city');
            if ($translated && !is_wp_error($translated)) {
                return $translated;
            }
        }
    }

    return $term;
}

/**
 * Resolve news_city terms for admin UI (edit screen + posts list column).
 *
 * @param int                $post_id Post ID.
 * @param WP_Term[]|int[]    $terms   Terms or term IDs from DB (may be empty).
 * @return WP_Term[]
 */
function akademiata_news_city_admin_terms_for_post($post_id, $terms) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return array();
    }

    if (is_wp_error($terms)) {
        $terms = array();
    }

    $slug = sanitize_title((string) get_post_meta($post_id, AKADEMIATA_NEWS_CITY_META_KEY, true));

    if ($slug === '' && !empty($terms)) {
        $first = $terms[0];
        if (is_object($first) && isset($first->slug)) {
            $slug = sanitize_title($first->slug);
        } elseif (is_numeric($first)) {
            $term = get_term((int) $first, 'news_city');
            if ($term && !is_wp_error($term)) {
                $slug = sanitize_title($term->slug);
            }
        }
    }

    if (!in_array($slug, array('warszawa', 'wroclaw'), true)) {
        return array();
    }

    $display = get_term_by('slug', $slug, 'news_city');
    if (!$display || is_wp_error($display)) {
        return array();
    }

    return array(akademiata_news_city_admin_translate_term_for_display($display));
}

/**
 * Match wp_get_object_terms fields arg (checklist uses fields=ids).
 *
 * @param WP_Term[] $terms  Term objects.
 * @param string    $fields fields argument from wp_get_object_terms.
 * @return array
 */
function akademiata_news_city_admin_format_object_terms($terms, $fields) {
    if (empty($terms)) {
        return array();
    }

    switch ($fields) {
        case 'ids':
            return array_values(array_map(static function ($term) {
                return (int) $term->term_id;
            }, $terms));

        case 'names':
            return array_values(array_map(static function ($term) {
                return $term->name;
            }, $terms));

        case 'slugs':
            return array_values(array_map(static function ($term) {
                return $term->slug;
            }, $terms));

        case 'id=>name':
            $out = array();
            foreach ($terms as $term) {
                $out[ (int) $term->term_id ] = $term->name;
            }
            return $out;

        case 'id=>slug':
            $out = array();
            foreach ($terms as $term) {
                $out[ (int) $term->term_id ] = $term->slug;
            }
            return $out;

        case 'tt_ids':
            return array_values(array_map(static function ($term) {
                return (int) $term->term_taxonomy_id;
            }, $terms));

        default:
            return $terms;
    }
}

function akademiata_news_city_admin_get_the_terms($terms, $post_id, $taxonomy) {
    if ($taxonomy !== 'news_city' || !akademiata_news_city_admin_is_posts_context()) {
        return $terms;
    }

    if (is_wp_error($terms)) {
        return $terms;
    }

    return akademiata_news_city_admin_terms_for_post((int) $post_id, $terms ?: array());
}

add_filter('get_the_terms', 'akademiata_news_city_admin_get_the_terms', 20, 3);

/**
 * Keep News cities checkboxes checked after save (map stored slug → visible term ID).
 */
function akademiata_news_city_admin_object_terms($terms, $object_ids, $taxonomies, $args) {
    if (!is_admin() || !in_array('news_city', (array) $taxonomies, true)) {
        return $terms;
    }

    if (!akademiata_news_city_admin_is_posts_context()) {
        return $terms;
    }

    $post_id = (int) ($object_ids[0] ?? 0);
    if ($post_id <= 0) {
        return $terms;
    }

    if (is_wp_error($terms)) {
        $terms = array();
    }

    $fields  = is_array($args) && isset($args['fields']) ? (string) $args['fields'] : 'all';
    $objects = akademiata_news_city_admin_terms_for_post($post_id, $terms ?: array());

    return akademiata_news_city_admin_format_object_terms($objects, $fields);
}

add_filter('wp_get_object_terms', 'akademiata_news_city_admin_object_terms', 20, 4);

/**
 * Hidden slug in list table row — used by Quick Edit JS.
 */
function akademiata_news_city_admin_column_inline_slug($column, $post_id) {
    if ($column !== 'taxonomy-news_city') {
        return;
    }

    $slug = sanitize_title((string) get_post_meta((int) $post_id, AKADEMIATA_NEWS_CITY_META_KEY, true));
    printf(
        '<span class="akademiata-news-city-slug hidden" data-slug="%s" aria-hidden="true"></span>',
        esc_attr(in_array($slug, array('warszawa', 'wroclaw'), true) ? $slug : '')
    );
}

add_action('manage_post_posts_custom_column', 'akademiata_news_city_admin_column_inline_slug', 20, 2);

/**
 * News cities checkboxes in Quick Edit (Szybka edycja).
 */
function akademiata_news_city_admin_quick_edit_box($column, $post_type) {
    if ($column !== 'taxonomy-news_city' || $post_type !== 'post') {
        return;
    }

    $choices = akademiata_news_city_admin_checkbox_terms();
    if (empty($choices)) {
        return;
    }

    echo '<fieldset class="inline-edit-col-right inline-edit-news-city">';
    echo '<div class="inline-edit-col">';
    echo '<span class="title">' . esc_html__('News cities', 'akademiata') . '</span>';
    echo '<ul class="cat-checklist akademiata-news-city-checklist">';

    foreach ($choices as $slug => $term) {
        printf(
            '<li><label class="selectit"><input type="checkbox" name="tax_input[news_city][]" value="%1$d" data-slug="%2$s"> %3$s</label></li>',
            (int) $term->term_id,
            esc_attr($slug),
            esc_html($term->name)
        );
    }

    echo '</ul></div></fieldset>';
}

add_action('quick_edit_custom_box', 'akademiata_news_city_admin_quick_edit_box', 10, 2);

function akademiata_news_city_admin_posts_list_script($hook) {
    if ($hook !== 'edit.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'post') {
        return;
    }

    wp_enqueue_script('jquery');
    wp_add_inline_script(
        'jquery',
        "(function ($) {
            if (typeof inlineEditPost === 'undefined') {
                return;
            }
            var wpInlineEdit = inlineEditPost.edit;
            inlineEditPost.edit = function (id) {
                wpInlineEdit.apply(this, arguments);
                var postId = 0;
                if (typeof id === 'object') {
                    postId = parseInt(inlineEditPost.getId(id), 10);
                }
                if (!postId) {
                    return;
                }
                var slug = $('#post-' + postId).find('.akademiata-news-city-slug').data('slug') || '';
                var \$edit = $('#edit-' + postId);
                \$edit.find('.akademiata-news-city-checklist input[type=checkbox]').prop('checked', false);
                if (slug) {
                    \$edit.find('.akademiata-news-city-checklist input[data-slug=\"' + slug + '\"]').prop('checked', true);
                }
            };
        })(jQuery);"
    );
}

add_action('admin_enqueue_scripts', 'akademiata_news_city_admin_posts_list_script');

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
