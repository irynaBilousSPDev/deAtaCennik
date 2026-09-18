<?php
/**
 * Dynamically Render ACF Components.
 *
 * @param string $component_type The type of ACF component.
 * @param array $data The component's ACF data.
 */
function render_acf_component($component_type, $data = array()) {
    $component_path = get_template_directory() . "/acf-components/acf-{$component_type}.php";

    if (file_exists($component_path)) {
        include $component_path;
    } else {
        echo "<!-- Component {$component_type} not found: {$component_path} -->";
    }
}

/**
 * Automatically Load All ACF Components
 */
function load_acf_components() {
    $component_dir = get_template_directory() . "/acf-components/";

    if (!is_dir($component_dir)) {
        return;
    }

    foreach (glob($component_dir . "acf-*.php") as $file) {
        require_once $file;
    }
}

add_action('init', 'load_acf_components');

// LP O Uczelni: defaults helpers (merge when ACF empty).
require_once get_template_directory() . '/configure/lp-defaults/o-uczelni/fields.php';

/**
 * Button: Component is reused in sliders/offers — not rendered on single posts.
 */
function akademiata_is_button_component_field_group($group) {
    $title = isset($group['title']) ? (string) $group['title'] : '';

    return ($title === 'Button: Component');
}

function akademiata_should_hide_button_component_on_post_screen() {
    if (!is_admin() || !function_exists('get_current_screen')) {
        return false;
    }

    $screen = get_current_screen();

    return ($screen && $screen->base === 'post' && $screen->post_type === 'post');
}

function akademiata_hide_button_component_on_posts($field_groups) {
    if (!akademiata_should_hide_button_component_on_post_screen()) {
        return $field_groups;
    }

    foreach ($field_groups as $index => $group) {
        if (akademiata_is_button_component_field_group($group)) {
            unset($field_groups[ $index ]);
        }
    }

    return array_values($field_groups);
}

add_filter('acf/load_field_groups', 'akademiata_hide_button_component_on_posts');

/**
 * Podcast sign-up form: populate the select with available Contact Form 7 forms.
 */
function akademiata_acf_load_cf7_forms($field) {
    $field['choices'] = array();

    if (post_type_exists('wpcf7_contact_form')) {
        $forms = get_posts(array(
            'post_type'        => 'wpcf7_contact_form',
            'posts_per_page'   => -1,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => false,
        ));

        foreach ($forms as $form) {
            $field['choices'][ $form->ID ] = sprintf('%s (#%d)', $form->post_title, $form->ID);
        }
    }

    return $field;
}

add_filter('acf/load_field/key=field_pod_signup_form_id', 'akademiata_acf_load_cf7_forms');
add_filter('acf/load_field/key=field_szk_signup_form_id', 'akademiata_acf_load_cf7_forms');

/**
 * Szkolenia signup: event meta for CF7 hidden fields / mail.
 *
 * @param int $post_id
 * @return array{nazwa:string,data:string,godzina:string,tryb:string}
 */
function akademiata_szk_cf7_event_meta($post_id) {
	$post_id = (int) $post_id;
	$nazwa   = '';
	$data    = '';
	$godzina = '';
	$tryb    = '';

	if ($post_id > 0 && get_post_type($post_id) === 'szkolenia') {
		$hero = function_exists('get_field') ? trim((string) get_field('szk_hero_title', $post_id)) : '';
		$nazwa = $hero !== '' ? $hero : get_the_title($post_id);
		$nazwa = preg_replace('/\s+/u', ' ', str_replace(array("\r", "\n"), ' ', $nazwa));
		$data    = function_exists('get_field') ? trim((string) get_field('szk_details_date', $post_id)) : '';
		$godzina = function_exists('get_field') ? trim((string) get_field('szk_details_time', $post_id)) : '';
		$tryb    = function_exists('get_field') ? trim((string) get_field('szk_details_place', $post_id)) : '';
	}

	return array(
		'nazwa'   => (string) $nazwa,
		'data'    => (string) $data,
		'godzina' => (string) $godzina,
		'tryb'    => (string) $tryb,
	);
}

/**
 * Sanitize a value for a CF7 [hidden name "value"] default.
 */
function akademiata_szk_cf7_escape_hidden_value($value) {
	$value = (string) $value;
	$value = str_replace(array('"', '[', ']'), '', $value);
	return $value;
}

/**
 * Inject hidden CF7 fields (szkolenie / data / godzina / tryb) on the landing form.
 */
function akademiata_szk_cf7_inject_hidden_fields($properties, $contact_form) {
	if (is_admin() || !is_singular('szkolenia') || !is_array($properties)) {
		return $properties;
	}

	$post_id = get_queried_object_id();
	$form_id = function_exists('get_field') ? (int) get_field('szk_signup_form_id', $post_id) : 0;
	if ($form_id <= 0 || (int) $contact_form->id() !== $form_id) {
		return $properties;
	}

	$meta = akademiata_szk_cf7_event_meta($post_id);
	$block = sprintf(
		"\n[hidden szk-nazwa \"%s\"]\n[hidden szk-data \"%s\"]\n[hidden szk-godzina \"%s\"]\n[hidden szk-tryb \"%s\"]\n",
		akademiata_szk_cf7_escape_hidden_value($meta['nazwa']),
		akademiata_szk_cf7_escape_hidden_value($meta['data']),
		akademiata_szk_cf7_escape_hidden_value($meta['godzina']),
		akademiata_szk_cf7_escape_hidden_value($meta['tryb'])
	);

	$form = isset($properties['form']) ? (string) $properties['form'] : '';
	if (strpos($form, 'szk-nazwa') === false) {
		$properties['form'] = rtrim($form) . $block;
	}

	return $properties;
}
add_filter('wpcf7_contact_form_properties', 'akademiata_szk_cf7_inject_hidden_fields', 10, 2);

