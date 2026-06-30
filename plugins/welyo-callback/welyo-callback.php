<?php
/**
 * Plugin Name: Welyo Callback (Zadzwoń / Oddzwonimy)
 * Description: Widget kontaktu dla rekrutacji. W godzinach pracy "Zadzwoń", po godzinach "Zostaw numer — oddzwonimy". Lead trafia bezpiecznie do Welyo przez serwer (klucz API nie wychodzi do przeglądarki). Shortcode: [welyo_callback]
 * Version: 1.3.0
 * Author: —
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WELYO_CALLBACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'WELYO_CALLBACK_BASENAME', plugin_basename( __FILE__ ) );

require_once WELYO_CALLBACK_PATH . 'includes/settings.php';

if ( is_admin() ) {
	require_once WELYO_CALLBACK_PATH . 'includes/admin.php';
}

welyo_bootstrap_config();

register_activation_hook( __FILE__, function () {
	if ( get_option( WELYO_OPTION_KEY ) === false ) {
		update_option( WELYO_OPTION_KEY, welyo_default_settings() );
	}
} );


/* =====================================================================
   POMOCNICZE
   ===================================================================== */

/** Czy teraz jest w godzinach pracy (strefa czasowa WP). */
function welyo_is_open_now() {
	$tz   = wp_timezone();
	$now  = new DateTime( 'now', $tz );
	$dow  = (int) $now->format( 'N' );          // 1..7
	$hour = (int) $now->format( 'G' );          // 0..23
	$days = welyo_workdays_array();
	$is_workday = in_array( $dow, $days, true );
	return $is_workday && $hour >= welyo_cfg_int( 'open_hour' ) && $hour < welyo_cfg_int( 'close_hour' );
}

/** Najbliższy roboczy poranek (format YYYY-MM-DD hh:mm) — dla recall po godzinach. */
function welyo_next_working_morning() {
	$tz   = wp_timezone();
	$dt   = new DateTime( 'now', $tz );
	$days = welyo_workdays_array();

	// jeśli dziś jeszcze przed otwarciem i dziś jest dniem roboczym → dzisiejszy poranek
	$today_dow  = (int) $dt->format( 'N' );
	$today_hour = (int) $dt->format( 'G' );
	$open_hour  = welyo_cfg_int( 'open_hour' );
	if ( in_array( $today_dow, $days, true ) && $today_hour < $open_hour ) {
		$dt->setTime( $open_hour, 5 );
		return $dt->format( 'Y-m-d H:i' );
	}

	// w przeciwnym razie szukaj kolejnego dnia roboczego
	for ( $i = 1; $i <= 8; $i++ ) {
		$dt->modify( '+1 day' );
		if ( in_array( (int) $dt->format( 'N' ), $days, true ) ) {
			$dt->setTime( $open_hour, 5 );
			return $dt->format( 'Y-m-d H:i' );
		}
	}
	return $dt->format( 'Y-m-d H:i' );
}

/** Normalizacja numeru do E.164 (proste reguły PL). */
function welyo_normalize_phone( $raw ) {
	$digits = preg_replace( '/[^\d+]/', '', (string) $raw );
	if ( strpos( $digits, '00' ) === 0 ) {                 // 0048... → +48...
		$digits = '+' . substr( $digits, 2 );
	}
	if ( strpos( $digits, '+' ) === 0 ) {
		return $digits;
	}
	$only = preg_replace( '/\D/', '', $digits );
	if ( strlen( $only ) === 9 ) {                          // 9 cyfr → domyślny prefiks
		return welyo_cfg( 'default_prefix' ) . $only;
	}
	if ( strlen( $only ) === 11 && strpos( $only, '48' ) === 0 ) {
		return '+' . $only;
	}
	return welyo_cfg( 'default_prefix' ) . $only;                     // fallback
}

