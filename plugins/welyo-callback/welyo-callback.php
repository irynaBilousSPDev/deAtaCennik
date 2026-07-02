<?php
/**
 * Plugin Name: Welyo Callback (Zadzwoń / Oddzwonimy)
 * Description: Widget kontaktu dla rekrutacji. W godzinach pracy "Zadzwoń", po godzinach "Zostaw numer — oddzwonimy". Lead trafia bezpiecznie do Welyo przez serwer (klucz API nie wychodzi do przeglądarki). Shortcode: [welyo_callback]
 * Version: 1.5.0
 * Author: —
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WELYO_CALLBACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'WELYO_CALLBACK_BASENAME', plugin_basename( __FILE__ ) );

require_once WELYO_CALLBACK_PATH . 'includes/settings.php';
require_once WELYO_CALLBACK_PATH . 'includes/forminator-bridge.php';

if ( is_admin() ) {
	require_once WELYO_CALLBACK_PATH . 'includes/admin.php';
}

welyo_bootstrap_config();

register_activation_hook( __FILE__, function () {
	if ( get_option( WELYO_OPTION_KEY ) === false ) {
		$settings = array_merge( welyo_default_global_settings(), array(
			'settings_version' => 2,
			'languages'        => array(),
		) );
		foreach ( welyo_supported_languages() as $code => $label ) {
			$settings['languages'][ $code ] = welyo_default_lang_settings( $code );
		}
		update_option( WELYO_OPTION_KEY, $settings );
	}
} );


/* =====================================================================
   POMOCNICZE
   ===================================================================== */

/** Czy teraz jest w godzinach pracy (strefa czasowa WP). */
function welyo_is_open_now( $lang = null ) {
	$tz   = wp_timezone();
	$now  = new DateTime( 'now', $tz );
	$dow  = (int) $now->format( 'N' );
	$hour = (int) $now->format( 'G' );
	$days = welyo_workdays_array( $lang );
	$is_workday = in_array( $dow, $days, true );
	return $is_workday && $hour >= welyo_cfg_int( 'open_hour', $lang ) && $hour < welyo_cfg_int( 'close_hour', $lang );
}

/** Najbliższy roboczy poranek (format YYYY-MM-DD hh:mm) — dla recall po godzinach. */
function welyo_next_working_morning( $lang = null ) {
	$tz   = wp_timezone();
	$dt   = new DateTime( 'now', $tz );
	$days = welyo_workdays_array( $lang );

	// jeśli dziś jeszcze przed otwarciem i dziś jest dniem roboczym → dzisiejszy poranek
	$today_dow  = (int) $dt->format( 'N' );
	$today_hour = (int) $dt->format( 'G' );
	$open_hour  = welyo_cfg_int( 'open_hour', $lang );
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
function welyo_normalize_phone( $raw, $lang = null ) {
	$digits = preg_replace( '/[^\d+]/', '', (string) $raw );
	if ( strpos( $digits, '00' ) === 0 ) {                 // 0048... → +48...
		$digits = '+' . substr( $digits, 2 );
	}
	if ( strpos( $digits, '+' ) === 0 ) {
		return $digits;
	}
	$only = preg_replace( '/\D/', '', $digits );
	if ( strlen( $only ) === 9 ) {                          // 9 cyfr → domyślny prefiks
		return welyo_cfg( 'default_prefix', $lang ) . $only;
	}
	if ( strlen( $only ) === 11 && strpos( $only, '48' ) === 0 ) {
		return '+' . $only;
	}
	return welyo_cfg( 'default_prefix', $lang ) . $only;                     // fallback
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
	$jwt  = welyo_extract_jwt_from_response( $body, $data );
	if ( is_wp_error( $jwt ) ) {
		return $jwt;
	}
	return $jwt;
}

/** Czy string wygląda jak token JWT. */
function welyo_is_jwt_shape( $token ) {
	$token = trim( (string) $token );
	return (bool) preg_match( '/^eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token );
}

/** Wyciąga JWT z odpowiedzi /fcc-create-jwt-token. */
function welyo_extract_jwt_from_response( $body, $data = null ) {
	if ( ! is_array( $data ) ) {
		$data = json_decode( $body, true );
	}
	if ( is_array( $data ) && isset( $data['success'] ) && ! $data['success'] ) {
		$msg = isset( $data['message'] ) ? (string) $data['message'] : substr( (string) $body, 0, 300 );
		return new WP_Error( 'welyo_jwt_api', 'Welyo JWT: ' . $msg );
	}

	$keys = array( 'token', 'jwt', 'access_token', 'accessToken', 'jwt_token' );
	if ( is_array( $data ) ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) && welyo_is_jwt_shape( $data[ $key ] ) ) {
				return $data[ $key ];
			}
		}
		if ( ! empty( $data['data'] ) ) {
			if ( is_string( $data['data'] ) && welyo_is_jwt_shape( $data['data'] ) ) {
				return $data['data'];
			}
			if ( is_array( $data['data'] ) ) {
				foreach ( $keys as $key ) {
					if ( ! empty( $data['data'][ $key ] ) && is_string( $data['data'][ $key ] ) && welyo_is_jwt_shape( $data['data'][ $key ] ) ) {
						return $data['data'][ $key ];
					}
				}
			}
		}
	}

	$trim = trim( (string) $body );
	if ( welyo_is_jwt_shape( $trim ) ) {
		return $trim;
	}

	return new WP_Error( 'welyo_jwt_parse', 'Nie udało się odczytać tokenu JWT z odpowiedzi: ' . substr( (string) $body, 0, 300 ) );
}

