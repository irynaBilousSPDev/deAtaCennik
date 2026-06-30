<?php
/**
 * Ustawienia wtyczki Welyo Callback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WELYO_OPTION_KEY', 'welyo_callback_settings' );

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
	);
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
	$cache = &welyo_settings_cache();
	if ( $cache === null ) {
		$cache = array_merge( welyo_default_settings(), welyo_get_saved_settings() );
	}
	return $cache;
}

function &welyo_settings_cache() {
	static $cache = null;
	return $cache;
}

function welyo_flush_settings_cache() {
	$cache       = &welyo_settings_cache();
	$cache       = null;
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
		return $settings[ $key ];
	}

	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
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

	if ( isset( $input['api_key'] ) ) {
		$api_key = sanitize_text_field( wp_unslash( $input['api_key'] ) );
		if ( $api_key !== '' ) {
			$out['api_key'] = $api_key;
		} else {
			$out['api_key'] = $current['api_key'];
		}
	}

	$out['open_hour']  = isset( $input['open_hour'] ) ? max( 0, min( 23, (int) $input['open_hour'] ) ) : $defaults['open_hour'];
	$out['close_hour'] = isset( $input['close_hour'] ) ? max( 1, min( 24, (int) $input['close_hour'] ) ) : $defaults['close_hour'];
	$out['auto_footer'] = ! empty( $input['auto_footer'] ) ? 1 : 0;

	if ( isset( $input['hash_method'] ) && in_array( $input['hash_method'], array( 'md5', 'sha1' ), true ) ) {
		$out['hash_method'] = $input['hash_method'];
	}

	return array_merge( $defaults, $current, $out );
}

function welyo_bootstrap_config() {
	welyo_load_config_files();
}