/** Generuje token JWT przez /fcc-create-jwt-token. Zwraca string token lub WP_Error. */
function welyo_get_jwt() {
	$login   = (string) welyo_cfg( 'login' );
	$apikey  = (string) welyo_cfg( 'api_key' );
	$method  = ( strtolower( welyo_cfg( 'hash_method' ) ) === 'sha1' ) ? 'sha1' : 'md5';

	if ( $login === '' || $apikey === '' ) {
		return new WP_Error( 'welyo_config', 'Brak WELYO_LOGIN lub WELYO_API_KEY w konfiguracji.' );
	}

	// login do hasha = część przed @
	$login_local = ( strpos( $login, '@' ) !== false ) ? substr( $login, 0, strpos( $login, '@' ) ) : $login;

	// change = losowe 50 znaków
	$change = wp_generate_password( 50, false, false );

	// hash = method(login_local + change + apikey)
	$hash = ( $method === 'sha1' )
		? sha1( $login_local . $change . $apikey )
		: md5( $login_local . $change . $apikey );

	$resp = wp_remote_post( rtrim( welyo_cfg( 'base_url' ), '/' ) . '/fcc-create-jwt-token', array(
		'timeout' => 15,
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( array(
			'login'  => $login,
			'change' => $change,
			'hash'   => $hash,
			'method' => $method,
		) ),
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$code = wp_remote_retrieve_response_code( $resp );
	$body = wp_remote_retrieve_body( $resp );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'welyo_jwt_http', 'Welyo JWT HTTP ' . $code . ': ' . $body );
	}

	$data = json_decode( $body, true );
	// Welyo może zwrócić token pod różnymi kluczami — sprawdzamy najczęstsze.
	// UWAGA: jeśli token nie zostanie znaleziony, zajrzyj do $body i dopasuj klucz.
	$candidates = array();
	if ( is_array( $data ) ) {
		foreach ( array( 'token', 'jwt', 'access_token', 'data' ) as $k ) {
			if ( isset( $data[ $k ] ) ) {
				$candidates[] = is_array( $data[ $k ] ) && isset( $data[ $k ]['token'] ) ? $data[ $k ]['token'] : $data[ $k ];
			}
		}
	}
	if ( ! empty( $candidates ) && is_string( $candidates[0] ) ) {
		return $candidates[0];
	}
	// czasem API zwraca surowy token jako tekst
	if ( is_string( $data ) && $data !== '' ) {
		return $data;
	}
	if ( preg_match( '/^[A-Za-z0-9\-_\.]+$/', trim( $body ) ) ) {
		return trim( $body );
	}
	return new WP_Error( 'welyo_jwt_parse', 'Nie udało się odczytać tokenu z odpowiedzi: ' . $body );
}

/** POST JSON do endpointu Welyo z autoryzacją JWT. Zwraca tablicę (json) lub WP_Error. */
function welyo_api_post( $jwt, $endpoint, $payload ) {
	$resp = wp_remote_post( rtrim( welyo_cfg( 'base_url' ), '/' ) . $endpoint, array(
		'timeout' => 15,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $jwt, // jeśli Welyo wymaga innego nagłówka — zmień też w welyo_add_record
		),
		'body'    => wp_json_encode( $payload ),
	) );
	if ( is_wp_error( $resp ) ) { return $resp; }
	$code = wp_remote_retrieve_response_code( $resp );
	$body = wp_remote_retrieve_body( $resp );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'welyo_http', 'Welyo ' . $endpoint . ' HTTP ' . $code . ': ' . $body );
	}
	$data = json_decode( $body, true );
	return is_array( $data ) ? $data : array();
}

/** Rekurencyjnie zbiera z odpowiedzi pary {id,name}, niezależnie od opakowania i nazw pól. */
function welyo_collect_items( $node, &$out, $depth = 0 ) {
	if ( ! is_array( $node ) || $depth > 6 ) {
		return;
	}
	$id_keys   = array( 'id', 'campaign_id', 'campaignId', 'id_campaign', 'classifier_id', 'classifierId', 'value' );
	$name_keys = array( 'name', 'campaign_name', 'campaignName', 'classifier_name', 'label', 'text', 'title', 'nazwa', 'description' );
	$is_assoc  = array_keys( $node ) !== range( 0, count( $node ) - 1 );

	if ( $is_assoc ) {
		$id   = null;
		$name = null;
		foreach ( $id_keys as $k ) {
			if ( isset( $node[ $k ] ) && ( is_string( $node[ $k ] ) || is_numeric( $node[ $k ] ) ) ) {
				$id = (string) $node[ $k ];
				break;
			}
		}
		foreach ( $name_keys as $k ) {
			if ( isset( $node[ $k ] ) && is_string( $node[ $k ] ) && $node[ $k ] !== '' ) {
				$name = $node[ $k ];
				break;
			}
		}
		if ( $id !== null && $name !== null ) {
			$out[] = array( 'id' => $id, 'name' => $name );
		}
		foreach ( $node as $v ) {
			if ( is_array( $v ) ) {
				welyo_collect_items( $v, $out, $depth + 1 );
			}
		}
	} else {
		foreach ( $node as $v ) {
			if ( is_array( $v ) ) {
				welyo_collect_items( $v, $out, $depth + 1 );
			}
		}
	}
}

/** Wyciąga z odpowiedzi listę pozycji {id,name} niezależnie od opakowania. */
function welyo_extract_list( $data ) {
	$out = array();
	welyo_collect_items( $data, $out );
	$seen = array();
	$uniq = array();
	foreach ( $out as $it ) {
		if ( ! isset( $seen[ $it['id'] ] ) ) {
			$seen[ $it['id'] ] = 1;
			$uniq[]            = $it;
		}
	}
	return $uniq;
}

/** Tylko litery i cyfry, małymi — do tolerancyjnego porównania nazw. */
function welyo_norm( $s ) {
	$s = (string) $s;
	if ( function_exists( 'mb_strtolower' ) ) {
		$s = mb_strtolower( $s );
	} else {
		$s = strtolower( $s );
	}
	return preg_replace( '/[^\p{L}\p{N}]+/u', '', $s );
}