/** Czy odpowiedź Welyo zawiera success:false (HTTP 200). */
function welyo_api_response_is_fail( $data ) {
	return is_array( $data ) && array_key_exists( 'success', $data ) && ! $data['success'];
}

/** Tryby autoryzacji JWT — auto wykrywa działający i cache'uje na dobę. */
function welyo_auth_modes_list() {
	$cached = get_transient( 'welyo_auth_mode' );
	$all    = array( 'body_jwt', 'bearer', 'jwt_header', 'raw', 'body_token' );
	if ( $cached && in_array( $cached, $all, true ) ) {
		return array_merge( array( $cached ), array_values( array_diff( $all, array( $cached ) ) ) );
	}
	return $all;
}

/** Nagłówki i body dla wybranego trybu autoryzacji. */
function welyo_build_auth_request( $jwt, $mode, $payload ) {
	$payload = is_array( $payload ) ? $payload : array();
	$headers = array( 'Content-Type' => 'application/json' );

	switch ( $mode ) {
		case 'body_jwt':
			$payload['jwt'] = $jwt;
			return array(
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
			);
		case 'body_token':
			$payload['token'] = $jwt;
			return array(
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
			);
		case 'jwt_header':
			$headers['Authorization'] = 'JWT ' . $jwt;
			break;
		case 'raw':
			$headers['Authorization'] = $jwt;
			break;
		default:
			$headers['Authorization'] = 'Bearer ' . $jwt;
			break;
	}

	return array(
		'headers' => $headers,
		'body'    => wp_json_encode( empty( $payload ) ? (object) array() : $payload ),
	);
}

/** Jedno żądanie POST z danym trybem autoryzacji. */
function welyo_api_post_once( $jwt, $endpoint, $payload, $mode ) {
	$req  = welyo_build_auth_request( $jwt, $mode, $payload );
	$resp = wp_remote_post( rtrim( welyo_cfg( 'base_url' ), '/' ) . $endpoint, array(
		'timeout' => 15,
		'headers' => $req['headers'],
		'body'    => $req['body'],
	) );
	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$code = wp_remote_retrieve_response_code( $resp );
	$body = wp_remote_retrieve_body( $resp );
	welyo_last_raw( $body );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'welyo_http', 'Welyo ' . $endpoint . ' HTTP ' . $code . ' (' . $mode . '): ' . $body );
	}
	$data = json_decode( $body, true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'welyo_json', 'Nieprawidłowa odpowiedź JSON z ' . $endpoint . ': ' . substr( (string) $body, 0, 300 ) );
	}
	if ( welyo_api_response_is_fail( $data ) ) {
		$msg = isset( $data['message'] ) ? (string) $data['message'] : 'Welyo API error';
		return new WP_Error( 'welyo_api', $msg . ' [' . $mode . ']' );
	}
	return $data;
}

/** Ostatnia surowa odpowiedź API (diagnostyka). */
function welyo_last_raw( $set = null ) {
	static $raw = '';
	if ( $set !== null ) {
		$raw = (string) $set;
	}
	return $raw;
}

/** POST JSON do endpointu Welyo z autoryzacją JWT. Zwraca tablicę (json) lub WP_Error. */
function welyo_api_post( $jwt, $endpoint, $payload ) {
	$modes      = welyo_auth_modes_list();
	$last_error = null;

	foreach ( $modes as $mode ) {
		$result = welyo_api_post_once( $jwt, $endpoint, $payload, $mode );
		if ( is_wp_error( $result ) ) {
			$last_error = $result;
			continue;
		}
		set_transient( 'welyo_auth_mode', $mode, DAY_IN_SECONDS );
		return $result;
	}

	return $last_error ? $last_error : new WP_Error( 'welyo_api', 'Welyo API request failed.' );
}

