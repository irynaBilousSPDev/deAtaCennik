<?php

/**
 * Seed O Uczelni ACF groups from theme defaults so admins see editable content.
 */

/**
 * Build ACF-ready payload (resolved static URLs, no PHP-only keys).
 *
 * @return array<string, array<string, mixed>>
 */
function akademiata_o_uczelni_acf_seed_payload(): array {
	$defaults = akademiata_o_uczelni_defaults();

	if (!empty($defaults['oucz_historia_section']['steps']) && is_array($defaults['oucz_historia_section']['steps'])) {
		foreach ($defaults['oucz_historia_section']['steps'] as $i => $step) {
			if (!empty($step['logo_key']) && empty($step['logo_url'])) {
				$defaults['oucz_historia_section']['steps'][$i]['logo_url'] = akademiata_o_uczelni_static_url((string) $step['logo_key']);
			}
			unset($defaults['oucz_historia_section']['steps'][$i]['logo_key']);
			$defaults['oucz_historia_section']['steps'][$i]['logo'] = null;
		}
	}

	if (!empty($defaults['oucz_wspolpraca_section']['partners']) && is_array($defaults['oucz_wspolpraca_section']['partners'])) {
		foreach ($defaults['oucz_wspolpraca_section']['partners'] as $i => $partner) {
			unset($defaults['oucz_wspolpraca_section']['partners'][$i]['theme_key']);
			$defaults['oucz_wspolpraca_section']['partners'][$i]['image'] = null;
		}
	}

	if (!empty($defaults['oucz_infra_section']['buildings']) && is_array($defaults['oucz_infra_section']['buildings'])) {
		foreach ($defaults['oucz_infra_section']['buildings'] as $bi => $building) {
			if (empty($building['gallery']) || !is_array($building['gallery'])) {
				continue;
			}
			foreach ($building['gallery'] as $gi => $img) {
				unset($defaults['oucz_infra_section']['buildings'][$bi]['gallery'][$gi]['theme_key']);
				$defaults['oucz_infra_section']['buildings'][$bi]['gallery'][$gi]['image'] = null;
			}
		}
	}

	$kim = &$defaults['oucz_kim_section'];
	$kim['badge_image'] = null;
	$kim['logo_image_old'] = null;
	$kim['logo_image_new'] = null;
	unset($kim['logo_image'], $kim['logo_image_url'], $kim['logo_image_alt']);

	$defaults['oucz_absolwenci_section']['badge_image'] = null;
	$defaults['oucz_hero_section']['bg_image'] = null;

	return $defaults;
}

/**
 * @param int $post_id
 */
function akademiata_o_uczelni_is_template_page(int $post_id): bool {
	return get_page_template_slug($post_id) === 'page-o-uczelni.php';
}

/**
 * @param int $post_id
 */
function akademiata_o_uczelni_has_seeded_content(int $post_id): bool {
	return (string) get_post_meta($post_id, '_oucz_defaults_seeded', true) === '1';
}

/**
 * @param int  $post_id
 * @param bool $force
 * @return bool True when fields were written.
 */
function akademiata_o_uczelni_seed_post(int $post_id, bool $force = false): bool {
	if ($post_id <= 0 || !akademiata_o_uczelni_is_template_page($post_id)) {
		return false;
	}
	if (!function_exists('update_field')) {
		return false;
	}
	if (!$force && akademiata_o_uczelni_has_seeded_content($post_id)) {
		return false;
	}

	// Don't overwrite existing editor work unless forced.
	if (!$force) {
		$hero = get_field('oucz_hero_section', $post_id);
		$subnav = get_field('oucz_subnav', $post_id);
		$has_content = (is_array($hero) && (!empty($hero['lead']) || !empty($hero['title_accent'])))
			|| (is_array($subnav) && !empty($subnav['links']));
		if ($has_content) {
			update_post_meta($post_id, '_oucz_defaults_seeded', '1');
			return false;
		}
	}

	$payload = akademiata_o_uczelni_acf_seed_payload();
	foreach ($payload as $field_name => $value) {
		update_field($field_name, $value, $post_id);
	}

	update_post_meta($post_id, '_oucz_defaults_seeded', '1');
	update_post_meta($post_id, '_oucz_defaults_seeded_at', gmdate('c'));

	return true;
}