/** Porównanie nazw (dokładne, bez rozróżniania wielkości liter). */
function welyo_lower( $s ) {
	$s = (string) $s;
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s ) : strtolower( $s );
}

/** Dopasuj pozycję po nazwie: dokładnie → znormalizowane → zawieranie. Zwraca id lub null. */
function welyo_match_id( $items, $target ) {
	$t = welyo_lower( trim( $target ) );
	foreach ( $items as $c ) {
		if ( welyo_lower( trim( $c['name'] ) ) === $t ) {
			return $c['id'];
		}
	}
	$tn = welyo_norm( $target );
	if ( $tn === '' ) {
		return null;
	}
	foreach ( $items as $c ) {
		if ( welyo_norm( $c['name'] ) === $tn ) {
			return $c['id'];
		}
	}
	foreach ( $items as $c ) {
		$cn = welyo_norm( $c['name'] );
		if ( $cn !== '' && ( strpos( $cn, $tn ) !== false || strpos( $tn, $cn ) !== false ) ) {
			return $c['id'];
		}
	}
	return null;
}

/** Loguje nazwy, które realnie przyszły z API (diagnostyka). */
function welyo_log_items( $label, $items ) {
	$names = array();
	foreach ( $items as $c ) {
		$names[] = $c['name'] . ' #' . $c['id'];
	}
	error_log( '[Welyo] ' . $label . ': ' . ( $names ? implode( ' | ', $names ) : '(pusta lista)' ) );
}

/** Zwraca id kampanii: z konfiguracji lub odnalezione po nazwie (cache 1 dzień). */
function welyo_resolve_campaign_id( $jwt ) {
	if ( welyo_cfg( 'campaign_id' ) !== '' ) {
		return (string) welyo_cfg( 'campaign_id' );
	}
	$cached = get_transient( 'welyo_campaign_id' );
	if ( $cached ) {
		return $cached;
	}
	$data  = welyo_api_post( $jwt, '/fcc-campaigns-list', array() );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$items = welyo_extract_list( $data );
	$id    = welyo_match_id( $items, welyo_cfg( 'campaign_name' ) );
	if ( $id !== null ) {
		set_transient( 'welyo_campaign_id', $id, DAY_IN_SECONDS );
		return $id;
	}
	welyo_log_items( 'kampanie z API', $items );
	$preview = array();
	foreach ( array_slice( $items, 0, 12 ) as $c ) {
		$preview[] = $c['name'];
	}
	$hint = $preview
		? ' Dostępne nazwy z API: ' . implode( ' | ', $preview )
		: ' API nie zwróciło żadnej kampanii (możliwy problem z uprawnieniami konta API albo inny format odpowiedzi).';
	return new WP_Error( 'welyo_no_campaign', 'Nie znaleziono kampanii o nazwie: ' . welyo_cfg( 'campaign_name' ) . '.' . $hint );
}

/** Zwraca id klasyfikatora recall: z konfiguracji lub po nazwie w danej kampanii (cache 1 dzień). */
function welyo_resolve_classifier_id( $jwt, $campaign_id ) {
	if ( welyo_cfg( 'classifier_id' ) !== '' ) {
		return (string) welyo_cfg( 'classifier_id' );
	}
	if ( welyo_cfg( 'classifier_name' ) === '' ) {
		return new WP_Error( 'welyo_no_classifier_cfg', 'Brak WELYO_CLASSIFIER_ID i WELYO_CLASSIFIER_NAME.' );
	}
	$cached = get_transient( 'welyo_classifier_id' );
	if ( $cached ) {
		return $cached;
	}
	$data  = welyo_api_post( $jwt, '/fcc-classifiers-list', array( 'campaigns_id' => (string) $campaign_id ) );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$items = welyo_extract_list( $data );
	$id    = welyo_match_id( $items, welyo_cfg( 'classifier_name' ) );
	if ( $id !== null ) {
		set_transient( 'welyo_classifier_id', $id, DAY_IN_SECONDS );
		return $id;
	}
	welyo_log_items( 'klasyfikatory z API', $items );
	$preview = array();
	foreach ( array_slice( $items, 0, 12 ) as $c ) {
		$preview[] = $c['name'];
	}
	$hint = $preview ? ' Dostępne nazwy z API: ' . implode( ' | ', $preview ) : '';
	return new WP_Error( 'welyo_no_classifier', 'Nie znaleziono klasyfikatora: ' . welyo_cfg( 'classifier_name' ) . '.' . $hint . ' (sprawdź error_log)' );
}

