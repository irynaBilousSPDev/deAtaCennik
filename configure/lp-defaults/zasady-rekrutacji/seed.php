<?php

/**
 * Seed Zasady rekrutacji ACF from theme defaults so admins can edit in WP.
 *
 * Default button = merge: keep existing ACF values, fill only empty fields / empty repeaters.
 * Optional replace = full overwrite from theme defaults.
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
 * @param int    $post_id
 * @param string $mode    'merge' | 'replace' | 'auto'
 * @return bool True when fields were written.
 */
function akademiata_zasady_rekrutacji_seed_post(int $post_id, string $mode = 'merge'): bool {
	if ($post_id <= 0 || !akademiata_zasady_rekrutacji_is_template_page($post_id)) {
		return false;
	}
	if (!function_exists('update_field') || !function_exists('akademiata_lp_merge_defaults')) {
		return false;
	}

	if ($mode === 'auto' && akademiata_zasady_rekrutacji_has_seeded_content($post_id)) {
		return false;
	}

	$defaults = akademiata_zasady_rekrutacji_acf_seed_payload();
	$replace  = ($mode === 'replace');

	foreach ($defaults as $field_name => $default_val) {
		if (!is_array($default_val)) {
			continue;
		}

		if ($replace) {
			update_field($field_name, $default_val, $post_id);
			continue;
		}

		$existing = get_field($field_name, $post_id);
		$merged   = akademiata_lp_merge_defaults(
			$default_val,
			is_array($existing) ? $existing : null
		);
		update_field($field_name, $merged, $post_id);
	}

	update_post_meta($post_id, '_rekr_defaults_seeded', '1');
	update_post_meta($post_id, '_rekr_defaults_seeded_at', gmdate('c'));
	update_post_meta($post_id, '_rekr_defaults_seed_mode', $replace ? 'replace' : 'merge');

	return true;
}

/**
 * Auto-seed empty page / handle metabox actions.
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

	if (!empty($_GET['rekr_seed']) && !empty($_GET['_wpnonce'])
		&& wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'rekr_seed_' . $post_id)
	) {
		$raw  = sanitize_text_field(wp_unslash($_GET['rekr_seed']));
		$mode = ($raw === 'replace') ? 'replace' : 'merge';
		$written = akademiata_zasady_rekrutacji_seed_post($post_id, $mode);
		set_transient(
			'rekr_seed_notice_' . get_current_user_id(),
			$written ? $mode : 'noop',
			45
		);
		wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
		exit;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($screen && $screen->base === 'post' && $screen->post_type === 'page') {
		if (akademiata_zasady_rekrutacji_seed_post($post_id, 'auto')) {
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
	akademiata_zasady_rekrutacji_seed_post($post_id, 'auto');
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

	if ($flag === 'merge') {
		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__('Zasady rekrutacji: uzupełniono tylko puste pola / puste repeatery. Istniejąca treść została zachowana.', 'akademiata')
			. '</p></div>';
		return;
	}
	if ($flag === 'replace') {
		echo '<div class="notice notice-warning is-dismissible"><p>'
			. esc_html__('Zasady rekrutacji: nadpisano całą treść ACF domyślną z motywu.', 'akademiata')
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
 * Side meta box.
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
	$merge_url = wp_nonce_url(
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

	$replace_url = wp_nonce_url(
		add_query_arg(
			[
				'post'      => (int) $post->ID,
				'action'    => 'edit',
				'rekr_seed' => 'replace',
			],
			admin_url('post.php')
		),
		'rekr_seed_' . (int) $post->ID
	);

	echo '<p>' . esc_html__('Nowe / puste pola i repeatery uzupełnij z motywu — już wypełnione treści NIE zostaną nadpisane.', 'akademiata') . '</p>';
	echo '<p><a class="button button-primary" href="' . esc_url($merge_url) . '">'
		. esc_html__('Uzupełnij puste pola', 'akademiata')
		. '</a></p>';
	echo '<p class="description">'
		. esc_html__('Pełne nadpisanie (kasuje ręczne zmiany):', 'akademiata')
		. ' <a href="' . esc_url($replace_url) . '" onclick="return confirm(\''
		. esc_js(__('Na pewno nadpisać CAŁĄ treść ACF domyślną z motywu?', 'akademiata'))
		. '\');">'
		. esc_html__('Wczytaj wszystko od nowa', 'akademiata')
		. '</a></p>';
	if (akademiata_zasady_rekrutacji_has_seeded_content((int) $post->ID)) {
		echo '<p class="description">' . esc_html__('Status: treść domyślna już była wczytywana.', 'akademiata') . '</p>';
	}
}
