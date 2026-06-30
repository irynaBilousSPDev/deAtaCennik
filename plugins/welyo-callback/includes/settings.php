<?php
/**
 * Ustawienia wtyczki Welyo Callback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WELYO_OPTION_KEY' ) ) {
	define( 'WELYO_OPTION_KEY', 'welyo_callback_settings' );
}

/** Domyślne wartości (API + widget). */
function welyo_default_settings() {
	return array(
		'base_url'        => 'https://ataedu.welyo.pl/external-api',
		'login'           => 'login@ataedu',
		'api_key'         => '',
		'campaign_id'     => '',
		'classifier_id'   => '',
		'campaign_name'   => 'Rekrutacja - formularz WWW (callback)',
		'classifier_name' => '',
		'hash_method'     => 'md5',
		'default_prefix'  => '+48',
		'phone_dial'      => '+48220000000',
		'phone_pretty'    => '+48 22 000 00 00',
		'open_hour'       => 8,
		'close_hour'      => 18,
		'workdays'        => '1,2,3,4,5',
		'privacy_url'     => '/polityka-prywatnosci/',
		'auto_footer'     => 1,
		'text_status_open'       => 'Jesteśmy teraz dostępni',
		'text_status_closed'     => 'Jesteśmy już po godzinach',
		'text_title_open'        => 'Masz pytanie?',
		'text_title_closed'      => 'Zostaw numer',
		'text_sub_open'          => 'Zadzwoń do działu rekrutacji — pomożemy dokończyć zgłoszenie.',
		'text_sub_closed'        => 'Jesteśmy już po godzinach. Zostaw numer — oddzwonimy najszybciej, jak to możliwe.',
		'text_launch_open'       => 'Masz pytanie? Zadzwoń',
		'text_launch_closed'     => 'Masz pytanie? Oddzwonimy',
		'text_call_btn'          => 'Zadzwoń teraz',
		'text_name_label'        => 'Imię',
		'text_name_placeholder'  => 'Jak się do Ciebie zwracać?',
		'text_phone_label'       => 'Numer telefonu',
		'text_phone_placeholder' => 'np. 600 100 200',
		'text_consent'           => 'Wyrażam zgodę na kontakt telefoniczny w sprawie mojej rekrutacji. Rozmowa może być nagrywana w celach jakościowych. <a href="{privacy_url}" target="_blank" rel="noopener">Informacja o przetwarzaniu danych</a>.',
		'text_submit'            => 'Oddzwońcie do mnie',
		'text_done_title'        => 'Dziękujemy!',
		'text_done_scheduled'    => 'Mamy Twój numer. Oddzwonimy najszybciej, jak to możliwe.',
		'text_done_immediate'    => 'Mamy Twój numer. Oddzwaniamy teraz — odbierz proszę połączenie.',
		'text_footer'            => 'Dział Rekrutacji',
		'text_hours_prefix'      => 'Pon–Pt, ',
		'text_error_phone'       => 'Podaj poprawny numer telefonu.',
		'text_error_consent'     => 'Potrzebujemy zgody na kontakt telefoniczny.',
		'text_error_generic'     => 'Coś poszło nie tak. Spróbuj ponownie lub zadzwoń do nas.',
		'text_sending'           => 'Wysyłanie…',
		'color_brand'            => '#2a3a86',
		'color_brand_hover'      => '#3650c8',
		'color_brand_dark'       => '#1a2766',
		'color_accent'           => '#ff5a3c',
		'color_accent_hover'     => '#e8421f',
		'color_text'             => '#1b2347',
		'color_text_muted'       => '#5b6385',
		'color_border'           => '#e6e9f2',
		'color_panel_bg'         => '#ffffff',
		'color_launcher_text'    => '#ffffff',
		'color_status_open'      => '#46e08a',
		'color_status_closed'    => '#ffc24b',
		'color_success'          => '#1f9d63',
		'color_footer_text'      => '#9aa1ba',
		'color_input_bg'         => '#fbfcfe',
		'color_disabled'         => '#c7ccdd',
	);
}

