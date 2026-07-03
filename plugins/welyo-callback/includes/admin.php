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

	if ( ! empty( $_POST['welyo_write_config_file'] ) || ! empty( $input['welyo_write_config_file'] ) ) {
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
	$name_key = '';
	if ( $key === 'campaign_id' ) {
		$name_key = 'campaign_name';
	} elseif ( $key === 'classifier_id' ) {
		$name_key = 'classifier_name';
	} elseif ( $key === 'forminator_quiz_campaign_id' ) {
		$name_key = 'forminator_quiz_campaign_name';
	}
	$saved_name = $name_key ? (string) welyo_admin_get_value( $settings, $name_key, $lang ) : '';
	if ( $value !== '' ) {
		$saved_label = $saved_name !== ''
			? $saved_name . ' (#' . $value . ')'
			: '#' . $value . ' (' . __( 'zapisane', 'akademiata' ) . ')';
	} else {
		$saved_label = '';
	}
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<select class="regular-text welyo-api-select" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( welyo_admin_option_name( $key, $lang ) ); ?>" data-welyo-api="<?php echo esc_attr( $key ); ?>" data-welyo-lang="<?php echo esc_attr( $lang ? $lang : '' ); ?>">
				<option value=""><?php echo esc_html( $empty ); ?></option>
				<?php if ( $value !== '' ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" selected><?php echo esc_html( $saved_label ); ?></option>
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

function welyo_admin_render_general_help_box() {
	?>
	<div class="welyo-admin-help welyo-admin-help--general">
		<p>
			<strong><?php esc_html_e( 'Numery i godziny pracy', 'akademiata' ); ?></strong> —
			<?php esc_html_e( 'dotyczą widgetu „Oddzwonimy” na stronie (zadzwoń w godzinach pracy, formularz po godzinach).', 'akademiata' ); ?>
		</p>
	</div>
	<?php
}

function welyo_admin_render_forminator_help_box() {
	?>
	<div class="welyo-forminator-help">
		<h3><?php esc_html_e( 'Jak skonfigurować quiz', 'akademiata' ); ?></h3>
		<p><?php esc_html_e( 'Potrzebujesz osobnej kampanii Welyo na każdy język oraz ID quizu z Forminator.', 'akademiata' ); ?></p>

		<ol class="welyo-forminator-help__steps">
			<li>
				<strong><?php esc_html_e( 'W panelu Welyo', 'akademiata' ); ?></strong>
				<?php esc_html_e( 'Utwórz kampanię na dany język (np. „Rekrutacja - quiz Forminator WWW”). W polach kampanii dodaj:', 'akademiata' ); ?>
				<code>TELEFON</code>, <code>EMAIL</code>, <code>WYNIK_QUIZU</code> (nazwa i opis osobowości w jednym polu).
			</li>
			<li>
				<strong><?php esc_html_e( 'W Forminatorze', 'akademiata' ); ?></strong>
				<?php esc_html_e( 'Quiz powinien zbierać telefon (wymagany), e-mail i zgodę RODO. Przy quizie osobowości wynik (np. Lider Zespołu) zapisze się automatycznie.', 'akademiata' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Na tej zakładce', 'akademiata' ); ?></strong>
				<?php esc_html_e( 'Włącz integrację, wybierz język, podaj ID quizu i przypisz kampanię z listy.', 'akademiata' ); ?>
			</li>
		</ol>

		<p class="welyo-forminator-help__note">
			<strong><?php esc_html_e( 'Ważne:', 'akademiata' ); ?></strong>
			<?php esc_html_e( 'Widget „Oddzwonimy” i quiz korzystają z osobnych kampanii Welyo — nie używaj tej samej kampanii w obu miejscach.', 'akademiata' ); ?>
		</p>
	</div>
	<?php
}

function welyo_admin_render_forminator_lang_panel( $lang, $label, $settings ) {
	?>
	<div class="welyo-forminator-lang-panel" id="welyo-forminator-lang-<?php echo esc_attr( $lang ); ?>" data-lang="<?php echo esc_attr( $lang ); ?>" hidden>
		<h2 class="title"><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $lang ); ?></code></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Integracja quizu', 'akademiata' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( welyo_admin_option_name( 'forminator_enabled', $lang ) ); ?>" value="1" <?php checked( ! empty( welyo_admin_get_value( $settings, 'forminator_enabled', $lang ) ) ); ?>>
						<?php esc_html_e( 'Wysyłaj wypełnienia tego quizu do Welyo', 'akademiata' ); ?>
					</label>
				</td>
			</tr>
			<?php
			welyo_admin_field_text( 'forminator_form_id', 'ID quizu Forminator', $settings, array(
				'lang' => $lang,
				'type' => 'number',
				'desc' => 'ID quizu ze shortcode na stronie (np. PL: 20803).',
			) );
			welyo_admin_field_api_select( 'forminator_quiz_campaign_id', 'Kampania quizu (z API)', $settings, array(
				'lang' => $lang,
				'desc' => 'Kampania dla leadów z quizu — inna niż dla widgetu „Oddzwonimy”.',
			) );
			welyo_admin_field_text( 'forminator_quiz_campaign_name', 'Nazwa kampanii quizu (opcjonalnie)', $settings, array(
				'lang' => $lang,
				'wide' => true,
			) );
			?>
		</table>

		<details class="welyo-forminator-advanced">
			<summary><?php esc_html_e( 'Zaawansowane — nazwy pól formularza', 'akademiata' ); ?></summary>
			<p class="description"><?php esc_html_e( 'Zostaw puste — wtyczka sama odczyta telefon, e-mail, zgodę i wynik quizu. Wypełniaj tylko na prośbę supportu.', 'akademiata' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'forminator_field_phone', 'Pole: telefon', $settings, array( 'lang' => $lang ) );
				welyo_admin_field_text( 'forminator_field_name', 'Pole: imię', $settings, array( 'lang' => $lang ) );
				welyo_admin_field_text( 'forminator_field_email', 'Pole: e-mail', $settings, array( 'lang' => $lang ) );
				welyo_admin_field_text( 'forminator_field_quiz_result', 'Pole: wynik quizu', $settings, array( 'lang' => $lang ) );
				welyo_admin_field_text( 'forminator_field_consent', 'Pole: zgoda RODO', $settings, array( 'lang' => $lang ) );
				?>
			</table>
		</details>

		<p>
			<button type="button" class="button button-secondary welyo-run-forminator-diagnostics" data-lang="<?php echo esc_attr( $lang ); ?>">
				<?php esc_html_e( 'Testuj ostatni wpis quizu', 'akademiata' ); ?>
			</button>
			<button type="button" class="button button-primary welyo-send-forminator-last" data-lang="<?php echo esc_attr( $lang ); ?>">
				<?php esc_html_e( 'Wyślij ostatni wpis do Welyo', 'akademiata' ); ?>
			</button>
			<span class="spinner welyo-forminator-diagnostics-spinner" data-lang="<?php echo esc_attr( $lang ); ?>" style="float:none;"></span>
		</p>
		<p class="description"><?php esc_html_e( '„Testuj” sprawdza dane bez wysyłki. „Wyślij” faktycznie dodaje rekord do kampanii Welyo (przydatne po naprawie integracji).', 'akademiata' ); ?></p>
		<ul class="welyo-diagnostics-results welyo-forminator-diagnostics-results" data-lang="<?php echo esc_attr( $lang ); ?>" hidden></ul>
	</div>
	<?php
}

function welyo_admin_render_general_lang_panel( $lang, $label, $settings ) {
	?>
	<div class="welyo-general-lang-panel" id="welyo-general-lang-<?php echo esc_attr( $lang ); ?>" data-lang="<?php echo esc_attr( $lang ); ?>" hidden>
		<h2 class="title"><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $lang ); ?></code></h2>
		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_text( 'phone_dial', 'Numer infolinii (tel:)', $settings, array(
				'lang' => $lang,
				'desc' => 'Numer widoczny w przycisku „Zadzwoń” w widgetcie.',
			) );
			welyo_admin_field_text( 'phone_pretty', 'Numer (wyświetlany)', $settings, array( 'lang' => $lang ) );
			welyo_admin_field_text( 'open_hour', 'Godzina otwarcia', $settings, array( 'lang' => $lang, 'type' => 'number' ) );
			welyo_admin_field_text( 'close_hour', 'Godzina zamknięcia', $settings, array( 'lang' => $lang, 'type' => 'number' ) );
			welyo_admin_field_text( 'workdays', 'Dni robocze', $settings, array( 'lang' => $lang, 'desc' => '1=pon … 7=niedz, np. 1,2,3,4,5' ) );
			welyo_admin_field_text( 'default_prefix', 'Prefiks numeru klienta', $settings, array( 'lang' => $lang, 'desc' => 'Np. +48 — normalizacja telefonu z formularza / quizu.' ) );
			welyo_admin_field_text( 'privacy_url', 'URL polityki prywatności', $settings, array( 'lang' => $lang, 'wide' => true ) );
			?>
		</table>
	</div>
	<?php
}

function welyo_admin_render_callback_lang_panel( $lang, $label, $settings ) {
	?>
	<div class="welyo-callback-lang-panel" id="welyo-callback-lang-<?php echo esc_attr( $lang ); ?>" data-lang="<?php echo esc_attr( $lang ); ?>" hidden>
		<h2 class="title"><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $lang ); ?></code></h2>

		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_api_select( 'campaign_id', 'Kampania callback (z API)', $settings, array(
				'lang' => $lang,
				'desc' => 'Kampania dla widgetu „Oddzwonimy” — inna niż dla quizu.',
			) );
			welyo_admin_field_text( 'campaign_name', 'Nazwa kampanii (opcjonalnie)', $settings, array(
				'lang' => $lang,
				'wide' => true,
			) );
			welyo_admin_field_api_select( 'classifier_id', 'Klasyfikator recall (z API)', $settings, array(
				'lang' => $lang,
				'desc' => 'Oddzwonienie po godzinach pracy.',
			) );
			welyo_admin_field_text( 'classifier_name', 'Nazwa klasyfikatora (opcjonalnie)', $settings, array(
				'lang' => $lang,
				'wide' => true,
			) );
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

function welyo_admin_render_content_lang_panel( $lang, $label, $settings ) {
	?>
	<div class="welyo-content-lang-panel" id="welyo-content-lang-<?php echo esc_attr( $lang ); ?>" data-lang="<?php echo esc_attr( $lang ); ?>" hidden>
		<h2 class="title"><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $lang ); ?></code></h2>

		<h3><?php esc_html_e( 'W godzinach pracy', 'akademiata' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			welyo_admin_field_text( 'text_launch_open', 'Przycisk pływający', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_status_open', 'Status', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_title_open', 'Tytuł', $settings, array( 'lang' => $lang, 'wide' => true ) );
			welyo_admin_field_text( 'text_sub_open', 'Podtytuł', $settings, array( 'lang' => $lang, 'type' => 'textarea' ) );
			welyo_admin_field_text( 'text_call_btn', 'Przycisk „Zadzwoń”', $settings, array( 'lang' => $lang, 'wide' => true ) );
			?>
		</table>

		<h3><?php esc_html_e( 'Po godzinach', 'akademiata' ); ?></h3>
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

		<h3><?php esc_html_e( 'Komunikaty i błędy', 'akademiata' ); ?></h3>
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
		<p><?php esc_html_e( 'Widget „Oddzwonimy” na stronie — połączenie z Welyo, kampanie, teksty i wygląd.', 'akademiata' ); ?></p>

		<?php settings_errors( 'welyo_callback' ); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'welyo_callback' ); ?>

			<nav class="nav-tab-wrapper welyo-main-tabs" aria-label="<?php esc_attr_e( 'Sekcje ustawień', 'akademiata' ); ?>">
				<a href="#welyo-main-api" class="nav-tab nav-tab-active" data-welyo-main="api"><?php esc_html_e( 'API Welyo', 'akademiata' ); ?></a>
				<a href="#welyo-main-general" class="nav-tab" data-welyo-main="general"><?php esc_html_e( 'Ogólne', 'akademiata' ); ?></a>
				<a href="#welyo-main-callback" class="nav-tab" data-welyo-main="callback"><?php esc_html_e( 'Widget Oddzwonimy', 'akademiata' ); ?></a>
				<a href="#welyo-main-forminator" class="nav-tab" data-welyo-main="forminator"><?php esc_html_e( 'Quiz Forminator', 'akademiata' ); ?></a>
				<a href="#welyo-main-content" class="nav-tab" data-welyo-main="content"><?php esc_html_e( 'Treści widgetu', 'akademiata' ); ?></a>
				<a href="#welyo-main-colors" class="nav-tab" data-welyo-main="colors"><?php esc_html_e( 'Kolory', 'akademiata' ); ?></a>
				<a href="#welyo-main-display" class="nav-tab" data-welyo-main="display"><?php esc_html_e( 'Wyświetlanie', 'akademiata' ); ?></a>
			</nav>

			<div id="welyo-main-api" class="welyo-main-panel">
			<table class="form-table" role="presentation">
				<?php
				welyo_admin_field_text( 'base_url', 'URL API', $settings, array( 'wide' => true ) );
				welyo_admin_field_text( 'login', 'Login', $settings, array( 'desc' => 'np. login@ataedu' ) );
				welyo_admin_field_secret( 'api_key', 'Klucz API', $settings );
				?>
			</table>

			<div class="welyo-api-lists-box">
				<h3><?php esc_html_e( 'Kampanie z API', 'akademiata' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Pobierz listę kampanii z Welyo, a potem przypisz je w zakładce Widget Oddzwonimy lub Quiz Forminator.', 'akademiata' ); ?></p>
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
				<p class="description"><?php esc_html_e( 'Sprawdza, czy login i klucz API do Welyo są poprawne.', 'akademiata' ); ?></p>
				<p>
					<button type="button" class="button button-secondary" id="welyo-run-diagnostics"><?php esc_html_e( 'Sprawdź połączenie z Welyo', 'akademiata' ); ?></button>
					<span class="spinner" id="welyo-diagnostics-spinner" style="float:none;"></span>
				</p>
				<ul id="welyo-diagnostics-results" class="welyo-diagnostics-results" hidden></ul>
			</div>
			</div>

			<div id="welyo-main-general" class="welyo-main-panel" hidden>
			<?php welyo_admin_render_general_help_box(); ?>
			<nav class="nav-tab-wrapper welyo-general-lang-tabs">
				<?php
				$first_general = true;
				foreach ( welyo_supported_languages() as $code => $lang_label ) {
					printf(
						'<a href="#welyo-general-lang-%1$s" class="nav-tab%2$s" data-welyo-general-tab="%1$s">%3$s (%1$s)</a>',
						esc_attr( $code ),
						$first_general ? ' nav-tab-active' : '',
						esc_html( $lang_label )
					);
					$first_general = false;
				}
				?>
			</nav>
			<?php
			foreach ( welyo_supported_languages() as $code => $lang_label ) {
				welyo_admin_render_general_lang_panel( $code, $lang_label, $settings );
			}
			?>
			</div>

			<div id="welyo-main-callback" class="welyo-main-panel" hidden>
			<p class="description"><?php esc_html_e( 'Przypisz kampanię Welyo do widgetu „Oddzwonimy” dla wybranego języka.', 'akademiata' ); ?></p>
			<nav class="nav-tab-wrapper welyo-callback-lang-tabs">
				<?php
				$first = true;
				foreach ( welyo_supported_languages() as $code => $lang_label ) {
					printf(
						'<a href="#welyo-callback-lang-%1$s" class="nav-tab%2$s" data-welyo-callback-tab="%1$s">%3$s (%1$s)</a>',
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
				welyo_admin_render_callback_lang_panel( $code, $lang_label, $settings );
			}
			?>
			</div>

			<div id="welyo-main-forminator" class="welyo-main-panel" hidden>
			<p class="description"><?php esc_html_e( 'Lead z quizu trafia od razu do kampanii Welyo — konsultanci dzwonią według reguł tej kampanii.', 'akademiata' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Integracja quizu', 'akademiata' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[forminator_integration_enabled]" value="1" <?php checked( ! empty( $settings['forminator_integration_enabled'] ) ); ?>>
							<?php esc_html_e( 'Włącz wysyłkę leadów z quizu Forminator do Welyo', 'akademiata' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Wymaga aktywnej wtyczki Forminator.', 'akademiata' ); ?></p>
					</td>
				</tr>
			</table>
			<?php welyo_admin_render_forminator_help_box(); ?>
			<nav class="nav-tab-wrapper welyo-forminator-lang-tabs">
				<?php
				$first_forminator = true;
				foreach ( welyo_supported_languages() as $code => $lang_label ) {
					printf(
						'<a href="#welyo-forminator-lang-%1$s" class="nav-tab%2$s" data-welyo-forminator-tab="%1$s">%3$s (%1$s)</a>',
						esc_attr( $code ),
						$first_forminator ? ' nav-tab-active' : '',
						esc_html( $lang_label )
					);
					$first_forminator = false;
				}
				?>
			</nav>
			<?php
			foreach ( welyo_supported_languages() as $code => $lang_label ) {
				welyo_admin_render_forminator_lang_panel( $code, $lang_label, $settings );
			}
			?>
			</div>

			<div id="welyo-main-content" class="welyo-main-panel" hidden>
			<p class="description"><?php esc_html_e( 'Teksty widoczne w panelu „Oddzwonimy” — osobno dla każdej wersji językowej strony.', 'akademiata' ); ?></p>
			<nav class="nav-tab-wrapper welyo-content-lang-tabs">
				<?php
				$first_content = true;
				foreach ( welyo_supported_languages() as $code => $lang_label ) {
					printf(
						'<a href="#welyo-content-lang-%1$s" class="nav-tab%2$s" data-welyo-content-tab="%1$s">%3$s (%1$s)</a>',
						esc_attr( $code ),
						$first_content ? ' nav-tab-active' : '',
						esc_html( $lang_label )
					);
					$first_content = false;
				}
				?>
			</nav>
			<?php
			foreach ( welyo_supported_languages() as $code => $lang_label ) {
				welyo_admin_render_content_lang_panel( $code, $lang_label, $settings );
			}
			?>
			</div>

			<div id="welyo-main-colors" class="welyo-main-panel" hidden>
			<p class="description"><?php esc_html_e( 'Wspólne kolory marki dla wszystkich języków.', 'akademiata' ); ?></p>
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
			</div>

			<div id="welyo-main-display" class="welyo-main-panel" hidden>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Widget na stronie', 'akademiata' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[auto_footer]" value="1" <?php checked( ! empty( $settings['auto_footer'] ) ); ?>>
							<?php esc_html_e( 'Pokazuj automatycznie na wszystkich stronach (w stopce)', 'akademiata' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Shortcode: [welyo_callback]', 'akademiata' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Pokaż widget w językach', 'akademiata' ); ?></th>
					<td>
						<?php
						$enabled = welyo_get_enabled_languages();
						foreach ( welyo_supported_languages() as $code => $lang_label ) :
							?>
							<label style="display:block;margin-bottom:8px;">
								<input type="checkbox" name="<?php echo esc_attr( WELYO_OPTION_KEY ); ?>[enabled_languages][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $enabled, true ) ); ?>>
								<?php echo esc_html( $lang_label ); ?> (<?php echo esc_html( $code ); ?>)
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Odznacz język, aby ukryć widget na tej wersji strony (w tym polski). Żaden zaznaczony = widget nigdzie się nie pokazuje.', 'akademiata' ); ?></p>
					</td>
				</tr>
			</table>

			<div class="welyo-config-box">
				<h3><?php esc_html_e( 'Plik konfiguracyjny (zwykle niepotrzebny)', 'akademiata' ); ?></h3>
				<p>
					<?php
					if ( is_readable( $config_path ) ) {
						printf(
							esc_html__( 'Na serwerze istnieje: %s', 'akademiata' ),
							'<code>wp-content/welyo-config.php</code>'
						);
					} else {
						esc_html_e( 'Brak wp-content/welyo-config.php na serwerze.', 'akademiata' );
					}
					?>
				</p>
				<p class="description"><?php esc_html_e( 'Ustawienia zapisują się od razu w WordPressie. Opcja poniżej jest tylko dla administratora serwera.', 'akademiata' ); ?></p>
				<p>
					<label>
						<input type="checkbox" name="welyo_write_config_file" value="1">
						<?php esc_html_e( 'Wygeneruj plik konfiguracyjny na serwerze', 'akademiata' ); ?>
					</label>
				</p>
			</div>
			</div>

			<p class="submit">
				<?php submit_button( __( 'Zapisz ustawienia', 'akademiata' ), 'primary', 'submit', false ); ?>
			</p>
		</form>
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
		.welyo-general-lang-panel { margin-top:12px; }
		.welyo-content-lang-panel { margin-top:12px; }
		.welyo-callback-lang-panel { margin-top:12px; }
		.welyo-forminator-lang-panel { margin-top:12px; }
		.welyo-general-lang-tabs { margin-bottom:0; }
		.welyo-callback-lang-tabs { margin-bottom:0; }
		.welyo-content-lang-tabs { margin-bottom:0; }
		.welyo-forminator-lang-tabs { margin-bottom:0; }
		.welyo-admin-help { margin:0 0 16px; padding:12px 16px; max-width:52rem; background:#f0f6fc; border:1px solid #c3d9ed; border-radius:4px; }
		.welyo-admin-help p { margin:0; }
		.welyo-main-tabs { margin-top:16px; }
		.welyo-main-panel { margin-top:16px; max-width:52rem; }
		.welyo-config-box { margin-top:20px; padding:16px 18px; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; }
		.welyo-config-box h3 { margin:0 0 8px; }
		.welyo-forminator-help { margin:0 0 20px; padding:16px 20px; max-width:52rem; background:#fff8e5; border:1px solid #e6c200; border-radius:4px; }
		.welyo-forminator-help h3 { margin:0 0 10px; font-size:1.05em; }
		.welyo-forminator-help p { margin:0 0 10px; }
		.welyo-forminator-help__steps { margin:0 0 12px; padding-left:1.4em; }
		.welyo-forminator-help__steps > li { margin-bottom:12px; }
		.welyo-forminator-help__steps ul { margin:6px 0 0; padding-left:1.2em; }
		.welyo-forminator-help__steps ul li { margin-bottom:4px; }
		.welyo-forminator-help__steps code { margin:0 2px; padding:1px 5px; background:#f6f7f7; border-radius:3px; font-size:12px; }
		.welyo-forminator-help__note { margin:0; padding:10px 12px; background:#f6f7f7; border-radius:4px; font-size:13px; }
		.welyo-forminator-advanced { margin:12px 0 20px; padding:12px 16px; max-width:52rem; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; }
		.welyo-forminator-advanced summary { cursor:pointer; font-weight:600; margin-bottom:8px; }
	</style>
	<script>
	(function () {
		var restBase = <?php echo wp_json_encode( esc_url_raw( rest_url( 'welyo/v1/' ) ) ); ?>;
		var restNonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
		var loadBtn = document.getElementById('welyo-load-api-lists');
		var loadSpinner = document.getElementById('welyo-api-lists-spinner');
		var loadStatus = document.getElementById('welyo-api-lists-status');

		function campaignSelects() {
			return Array.prototype.slice.call(document.querySelectorAll(
				'.welyo-api-select[data-welyo-api="campaign_id"], .welyo-api-select[data-welyo-api="forminator_quiz_campaign_id"]'
			));
		}
		function classifierSelectForLang(lang, campaignApiKey) {
			if (campaignApiKey === 'forminator_quiz_campaign_id') {
				return null;
			}
			return document.getElementById('welyo_' + lang + '_classifier_id');
		}
		function campaignSelectForLang(lang, campaignApiKey) {
			campaignApiKey = campaignApiKey || 'campaign_id';
			return document.getElementById('welyo_' + lang + '_' + campaignApiKey);
		}

		function loadClassifiers(campaignId, lang, campaignApiKey) {
			campaignApiKey = campaignApiKey || 'campaign_id';
			var classifierSelect = classifierSelectForLang(lang, campaignApiKey);
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

		function loadCampaignListsFromApi(showStatus) {
			if (showStatus && loadStatus) { loadStatus.textContent = ''; }
			if (showStatus && loadSpinner) { loadSpinner.classList.add('is-active'); }
			if (showStatus && loadBtn) { loadBtn.disabled = true; }
			return fetch(restBase + 'campaigns', { headers: { 'X-WP-Nonce': restNonce } })
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
					if (showStatus && loadStatus) {
						loadStatus.textContent = items.length
							? (<?php echo wp_json_encode( __( 'Załadowano kampanii:', 'akademiata' ) ); ?> + ' ' + items.length)
							: (data.debug
								? (<?php echo wp_json_encode( __( 'Brak kampanii. Odpowiedź API:', 'akademiata' ) ); ?> + ' ' + data.debug)
								: <?php echo wp_json_encode( __( 'Brak kampanii w odpowiedzi API.', 'akademiata' ) ); ?>);
					}
					var promises = [];
					campaignSelects().forEach(function (sel) {
						var lang = sel.getAttribute('data-welyo-lang');
						var apiKey = sel.getAttribute('data-welyo-api') || 'campaign_id';
						var cid = sel.value || (items[0] ? items[0].id : '');
						if (lang && cid) {
							promises.push(loadClassifiers(cid, lang, apiKey));
						}
					});
					return Promise.all(promises);
				})
				.catch(function (err) {
					if (showStatus && loadStatus) {
						loadStatus.textContent = err && err.message ? err.message : <?php echo wp_json_encode( __( 'Nie udało się pobrać listy.', 'akademiata' ) ); ?>;
					}
				})
				.finally(function () {
					if (showStatus && loadSpinner) { loadSpinner.classList.remove('is-active'); }
					if (showStatus && loadBtn) { loadBtn.disabled = false; }
				});
		}

		if (loadBtn) {
			loadBtn.addEventListener('click', function () {
				loadCampaignListsFromApi(true);
			});
		}

		if (campaignSelects().some(function (sel) { return sel.value; })) {
			loadCampaignListsFromApi(false);
		}

		campaignSelects().forEach(function (sel) {
			sel.addEventListener('change', function () {
				var lang = sel.getAttribute('data-welyo-lang');
				var apiKey = sel.getAttribute('data-welyo-api') || 'campaign_id';
				if (lang && sel.value) {
					loadClassifiers(sel.value, lang, apiKey);
				}
			});
		});

		function initLangSubTabs(tabSelector, panelSelector, tabAttr) {
			document.querySelectorAll(tabSelector + ' .nav-tab').forEach(function (tab) {
				tab.addEventListener('click', function (e) {
					e.preventDefault();
					var code = tab.getAttribute(tabAttr);
					document.querySelectorAll(tabSelector + ' .nav-tab').forEach(function (t) {
						t.classList.toggle('nav-tab-active', t === tab);
					});
					document.querySelectorAll(panelSelector).forEach(function (panel) {
						panel.hidden = panel.getAttribute('data-lang') !== code;
					});
				});
			});
			var firstTab = document.querySelector(tabSelector + ' .nav-tab');
			if (firstTab) { firstTab.click(); }
		}

		initLangSubTabs('.welyo-general-lang-tabs', '.welyo-general-lang-panel', 'data-welyo-general-tab');
		initLangSubTabs('.welyo-callback-lang-tabs', '.welyo-callback-lang-panel', 'data-welyo-callback-tab');
		initLangSubTabs('.welyo-content-lang-tabs', '.welyo-content-lang-panel', 'data-welyo-content-tab');
		initLangSubTabs('.welyo-forminator-lang-tabs', '.welyo-forminator-lang-panel', 'data-welyo-forminator-tab');

		document.querySelectorAll('.welyo-main-tabs .nav-tab').forEach(function (tab) {
			tab.addEventListener('click', function (e) {
				e.preventDefault();
				var id = tab.getAttribute('data-welyo-main');
				document.querySelectorAll('.welyo-main-tabs .nav-tab').forEach(function (t) {
					t.classList.toggle('nav-tab-active', t === tab);
				});
				document.querySelectorAll('.welyo-main-panel').forEach(function (panel) {
					panel.hidden = panel.id !== 'welyo-main-' + id;
				});
			});
		});

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

		document.querySelectorAll('.welyo-run-forminator-diagnostics').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var lang = btn.getAttribute('data-lang');
				var list = document.querySelector('.welyo-forminator-diagnostics-results[data-lang="' + lang + '"]');
				var spinner = document.querySelector('.welyo-forminator-diagnostics-spinner[data-lang="' + lang + '"]');
				if (list && spinner) {
					runDiagnostics(restBase + 'forminator-diagnostics?lang=' + encodeURIComponent(lang), list, spinner, btn);
				}
			});
		});

		document.querySelectorAll('.welyo-send-forminator-last').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var lang = btn.getAttribute('data-lang');
				var list = document.querySelector('.welyo-forminator-diagnostics-results[data-lang="' + lang + '"]');
				var spinner = document.querySelector('.welyo-forminator-diagnostics-spinner[data-lang="' + lang + '"]');
				if (!list || !spinner) { return; }
				list.hidden = false;
				list.innerHTML = '';
				spinner.classList.add('is-active');
				btn.disabled = true;
				fetch(restBase + 'forminator-send-last?lang=' + encodeURIComponent(lang), {
					method: 'POST',
					headers: {
						'X-WP-Nonce': restNonce,
						'Content-Type': 'application/json'
					}
				})
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.steps) {
							data.steps.forEach(function (step) {
								var li = document.createElement('li');
								li.className = step.ok ? 'is-ok' : 'is-fail';
								li.textContent = step.message || '';
								list.appendChild(li);
							});
							return;
						}
						var li = document.createElement('li');
						li.className = data && data.ok ? 'is-ok' : 'is-fail';
						li.textContent = (data && data.message) ? data.message : (data && data.code ? data.message || data.code : <?php echo wp_json_encode( __( 'Wysyłka nie powiodła się.', 'akademiata' ) ); ?>);
						list.appendChild(li);
					})
					.catch(function () {
						var li = document.createElement('li');
						li.className = 'is-fail';
						li.textContent = <?php echo wp_json_encode( __( 'Nie udało się wysłać wpisu do Welyo.', 'akademiata' ) ); ?>;
						list.appendChild(li);
					})
					.finally(function () {
						spinner.classList.remove('is-active');
						btn.disabled = false;
					});
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