/**
 * Auto-seed when opening / saving a page with this template.
 */
function akademiata_o_uczelni_admin_maybe_seed(): void {
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

	if (!empty($_GET['oucz_seed']) && $_GET['oucz_seed'] === '1' && !empty($_GET['_wpnonce'])
		&& wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'oucz_seed_' . $post_id)
	) {
		$written = akademiata_o_uczelni_seed_post($post_id, true);
		set_transient('oucz_seed_notice_' . get_current_user_id(), $written ? 'forced' : 'noop', 45);
		wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
		exit;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($screen && $screen->base === 'post' && $screen->post_type === 'page') {
		if (akademiata_o_uczelni_seed_post($post_id, false)) {
			set_transient('oucz_seed_notice_' . get_current_user_id(), 'auto', 45);
		}
	}
}
add_action('admin_init', 'akademiata_o_uczelni_admin_maybe_seed', 30);

/**
 * Also seed after template is assigned on save.
 *
 * @param int $post_id
 */
function akademiata_o_uczelni_seed_on_save(int $post_id): void {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (wp_is_post_revision($post_id)) {
		return;
	}
	akademiata_o_uczelni_seed_post($post_id, false);
}
add_action('save_post_page', 'akademiata_o_uczelni_seed_on_save', 40);

/**
 * Admin notice after seed.
 */
function akademiata_o_uczelni_seed_admin_notice(): void {
	$key = 'oucz_seed_notice_' . get_current_user_id();
	$flag = get_transient($key);
	if (!$flag) {
		return;
	}
	delete_transient($key);

	if ($flag === 'forced') {
		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__('O Uczelni: wczytano domyślną treść z motywu. Możesz ją edytować i zapisać.', 'akademiata')
			. '</p></div>';
		return;
	}
	if ($flag === 'auto') {
		echo '<div class="notice notice-info is-dismissible"><p>'
			. esc_html__('O Uczelni: uzupełniono puste pola domyślną treścią z motywu (teksty i URL-e zdjęć). Edytuj i zapisz stronę.', 'akademiata')
			. '</p></div>';
	}
}
add_action('admin_notices', 'akademiata_o_uczelni_seed_admin_notice');

/**
 * Side meta box: reload defaults.
 */
function akademiata_o_uczelni_seed_metabox(): void {
	add_meta_box(
		'oucz_seed_defaults',
		__('O Uczelni — treść domyślna', 'akademiata'),
		'akademiata_o_uczelni_seed_metabox_render',
		'page',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'akademiata_o_uczelni_seed_metabox');

/**
 * @param WP_Post $post
 */
function akademiata_o_uczelni_seed_metabox_render($post): void {
	if (!akademiata_o_uczelni_is_template_page((int) $post->ID)) {
		echo '<p>' . esc_html__('Ustaw szablon „O Uczelni”, aby wczytać treść domyślną.', 'akademiata') . '</p>';
		return;
	}

	$url = wp_nonce_url(
		add_query_arg(
			[
				'post' => (int) $post->ID,
				'action' => 'edit',
				'oucz_seed' => '1',
			],
			admin_url('post.php')
		),
		'oucz_seed_' . (int) $post->ID
	);

	echo '<p>' . esc_html__('Wszystkie sekcje (teksty, repeatery, URL-e zdjęć z motywu) można wczytać do ACF i potem edytować w adminie.', 'akademiata') . '</p>';
	echo '<p><a class="button button-primary" href="' . esc_url($url) . '">'
		. esc_html__('Wczytaj / odśwież treść domyślną', 'akademiata')
		. '</a></p>';
	if (akademiata_o_uczelni_has_seeded_content((int) $post->ID)) {
		echo '<p class="description">' . esc_html__('Status: treść domyślna już była wczytywana.', 'akademiata') . '</p>';
	}
}