/** Pola kolorów widgetu (klucz opcji → etykieta). */
function welyo_color_fields() {
	return array(
		'color_brand'         => array(
			'label' => __( 'Kolor główny (marka)', 'akademiata' ),
			'desc'  => __( 'Przycisk pływający, nagłówek panelu', 'akademiata' ),
		),
		'color_brand_hover'   => array(
			'label' => __( 'Marka — hover / linki', 'akademiata' ),
			'desc'  => __( 'Najazd na przycisk, linki w zgodzie', 'akademiata' ),
		),
		'color_brand_dark'    => array(
			'label' => __( 'Marka — ciemny (gradient)', 'akademiata' ),
			'desc'  => __( 'Drugi kolor tła nagłówka panelu', 'akademiata' ),
		),
		'color_accent'        => array(
			'label' => __( 'Kolor akcentu (CTA)', 'akademiata' ),
			'desc'  => __( '„Zadzwoń”, „Oddzwońcie do mnie”', 'akademiata' ),
		),
		'color_accent_hover'  => array(
			'label' => __( 'Akcent — hover / błędy', 'akademiata' ),
			'desc'  => __( 'Najazd na CTA, komunikaty błędów', 'akademiata' ),
		),
		'color_text'          => array(
			'label' => __( 'Tekst główny', 'akademiata' ),
		),
		'color_text_muted'    => array(
			'label' => __( 'Tekst drugorzędny', 'akademiata' ),
			'desc'  => __( 'Podtytuły, zgoda, godziny', 'akademiata' ),
		),
		'color_border'        => array(
			'label' => __( 'Obramowania', 'akademiata' ),
		),
		'color_panel_bg'      => array(
			'label' => __( 'Tło panelu', 'akademiata' ),
		),
		'color_launcher_text' => array(
			'label' => __( 'Tekst na przycisku pływającym', 'akademiata' ),
		),
		'color_status_open'   => array(
			'label' => __( 'Status: otwarte', 'akademiata' ),
			'desc'  => __( 'Kropka „jesteśmy dostępni”', 'akademiata' ),
		),
		'color_status_closed' => array(
			'label' => __( 'Status: zamknięte', 'akademiata' ),
			'desc'  => __( 'Kropka po godzinach', 'akademiata' ),
		),
		'color_success'       => array(
			'label' => __( 'Sukces (ptaszek)', 'akademiata' ),
		),
		'color_footer_text'   => array(
			'label' => __( 'Stopka panelu', 'akademiata' ),
		),
		'color_input_bg'      => array(
			'label' => __( 'Tło pól formularza', 'akademiata' ),
		),
		'color_disabled'      => array(
			'label' => __( 'Przycisk wyłączony', 'akademiata' ),
		),
	);
}

function welyo_sanitize_color( $value, $fallback ) {
	$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
	return $color ? strtolower( $color ) : $fallback;
}

function welyo_normalize_hex( $hex ) {
	$hex = welyo_sanitize_color( $hex, '' );
	if ( $hex === '' ) {
		return '';
	}
	if ( strlen( $hex ) === 4 ) {
		$hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
	}
	return $hex;
}

function welyo_hex_rgb( $hex ) {
	$hex = welyo_normalize_hex( $hex );
	if ( $hex === '' ) {
		return '0,0,0';
	}
	return hexdec( substr( $hex, 1, 2 ) ) . ',' . hexdec( substr( $hex, 3, 2 ) ) . ',' . hexdec( substr( $hex, 5, 2 ) );
}

/** Kolory widgetu z ustawień (z fallbackami). */
function welyo_widget_colors() {
	$defaults = welyo_default_settings();
	$out      = array();
	foreach ( array_keys( welyo_color_fields() ) as $key ) {
		$out[ $key ] = welyo_sanitize_color( welyo_cfg( $key ), $defaults[ $key ] );
	}
	return $out;
}