/** Dodaje rekord do kampanii przez /fcc-add-records. */
function welyo_add_record( $jwt, $campaign_id, $classifier_id, $name, $phone_e164, $recall_or_null, $ext_id ) {
	// Numer leci do dzwonienia w "numbers"; jego etykietą jest imię, więc konsultant
	// widzi je przy rekordzie. "values.TELEFON" wypełnia pole znaczące (TELEFON),
	// czyli to, co pokazuje się na listach recall / nagrań.
	$number_entry = ( $name !== '' ) ? array( 'name' => $name, 'value' => $phone_e164 ) : $phone_e164;
	$record = array(
		'values'  => array( 'TELEFON' => $phone_e164 ), // nazwa klucza MUSI = nazwie pola w "Podstawowe dane"
		'numbers' => array( $number_entry ),
		'ext_id'  => $ext_id,
	);
	if ( $recall_or_null ) {
		$record['recall']         = $recall_or_null;        // YYYY-MM-DD hh:mm
		$record['classifiers_id'] = (string) $classifier_id; // wymagany przy recall
	} else {
		$record['priority'] = '100'; // w godzinach pracy — wyższy priorytet wydzwaniania
	}

	$payload = array(
		'campaigns_id' => (string) $campaign_id,
		'records'      => array( $record ),
	);

	$resp = wp_remote_post( rtrim( welyo_cfg( 'base_url' ), '/' ) . '/fcc-add-records', array(
		'timeout' => 15,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $jwt, // jeśli Welyo wymaga innego nagłówka — tu go zmień
		),
		'body'    => wp_json_encode( $payload ),
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$code = wp_remote_retrieve_response_code( $resp );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'welyo_add_http', 'Welyo add-records HTTP ' . $code . ': ' . wp_remote_retrieve_body( $resp ) );
	}
	return true;
}


/* =====================================================================
   DIAGNOSTYKA API (panel WP)
   ===================================================================== */

/** Kroki testu połączenia z Welyo (bez ujawniania sekretów). */
function welyo_run_diagnostics() {
	$steps = array();

	$login   = (string) welyo_cfg( 'login' );
	$api_key = (string) welyo_cfg( 'api_key' );
	$config_ok = ( $login !== '' && $api_key !== '' );

	$steps[] = array(
		'id'      => 'config',
		'ok'      => $config_ok,
		'message' => $config_ok
			? __( 'Login i klucz API są ustawione.', 'akademiata' )
			: __( 'Brak loginu lub klucza API — uzupełnij w sekcji API Welyo i zapisz.', 'akademiata' ),
	);

	if ( ! $config_ok ) {
		return $steps;
	}

	$jwt = welyo_get_jwt();
	if ( is_wp_error( $jwt ) ) {
		$steps[] = array(
			'id'      => 'jwt',
			'ok'      => false,
			'message' => $jwt->get_error_message(),
		);
		return $steps;
	}

	$steps[] = array(
		'id'      => 'jwt',
		'ok'      => true,
		'message' => __( 'Połączenie z API — token JWT uzyskany.', 'akademiata' ),
	);

	$campaign_id = welyo_resolve_campaign_id( $jwt );
	if ( is_wp_error( $campaign_id ) ) {
		$steps[] = array(
			'id'      => 'campaign',
			'ok'      => false,
			'message' => $campaign_id->get_error_message(),
		);
		return $steps;
	}

	$campaign_label = welyo_cfg( 'campaign_id' ) !== ''
		? sprintf( __( 'Kampania ID %s (z ustawień).', 'akademiata' ), $campaign_id )
		: sprintf(
			/* translators: 1: campaign id, 2: campaign name */
			__( 'Kampania ID %1$s (nazwa: „%2$s”).', 'akademiata' ),
			$campaign_id,
			welyo_cfg( 'campaign_name' )
		);

	$steps[] = array(
		'id'      => 'campaign',
		'ok'      => true,
		'message' => $campaign_label,
	);

	$classifier_cfg = welyo_cfg( 'classifier_id' ) !== '' || welyo_cfg( 'classifier_name' ) !== '';
	if ( $classifier_cfg ) {
		$classifier_id = welyo_resolve_classifier_id( $jwt, $campaign_id );
		if ( is_wp_error( $classifier_id ) ) {
			$steps[] = array(
				'id'      => 'classifier',
				'ok'      => false,
				'message' => $classifier_id->get_error_message(),
			);
		} else {
			$steps[] = array(
				'id'      => 'classifier',
				'ok'      => true,
				'message' => sprintf( __( 'Klasyfikator recall ID %s.', 'akademiata' ), $classifier_id ),
			);
		}
	} else {
		$steps[] = array(
			'id'      => 'classifier',
			'ok'      => true,
			'message' => __( 'Brak klasyfikatora recall — po godzinach lead trafi do kampanii bez zaplanowanego recall.', 'akademiata' ),
		);
	}

	$all_ok = true;
	foreach ( $steps as $step ) {
		if ( empty( $step['ok'] ) ) {
			$all_ok = false;
			break;
		}
	}

	$steps[] = array(
		'id'      => 'summary',
		'ok'      => $all_ok,
		'message' => $all_ok
			? __( 'Konfiguracja wygląda poprawnie. Jeśli formularz nadal nie działa, sprawdź log serwera (wp-content/debug.log, wpisy [Welyo]).', 'akademiata' )
			: __( 'Napraw kroki oznaczone na czerwono, zapisz ustawienia i uruchom test ponownie.', 'akademiata' ),
	);

	return $steps;
}

