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

	delete_transient( 'welyo_auth_mode' );
	welyo_clear_lang_transients();
	welyo_flush_settings_cache();

	return $settings;
}

function welyo_admin_option_name( $key, $lang = null ) {
	if ( $lang ) {
		return WELYO_OPTION_KEY . '[languages][' . $lang . '][' . $key . ']';
	}
	return WELYO_OPTION_KEY . '[' . $key . ']';
}

function welyo_admin_field_id( $key, $lang = null ) {
	return $lang ? 'welyo_' . $lang . '_' . $key : 'welyo_' . $key;
}

function welyo_admin_get_value( $settings, $key, $lang = null ) {
	if ( $lang ) {
		return isset( $settings['languages'][ $lang ][ $key ] ) ? $settings['languages'][ $lang ][ $key ] : '';
	}
	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
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
	$lang     = isset( $args['lang'] ) ? $args['lang'] : null;
	$empty    = isset( $args['empty_label'] ) ? $args['empty_label'] : __( '— auto z API —', 'akademiata' );
	$value    = (string) welyo_admin_get_value( $settings, $key, $lang );
	$field_id = welyo_admin_field_id( $key, $lang );
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<select class="regular-text welyo-api-select" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( welyo_admin_option_name( $key, $lang ) ); ?>" data-welyo-api="<?php echo esc_attr( $key ); ?>" data-welyo-lang="<?php echo esc_attr( $lang ? $lang : '' ); ?>">
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
	$lang  = isset( $args['lang'] ) ? $args['lang'] : null;
	$value = welyo_admin_get_value( $settings, $key, $lang );
	$field_id = welyo_admin_field_id( $key, $lang );
	$name  = welyo_admin_option_name( $key, $lang );
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<?php if ( $type === 'textarea' ) : ?>
				<textarea class="large-text" rows="3" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
			<?php else : ?>
				<input type="<?php echo esc_attr( $type ); ?>" class="<?php echo $wide ? 'large-text' : 'regular-text'; ?>" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function welyo_admin_render_lang_panel( $lang, $label, $settings ) {
	?>
	<div class="welyo-lang-panel" id="welyo-lang-<?php echo esc_attr( $lang ); ?>" data-lang="<?php echo esc_attr( $lang ); ?>" hidden>
		<h2 class="title"><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $lang ); ?></code></h2>

		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_api_select( 'campaign_id', 'Kampania (z API)', $settings, array(
				'lang' => $lang,
				'desc' => 'Wybierz kampanię Welyo dla tego języka.',
			) );
			welyo_admin_field_text( 'campaign_name', 'Nazwa kampanii (opcjonalnie)', $settings, array(
				'lang' => $lang,
				'wide' => true,
			) );
			welyo_admin_field_api_select( 'classifier_id', 'Klasyfikator recall (z API)', $settings, array(
				'lang' => $lang,
				'desc' => 'Po godzinach — recall.',
			) );
			welyo_admin_field_text( 'classifier_name', 'Nazwa klasyfikatora (opcjonalnie)', $settings, array(
				'lang' => $lang,
				'wide' => true,
			) );
			?>
		</table>

		<h3><?php esc_html_e( 'Telefon i godziny', 'akademiata' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_text( 'phone_dial', 'Numer (tel:)', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'phone_pretty', 'Numer (wyświetlany)', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'open_hour', 'Godzina otwarcia', $settings, array( 'lang' => $lang, 'type' => 'number' ) );
			welyo_admin_field_text( 'close_hour', 'Godzina zamknięcia', $settings, array( 'lang' => $lang, 'type' => 'number' ) );
			welyo_admin_field_text( 'workdays', 'Dni robocze', $settings, array( 'lang' => $lang, 'desc' => '1=pon … 7=niedz, np. 1,2,3,4,5' ) );
			welyo_admin_field_text( 'default_prefix', 'Prefiks numeru', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'privacy_url', 'URL polityki prywatności', $settings, array( 'lang' => $lang, 'wide' => true ) );
			?>
		</table>

		<h3><?php esc_html_e( 'Teksty — w godzinach pracy', 'akademiata' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_text( 'text_launch_open', 'Przycisk pływający', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_status_open', 'Status', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_title_open', 'Tytuł', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_sub_open', 'Podtytuł', $settings, array( 'lang' => $lang, 'type' => 'textarea' ) );
			welyo_admin_field_text( 'text_call_btn', 'Przycisk „Zadzwoń”', $settings, array( 'lang' => $lang, 'wide' => true ) );
			?>
		</table>

		<h3><?php esc_html_e( 'Teksty — po godzinach', 'akademiata' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_text( 'text_launch_closed', 'Przycisk pływający', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_status_closed', 'Status', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_title_closed', 'Tytuł', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_sub_closed', 'Podtytuł', $settings, array( 'lang' => $lang, 'type' => 'textarea' ) );
			welyo_admin_field_text( 'text_name_label', 'Etykieta: imię', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'text_name_placeholder', 'Placeholder: imię', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_phone_label', 'Etykieta: telefon', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'text_phone_placeholder', 'Placeholder: telefon', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_consent', 'Zgoda RODO', $settings, array( 'lang' => $lang, 'type' => 'textarea', 'desc' => 'Dozwolony HTML. Użyj {privacy_url}.' ) );
			welyo_admin_field_text( 'text_submit', 'Przycisk wyślij', $settings, array( 'lang' => $lang, 'wide' => true ) );
			?>
		</table>

		<h3><?php esc_html_e( 'Teksty — komunikaty', 'akademiata' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_text( 'text_done_title', 'Sukces: tytuł', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'text_done_scheduled', 'Sukces: oddzwonimy później', $settings, array( 'lang' => $lang, 'type' => 'textarea' ) );
			welyo_admin_field_text( 'text_done_immediate', 'Sukces: oddzwaniamy teraz', $settings, array( 'lang' => $lang, 'type' => 'textarea' ) );
			welyo_admin_field_text( 'text_footer', 'Stopka panelu', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_hours_prefix', 'Prefiks godzin', $settings, array( 'lang' => $lang, 'desc' => 'Przed „08:00–18:00”' ) );
			welyo_admin_field_text( 'text_error_phone', 'Błąd: telefon', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_consent', 'Błąd: zgoda', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_auth', 'Błąd: logowanie Welyo', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_campaign', 'Błąd: kampania', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_welyo', 'Błąd: odrzucenie przez Welyo', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_rate', 'Błąd: limit prób', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_nonce', 'Błąd: wygasła sesja', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_error_generic', 'Błąd: ogólny', $settings, array( 'lang' => $lang, 'type' => 'textarea' ) );
			welyo_admin_field_text( 'text_sending', 'Trwa wysyłanie', $settings, array( 'lang' => $lang ) );
			?>
		</table>

		<p>
			<button type="button" class="button button-secondary welyo-run-diagnostics-lang" data-lang="<?php echo esc_attr( $lang ); ?>">
				<?php esc_html_e( 'Sprawdź połączenie (ten język)', 'akademiata' ); ?>
			</button>
			<span class="spinner welyo-diagnostics-lang-spinner" data-lang="<?php echo esc_attr( $lang ); ?>" style="float:none;"></span>
		</p>
		<ul class="welyo-diagnostics-results welyo-diagnostics-lang-results" data-lang="<?php echo esc_attr( $lang ); ?>" hidden></ul>
	</div>
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
		<p><?php esc_html_e( 'API Welyo jest wspólne. Kampania, telefon, godziny i teksty ustawiasz osobno dla każdego języka WPML.', 'akademiata' ); ?></p>

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
				<?php welyo_admin_field_text( 'hash_method', 'Metoda hash', $settings, array( 'desc' => 'md5 lub sha1' ) ); ?>
			</table>

			<div class="welyo-diagnostics-box">
				<h3><?php esc_html_e( 'Test połączenia (API)', 'akademiata' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Sprawdza login, klucz API i JWT. Test kampanii per język — w zakładce danego języka.', 'akademiata' ); ?></p>
				<p>
					<button type="button" class="button button-secondary" id="welyo-run-diagnostics"><?php esc_html_e( 'Sprawdź połączenie z Welyo', 'akademiata' ); ?></button>
					<span class="spinner" id="welyo-diagnostics-spinner" style="float:none;"></span>
				</p>
				<ul id="welyo-diagnostics-results" class="welyo-diagnostics-results" hidden></ul>
			</div>

			<h2 class="title"><?php esc_html_e( 'Ustawienia per język', 'akademiata' ); ?></h2>
			<nav class="nav-tab-wrapper welyo-lang-tabs">
				<?php
				$first = true;
				foreach ( welyo_supported_languages() as $code => $lang_label ) {
					printf(
						'<a href="#welyo-lang-%1$s" class="nav-tab%2$s" data-welyo-tab="%1$s">%3$s (%1$s)</a>',
						esc_attr( $code ),
						$first ? ' nav-tab-active' : '',
						esc_html( $lang_label )
					);
					$first = false;
				}
				?>
			</nav>
			<?php
			foreach ( welyo_supported_languages() as $code => $lang_label ) {
				welyo_admin_render_lang_panel( $code, $lang_label, $settings );
			}
			?>

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
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Języki WPML', 'akademiata' ); ?></th>
					<td>
						<?php
						$enabled = welyo_get_enabled_languages();
						foreach ( welyo_supported_languages() as $code => $lang_label ) :
							?>
							<label style="display:inline-block;margin-right:16px;margin-bottom:6px;">
								<input type="checkbox" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[enabled_languages][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $enabled, true ) ); ?>>
								<?php echo esc_html( $lang_label ); ?> (<?php echo esc_html( $code ); ?>)
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Widget pokazuje się tylko na zaznaczonych wersjach językowych strony.', 'akademiata' ); ?></p>
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
		.welyo-lang-panel { margin-top:12px; }
		.welyo-lang-tabs { margin-bottom:0; }
	</style>
	<script>
	(function () {
		var restBase = <?php echo wp_json_encode( esc_url_raw( rest_url( 'welyo/v1/' ) ) ); ?>;
		var restNonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
		var loadBtn = document.getElementById('welyo-load-api-lists');
		var loadSpinner = document.getElementById('welyo-api-lists-spinner');
		var loadStatus = document.getElementById('welyo-api-lists-status');

		function campaignSelects() {
			return Array.prototype.slice.call(document.querySelectorAll('.welyo-api-select[data-welyo-api="campaign_id"]'));
		}
		function classifierSelectForLang(lang) {
			return document.getElementById('welyo_' + lang + '_classifier_id');
		}
		function campaignSelectForLang(lang) {
			return document.getElementById('welyo_' + lang + '_campaign_id');
		}

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

		function loadClassifiers(campaignId, lang) {
			var classifierSelect = classifierSelectForLang(lang);
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
						campaignSelects().forEach(function (sel) {
							fillSelect(
								sel,
								items,
								sel.value,
								<?php echo wp_json_encode( __( '— auto z API —', 'akademiata' ) ); ?>
							);
						});
						loadStatus.textContent = items.length
							? (<?php echo wp_json_encode( __( 'Załadowano kampanii:', 'akademiata' ) ); ?> + ' ' + items.length)
							: (data.debug
								? (<?php echo wp_json_encode( __( 'Brak kampanii. Odpowiedź API:', 'akademiata' ) ); ?> + ' ' + data.debug)
								: <?php echo wp_json_encode( __( 'Brak kampanii w odpowiedzi API.', 'akademiata' ) ); ?>);
						var promises = [];
						campaignSelects().forEach(function (sel) {
							var lang = sel.getAttribute('data-welyo-lang');
							var cid = sel.value || (items[0] ? items[0].id : '');
							if (lang && cid) {
								promises.push(loadClassifiers(cid, lang));
							}
						});
						return Promise.all(promises);
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

		campaignSelects().forEach(function (sel) {
			sel.addEventListener('change', function () {
				var lang = sel.getAttribute('data-welyo-lang');
				if (lang && sel.value) {
					loadClassifiers(sel.value, lang);
				}
			});
		});

		document.querySelectorAll('.welyo-lang-tabs .nav-tab').forEach(function (tab) {
			tab.addEventListener('click', function (e) {
				e.preventDefault();
				var code = tab.getAttribute('data-welyo-tab');
				document.querySelectorAll('.welyo-lang-tabs .nav-tab').forEach(function (t) {
					t.classList.toggle('nav-tab-active', t === tab);
				});
				document.querySelectorAll('.welyo-lang-panel').forEach(function (panel) {
					panel.hidden = panel.getAttribute('data-lang') !== code;
				});
			});
		});
		var firstTab = document.querySelector('.welyo-lang-tabs .nav-tab');
		if (firstTab) { firstTab.click(); }

		function runDiagnostics(url, listEl, spinnerEl, btnEl) {
			listEl.hidden = true;
			listEl.innerHTML = '';
			spinnerEl.classList.add('is-active');
			if (btnEl) { btnEl.disabled = true; }
			fetch(url, { headers: { 'X-WP-Nonce': restNonce } })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					var steps = data && data.steps ? data.steps : [];
					steps.forEach(function (step) {
						var li = document.createElement('li');
						li.className = step.ok ? 'is-ok' : 'is-fail';
						li.textContent = step.message || '';
						listEl.appendChild(li);
					});
					listEl.hidden = steps.length === 0;
				})
				.catch(function () {
					var li = document.createElement('li');
					li.className = 'is-fail';
					li.textContent = <?php echo wp_json_encode( __( 'Nie udało się uruchomić testu.', 'akademiata' ) ); ?>;
					listEl.appendChild(li);
					listEl.hidden = false;
				})
				.finally(function () {
					spinnerEl.classList.remove('is-active');
					if (btnEl) { btnEl.disabled = false; }
				});
		}

		var diagBtn = document.getElementById('welyo-run-diagnostics');
		var diagList = document.getElementById('welyo-diagnostics-results');
		var diagSpinner = document.getElementById('welyo-diagnostics-spinner');
		if (diagBtn && diagList) {
			diagBtn.addEventListener('click', function () {
				runDiagnostics(restBase + 'diagnostics', diagList, diagSpinner, diagBtn);
			});
		}

		document.querySelectorAll('.welyo-run-diagnostics-lang').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var lang = btn.getAttribute('data-lang');
				var list = document.querySelector('.welyo-diagnostics-lang-results[data-lang="' + lang + '"]');
				var spinner = document.querySelector('.welyo-diagnostics-lang-spinner[data-lang="' + lang + '"]');
				if (list && spinner) {
					runDiagnostics(restBase + 'diagnostics?lang=' + encodeURIComponent(lang), list, spinner, btn);
				}
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
