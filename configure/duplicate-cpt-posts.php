<?php function duplicate_cpt_post_as_draft() {
    global $wpdb;

    if (!isset($_GET['post']) || !isset($_REQUEST['action']) || 'duplicate_cpt_post_as_draft' !== $_REQUEST['action']) {
        wp_die('No post to duplicate has been supplied!');
    }

    $post_id = absint($_GET['post']);

    if (!$post_id) {
        wp_die('Invalid post ID.');
    }

    // Get the original post
    $post = get_post($post_id);

    if (!$post) {
        wp_die('Post not found.');
    }

    // Check user capability
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to duplicate this post.');
    }

    // Duplicate post data
    $new_post_data = array(
        'post_title'   => $post->post_title . ' (Copy)',
        'post_content' => $post->post_content,
        'post_status'  => 'draft',
        'post_type'    => $post->post_type,
        'post_author'  => get_current_user_id(),
    );

    // Insert new post
    $new_post_id = wp_insert_post($new_post_data);

    if ($new_post_id) {
        // Copy taxonomies
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy);
        }

        // Copy metadata
        $post_meta = get_post_meta($post_id);
        foreach ($post_meta as $meta_key => $meta_values) {
            foreach ($meta_values as $meta_value) {
                update_post_meta($new_post_id, $meta_key, $meta_value);
            }
        }

        // Redirect to edit the duplicated post
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    } else {
        wp_die('Error: Could not duplicate the post.');
    }
}
add_action('admin_action_duplicate_cpt_post_as_draft', 'duplicate_cpt_post_as_draft');

function add_duplicate_cpt_link($actions, $post) {
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="' . wp_nonce_url(admin_url('admin.php?action=duplicate_cpt_post_as_draft&post=' . $post->ID), 'duplicate_cpt_post_as_draft') . '" title="Duplicate this post" rel="permalink">Duplicate</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'add_duplicate_cpt_link', 10, 2);
add_filter('page_row_actions', 'add_duplicate_cpt_link', 10, 2);