/** Limit zgłoszeń z formularza (nie dotyczy administratorów). */
function welyo_check_rate_limit() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$key = 'welyo_rl_' . md5( $ip );
	$cnt = (int) get_transient( $key );

	if ( $cnt >= 8 ) {
		return new WP_Error( 'welyo_rate', __( 'Zbyt wiele prób. Spróbuj później.', 'akademiata' ), array( 'status' => 429 ) );
	}

	set_transient( $key, $cnt + 1, 10 * MINUTE_IN_SECONDS );
	return true;
}


/* =====================================================================
   ENDPOINT REST: POST /wp-json/welyo/v1/callback
   ===================================================================== */

add_action( 'rest_api_init', function () {
	register_rest_route( 'welyo/v1', '/callback', array(
		'methods'             => 'POST',
		'callback'            => 'welyo_handle_callback',
		'permission_callback' => 'welyo_permission_check',
	) );

	register_rest_route( 'welyo/v1', '/diagnostics', array(
		'methods'             => 'GET',
		'callback'            => function () {
			return new WP_REST_Response( array( 'steps' => welyo_run_diagnostics() ), 200 );
		},
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );
} );

/** Lekka ochrona: nonce + limit zapytań na IP (po walidacji formularza). */
function welyo_permission_check( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'x_wp_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error( 'welyo_nonce', __( 'Nieprawidłowy token żądania.', 'akademiata' ), array( 'status' => 403 ) );
	}
	return true;
}

function welyo_handle_callback( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) { $params = $request->get_params(); }

	$name    = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
	$phone   = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
	$consent = ! empty( $params['consent'] );
	$hp      = isset( $params['company'] ) ? trim( (string) $params['company'] ) : ''; // honeypot

	// honeypot wypełniony → bot
	if ( $hp !== '' ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 ); // udajemy sukces
	}
	$digits = preg_replace( '/\D/', '', $phone );
	if ( strlen( $digits ) < 9 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'phone' ), 200 );
	}
	if ( ! $consent ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'consent' ), 200 );
	}

	$rate = welyo_check_rate_limit();
	if ( is_wp_error( $rate ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate' ), 200 );
	}

	$jwt = welyo_get_jwt();
	if ( is_wp_error( $jwt ) ) {
		error_log( '[Welyo] JWT: ' . $jwt->get_error_message() );
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'auth' ), 200 );
	}

	$recall = welyo_is_open_now() ? null : welyo_next_working_morning();
	$phone_e164 = welyo_normalize_phone( $phone );
	$ext_id = 'web-' . gmdate( 'Ymd-His' ) . '-' . substr( md5( $phone_e164 . microtime() ), 0, 6 );

	// id kampanii (z konfiguracji albo po nazwie)
	$campaign_id = welyo_resolve_campaign_id( $jwt );
	if ( is_wp_error( $campaign_id ) ) {
		error_log( '[Welyo] campaign: ' . $campaign_id->get_error_message() );
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'campaign' ), 200 );
	}

	// id klasyfikatora potrzebny tylko przy recall (po godzinach)
	$classifier_id = '';
	if ( $recall ) {
		$classifier_id = welyo_resolve_classifier_id( $jwt, $campaign_id );
		if ( is_wp_error( $classifier_id ) ) {
			error_log( '[Welyo] classifier: ' . $classifier_id->get_error_message() );
			// awaryjnie: dodaj bez recall (lead trafi do kampanii i będzie wydzwaniany w godzinach)
			$recall = null;
			$classifier_id = '';
		}
	}

	$res = welyo_add_record( $jwt, $campaign_id, $classifier_id, $name, $phone_e164, $recall, $ext_id );
	if ( is_wp_error( $res ) ) {
		error_log( '[Welyo] add-records: ' . $res->get_error_message() );
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'welyo' ), 200 );
	}

	return new WP_REST_Response( array( 'ok' => true, 'scheduled' => (bool) $recall ), 200 );
}


/* =====================================================================
   SHORTCODE: [welyo_callback]  → renderuje widget na stronie
   ===================================================================== */

add_shortcode( 'welyo_callback', 'welyo_render_widget' );