/** Rekurencyjnie zbiera z odpowiedzi pary {id,name}, niezależnie od opakowania i nazw pól. */
function welyo_collect_items( $node, &$out, $depth = 0 ) {
	if ( ! is_array( $node ) || $depth > 6 ) {
		return;
	}
	$id_keys   = array( 'id', 'campaign_id', 'campaignId', 'campaigns_id', 'id_campaign', 'classifier_id', 'classifierId', 'classifiers_id', 'value' );
	$name_keys = array( 'name', 'campaign_name', 'campaignName', 'campaigns_name', 'classifier_name', 'classifiers_name', 'label', 'text', 'title', 'nazwa', 'description' );
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
		if ( $id !== null ) {
			if ( $name === null || $name === '' ) {
				$name = '#' . $id;
			}
			$out[] = array( 'id' => $id, 'name' => (string) $name );
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

/** Podpowiedź diagnostyczna gdy lista z API jest pusta. */
function welyo_api_list_debug_hint( $endpoint, $data ) {
	$hint = '';
	if ( is_array( $data ) && ! empty( $data ) ) {
		$hint = ' Klucze w odpowiedzi: ' . implode( ', ', array_slice( array_keys( $data ), 0, 12 ) );
	}
	$raw = welyo_last_raw();
	if ( $raw !== '' ) {
		$hint .= ' Fragment odpowiedzi API: ' . substr( $raw, 0, 400 );
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_array( $data ) ) {
		error_log( '[Welyo] ' . $endpoint . ' raw: ' . substr( wp_json_encode( $data ), 0, 1200 ) );
	}
	return $hint;
}

/** Pobiera listę kampanii z API. */
function welyo_fetch_campaign_items( $jwt ) {
	$data = welyo_api_post( $jwt, '/fcc-campaigns-list', array() );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	return welyo_extract_list( $data );
}

/** Pobiera listę klasyfikatorów kampanii z API. */
function welyo_fetch_classifier_items( $jwt, $campaign_id ) {
	$data = welyo_api_post( $jwt, '/fcc-classifiers-list', array( 'campaigns_id' => (string) $campaign_id ) );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	return welyo_extract_list( $data );
}

/**
 * Wybiera pozycję z listy API: ID z ustawień → nazwa → jedyna pozycja → słowa kluczowe → pierwsza.
 *
 * @param array  $items      Lista {id,name}.
 * @param string $id_cfg     campaign_id / classifier_id z panelu.
 * @param string $name_cfg   campaign_name / classifier_name (opcjonalnie).
 * @param array  $keywords   Np. array( 'callback', 'rekrut', 'www' ).
 * @return string|null
 */
function welyo_pick_item( $items, $id_cfg, $name_cfg, $keywords = array() ) {
	if ( $id_cfg !== '' ) {
		$id_cfg = (string) $id_cfg;
		foreach ( $items as $it ) {
			if ( (string) $it['id'] === $id_cfg ) {
				return $it['id'];
			}
		}
	}
	if ( $name_cfg !== '' ) {
		$matched = welyo_match_id( $items, $name_cfg );
		if ( $matched !== null ) {
			return $matched;
		}
	}
	if ( count( $items ) === 1 ) {
		return $items[0]['id'];
	}
	foreach ( $keywords as $kw ) {
		$kw = welyo_lower( $kw );
		foreach ( $items as $it ) {
			if ( strpos( welyo_lower( $it['name'] ), $kw ) !== false ) {
				return $it['id'];
			}
		}
	}
	if ( $id_cfg === '' && $name_cfg === '' && ! empty( $items ) ) {
		return $items[0]['id'];
	}
	return null;
}

/** Zwraca id kampanii: z konfiguracji lub odnalezione po nazwie (cache 1 dzień). */
function welyo_resolve_campaign_id( $jwt, $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}
	$cache_key = 'welyo_campaign_id_' . $lang;
	if ( welyo_cfg( 'campaign_id', $lang ) !== '' ) {
		return (string) welyo_cfg( 'campaign_id', $lang );
	}
	$cached = get_transient( $cache_key );
	if ( $cached ) {
		return $cached;
	}
	$raw   = welyo_api_post( $jwt, '/fcc-campaigns-list', array() );
	if ( is_wp_error( $raw ) ) {
		return $raw;
	}
	$items = welyo_extract_list( $raw );
	$id    = welyo_pick_item(
		$items,
		welyo_cfg( 'campaign_id', $lang ),
		welyo_cfg( 'campaign_name', $lang ),
		array( 'callback', 'rekrut', 'formularz', 'www' )
	);
	if ( $id !== null ) {
		set_transient( $cache_key, $id, DAY_IN_SECONDS );
		return $id;
	}
	welyo_log_items( 'kampanie z API', $items );
	$preview = array();
	foreach ( array_slice( $items, 0, 12 ) as $c ) {
		$preview[] = $c['name'] . ' #' . $c['id'];
	}
	$hint = $preview
		? ' Dostępne z API: ' . implode( ' | ', $preview ) . '. Wybierz kampanię z listy w panelu (przycisk „Pobierz z API”).'
		: ' API nie zwróciło kampanii.' . welyo_api_list_debug_hint( '/fcc-campaigns-list', $raw );
	$name_hint = welyo_cfg( 'campaign_name', $lang ) !== '' ? ' Szukano: „' . welyo_cfg( 'campaign_name', $lang ) . '”.' : ' Ustaw ID kampanii lub wybierz z listy API.';
	return new WP_Error( 'welyo_no_campaign', 'Nie udało się wybrać kampanii (' . $lang . ').' . $name_hint . $hint );
}

/** Zwraca id klasyfikatora recall: z konfiguracji lub po nazwie w danej kampanii (cache 1 dzień). */
function welyo_resolve_classifier_id( $jwt, $campaign_id, $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}
	$cache_key = 'welyo_classifier_id_' . $lang;
	if ( welyo_cfg( 'classifier_id', $lang ) !== '' ) {
		return (string) welyo_cfg( 'classifier_id', $lang );
	}
	$cached = get_transient( $cache_key );
	if ( $cached ) {
		return $cached;
	}
	$raw = welyo_api_post( $jwt, '/fcc-classifiers-list', array( 'campaigns_id' => (string) $campaign_id ) );
	if ( is_wp_error( $raw ) ) {
		return $raw;
	}
	$items = welyo_extract_list( $raw );
	$name_cfg = welyo_cfg( 'classifier_name', $lang );
	$id       = welyo_pick_item(
		$items,
		welyo_cfg( 'classifier_id', $lang ),
		$name_cfg,
		array( 'recall', 'oddzwon', 'callback', 'otwart', 'lead' )
	);
	if ( $id !== null ) {
		set_transient( $cache_key, $id, DAY_IN_SECONDS );
		return $id;
	}
	if ( $name_cfg === '' && empty( $items ) ) {
		return new WP_Error( 'welyo_no_classifier_cfg', 'Brak klasyfikatorów recall w tej kampanii — lead trafi bez recall.' );
	}
	welyo_log_items( 'klasyfikatory z API', $items );
	$preview = array();
	foreach ( array_slice( $items, 0, 12 ) as $c ) {
		$preview[] = $c['name'] . ' #' . $c['id'];
	}
	$hint = $preview
		? ' Dostępne z API: ' . implode( ' | ', $preview ) . '. Wybierz klasyfikator z listy w panelu.'
		: welyo_api_list_debug_hint( '/fcc-classifiers-list', $raw );
	return new WP_Error( 'welyo_no_classifier', 'Nie udało się wybrać klasyfikatora recall.' . $hint );
}