/** Atrybut style z CSS variables dla .wcb-root. */
function welyo_widget_color_style_attr() {
	$c          = welyo_widget_colors();
	$open_rgb   = welyo_hex_rgb( $c['color_status_open'] );
	$closed_rgb = welyo_hex_rgb( $c['color_status_closed'] );
	$success_rgb = welyo_hex_rgb( $c['color_success'] );
	$brand_rgb  = welyo_hex_rgb( $c['color_brand'] );
	$hover_rgb  = welyo_hex_rgb( $c['color_brand_hover'] );

	$vars = array(
		'--b'              => $c['color_brand'],
		'--b2'             => $c['color_brand_hover'],
		'--bd'             => $c['color_brand_dark'],
		'--a'              => $c['color_accent'],
		'--ad'             => $c['color_accent_hover'],
		'--ink'            => $c['color_text'],
		'--soft'           => $c['color_text_muted'],
		'--line'           => $c['color_border'],
		'--panel-bg'       => $c['color_panel_bg'],
		'--launcher-text'  => $c['color_launcher_text'],
		'--dot-open'       => $c['color_status_open'],
		'--dot-closed'     => $c['color_status_closed'],
		'--success'        => $c['color_success'],
		'--foot-text'      => $c['color_footer_text'],
		'--input-bg'       => $c['color_input_bg'],
		'--disabled'       => $c['color_disabled'],
		'--shadow'         => 'rgba(' . $brand_rgb . ',0.34)',
		'--dot-open-glow'  => 'rgba(' . $open_rgb . ',0.25)',
		'--dot-closed-glow' => 'rgba(' . $closed_rgb . ',0.22)',
		'--success-bg'     => 'rgba(' . $success_rgb . ',0.12)',
		'--focus-ring'     => 'rgba(' . $hover_rgb . ',0.14)',
	);

	$parts = array();
	foreach ( $vars as $var => $val ) {
		$parts[] = $var . ':' . $val;
	}

	return esc_attr( implode( ';', $parts ) );
}

/** Aktualny kod języka WPML (np. pl, en). Bez WPML → pl. */
function welyo_get_current_language() {
	if ( function_exists( 'apply_filters' ) ) {
		$lang = apply_filters( 'wpml_current_language', null );
		if ( is_string( $lang ) && $lang !== '' ) {
			return strtolower( $lang );
		}
	}
	if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
		return strtolower( ICL_LANGUAGE_CODE );
	}
	return 'pl';
}

/** Czy widget ma się pokazać w bieżącym języku (na razie tylko PL). */
function welyo_should_show_widget() {
	$allowed = apply_filters( 'welyo_callback_allowed_languages', array( 'pl' ) );
	if ( empty( $allowed ) ) {
		return true;
	}
	$lang = welyo_get_current_language();
	$show = in_array( $lang, $allowed, true );
	return (bool) apply_filters( 'welyo_callback_show_for_language', $show, $lang, $allowed );
}

/** Mapowanie klucza opcji → stała WELYO_*. */
function welyo_constant_map() {
	return array(
		'base_url'        => 'WELYO_BASE_URL',
		'login'           => 'WELYO_LOGIN',
		'api_key'         => 'WELYO_API_KEY',
		'campaign_id'     => 'WELYO_CAMPAIGN_ID',
		'classifier_id'   => 'WELYO_CLASSIFIER_ID',
		'campaign_name'   => 'WELYO_CAMPAIGN_NAME',
		'classifier_name' => 'WELYO_CLASSIFIER_NAME',
		'hash_method'     => 'WELYO_HASH_METHOD',
		'default_prefix'  => 'WELYO_DEFAULT_PREFIX',
		'phone_dial'      => 'WELYO_PHONE_DIAL',
		'phone_pretty'    => 'WELYO_PHONE_PRETTY',
		'open_hour'       => 'WELYO_OPEN_HOUR',
		'close_hour'      => 'WELYO_CLOSE_HOUR',
		'workdays'        => 'WELYO_WORKDAYS',
		'privacy_url'     => 'WELYO_PRIVACY_URL',
	);
}

function welyo_get_saved_settings() {
	$saved = get_option( WELYO_OPTION_KEY, array() );
	return is_array( $saved ) ? $saved : array();
}