function welyo_render_widget() {
	if ( ! welyo_should_show_widget() ) {
		return '';
	}

	$texts = welyo_widget_texts();
	$privacy_url = esc_url( welyo_cfg( 'privacy_url' ) );
	$consent_html = str_replace( '{privacy_url}', $privacy_url, $texts['text_consent'] );

	$cfg = array(
		'rest'        => esc_url_raw( rest_url( 'welyo/v1/callback' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'phoneDial'   => welyo_cfg( 'phone_dial' ),
		'phonePretty' => welyo_cfg( 'phone_pretty' ),
		'openHour'    => welyo_cfg_int( 'open_hour' ),
		'closeHour'   => welyo_cfg_int( 'close_hour' ),
		'workdays'    => welyo_workdays_array(),
		'privacyUrl'  => $privacy_url,
		'texts'       => array(
			'statusOpen'      => $texts['text_status_open'],
			'statusClosed'    => $texts['text_status_closed'],
			'titleOpen'       => $texts['text_title_open'],
			'titleClosed'     => $texts['text_title_closed'],
			'subOpen'         => $texts['text_sub_open'],
			'subClosed'       => $texts['text_sub_closed'],
			'launchOpen'      => $texts['text_launch_open'],
			'launchClosed'    => $texts['text_launch_closed'],
			'callBtn'         => $texts['text_call_btn'],
			'doneScheduled'   => $texts['text_done_scheduled'],
			'doneImmediate'   => $texts['text_done_immediate'],
			'hoursPrefix'     => $texts['text_hours_prefix'],
			'errorPhone'      => $texts['text_error_phone'],
			'errorConsent'    => $texts['text_error_consent'],
			'errorAuth'       => $texts['text_error_auth'],
			'errorCampaign'   => $texts['text_error_campaign'],
			'errorWelyo'      => $texts['text_error_welyo'],
			'errorRate'       => $texts['text_error_rate'],
			'errorNonce'      => $texts['text_error_nonce'],
			'errorGeneric'    => $texts['text_error_generic'],
			'sending'         => $texts['text_sending'],
			'submit'          => $texts['text_submit'],
		),
	);
	$json = wp_json_encode( $cfg );

	ob_start();
	?>
<div class="wcb-root" id="wcbRoot" data-mode="call" style="<?php echo welyo_widget_color_style_attr(); ?>">
  <div class="wcb-panel" role="dialog" aria-modal="false" aria-labelledby="wcbTitle">
    <div class="wcb-head">
      <span class="wcb-status"><span class="wcb-dot"></span><span id="wcbStatus"><?php echo esc_html( $texts['text_status_open'] ); ?></span></span>
      <h2 class="wcb-title" id="wcbTitle"><?php echo esc_html( $texts['text_title_open'] ); ?></h2>
      <p class="wcb-sub" id="wcbSub"><?php echo esc_html( $texts['text_sub_open'] ); ?></p>
      <button class="wcb-close" id="wcbClose" aria-label="Zamknij">&times;</button>
    </div>
    <div class="wcb-body">
      <div id="wcbModeCall">
        <a class="wcb-callbtn" id="wcbCallLink" href="tel:">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <?php echo esc_html( $texts['text_call_btn'] ); ?>
        </a>
        <p class="wcb-number" id="wcbNumber"></p>
        <p class="wcb-hours" id="wcbHours"></p>
      </div>
      <div id="wcbModeCb" class="wcb-hidden">
        <div class="wcb-field"><label for="wcbName"><?php echo esc_html( $texts['text_name_label'] ); ?></label>
          <input type="text" id="wcbName" autocomplete="given-name" placeholder="<?php echo esc_attr( $texts['text_name_placeholder'] ); ?>"></div>
        <div class="wcb-field"><label for="wcbPhone"><?php echo esc_html( $texts['text_phone_label'] ); ?></label>
          <input type="tel" id="wcbPhone" autocomplete="tel" inputmode="tel" placeholder="<?php echo esc_attr( $texts['text_phone_placeholder'] ); ?>"></div>
        <input type="text" id="wcbCompany" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">
        <p class="wcb-err" id="wcbErr"></p>
        <div class="wcb-consent">
          <input type="checkbox" id="wcbConsent">
          <label for="wcbConsent"><?php echo wp_kses_post( $consent_html ); ?></label>
        </div>
        <button class="wcb-submit" id="wcbSubmit"><?php echo esc_html( $texts['text_submit'] ); ?></button>
      </div>
      <div id="wcbDone" class="wcb-done wcb-hidden">
        <div class="wcb-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <h3><?php echo esc_html( $texts['text_done_title'] ); ?></h3>
        <p id="wcbDoneMsg"><?php echo esc_html( $texts['text_done_scheduled'] ); ?></p>
      </div>
    </div>
    <div class="wcb-foot"><?php echo esc_html( $texts['text_footer'] ); ?></div>
  </div>
  <button class="wcb-launcher" id="wcbLauncher" aria-haspopup="dialog" aria-expanded="false">
    <span class="wcb-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
    <span id="wcbLaunchLabel"><?php echo esc_html( $texts['text_launch_open'] ); ?></span>
  </button>
</div>

<style>
.wcb-root{position:fixed;right:22px;bottom:22px;z-index:99999;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.wcb-launcher{display:inline-flex;align-items:center;gap:12px;border:0;cursor:pointer;background:var(--b);color:var(--launcher-text);padding:14px 20px 14px 16px;border-radius:999px;font-weight:700;font-size:15px;box-shadow:0 18px 50px -12px var(--shadow);transition:transform .18s,background .18s}
.wcb-launcher:hover{transform:translateY(-2px);background:var(--b2)}
.wcb-launcher:focus-visible{outline:3px solid var(--panel-bg);outline-offset:3px}
.wcb-ic{position:relative;width:38px;height:38px;flex:none;display:grid;place-items:center;border-radius:50%;background:rgba(255,255,255,.16)}
.wcb-ic svg{width:19px;height:19px}
.wcb-root[data-mode="call"] .wcb-ic::after{content:"";position:absolute;width:38px;height:38px;border-radius:50%;border:2px solid var(--a);animation:wcbp 2.2s ease-out infinite}
@keyframes wcbp{0%{transform:scale(1);opacity:.7}100%{transform:scale(1.55);opacity:0}}
.wcb-panel{position:absolute;right:0;bottom:calc(100% + 14px);width:340px;max-width:calc(100vw - 32px);background:var(--panel-bg);border:1px solid var(--line);border-radius:20px;box-shadow:0 18px 50px -12px var(--shadow);overflow:hidden;transform-origin:bottom right;opacity:0;transform:translateY(8px) scale(.98);pointer-events:none;transition:opacity .2s,transform .2s}
.wcb-root.is-open .wcb-panel{opacity:1;transform:none;pointer-events:auto}
.wcb-head{background:linear-gradient(135deg,var(--b),var(--bd));color:var(--launcher-text);padding:20px 20px 18px;position:relative}
.wcb-status{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;opacity:.92}
.wcb-dot{width:8px;height:8px;border-radius:50%;flex:none}
.wcb-root[data-mode="call"] .wcb-dot{background:var(--dot-open);box-shadow:0 0 0 4px var(--dot-open-glow)}
.wcb-root[data-mode="callback"] .wcb-dot{background:var(--dot-closed);box-shadow:0 0 0 4px var(--dot-closed-glow)}
.wcb-title{font-size:19px;font-weight:800;line-height:1.2;margin:12px 0 4px}
.wcb-sub{font-size:13.5px;line-height:1.55;opacity:.88;margin:0}
.wcb-close{position:absolute;top:14px;right:14px;width:30px;height:30px;border:0;border-radius:50%;background:rgba(255,255,255,.14);color:var(--launcher-text);cursor:pointer;font-size:18px;line-height:1;display:grid;place-items:center}
.wcb-close:hover{background:rgba(255,255,255,.26)}
.wcb-body{padding:18px 20px 20px}
.wcb-callbtn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;text-decoration:none;background:var(--a);color:var(--launcher-text);padding:15px;border-radius:13px;font-weight:800;font-size:16px;transition:background .18s,transform .18s}
.wcb-callbtn:hover{background:var(--ad);transform:translateY(-1px)}
.wcb-callbtn svg{width:20px;height:20px}
.wcb-number{text-align:center;margin:12px 0 0;font-size:15px;font-weight:700;color:var(--ink)}
.wcb-hours{text-align:center;margin:4px 0 0;font-size:12.5px;color:var(--soft)}
.wcb-field{margin-bottom:12px}
.wcb-field label{display:block;font-size:12.5px;font-weight:600;color:var(--ink);margin-bottom:5px}
.wcb-field input{width:100%;border:1px solid var(--line);border-radius:11px;padding:12px 13px;font-size:15px;color:var(--ink);background:var(--input-bg);box-sizing:border-box}
.wcb-field input:focus{outline:0;border-color:var(--b2);box-shadow:0 0 0 3px var(--focus-ring);background:var(--panel-bg)}
.wcb-consent{display:flex;gap:9px;align-items:flex-start;margin:4px 0 14px}
.wcb-consent input{margin-top:2px;width:16px;height:16px;accent-color:var(--b);flex:none;cursor:pointer}
.wcb-consent label{font-size:11.5px;line-height:1.5;color:var(--soft);cursor:pointer}
.wcb-consent a{color:var(--b2)}
.wcb-submit{width:100%;border:0;cursor:pointer;background:var(--a);color:var(--launcher-text);padding:14px;border-radius:13px;font-weight:800;font-size:15.5px;transition:background .18s,transform .18s}
.wcb-submit:hover:not(:disabled){background:var(--ad);transform:translateY(-1px)}
.wcb-submit:disabled{background:var(--disabled);cursor:not-allowed}
.wcb-err{color:var(--ad);font-size:12px;margin:-6px 0 10px;min-height:0}
.wcb-done{text-align:center;padding:8px 4px 6px}
.wcb-check{width:56px;height:56px;margin:0 auto 14px;border-radius:50%;background:var(--success-bg);display:grid;place-items:center;color:var(--success)}
.wcb-check svg{width:28px;height:28px}
.wcb-done h3{margin:0 0 6px;font-size:18px;font-weight:800;color:var(--ink)}
.wcb-done p{margin:0;font-size:13.5px;line-height:1.55;color:var(--soft)}
.wcb-foot{text-align:center;padding:11px;font-size:11px;color:var(--foot-text);border-top:1px solid var(--line)}
.wcb-hidden{display:none !important}
@media (prefers-reduced-motion:reduce){.wcb-launcher,.wcb-panel,.wcb-callbtn,.wcb-submit{transition:none}.wcb-root[data-mode="call"] .wcb-ic::after{animation:none}}
</style>

<script>
(function(){
  "use strict";
  var CFG = <?php echo $json; // już zakodowane przez wp_json_encode ?>;
  var T = CFG.texts || {};
  var root=document.getElementById("wcbRoot"),launcher=document.getElementById("wcbLauncher"),
      closeBtn=document.getElementById("wcbClose"),modeCall=document.getElementById("wcbModeCall"),
      modeCb=document.getElementById("wcbModeCb"),done=document.getElementById("wcbDone"),
      submit=document.getElementById("wcbSubmit"),err=document.getElementById("wcbErr");

  document.getElementById("wcbNumber").textContent=CFG.phonePretty;
  document.getElementById("wcbCallLink").href="tel:"+CFG.phoneDial;
  var hh=(""+CFG.openHour).padStart(2,"0")+":00–"+(""+CFG.closeHour).padStart(2,"0")+":00";
  document.getElementById("wcbHours").textContent=(T.hoursPrefix||"")+hh;

  function openNow(){var n=new Date(),d=n.getDay()===0?7:n.getDay(),h=n.getHours();
    return CFG.workdays.indexOf(d)!==-1 && h>=CFG.openHour && h<CFG.closeHour;}
  function render(){
    var call=openNow();
    root.setAttribute("data-mode",call?"call":"callback");
    done.classList.add("wcb-hidden");
    if(call){
      document.getElementById("wcbLaunchLabel").textContent=T.launchOpen||"";
      document.getElementById("wcbStatus").textContent=T.statusOpen||"";
      document.getElementById("wcbTitle").textContent=T.titleOpen||"";
      document.getElementById("wcbSub").textContent=T.subOpen||"";
      modeCall.classList.remove("wcb-hidden");modeCb.classList.add("wcb-hidden");
    }else{
      document.getElementById("wcbLaunchLabel").textContent=T.launchClosed||"";
      document.getElementById("wcbStatus").textContent=T.statusClosed||"";
      document.getElementById("wcbTitle").textContent=T.titleClosed||"";
      document.getElementById("wcbSub").textContent=T.subClosed||"";
      modeCall.classList.add("wcb-hidden");modeCb.classList.remove("wcb-hidden");
    }
  }
  function open(){root.classList.add("is-open");launcher.setAttribute("aria-expanded","true");}
  function close(){root.classList.remove("is-open");launcher.setAttribute("aria-expanded","false");launcher.focus();}
  launcher.addEventListener("click",function(){root.classList.contains("is-open")?close():(render(),open());});
  closeBtn.addEventListener("click",close);
  document.addEventListener("keydown",function(e){if(e.key==="Escape"&&root.classList.contains("is-open"))close();});

  submit.addEventListener("click",function(){
    var name=document.getElementById("wcbName").value.trim();
    var phEl=document.getElementById("wcbPhone"),phone=phEl.value.trim();
    var consent=document.getElementById("wcbConsent").checked;
    var company=document.getElementById("wcbCompany").value;
    err.textContent="";
    if(phone.replace(/\D/g,"").length<9){err.textContent=T.errorPhone||"";phEl.focus();return;}
    if(!consent){err.textContent=T.errorConsent||"";return;}
    submit.disabled=true;submit.textContent=T.sending||"…";
    fetch(CFG.rest,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":CFG.nonce},
      body:JSON.stringify({name:name,phone:phone,consent:true,company:company})})
      .then(function(r){return r.json().then(function(d){return {ok:r.ok,status:r.status,data:d};});})
      .then(function(res){
        var d=res.data;
        if(!res.ok){
          var code=d&&d.code;
          if(code==="welyo_nonce"){err.textContent=T.errorNonce||T.errorGeneric||"";}
          else if(code==="welyo_rate"){err.textContent=T.errorRate||T.errorGeneric||"";}
          else{err.textContent=T.errorGeneric||"";}
          submit.disabled=false;submit.textContent=T.submit||"";
          return;
        }
        if(d&&d.ok){modeCb.classList.add("wcb-hidden");done.classList.remove("wcb-hidden");
          document.getElementById("wcbDoneMsg").textContent=d.scheduled===false?(T.doneImmediate||""):(T.doneScheduled||"");
        }else{
          var code=d&&d.error;
          var map={phone:T.errorPhone,consent:T.errorConsent,auth:T.errorAuth,campaign:T.errorCampaign,welyo:T.errorWelyo,rate:T.errorRate};
          err.textContent=(code&&map[code])?map[code]:(T.errorGeneric||"");
          submit.disabled=false;submit.textContent=T.submit||"";
        }
      })
      .catch(function(){err.textContent=T.errorGeneric||"";submit.disabled=false;submit.textContent=T.submit||"";});
  });

  render();
  setInterval(render,60000);
})();
</script>
	<?php
	return ob_get_clean();
}

/** Globalny widget w stopce. */
add_action( 'wp_footer', function () {
	if ( is_admin() || ! welyo_cfg_int( 'auto_footer' ) ) {
		return;
	}
	if ( ! apply_filters( 'welyo_callback_auto_footer', true ) ) {
		return;
	}
	static $rendered = false;
	if ( $rendered ) {
		return;
	}
	$rendered = true;
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo welyo_render_widget();
}, 5 );