/** Kampania Welyo dla leadów z quizu Forminator (osobna lista CC). */
function welyo_resolve_forminator_campaign_id( $jwt, $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}
	$cache_key = 'welyo_forminator_campaign_id_' . $lang;
	if ( welyo_cfg( 'forminator_quiz_campaign_id', $lang ) !== '' ) {
		return (string) welyo_cfg( 'forminator_quiz_campaign_id', $lang );
	}
	$cached = get_transient( $cache_key );
	if ( $cached ) {
		return $cached;
	}
	$raw   = welyo_api_post( $jwt, '/fcc-campaigns-list', array() );
	if ( is_wp_error( $raw ) ) {
		return $raw;
	}
	$items = welyo_extract_list( $raw );
	$id    = welyo_pick_item(
		$items,
		welyo_cfg( 'forminator_quiz_campaign_id', $lang ),
		welyo_cfg( 'forminator_quiz_campaign_name', $lang ),
		array( 'quiz', 'forminator', 'rekrut', 'www' )
	);
	if ( $id !== null ) {
		set_transient( $cache_key, $id, DAY_IN_SECONDS );
		return $id;
	}
	$name_hint = welyo_cfg( 'forminator_quiz_campaign_name', $lang ) !== ''
		? ' Szukano: „' . welyo_cfg( 'forminator_quiz_campaign_name', $lang ) . '”.'
		: ' Ustaw kampanię quizu w panelu Welyo Callback.';
	return new WP_Error( 'welyo_no_campaign', 'Nie udało się wybrać kampanii quizu (' . $lang . ').' . $name_hint );
}

