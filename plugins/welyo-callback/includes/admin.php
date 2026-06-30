<?php
/**
 * Panel ustawień Welyo Callback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'welyo_admin_register_menu' );
add_action( 'admin_init', 'welyo_admin_register_settings' );
add_action( 'admin_init', 'welyo_admin_register_plugin_links' );

function welyo_admin_register_plugin_links() {
	add_filter( 'plugin_action_links_' . WELYO_CALLBACK_BASENAME, 'welyo_admin_plugin_links' );
}

function welyo_admin_register_menu() {
	add_menu_page(
		__( 'Welyo Callback', 'akademiata' ),
		__( 'Welyo Callback', 'akademiata' ),
		'manage_options',
		'welyo-callback',
		'welyo_admin_render_page',
		'dashicons-phone',
		58
	);
}

function welyo_admin_plugin_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=welyo-callback' ) ) . '">' . esc_html__( 'Ustawienia', 'akademiata' ) . '</a>';
	return $links;
}

function welyo_admin_register_settings() {
	register_setting(
		'welyo_callback',
		WELYO_OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'welyo_admin_save_settings',
			'default'           => welyo_default_settings(),
		)
	);
}

function welyo_admin_save_settings( $input ) {
	$settings = welyo_sanitize_settings( is_array( $input ) ? $input : array() );

	if ( ! empty( $_POST['welyo_write_config_file'] ) ) {
		$written = welyo_write_config_file( $settings );
		if ( $written ) {
			add_settings_error( 'welyo_callback', 'welyo_config_written', __( 'Zapisano ustawienia i wygenerowano wp-content/welyo-config.php.', 'akademiata' ), 'success' );
		} else {
			add_settings_error( 'welyo_callback', 'welyo_config_failed', __( 'Ustawienia zapisane, ale nie udało się zapisać wp-content/welyo-config.php (uprawnienia?).', 'akademiata' ), 'warning' );
		}
	} else {
		add_settings_error( 'welyo_callback', 'welyo_saved', __( 'Ustawienia zapisane.', 'akademiata' ), 'success' );
	}

	delete_transient( 'welyo_campaign_id' );
	delete_transient( 'welyo_classifier_id' );
	welyo_flush_settings_cache();

	return $settings;
}

function welyo_admin_field_secret( $key, $label, $settings, $args = array() ) {
	$desc      = isset( $args['desc'] ) ? $args['desc'] : '';
	$wide      = ! empty( $args['wide'] );
	$has_value = welyo_has_stored_secret( $key );
	$field_id  = 'welyo_' . $key;
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<div class="welyo-secret-wrap">
				<input
					type="password"
					class="<?php echo $wide ? 'large-text' : 'regular-text'; ?>"
					id="<?php echo esc_attr( $field_id ); ?>"
					name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
					value=""
					autocomplete="new-password"
					spellcheck="false"
					<?php if ( $has_value ) : ?>
						placeholder="<?php echo esc_attr( str_repeat( '•', 16 ) ); ?>"
					<?php endif; ?>
				>
				<button type="button" class="button button-secondary welyo-toggle-secret" data-target="<?php echo esc_attr( $field_id ); ?>" aria-pressed="false">
					<?php esc_html_e( 'Pokaż', 'akademiata' ); ?>
				</button>
			</div>
			<p class="description" id="<?php echo esc_attr( $field_id ); ?>_desc">
				<?php if ( $has_value ) : ?>
					<span class="welyo-secret-status welyo-secret-status--saved"><?php esc_html_e( 'Zapisano — wartość jest ukryta.', 'akademiata' ); ?></span>
					<?php esc_html_e( 'Wpisz nową tylko przy zmianie klucza.', 'akademiata' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Wpisz klucz z panelu Welyo (Administracja → Integracje).', 'akademiata' ); ?>
				<?php endif; ?>
			</p>
			<?php if ( $has_value ) : ?>
				<p class="description">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>_clear]" value="1">
						<?php esc_html_e( 'Usuń zapisany klucz', 'akademiata' ); ?>
					</label>
				</p>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function welyo_admin_field_color( $key, $label, $settings, $args = array() ) {
	$desc     = isset( $args['desc'] ) ? $args['desc'] : '';
	$defaults = welyo_default_settings();
	$value    = welyo_sanitize_color(
		isset( $settings[ $key ] ) ? $settings[ $key ] : '',
		$defaults[ $key ]
	);
	$field_id = 'welyo_' . $key;
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<div class="welyo-color-wrap">
				<input type="color" class="welyo-color-picker" id="<?php echo esc_attr( $field_id ); ?>_picker" value="<?php echo esc_attr( welyo_normalize_hex( $value ) ); ?>" aria-hidden="true" tabindex="-1">
				<input type="text" class="regular-text welyo-color-text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" maxlength="7" spellcheck="false">
			</div>
			<?php if ( $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function welyo_admin_field_text( $key, $label, $settings, $args = array() ) {
	$type  = isset( $args['type'] ) ? $args['type'] : 'text';
	$desc  = isset( $args['desc'] ) ? $args['desc'] : '';
	$wide  = ! empty( $args['wide'] );
	$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	?>
	<tr>
		<th scope="row"><label for="welyo_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<?php if ( $type === 'textarea' ) : ?>
				<textarea class="large-text" rows="3" id="welyo_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea>
			<?php else : ?>
				<input type="<?php echo esc_attr( $type ); ?>" class="<?php echo $wide ? 'large-text' : 'regular-text'; ?>" id="welyo_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function welyo_admin_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = welyo_get_settings();
	$config_path = WP_CONTENT_DIR . '/welyo-config.php';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Welyo Callback', 'akademiata' ); ?></h1>
		<p><?php esc_html_e( 'Tutaj edytujesz API Welyo, numer telefonu, godziny pracy i wszystkie teksty widgetu na stronie.', 'akademiata' ); ?></p>

		<?php settings_errors( 'welyo_callback' ); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'welyo_callback' ); ?>

			<h2 class="title"><?php esc_html_e( 'API Welyo', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'base_url', 'URL API', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'login', 'Login', $settings, array( 'desc' => 'np. login@ataedu' ) );
				welyo_admin_field_secret( 'api_key', 'Klucz API', $settings );
				welyo_admin_field_text( 'campaign_id', 'ID kampanii', $settings, array( 'desc' => 'Puste = szukaj po nazwie kampanii' ) );
				welyo_admin_field_text( 'campaign_name', 'Nazwa kampanii', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'classifier_id', 'ID klasyfikatora (recall)', $settings );
				welyo_admin_field_text( 'classifier_name', 'Nazwa klasyfikatora', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'hash_method', 'Metoda hash', $settings, array( 'desc' => 'md5 lub sha1' ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Telefon i godziny', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'phone_dial', 'Numer (tel:)', $settings );
				welyo_admin_field_text( 'phone_pretty', 'Numer (wyświetlany)', $settings );
				welyo_admin_field_text( 'open_hour', 'Godzina otwarcia', $settings, array( 'type' => 'number' ) );
				welyo_admin_field_text( 'close_hour', 'Godzina zamknięcia', $settings, array( 'type' => 'number' ) );
				welyo_admin_field_text( 'workdays', 'Dni robocze', $settings, array( 'desc' => '1=pon … 7=niedz, np. 1,2,3,4,5' ) );
				welyo_admin_field_text( 'default_prefix', 'Prefiks PL', $settings );
				welyo_admin_field_text( 'privacy_url', 'URL polityki prywatności', $settings, array( 'wide' => true ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Teksty widgetu — w godzinach pracy', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'text_launch_open', 'Przycisk pływający', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_status_open', 'Status', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_title_open', 'Tytuł', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_sub_open', 'Podtytuł', $settings, array( 'type' => 'textarea' ) );
				welyo_admin_field_text( 'text_call_btn', 'Przycisk „Zadzwoń”', $settings, array( 'wide' => true ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Teksty widgetu — po godzinach', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'text_launch_closed', 'Przycisk pływający', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_status_closed', 'Status', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_title_closed', 'Tytuł', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_sub_closed', 'Podtytuł', $settings, array( 'type' => 'textarea' ) );
				welyo_admin_field_text( 'text_name_label', 'Etykieta: imię', $settings );
				welyo_admin_field_text( 'text_name_placeholder', 'Placeholder: imię', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_phone_label', 'Etykieta: telefon', $settings );
				welyo_admin_field_text( 'text_phone_placeholder', 'Placeholder: telefon', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_consent', 'Zgoda RODO', $settings, array( 'type' => 'textarea', 'desc' => 'Dozwolony HTML. Użyj {privacy_url} jako linku do polityki.' ) );
				welyo_admin_field_text( 'text_submit', 'Przycisk wyślij', $settings, array( 'wide' => true ) );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Teksty — komunikaty', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'text_done_title', 'Sukces: tytuł', $settings );
				welyo_admin_field_text( 'text_done_scheduled', 'Sukces: oddzwonimy później', $settings, array( 'type' => 'textarea' ) );
				welyo_admin_field_text( 'text_done_immediate', 'Sukces: oddzwaniamy teraz', $settings, array( 'type' => 'textarea' ) );
				welyo_admin_field_text( 'text_footer', 'Stopka panelu', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_hours_prefix', 'Prefiks godzin', $settings, array( 'desc' => 'Przed „08:00–18:00”, np. Pon–Pt, ' ) );
				welyo_admin_field_text( 'text_error_phone', 'Błąd: telefon', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_error_consent', 'Błąd: zgoda', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_error_generic', 'Błąd: ogólny', $settings, array( 'type' => 'textarea' ) );
				welyo_admin_field_text( 'text_sending', 'Trwa wysyłanie', $settings );
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Kolory widgetu', 'akademiata' ); ?></h2>
			<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Domyślnie kolory marki Akademiata. Zmiany widać od razu po zapisaniu.', 'akademiata' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				foreach ( welyo_color_fields() as $color_key => $color_meta ) {
					welyo_admin_field_color(
						$color_key,
						$color_meta['label'],
						$settings,
						array( 'desc' => isset( $color_meta['desc'] ) ? $color_meta['desc'] : '' )
					);
				}
				?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Wyświetlanie', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Widget na stronie', 'akademiata' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[auto_footer]" value="1" <?php checked( ! empty( $settings['auto_footer'] ) ); ?>>
							<?php esc_html_e( 'Pokazuj automatycznie we wszystkich stopkach (wp_footer)', 'akademiata' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Shortcode: [welyo_callback]', 'akademiata' ); ?></p>
						<p class="description"><?php esc_html_e( 'WPML: widget pokazuje się tylko w wersji polskiej (pl). Na EN i innych językach jest ukryty.', 'akademiata' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<?php submit_button( __( 'Zapisz ustawienia', 'akademiata' ), 'primary', 'submit', false ); ?>
				<button type="submit" class="button button-secondary" name="welyo_write_config_file" value="1"><?php esc_html_e( 'Zapisz i wygeneruj welyo-config.php', 'akademiata' ); ?></button>
			</p>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Plik welyo-config.php', 'akademiata' ); ?></h2>
		<p>
			<?php
			if ( is_readable( $config_path ) ) {
				printf(
					/* translators: %s: file path */
					esc_html__( 'Na serwerze istnieje: %s', 'akademiata' ),
					'<code>wp-content/welyo-config.php</code>'
				);
			} else {
				esc_html_e( 'Brak wp-content/welyo-config.php — możesz wygenerować przyciskiem powyżej lub wgrać przez deploy (deploy.local.env).', 'akademiata' );
			}
			?>
		</p>
		<p class="description"><?php esc_html_e( 'Wartości z tego panelu działają od razu po zapisaniu. Klucz API w bazie jest szyfrowany (nie w plain text). Opcjonalnie: plik welyo-config.php lub wp-config.php mogą nadpisać wybrane pola — wtedy klucz trafia do pliku jako zwykły tekst (tylko na serwerze).', 'akademiata' ); ?></p>
	</div>
	<style>
		.welyo-secret-wrap { display:flex; align-items:center; gap:8px; max-width:36rem; }
		.welyo-secret-wrap input { flex:1 1 auto; font-family:Consolas, Monaco, monospace; }
		.welyo-secret-status--saved { display:inline-block; margin-right:6px; padding:2px 8px; border-radius:999px; background:#edfaef; color:#1f6b3a; font-weight:600; }
		.welyo-color-wrap { display:flex; align-items:center; gap:10px; max-width:20rem; }
		.welyo-color-wrap input[type="color"] { width:48px; height:36px; padding:2px; border:1px solid #8c8f94; border-radius:4px; cursor:pointer; background:#fff; }
		.welyo-color-text { font-family:Consolas, Monaco, monospace; width:7.5em; }
	</style>
	<script>
	(function () {
		var showLabel = <?php echo wp_json_encode( __( 'Pokaż', 'akademiata' ) ); ?>;
		var hideLabel = <?php echo wp_json_encode( __( 'Ukryj', 'akademiata' ) ); ?>;
		document.querySelectorAll('.welyo-toggle-secret').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var input = document.getElementById(btn.getAttribute('data-target'));
				if (!input) { return; }
				var show = input.type === 'password';
				input.type = show ? 'text' : 'password';
				btn.textContent = show ? hideLabel : showLabel;
				btn.setAttribute('aria-pressed', show ? 'true' : 'false');
			});
		});

		function normalizeHex(val) {
			if (!val) { return ''; }
			val = val.trim();
			if (val.charAt(0) !== '#') { val = '#' + val; }
			if (/^#[0-9a-fA-F]{3}$/.test(val)) {
				return ('#' + val.charAt(1) + val.charAt(1) + val.charAt(2) + val.charAt(2) + val.charAt(3) + val.charAt(3)).toLowerCase();
			}
			if (/^#[0-9a-fA-F]{6}$/.test(val)) { return val.toLowerCase(); }
			return '';
		}

		document.querySelectorAll('.welyo-color-wrap').forEach(function (wrap) {
			var picker = wrap.querySelector('.welyo-color-picker');
			var text = wrap.querySelector('.welyo-color-text');
			if (!picker || !text) { return; }
			picker.addEventListener('input', function () {
				text.value = picker.value.toLowerCase();
			});
			text.addEventListener('input', function () {
				var hex = normalizeHex(text.value);
				if (hex) { picker.value = hex; }
			});
			text.addEventListener('change', function () {
				var hex = normalizeHex(text.value);
				text.value = hex || text.value;
				if (hex) { picker.value = hex; }
			});
		});
	})();
	</script>
	<?php
}