function welyo_get_settings() {
	global $welyo_settings_cache;
	if ( ! isset( $welyo_settings_cache ) ) {
		$welyo_settings_cache = array_merge( welyo_default_settings(), welyo_get_saved_settings() );
	}
	return $welyo_settings_cache;
}

function welyo_flush_settings_cache() {
	global $welyo_settings_cache;
	unset( $welyo_settings_cache );
}

/** Wartość: wp-config / welyo-config.php (jeśli niepuste) → panel WP → domyślna. */
function welyo_cfg( $key ) {
	$defaults = welyo_default_settings();
	$settings = welyo_get_settings();
	$map      = welyo_constant_map();

	if ( isset( $map[ $key ] ) && defined( $map[ $key ] ) ) {
		$from_const = constant( $map[ $key ] );
		if ( is_int( $from_const ) || ( is_string( $from_const ) && $from_const !== '' ) ) {
			return $from_const;
		}
	}

	if ( isset( $settings[ $key ] ) && $settings[ $key ] !== '' ) {
		$value = $settings[ $key ];
		if ( $key === 'api_key' ) {
			$value = welyo_decrypt_secret( $value );
		}
		return $value;
	}

	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/** Czy w bazie jest zapisany sekret (bez ujawniania wartości). */
function welyo_has_stored_secret( $key ) {
	$settings = welyo_get_settings();
	return isset( $settings[ $key ] ) && (string) $settings[ $key ] !== '';
}

function welyo_is_encrypted_secret( $value ) {
	return is_string( $value ) && strpos( $value, 'enc:' ) === 0;
}

function welyo_encrypt_secret( $plaintext ) {
	$plaintext = (string) $plaintext;
	if ( $plaintext === '' || ! function_exists( 'openssl_encrypt' ) ) {
		return $plaintext;
	}

	$key    = hash( 'sha256', wp_salt( 'auth' ), true );
	$iv     = function_exists( 'random_bytes' ) ? random_bytes( 16 ) : openssl_random_pseudo_bytes( 16 );
	$cipher = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
	if ( $cipher === false ) {
		return $plaintext;
	}

	return 'enc:' . base64_encode( $iv . $cipher );
}

function welyo_decrypt_secret( $stored ) {
	$stored = (string) $stored;
	if ( $stored === '' || ! welyo_is_encrypted_secret( $stored ) || ! function_exists( 'openssl_decrypt' ) ) {
		return $stored;
	}

	$raw = base64_decode( substr( $stored, 4 ), true );
	if ( $raw === false || strlen( $raw ) < 17 ) {
		return '';
	}

	$key    = hash( 'sha256', wp_salt( 'auth' ), true );
	$plain  = openssl_decrypt( substr( $raw, 16 ), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );

	return $plain !== false ? $plain : '';
}

function welyo_cfg_int( $key ) {
	return (int) welyo_cfg( $key );
}

function welyo_workdays_array() {
	return array_map(
		'intval',
		array_filter(
			array_map( 'trim', explode( ',', (string) welyo_cfg( 'workdays' ) ) ),
			'strlen'
		)
	);
}

function welyo_widget_texts() {
	$settings = welyo_get_settings();
	$defaults = welyo_default_settings();
	$out      = array();
	foreach ( $defaults as $key => $default ) {
		if ( strpos( $key, 'text_' ) !== 0 ) {
			continue;
		}
		$out[ $key ] = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : (string) $default;
	}
	return $out;
}

function welyo_load_config_files() {
	$path = WP_CONTENT_DIR . '/welyo-config.php';
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

/** Generuje treść wp-content/welyo-config.php z zapisanych ustawień. */
function welyo_build_config_php( $settings = null ) {
	if ( $settings === null ) {
		$settings = welyo_get_settings();
	}

	$lines = array(
		'<?php',
		'/** Wygenerowano z panelu Welyo Callback. */',
		"if ( ! defined( 'ABSPATH' ) ) { exit; }",
		'',
	);

	foreach ( welyo_constant_map() as $key => $const ) {
		if ( ! isset( $settings[ $key ] ) || $settings[ $key ] === '' ) {
			continue;
		}
		$val = $settings[ $key ];
		if ( $key === 'api_key' ) {
			$val = welyo_decrypt_secret( $val );
		}
		if ( in_array( $key, array( 'open_hour', 'close_hour' ), true ) ) {
			$php_val = (int) $val;
		} elseif ( is_numeric( $val ) && $key !== 'phone_dial' && $key !== 'phone_pretty' && $key !== 'default_prefix' ) {
			$php_val = (int) $val;
		} else {
			$php_val = "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), (string) $val ) . "'";
		}
		$lines[] = "if ( ! defined( '{$const}' ) ) define( '{$const}', {$php_val} );";
	}

	$lines[] = '';
	return implode( "\n", $lines );
}

function welyo_write_config_file( $settings = null ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$content = welyo_build_config_php( $settings );
	$path    = WP_CONTENT_DIR . '/welyo-config.php';

	if ( WP_Filesystem() ) {
		global $wp_filesystem;
		return (bool) $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE );
	}

	return (bool) file_put_contents( $path, $content );
}

function welyo_sanitize_settings( $input ) {
	$defaults = welyo_default_settings();
	$current  = welyo_get_settings();
	$out      = array();

	$string_keys = array(
		'base_url', 'login', 'campaign_id', 'classifier_id', 'campaign_name', 'classifier_name',
		'hash_method', 'default_prefix', 'phone_dial', 'phone_pretty', 'workdays', 'privacy_url',
		'text_status_open', 'text_status_closed', 'text_title_open', 'text_title_closed',
		'text_sub_open', 'text_sub_closed', 'text_launch_open', 'text_launch_closed',
		'text_call_btn', 'text_name_label', 'text_name_placeholder', 'text_phone_label',
		'text_phone_placeholder', 'text_submit', 'text_done_title', 'text_done_scheduled',
		'text_done_immediate', 'text_footer', 'text_hours_prefix', 'text_error_phone',
		'text_error_consent', 'text_error_generic', 'text_sending',
	);

	foreach ( $string_keys as $key ) {
		if ( ! isset( $input[ $key ] ) ) {
			continue;
		}
		$out[ $key ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
	}

	if ( isset( $input['text_consent'] ) ) {
		$out['text_consent'] = wp_kses_post( wp_unslash( $input['text_consent'] ) );
	}

	if ( ! empty( $input['api_key_clear'] ) ) {
		$out['api_key'] = '';
	} elseif ( isset( $input['api_key'] ) ) {
		$api_key = sanitize_text_field( wp_unslash( $input['api_key'] ) );
		if ( $api_key !== '' ) {
			$out['api_key'] = welyo_encrypt_secret( $api_key );
		} else {
			$out['api_key'] = $current['api_key'];
			if ( $out['api_key'] !== '' && ! welyo_is_encrypted_secret( $out['api_key'] ) ) {
				$out['api_key'] = welyo_encrypt_secret( welyo_decrypt_secret( $out['api_key'] ) );
			}
		}
	}

	$out['open_hour']  = isset( $input['open_hour'] ) ? max( 0, min( 23, (int) $input['open_hour'] ) ) : $defaults['open_hour'];
	$out['close_hour'] = isset( $input['close_hour'] ) ? max( 1, min( 24, (int) $input['close_hour'] ) ) : $defaults['close_hour'];
	$out['auto_footer'] = ! empty( $input['auto_footer'] ) ? 1 : 0;

	if ( isset( $input['hash_method'] ) && in_array( $input['hash_method'], array( 'md5', 'sha1' ), true ) ) {
		$out['hash_method'] = $input['hash_method'];
	}

	foreach ( array_keys( welyo_color_fields() ) as $color_key ) {
		if ( ! isset( $input[ $color_key ] ) ) {
			continue;
		}
		$out[ $color_key ] = welyo_sanitize_color( wp_unslash( $input[ $color_key ] ), $defaults[ $color_key ] );
	}

	return array_merge( $defaults, $current, $out );
}

function welyo_bootstrap_config() {
	welyo_load_config_files();
}