/**
 * Append szkolenie details to CF7 mail body (admin notification).
 */
function akademiata_szk_cf7_append_mail_meta($components, $contact_form, $mail = null) {
	if (!is_array($components) || empty($components['body'])) {
		return $components;
	}
	// Admin notification only (not auto-reply mail_2).
	if (is_object($mail) && method_exists($mail, 'name') && $mail->name() !== 'mail') {
		return $components;
	}

	$post_id = 0;
	if (class_exists('WPCF7_Submission')) {
		$submission = WPCF7_Submission::get_instance();
		if ($submission) {
			$post_id = (int) $submission->get_meta('container_post_id');
		}
	}
	if ($post_id <= 0 && !empty($_POST['_wpcf7_container_post'])) {
		$post_id = absint(wp_unslash($_POST['_wpcf7_container_post']));
	}
	if ($post_id <= 0 || get_post_type($post_id) !== 'szkolenia') {
		return $components;
	}

	$expected = function_exists('get_field') ? (int) get_field('szk_signup_form_id', $post_id) : 0;
	if ($expected > 0 && (int) $contact_form->id() !== $expected) {
		return $components;
	}

	$meta = akademiata_szk_cf7_event_meta($post_id);
	if ($meta['nazwa'] === '' && $meta['data'] === '' && $meta['godzina'] === '' && $meta['tryb'] === '') {
		return $components;
	}

	$marker = '--- Szkolenie / webinar ---';
	if (strpos((string) $components['body'], $marker) !== false) {
		return $components;
	}

	$components['body'] .= "\n\n" . $marker . "\n"
		. 'Nazwa: ' . $meta['nazwa'] . "\n"
		. 'Data: ' . $meta['data'] . "\n"
		. 'Godzina: ' . $meta['godzina'] . "\n"
		. 'Tryb: ' . $meta['tryb'] . "\n";

	return $components;
}
add_filter('wpcf7_mail_components', 'akademiata_szk_cf7_append_mail_meta', 10, 3);

/**
 * Optional mail tags: [_szk_nazwa] [_szk_data] [_szk_godzina] [_szk_tryb]
 */
function akademiata_szk_cf7_special_mail_tags($output, $name, $html = false) {
	$map = array(
		'_szk_nazwa'   => 'nazwa',
		'_szk_data'    => 'data',
		'_szk_godzina' => 'godzina',
		'_szk_tryb'    => 'tryb',
	);
	if (!isset($map[ $name ])) {
		return $output;
	}

	$post_id = 0;
	if (class_exists('WPCF7_Submission')) {
		$submission = WPCF7_Submission::get_instance();
		if ($submission) {
			$post_id = (int) $submission->get_meta('container_post_id');
		}
	}
	if ($post_id <= 0 && !empty($_POST['_wpcf7_container_post'])) {
		$post_id = absint(wp_unslash($_POST['_wpcf7_container_post']));
	}

	$meta = akademiata_szk_cf7_event_meta($post_id);
	return $meta[ $map[ $name ] ];
}
add_filter('wpcf7_special_mail_tags', 'akademiata_szk_cf7_special_mail_tags', 10, 3);

/**
 * ACF local JSON — field groups per page template (acf-json/).
 */
function akademiata_acf_json_save_path(): string {
    return get_template_directory() . '/acf-json';
}

function akademiata_acf_json_load_paths(array $paths): array {
    $paths[] = get_template_directory() . '/acf-json';

    return $paths;
}

add_filter('acf/settings/save_json', 'akademiata_acf_json_save_path');
add_filter('acf/settings/load_json', 'akademiata_acf_json_load_paths');

/**
 * Large Front: Page ACF forms (slider + promos) need more than default 1000.
 */
function akademiata_acf_raise_input_vars(): void {
	if ( ! is_admin() ) {
		return;
	}
	$current = (int) ini_get( 'max_input_vars' );
	if ( $current > 0 && $current < 5000 ) {
		@ini_set( 'max_input_vars', '5000' );
	}
}
add_action( 'admin_init', 'akademiata_acf_raise_input_vars', 1 );

/**
 * Orphan "Front: Promocje" (same home_promos name) overwrites cards on save — hide + trash.
 */
function akademiata_acf_is_orphan_front_promos_group( $group ): bool {
	if ( ! is_array( $group ) ) {
		return false;
	}
	$key   = (string) ( $group['key'] ?? '' );
	$title = (string) ( $group['title'] ?? '' );

	return $key === 'group_front_promos' || $title === 'Front: Promocje';
}

function akademiata_acf_hide_orphan_front_promos( $field_groups ) {
	if ( ! is_array( $field_groups ) ) {
		return $field_groups;
	}

	return array_values(
		array_filter(
			$field_groups,
			static function ( $group ) {
				return ! akademiata_acf_is_orphan_front_promos_group( $group );
			}
		)
	);
}
add_filter( 'acf/load_field_groups', 'akademiata_acf_hide_orphan_front_promos', 20 );

function akademiata_acf_trash_orphan_front_promos_group(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! function_exists( 'acf_get_field_group' ) ) {
		return;
	}
	if ( get_option( 'akademiata_trashed_front_promos_group' ) ) {
		return;
	}

	$group = acf_get_field_group( 'group_front_promos' );
	if ( ! is_array( $group ) || empty( $group['ID'] ) ) {
		// Mark done even if already gone — avoid repeat lookups.
		update_option( 'akademiata_trashed_front_promos_group', 1, false );
		return;
	}

	wp_trash_post( (int) $group['ID'] );
	update_option( 'akademiata_trashed_front_promos_group', 1, false );
}
add_action( 'admin_init', 'akademiata_acf_trash_orphan_front_promos_group', 5 );