/** Klasyfikator recall dla kampanii quizu Forminator. */
function welyo_resolve_forminator_classifier_id( $jwt, $campaign_id, $lang = null ) {
	if ( $lang === null ) {
		$lang = welyo_lang_context();
	}
	$cache_key = 'welyo_forminator_classifier_id_' . $lang;
	if ( welyo_cfg( 'forminator_quiz_classifier_id', $lang ) !== '' ) {
		return (string) welyo_cfg( 'forminator_quiz_classifier_id', $lang );
	}
	$cached = get_transient( $cache_key );
	if ( $cached ) {
		return $cached;
	}
	$raw = welyo_api_post( $jwt, '/fcc-classifiers-list', array( 'campaigns_id' => (string) $campaign_id ) );
	if ( is_wp_error( $raw ) ) {
		return $raw;
	}
	$items = welyo_extract_list( $raw );
	$name_cfg = welyo_cfg( 'forminator_quiz_classifier_name', $lang );
	$id       = welyo_pick_item(
		$items,
		welyo_cfg( 'forminator_quiz_classifier_id', $lang ),
		$name_cfg,
		array( 'recall', 'oddzwon', 'callback', 'quiz' )
	);
	if ( $id !== null ) {
		set_transient( $cache_key, $id, DAY_IN_SECONDS );
		return $id;
	}
	if ( empty( $items ) ) {
		return new WP_Error( 'welyo_no_classifier_cfg', 'Brak klasyfikatorów recall w kampanii quizu — lead trafi bez recall.' );
	}
	return new WP_Error( 'welyo_no_classifier', 'Nie udało się wybrać klasyfikatora recall dla quizu.' );
}

