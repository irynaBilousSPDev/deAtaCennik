<?php

/**
 * Seed Zasady rekrutacji ACF from theme defaults so admins can edit in WP.
 */

/**
 * @return array<string, array<string, mixed>>
 */
function akademiata_zasady_rekrutacji_acf_seed_payload(): array {
	return akademiata_zasady_rekrutacji_defaults();
}

/**
 * @param int $post_id
 */
function akademiata_zasady_rekrutacji_is_template_page(int $post_id): bool {
	return get_page_template_slug($post_id) === 'page-zasady-rekrutacji.php';
}

/**
 * @param int $post_id
 */
function akademiata_zasady_rekrutacji_has_seeded_content(int $post_id): bool {
	return (string) get_post_meta($post_id, '_rekr_defaults_seeded', true) === '1';
}

/**
 * @param int  $post_id
 * @param bool $force
 * @return bool True when fields were written.
 */
function akademiata_zasady_rekrutacji_seed_post(int $post_id, bool $force = false): bool {
	if ($post_id <= 0 || !akademiata_zasady_rekrutacji_is_template_page($post_id)) {
		return false;
	}
	if (!function_exists('update_field')) {
		return false;
	}
	if (!$force && akademiata_zasady_rekrutacji_has_seeded_content($post_id)) {
		return false;
	}

	if (!$force) {
		$hero = get_field('rekr_hero_section', $post_id);
		$nav  = get_field('rekr_quick_nav', $post_id);
		$has_content = (is_array($hero) && (!empty($hero['lead']) || !empty($hero['title'])))
			|| (is_array($nav) && !empty($nav['links']));
		if ($has_content) {
			update_post_meta($post_id, '_rekr_defaults_seeded', '1');
			return false;
		}
	}

	$payload = akademiata_zasady_rekrutacji_acf_seed_payload();
	foreach ($payload as $field_name => $value) {
		update_field($field_name, $value, $post_id);
	}

	update_post_meta($post_id, '_rekr_defaults_seeded', '1');
	update_post_meta($post_id, '_rekr_defaults_seeded_at', gmdate('c'));

	return true;
}

/**
 * Auto-seed empty page / handle force reload from metabox.
 */
function akademiata_zasady_rekrutacji_admin_maybe_seed(): void {
	if (!is_admin() || !current_user_can('edit_pages')) {
		return;
	}

	$post_id = 0;
	if (!empty($_GET['post'])) {
		$post_id = (int) $_GET['post'];
	} elseif (!empty($_POST['post_ID'])) {
		$post_id = (int) $_POST['post_ID'];
	}

	if ($post_id <= 0) {
		return;
	}

	if (!empty($_GET['rekr_seed']) && $_GET['rekr_seed'] === '1' && !empty($_GET['_wpnonce'])
		&& wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'rekr_seed_' . $post_id)
	) {
		$written = akademiata_zasady_rekrutacji_seed_post($post_id, true);
		set_transient('rekr_seed_notice_' . get_current_user_id(), $written ? 'forced' : 'noop', 45);
		wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
		exit;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($screen && $screen->base === 'post' && $screen->post_type === 'page') {
		if (akademiata_zasady_rekrutacji_seed_post($post_id, false)) {
			set_transient('rekr_seed_notice_' . get_current_user_id(), 'auto', 45);
		}
	}
}
add_action('admin_init', 'akademiata_zasady_rekrutacji_admin_maybe_seed', 30);

/**
 * @param int $post_id
 */
function akademiata_zasady_rekrutacji_seed_on_save(int $post_id): void {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (wp_is_post_revision($post_id)) {
		return;
	}
	akademiata_zasady_rekrutacji_seed_post($post_id, false);
}
add_action('save_post_page', 'akademiata_zasady_rekrutacji_seed_on_save', 40);

/**
 * Admin notice after seed.
 */
function akademiata_zasady_rekrutacji_seed_admin_notice(): void {
	$key  = 'rekr_seed_notice_' . get_current_user_id();
	$flag = get_transient($key);
	if (!$flag) {
		return;
	}
	delete_transient($key);

	if ($flag === 'forced') {
		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__('Zasady rekrutacji: wczytano domyślną treść z motywu. Możesz ją edytować i zapisać.', 'akademiata')
			. '</p></div>';
		return;
	}
	if ($flag === 'auto') {
		echo '<div class="notice notice-info is-dismissible"><p>'
			. esc_html__('Zasady rekrutacji: uzupełniono puste pola domyślną treścią z motywu. Edytuj i zapisz stronę.', 'akademiata')
			. '</p></div>';
	}
}
add_action('admin_notices', 'akademiata_zasady_rekrutacji_seed_admin_notice');

/**
 * Side meta box: reload defaults.
 */
function akademiata_zasady_rekrutacji_seed_metabox(): void {
	$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
	if ($post_id <= 0 || !akademiata_zasady_rekrutacji_is_template_page($post_id)) {
		return;
	}

	add_meta_box(
		'rekr_seed_defaults',
		__('Zasady rekrutacji — treść domyślna', 'akademiata'),
		'akademiata_zasady_rekrutacji_seed_metabox_render',
		'page',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'akademiata_zasady_rekrutacji_seed_metabox');

/**
 * @param WP_Post $post
 */
function akademiata_zasady_rekrutacji_seed_metabox_render($post): void {
	$url = wp_nonce_url(
		add_query_arg(
			[
				'post'      => (int) $post->ID,
				'action'    => 'edit',
				'rekr_seed' => '1',
			],
			admin_url('post.php')
		),
		'rekr_seed_' . (int) $post->ID
	);

	echo '<p>' . esc_html__('Wczytaj teksty i repeatery z motywu do ACF — potem edytujesz je w adminie.', 'akademiata') . '</p>';
	echo '<p><a class="button button-primary" href="' . esc_url($url) . '">'
		. esc_html__('Wczytaj / odśwież treść domyślną', 'akademiata')
		. '</a></p>';
	if (akademiata_zasady_rekrutacji_has_seeded_content((int) $post->ID)) {
		echo '<p class="description">' . esc_html__('Status: treść domyślna już była wczytywana.', 'akademiata') . '</p>';
	}
}
