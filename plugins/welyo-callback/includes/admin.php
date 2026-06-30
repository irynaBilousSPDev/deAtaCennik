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
	delete_transient( 'welyo_auth_mode' );
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
			<input
				type="password"
				class="<?php echo $wide ? 'large-text' : 'regular-text'; ?> welyo-secret-input"
				id="<?php echo esc_attr( $field_id ); ?>"
				name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
				value=""
				autocomplete="new-password"
				spellcheck="false"
				<?php if ( $has_value ) : ?>
					placeholder="<?php echo esc_attr( str_repeat( '•', 16 ) ); ?>"
				<?php endif; ?>
			>
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

function welyo_admin_field_api_select( $key, $label, $settings, $args = array() ) {
	$desc     = isset( $args['desc'] ) ? $args['desc'] : '';
	$empty    = isset( $args['empty_label'] ) ? $args['empty_label'] : __( '— auto z API —', 'akademiata' );
	$value    = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
	$field_id = 'welyo_' . $key;
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<select class="regular-text welyo-api-select" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" data-welyo-api="<?php echo esc_attr( $key ); ?>">
				<option value=""><?php echo esc_html( $empty ); ?></option>
				<?php if ( $value !== '' ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" selected><?php echo esc_html( '#' . $value . ' (zapisane)' ); ?></option>
				<?php endif; ?>
			</select>
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
				?>
			</table>

			<div class="welyo-api-lists-box">
				<h3><?php esc_html_e( 'Kampania i klasyfikator z API', 'akademiata' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Pobierz listy z Welyo i wybierz z rozwijanej listy — nie musisz znać ID ani dokładnej nazwy. Puste pola = automatyczny wybór (jedyna kampania, dopasowanie słów „callback” / „rekrut” itd.).', 'akademiata' ); ?></p>
				<p>
					<button type="button" class="button button-secondary" id="welyo-load-api-lists"><?php esc_html_e( 'Pobierz listę z API', 'akademiata' ); ?></button>
					<span class="spinner" id="welyo-api-lists-spinner" style="float:none;"></span>
					<span id="welyo-api-lists-status" class="description" style="margin-left:8px;"></span>
				</p>
			</div>

			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_api_select( 'campaign_id', 'Kampania (z API)', $settings, array(
					'desc' => 'Zalecane: wybierz z listy po „Pobierz listę z API”.',
				) );
				welyo_admin_field_text( 'campaign_name', 'Nazwa kampanii (opcjonalnie)', $settings, array(
					'wide' => true,
					'desc' => 'Tylko gdy nie wybierasz ID — dopasowanie tolerancyjne do nazwy w Welyo.',
				) );
				welyo_admin_field_api_select( 'classifier_id', 'Klasyfikator recall (z API)', $settings, array(
					'desc' => 'Po godzinach — recall. Puste = auto z API lub lead bez recall.',
				) );
				welyo_admin_field_text( 'classifier_name', 'Nazwa klasyfikatora (opcjonalnie)', $settings, array(
					'wide' => true,
					'desc' => 'Tylko gdy nie wybierasz ID — np. Lead WWW – oddzwonić.',
				) );
				welyo_admin_field_text( 'hash_method', 'Metoda hash', $settings, array( 'desc' => 'md5 lub sha1' ) );
				?>
			</table>

			<div class="welyo-diagnostics-box">
				<h3><?php esc_html_e( 'Test połączenia', 'akademiata' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Sprawdza login, klucz API, JWT, kampanię i klasyfikator — bez wysyłania testowego leada.', 'akademiata' ); ?></p>
				<p>
					<button type="button" class="button button-secondary" id="welyo-run-diagnostics"><?php esc_html_e( 'Sprawdź połączenie z Welyo', 'akademiata' ); ?></button>
					<span class="spinner" id="welyo-diagnostics-spinner" style="float:none;"></span>
				</p>
				<ul id="welyo-diagnostics-results" class="welyo-diagnostics-results" hidden></ul>
			</div>

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
				welyo_admin_field_text( 'text_error_auth', 'Błąd: logowanie Welyo', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_error_campaign', 'Błąd: kampania', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_error_welyo', 'Błąd: odrzucenie przez Welyo', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_error_rate', 'Błąd: limit prób', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'text_error_nonce', 'Błąd: wygasła sesja', $settings, array( 'wide' => true ) );
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
		.welyo-secret-input { max-width:36rem; font-family:Consolas, Monaco, monospace; }
		.welyo-secret-status--saved { display:inline-block; margin-right:6px; padding:2px 8px; border-radius:999px; background:#edfaef; color:#1f6b3a; font-weight:600; }
		.welyo-diagnostics-box { margin:0 0 24px; padding:16px 18px; max-width:48rem; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; }
		.welyo-api-lists-box { margin:0 0 16px; padding:16px 18px; max-width:48rem; background:#f0f6fc; border:1px solid #c3d9ed; border-radius:4px; }
		.welyo-api-lists-box h3 { margin:0 0 8px; }
		.welyo-diagnostics-box h3 { margin:0 0 8px; }
		.welyo-diagnostics-results { margin:12px 0 0; padding-left:0; list-style:none; }
		.welyo-diagnostics-results li { margin:0 0 8px; padding:8px 12px; border-radius:4px; }
		.welyo-diagnostics-results li.is-ok { background:#edfaef; color:#1f6b3a; }
		.welyo-diagnostics-results li.is-fail { background:#fcf0f1; color:#8a2424; }
		.welyo-color-wrap { display:flex; align-items:center; gap:10px; max-width:20rem; }
		.welyo-color-wrap input[type="color"] { width:48px; height:36px; padding:2px; border:1px solid #8c8f94; border-radius:4px; cursor:pointer; background:#fff; }
		.welyo-color-text { font-family:Consolas, Monaco, monospace; width:7.5em; }
	</style>
	<script>
	(function () {
		var restBase = <?php echo wp_json_encode( esc_url_raw( rest_url( 'welyo/v1/' ) ) ); ?>;
		var restNonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
		var campaignSelect = document.getElementById('welyo_campaign_id');
		var classifierSelect = document.getElementById('welyo_classifier_id');
		var loadBtn = document.getElementById('welyo-load-api-lists');
		var loadSpinner = document.getElementById('welyo-api-lists-spinner');
		var loadStatus = document.getElementById('welyo-api-lists-status');

		function fillSelect(select, items, savedValue, emptyLabel) {
			if (!select) { return; }
			select.innerHTML = '';
			var empty = document.createElement('option');
			empty.value = '';
			empty.textContent = emptyLabel;
			select.appendChild(empty);
			items.forEach(function (it) {
				var opt = document.createElement('option');
				opt.value = it.id;
				opt.textContent = it.name + ' (#' + it.id + ')';
				if (savedValue && String(savedValue) === String(it.id)) {
					opt.selected = true;
				}
				select.appendChild(opt);
			});
		}

		function loadClassifiers(campaignId) {
			if (!classifierSelect || !campaignId) { return Promise.resolve(); }
			return fetch(restBase + 'classifiers?campaign_id=' + encodeURIComponent(campaignId), {
				headers: { 'X-WP-Nonce': restNonce }
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data && data.code) {
						throw new Error(data.message || 'classifiers');
					}
					fillSelect(
						classifierSelect,
						data.items || [],
						classifierSelect.value,
						<?php echo wp_json_encode( __( '— auto z API —', 'akademiata' ) ); ?>
					);
				});
		}

		if (loadBtn) {
			loadBtn.addEventListener('click', function () {
				loadStatus.textContent = '';
				loadSpinner.classList.add('is-active');
				loadBtn.disabled = true;
				fetch(restBase + 'campaigns', { headers: { 'X-WP-Nonce': restNonce } })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.code) {
							throw new Error(data.message || 'campaigns');
						}
						var items = data.items || [];
						fillSelect(
							campaignSelect,
							items,
							campaignSelect ? campaignSelect.value : '',
							<?php echo wp_json_encode( __( '— auto z API —', 'akademiata' ) ); ?>
						);
						loadStatus.textContent = items.length
							? (<?php echo wp_json_encode( __( 'Załadowano kampanii:', 'akademiata' ) ); ?> + ' ' + items.length)
							: (data.debug
								? (<?php echo wp_json_encode( __( 'Brak kampanii. Odpowiedź API:', 'akademiata' ) ); ?> + ' ' + data.debug)
								: <?php echo wp_json_encode( __( 'Brak kampanii w odpowiedzi API.', 'akademiata' ) ); ?>);
						var cid = campaignSelect && campaignSelect.value ? campaignSelect.value : (items[0] ? items[0].id : '');
						return loadClassifiers(cid);
					})
					.catch(function (err) {
						loadStatus.textContent = err && err.message ? err.message : <?php echo wp_json_encode( __( 'Nie udało się pobrać listy.', 'akademiata' ) ); ?>;
					})
					.finally(function () {
						loadSpinner.classList.remove('is-active');
						loadBtn.disabled = false;
					});
			});
		}

		if (campaignSelect) {
			campaignSelect.addEventListener('change', function () {
				if (campaignSelect.value) {
					loadClassifiers(campaignSelect.value);
				}
			});
		}

		var diagBtn = document.getElementById('welyo-run-diagnostics');
		var diagList = document.getElementById('welyo-diagnostics-results');
		var diagSpinner = document.getElementById('welyo-diagnostics-spinner');
		if (diagBtn && diagList) {
			diagBtn.addEventListener('click', function () {
				diagList.hidden = true;
				diagList.innerHTML = '';
				diagSpinner.classList.add('is-active');
				diagBtn.disabled = true;
				fetch(<?php echo wp_json_encode( esc_url_raw( rest_url( 'welyo/v1/diagnostics' ) ) ); ?>, {
					headers: { 'X-WP-Nonce': <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?> }
				})
					.then(function (r) { return r.json(); })
					.then(function (data) {
						var steps = data && data.steps ? data.steps : [];
						steps.forEach(function (step) {
							var li = document.createElement('li');
							li.className = step.ok ? 'is-ok' : 'is-fail';
							li.textContent = step.message || '';
							diagList.appendChild(li);
						});
						diagList.hidden = steps.length === 0;
					})
					.catch(function () {
						var li = document.createElement('li');
						li.className = 'is-fail';
						li.textContent = <?php echo wp_json_encode( __( 'Nie udało się uruchomić testu. Odśwież stronę i spróbuj ponownie.', 'akademiata' ) ); ?>;
						diagList.appendChild(li);
						diagList.hidden = false;
					})
					.finally(function () {
						diagSpinner.classList.remove('is-active');
						diagBtn.disabled = false;
					});
			});
		}

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