/** Dodaje rekord do kampanii przez /fcc-add-records. */
function welyo_add_record( $jwt, $campaign_id, $classifier_id, $name, $phone_e164, $recall_or_null, $ext_id, $extra_values = array() ) {
	// Numer leci do dzwonienia w "numbers"; jego etykietą jest imię, więc konsultant
	// widzi je przy rekordzie. "values.TELEFON" wypełnia pole znaczące (TELEFON),
	// czyli to, co pokazuje się na listach recall / nagrań.
	$number_entry = ( $name !== '' ) ? array( 'name' => $name, 'value' => $phone_e164 ) : $phone_e164;
	$values       = array( 'TELEFON' => $phone_e164 );
	if ( is_array( $extra_values ) ) {
		foreach ( $extra_values as $field_key => $field_value ) {
			$field_key = strtoupper( preg_replace( '/[^A-Za-z0-9_]/', '', (string) $field_key ) );
			if ( $field_key === '' || $field_key === 'TELEFON' ) {
				continue;
			}
			if ( is_array( $field_value ) ) {
				$field_value = implode( ', ', array_filter( array_map( 'strval', $field_value ) ) );
			}
			$field_value = trim( (string) $field_value );
			if ( $field_value !== '' ) {
				$values[ $field_key ] = $field_value;
			}
		}
	}
	$record = array(
		'values'  => $values,
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

	$modes      = welyo_auth_modes_list();
	$last_error = null;

	foreach ( $modes as $mode ) {
		$req  = welyo_build_auth_request( $jwt, $mode, $payload );
		$resp = wp_remote_post( rtrim( welyo_cfg( 'base_url' ), '/' ) . '/fcc-add-records', array(
			'timeout' => 15,
			'headers' => $req['headers'],
			'body'    => $req['body'],
		) );

		if ( is_wp_error( $resp ) ) {
			$last_error = $resp;
			continue;
		}

		$code = wp_remote_retrieve_response_code( $resp );
		$body = wp_remote_retrieve_body( $resp );
		welyo_last_raw( $body );

		if ( $code < 200 || $code >= 300 ) {
			$last_error = new WP_Error( 'welyo_add_http', 'Welyo add-records HTTP ' . $code . ' (' . $mode . '): ' . $body );
			continue;
		}

		$data = json_decode( $body, true );
		if ( is_array( $data ) && welyo_api_response_is_fail( $data ) ) {
			$msg        = isset( $data['message'] ) ? (string) $data['message'] : 'Welyo add-records error';
			$last_error = new WP_Error( 'welyo_add_api', $msg . ' [' . $mode . ']' );
			continue;
		}

		set_transient( 'welyo_auth_mode', $mode, DAY_IN_SECONDS );
		return true;
	}

	return $last_error ? $last_error : new WP_Error( 'welyo_add_http', 'Welyo add-records failed.' );
}


/* =====================================================================
   DIAGNOSTYKA API (panel WP)
   ===================================================================== */

/** Kroki testu połączenia z Welyo (bez ujawniania sekretów). */
function welyo_run_diagnostics( $lang = null ) {
	$steps = array();
	$lang_only = ( $lang !== null && $lang !== '' );

	if ( $lang_only ) {
		$lang = strtolower( sanitize_key( $lang ) );
		if ( ! isset( welyo_supported_languages()[ $lang ] ) ) {
			$steps[] = array(
				'id'      => 'lang',
				'ok'      => false,
				'message' => __( 'Nieobsługiwany kod języka.', 'akademiata' ),
			);
			return $steps;
		}
		welyo_lang_context( $lang );
		$steps[] = array(
			'id'      => 'lang',
			'ok'      => true,
			'message' => sprintf(
				/* translators: %s: language code */
				__( 'Test konfiguracji języka: %s', 'akademiata' ),
				$lang
			),
		);
	}

	$login   = (string) welyo_cfg( 'login' );
	$api_key = (string) welyo_cfg( 'api_key' );
	$config_ok = ( $login !== '' && $api_key !== '' );

	if ( ! $lang_only ) {
		$steps[] = array(
			'id'      => 'config',
			'ok'      => $config_ok,
			'message' => $config_ok
				? __( 'Login i klucz API są ustawione.', 'akademiata' )
				: __( 'Brak loginu lub klucza API — uzupełnij w sekcji API Welyo i zapisz.', 'akademiata' ),
		);
	}

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

	if ( ! $lang_only ) {
		$steps[] = array(
			'id'      => 'jwt',
			'ok'      => true,
			'message' => __( 'Połączenie z API — token JWT uzyskany.', 'akademiata' ) . ( welyo_is_jwt_shape( $jwt ) ? ' (' . substr( $jwt, 0, 12 ) . '…)' : '' ),
		);

		$auth_mode = get_transient( 'welyo_auth_mode' );
		if ( $auth_mode ) {
			$steps[] = array(
				'id'      => 'auth_mode',
				'ok'      => true,
				'message' => sprintf(
					/* translators: %s: auth mode slug */
					__( 'Tryb autoryzacji API (cache): %s', 'akademiata' ),
					$auth_mode
				),
			);
		}

		$campaign_items = welyo_fetch_campaign_items( $jwt );
		if ( is_wp_error( $campaign_items ) ) {
			$steps[] = array(
				'id'      => 'campaigns_api',
				'ok'      => false,
				'message' => $campaign_items->get_error_message(),
			);
			return $steps;
		}

		$api_names = array();
		foreach ( array_slice( $campaign_items, 0, 8 ) as $c ) {
			$api_names[] = $c['name'] . ' #' . $c['id'];
		}
		$steps[] = array(
			'id'      => 'campaigns_api',
			'ok'      => ! empty( $campaign_items ),
			'message' => ! empty( $campaign_items )
				? sprintf(
					/* translators: 1: count, 2: sample list */
					__( 'API zwróciło %1$d kampanii: %2$s', 'akademiata' ),
					count( $campaign_items ),
					implode( ' | ', $api_names )
				)
				: __( 'API nie zwróciło kampanii — sprawdź uprawnienia konta API w Welyo.', 'akademiata' ) . welyo_api_list_debug_hint( '/fcc-campaigns-list', array() ),
		);

		return $steps;
	}

	$campaign_id = welyo_resolve_campaign_id( $jwt, $lang );
	if ( is_wp_error( $campaign_id ) ) {
		$steps[] = array(
			'id'      => 'campaign',
			'ok'      => false,
			'message' => $campaign_id->get_error_message(),
		);
		return $steps;
	}

	$campaign_label = sprintf( __( 'Używana kampania: ID %s.', 'akademiata' ), $campaign_id );
	if ( welyo_cfg( 'campaign_id', $lang ) !== '' ) {
		$campaign_label = sprintf( __( 'Kampania ID %s (wybrana w panelu).', 'akademiata' ), $campaign_id );
	} elseif ( welyo_cfg( 'campaign_name', $lang ) !== '' ) {
		$campaign_label = sprintf(
			__( 'Kampania ID %1$s (dopasowano: „%2$s”).', 'akademiata' ),
			$campaign_id,
			welyo_cfg( 'campaign_name', $lang )
		);
	} elseif ( ! $lang_only ) {
		$campaign_items = welyo_fetch_campaign_items( $jwt );
		if ( ! is_wp_error( $campaign_items ) && count( $campaign_items ) === 1 ) {
			$campaign_label = sprintf( __( 'Kampania ID %s (jedyna dostępna w API).', 'akademiata' ), $campaign_id );
		}
	}

	$steps[] = array(
		'id'      => 'campaign',
		'ok'      => true,
		'message' => $campaign_label,
	);

	$classifier_id = welyo_resolve_classifier_id( $jwt, $campaign_id, $lang );
	if ( is_wp_error( $classifier_id ) ) {
		$is_optional = ( $classifier_id->get_error_code() === 'welyo_no_classifier_cfg' );
		$steps[] = array(
			'id'      => 'classifier',
			'ok'      => $is_optional,
			'message' => $is_optional
				? __( 'Brak klasyfikatora recall w API — po godzinach lead trafi bez zaplanowanego recall.', 'akademiata' )
				: $classifier_id->get_error_message(),
		);
	} else {
		$steps[] = array(
			'id'      => 'classifier',
			'ok'      => true,
			'message' => sprintf( __( 'Klasyfikator recall ID %s.', 'akademiata' ), $classifier_id ),
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
		'callback'            => function ( WP_REST_Request $request ) {
			$lang = sanitize_key( (string) $request->get_param( 'lang' ) );
			return new WP_REST_Response(
				array( 'steps' => welyo_run_diagnostics( $lang !== '' ? $lang : null ) ),
				200
			);
		},
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	register_rest_route( 'welyo/v1', '/campaigns', array(
		'methods'             => 'GET',
		'callback'            => function () {
			$jwt = welyo_get_jwt();
			if ( is_wp_error( $jwt ) ) {
				return $jwt;
			}
			$items = welyo_fetch_campaign_items( $jwt );
			if ( is_wp_error( $items ) ) {
				return $items;
			}
			return new WP_REST_Response(
				array(
					'items' => $items,
					'debug' => empty( $items ) ? substr( welyo_last_raw(), 0, 400 ) : '',
				),
				200
			);
		},
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );

	register_rest_route( 'welyo/v1', '/classifiers', array(
		'methods'             => 'GET',
		'callback'            => function ( WP_REST_Request $request ) {
			$campaign_id = sanitize_text_field( (string) $request->get_param( 'campaign_id' ) );
			if ( $campaign_id === '' ) {
				return new WP_Error( 'welyo_param', __( 'Brak parametru campaign_id.', 'akademiata' ), array( 'status' => 400 ) );
			}
			$jwt = welyo_get_jwt();
			if ( is_wp_error( $jwt ) ) {
				return $jwt;
			}
			$items = welyo_fetch_classifier_items( $jwt, $campaign_id );
			if ( is_wp_error( $items ) ) {
				return $items;
			}
			return new WP_REST_Response( array( 'items' => $items ), 200 );
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
	$hp      = isset( $params['company'] ) ? trim( (string) $params['company'] ) : '';
	$lang    = isset( $params['lang'] ) ? strtolower( sanitize_key( $params['lang'] ) ) : welyo_get_current_language();

	if ( ! welyo_is_language_enabled( $lang ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'generic' ), 200 );
	}
	welyo_lang_context( $lang );

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

	$recall = welyo_is_open_now( $lang ) ? null : welyo_next_working_morning( $lang );
	$phone_e164 = welyo_normalize_phone( $phone, $lang );
	$ext_id = 'web-' . $lang . '-' . gmdate( 'Ymd-His' ) . '-' . substr( md5( $phone_e164 . microtime() ), 0, 6 );

	$campaign_id = welyo_resolve_campaign_id( $jwt, $lang );
	if ( is_wp_error( $campaign_id ) ) {
		error_log( '[Welyo] campaign: ' . $campaign_id->get_error_message() );
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'campaign' ), 200 );
	}

	// id klasyfikatora potrzebny tylko przy recall (po godzinach)
	$classifier_id = '';
	if ( $recall ) {
		$classifier_id = welyo_resolve_classifier_id( $jwt, $campaign_id, $lang );
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

	$lang = welyo_get_current_language();
	welyo_lang_context( $lang );

	$texts = welyo_widget_texts( $lang );
	$privacy_url = esc_url( welyo_cfg( 'privacy_url', $lang ) );
	$consent_html = str_replace( '{privacy_url}', $privacy_url, $texts['text_consent'] );

	$cfg = array(
		'lang'        => $lang,
		'rest'        => esc_url_raw( rest_url( 'welyo/v1/callback' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'phoneDial'   => welyo_cfg( 'phone_dial', $lang ),
		'phonePretty' => welyo_cfg( 'phone_pretty', $lang ),
		'openHour'    => welyo_cfg_int( 'open_hour', $lang ),
		'closeHour'   => welyo_cfg_int( 'close_hour', $lang ),
		'workdays'    => welyo_workdays_array( $lang ),
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
    <span class="wcb-launch-label" id="wcbLaunchLabel"><?php echo esc_html( $texts['text_launch_open'] ); ?></span>
  </button>
</div>

<style>
.wcb-root{position:fixed;right:var(--wcb-side-right,22px);left:auto;bottom:var(--wcb-bottom,22px);z-index:99999;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.wcb-root.wcb-side-left{right:auto;left:var(--wcb-side-left,14px)}
.wcb-root.wcb-side-left .wcb-panel{right:auto;left:0;transform-origin:bottom left}
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
@media (max-width:767px){
  .wcb-root{--wcb-side-right:14px;--wcb-bottom:calc(14px + env(safe-area-inset-bottom,0px))}
  .wcb-launcher{gap:0;padding:0;width:56px;height:56px;justify-content:center;border-radius:50%}
  .wcb-launch-label{display:none}
  .wcb-ic{width:56px;height:56px}
  .wcb-ic svg{width:22px;height:22px}
  .wcb-root[data-mode="call"] .wcb-ic::after{width:56px;height:56px}
}
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
      launcher.setAttribute("aria-label",T.launchOpen||"");
      document.getElementById("wcbStatus").textContent=T.statusOpen||"";
      document.getElementById("wcbTitle").textContent=T.titleOpen||"";
      document.getElementById("wcbSub").textContent=T.subOpen||"";
      modeCall.classList.remove("wcb-hidden");modeCb.classList.add("wcb-hidden");
    }else{
      document.getElementById("wcbLaunchLabel").textContent=T.launchClosed||"";
      launcher.setAttribute("aria-label",T.launchClosed||"");
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
      body:JSON.stringify({name:name,phone:phone,consent:true,company:company,lang:CFG.lang||"pl"})})
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

  function wcbFindFixedEl(el){
    while(el&&el!==document.documentElement){
      var p=window.getComputedStyle(el).position;
      if(p==="fixed"||p==="sticky"){return el;}
      el=el.parentElement;
    }
    return null;
  }
  function wcbFindA11yBar(){
    var selectors=[
      "#pojo-a11y-toolbar",
      "[aria-label='Toggle Accessibility Toolbar']",
      "[aria-label*='Accessibility Toolbar']",
      ".pojo-a11y-toolbar-toggle",
      "#accessibility-toolbar",
      ".accessibility-toolbar-toggle"
    ];
    for(var i=0;i<selectors.length;i++){
      var hit=document.querySelector(selectors[i]);
      if(hit){return wcbFindFixedEl(hit)||hit;}
    }
    return null;
  }
  function wcbAdjustForOverlap(){
    if(!window.matchMedia("(max-width:767px)").matches){
      root.classList.remove("wcb-side-left");
      root.style.removeProperty("--wcb-bottom");
      root.style.removeProperty("--wcb-side-left");
      root.style.removeProperty("--wcb-side-right");
      return;
    }
    var margin=14,gap=12,base="calc("+margin+"px + env(safe-area-inset-bottom,0px))";
    var bar=wcbFindA11yBar();
    root.classList.remove("wcb-side-left");
    root.style.setProperty("--wcb-side-right",margin+"px");
    root.style.removeProperty("--wcb-side-left");
    root.style.setProperty("--wcb-bottom",base);
    if(!bar){return;}
    var bRect=bar.getBoundingClientRect();
    if(bRect.width<1||bRect.height<1){return;}
    function overlaps(){
      var l=launcher.getBoundingClientRect(),b=bar.getBoundingClientRect(),pad=6;
      return!(l.right+pad<b.left||l.left-pad>b.right||l.bottom+pad<b.top||l.top-pad>b.bottom);
    }
    if(!overlaps()){return;}
    var onRight=bRect.left+bRect.width/2>window.innerWidth/2;
    if(bar.id==="pojo-a11y-toolbar"){
      if(bar.classList.contains("pojo-a11y-toolbar-right")){onRight=true;}
      if(bar.classList.contains("pojo-a11y-toolbar-left")){onRight=false;}
    }
    if(onRight){
      root.classList.add("wcb-side-left");
      root.style.setProperty("--wcb-side-left",margin+"px");
      root.style.removeProperty("--wcb-side-right");
    }else{
      var lift=Math.ceil(window.innerHeight-bRect.top+gap);
      if(lift>margin){
        root.style.setProperty("--wcb-bottom","calc("+lift+"px + env(safe-area-inset-bottom,0px))");
      }
    }
    if(overlaps()){
      var lift2=Math.ceil(window.innerHeight-bar.getBoundingClientRect().top+gap);
      root.style.setProperty("--wcb-bottom","calc("+lift2+"px + env(safe-area-inset-bottom,0px))");
    }
  }
  wcbAdjustForOverlap();
  window.addEventListener("resize",wcbAdjustForOverlap);
  [0,400,1500].forEach(function(ms){setTimeout(wcbAdjustForOverlap,ms);});
  if(window.ResizeObserver){
    var wcbRo=new ResizeObserver(wcbAdjustForOverlap);
    wcbRo.observe(document.documentElement);
  }
  if(window.MutationObserver){
    var wcbMoTimer;
    var wcbMo=new MutationObserver(function(){
      clearTimeout(wcbMoTimer);
      wcbMoTimer=setTimeout(wcbAdjustForOverlap,120);
    });
    wcbMo.observe(document.body,{childList:true,subtree:true,attributes:true,attributeFilter:["class","style"]});
  }
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
