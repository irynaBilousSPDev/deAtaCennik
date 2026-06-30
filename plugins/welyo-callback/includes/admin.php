<?php
/**
 * Panel ustawień Welyo Callback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'welyo_admin_register_menu' );
add_action( 'admin_init', 'welyo_admin_register_settings' );

function welyo_admin_register_menu() {
	add_options_page(
		'Welyo Callback',
		'Welyo Callback',
		'manage_options',
		'welyo-callback',
		'welyo_admin_render_page'
	);
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

	return $settings;
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
				<input type="<?php echo esc_attr( $type ); ?>" class="<?php echo $wide ? 'large-text' : 'regular-text'; ?>" id="welyo_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $type === 'password' ? '' : $value ); ?>" <?php echo $type === 'password' ? 'autocomplete="new-password"' : ''; ?>>
			<?php endif; ?>
			<?php if ( $type === 'password' && $value !== '' ) : ?>
				<p class="description"><?php esc_html_e( 'Klucz jest zapisany. Zostaw puste, aby go nie zmieniać.', 'akademiata' ); ?></p>
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
		<p><?php esc_html_e( 'Widget „Zadzwoń / Oddzwonimy” — integracja z Welyo i treści widoczne na stronie.', 'akademiata' ); ?></p>

		<?php settings_errors( 'welyo_callback' ); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'welyo_callback' ); ?>

			<h2 class="title"><?php esc_html_e( 'API Welyo', 'akademiata' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'base_url', 'URL API', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'login', 'Login', $settings, array( 'desc' => 'np. login@ataedu' ) );
				welyo_admin_field_text( 'api_key', 'Klucz API', $settings, array( 'type' => 'password' ) );
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
		<p class="description"><?php esc_html_e( 'Priorytet: wp-config.php → welyo-config.php → ustawienia z tego panelu.', 'akademiata' ); ?></p>
	</div>
	<?php
}
