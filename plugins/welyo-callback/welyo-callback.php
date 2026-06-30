<?php
/**
 * Plugin Name: Welyo Callback (Zadzwoń / Oddzwonimy)
 * Description: Widget kontaktu dla rekrutacji. W godzinach pracy "Zadzwoń", po godzinach "Zostaw numer — oddzwonimy". Lead trafia bezpiecznie do Welyo przez serwer (klucz API nie wychodzi do przeglądarki). Shortcode: [welyo_callback]
 * Version: 1.0.0
 * Author: —
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // brak bezpośredniego dostępu

/* =====================================================================
   KONFIGURACJA
   Najlepiej NIE wpisywać sekretów tutaj, tylko w wp-config.php, np.:

     define( 'WELYO_BASE_URL',     'https://ataedu.welyo.pl/external-api' );
     define( 'WELYO_LOGIN',        'login@ataedu' );   // login@domena
     define( 'WELYO_API_KEY',      'TWOJ_KLUCZ_API' ); // z panelu: Administracja → Integracje → Klucz API
     define( 'WELYO_CAMPAIGN_ID',  '123' );            // id kampanii docelowej (z /fcc-campaigns-list)
     define( 'WELYO_CLASSIFIER_ID','1' );              // id klasyfikatora (wymagany przy recall) (z /fcc-classifiers-list)

   Poniższe wartości to tylko fallbacki / ustawienia jawne (nie-sekretne).
   ===================================================================== */
if ( ! defined( 'WELYO_BASE_URL' ) )      define( 'WELYO_BASE_URL',      'https://ataedu.welyo.pl/external-api' );
if ( ! defined( 'WELYO_LOGIN' ) )         define( 'WELYO_LOGIN',         '' );
if ( ! defined( 'WELYO_API_KEY' ) )       define( 'WELYO_API_KEY',       '' );
// ID możesz podać wprost (wtedy są używane bez pytania API). Jeśli zostawisz puste,
// wtyczka SAMA odnajdzie je po nazwie przez /fcc-campaigns-list i /fcc-classifiers-list
// i zacache'uje na dobę. Najwygodniej: zostaw ID puste, a podaj nazwy poniżej.
if ( ! defined( 'WELYO_CAMPAIGN_ID' ) )   define( 'WELYO_CAMPAIGN_ID',   '' );
if ( ! defined( 'WELYO_CLASSIFIER_ID' ) ) define( 'WELYO_CLASSIFIER_ID', '' );
// Nazwy używane do automatycznego odnalezienia ID (dokładnie jak w panelu Welyo):
if ( ! defined( 'WELYO_CAMPAIGN_NAME' ) )   define( 'WELYO_CAMPAIGN_NAME',   'Rekrutacja - formularz WWW (callback)' );
if ( ! defined( 'WELYO_CLASSIFIER_NAME' ) ) define( 'WELYO_CLASSIFIER_NAME', '' ); // np. 'Lead WWW – oddzwonić' (klasyfikator otwarty/recall)
if ( ! defined( 'WELYO_HASH_METHOD' ) )   define( 'WELYO_HASH_METHOD',   'md5' );   // md5 lub sha1
if ( ! defined( 'WELYO_DEFAULT_PREFIX' ) )define( 'WELYO_DEFAULT_PREFIX', '+48' );   // doklejany do numerów 9-cyfrowych

// Godziny i dni pracy (czas wg strefy WordPressa)
if ( ! defined( 'WELYO_OPEN_HOUR' ) )     define( 'WELYO_OPEN_HOUR',  8 );   // od 08:00
if ( ! defined( 'WELYO_CLOSE_HOUR' ) )    define( 'WELYO_CLOSE_HOUR', 18 );  // do 18:00
if ( ! defined( 'WELYO_WORKDAYS' ) )      define( 'WELYO_WORKDAYS',  '1,2,3,4,5' ); // 1=pon ... 7=niedz

// Numer telefonu pokazywany/wybierany w trybie "Zadzwoń"
if ( ! defined( 'WELYO_PHONE_DIAL' ) )    define( 'WELYO_PHONE_DIAL',   '+48220000000' );
if ( ! defined( 'WELYO_PHONE_PRETTY' ) )  define( 'WELYO_PHONE_PRETTY', '+48 22 000 00 00' );

// Link do informacji o przetwarzaniu danych (RODO)
if ( ! defined( 'WELYO_PRIVACY_URL' ) )   define( 'WELYO_PRIVACY_URL',  '/polityka-prywatnosci/' );


/* =====================================================================
   POMOCNICZE
   ===================================================================== */

/** Czy teraz jest w godzinach pracy (strefa czasowa WP). */
function welyo_is_open_now() {
	$tz   = wp_timezone();
	$now  = new DateTime( 'now', $tz );
	$dow  = (int) $now->format( 'N' );          // 1..7
	$hour = (int) $now->format( 'G' );          // 0..23
	$days = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', WELYO_WORKDAYS ) ), 'strlen' ) );
	$is_workday = in_array( $dow, $days, true );
	return $is_workday && $hour >= (int) WELYO_OPEN_HOUR && $hour < (int) WELYO_CLOSE_HOUR;
}

/** Najbliższy roboczy poranek (format YYYY-MM-DD hh:mm) — dla recall po godzinach. */
function welyo_next_working_morning() {
	$tz   = wp_timezone();
	$dt   = new DateTime( 'now', $tz );
	$days = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', WELYO_WORKDAYS ) ), 'strlen' ) );

	// jeśli dziś jeszcze przed otwarciem i dziś jest dniem roboczym → dzisiejszy poranek
	$today_dow  = (int) $dt->format( 'N' );
	$today_hour = (int) $dt->format( 'G' );
	if ( in_array( $today_dow, $days, true ) && $today_hour < (int) WELYO_OPEN_HOUR ) {
		$dt->setTime( (int) WELYO_OPEN_HOUR, 5 );
		return $dt->format( 'Y-m-d H:i' );
	}

	// w przeciwnym razie szukaj kolejnego dnia roboczego
	for ( $i = 1; $i <= 8; $i++ ) {
		$dt->modify( '+1 day' );
		if ( in_array( (int) $dt->format( 'N' ), $days, true ) ) {
			$dt->setTime( (int) WELYO_OPEN_HOUR, 5 );
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
		return WELYO_DEFAULT_PREFIX . $only;
	}
	if ( strlen( $only ) === 11 && strpos( $only, '48' ) === 0 ) {
		return '+' . $only;
	}
	return WELYO_DEFAULT_PREFIX . $only;                     // fallback
}

/** Generuje token JWT przez /fcc-create-jwt-token. Zwraca string token lub WP_Error. */
function welyo_get_jwt() {
	$login   = (string) WELYO_LOGIN;
	$apikey  = (string) WELYO_API_KEY;
	$method  = ( strtolower( WELYO_HASH_METHOD ) === 'sha1' ) ? 'sha1' : 'md5';

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

	$resp = wp_remote_post( rtrim( WELYO_BASE_URL, '/' ) . '/fcc-create-jwt-token', array(
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
	$resp = wp_remote_post( rtrim( WELYO_BASE_URL, '/' ) . $endpoint, array(
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

/** Wyciąga z odpowiedzi listę pozycji {id,name} niezależnie od opakowania. */
function welyo_extract_list( $data ) {
	$arr = $data;
	if ( isset( $data['data'] ) && is_array( $data['data'] ) )            $arr = $data['data'];
	elseif ( isset( $data['rows'] ) && is_array( $data['rows'] ) )        $arr = $data['rows'];
	elseif ( isset( $data['campaigns'] ) && is_array( $data['campaigns'] ) ) $arr = $data['campaigns'];
	elseif ( isset( $data['classifiers'] ) && is_array( $data['classifiers'] ) ) $arr = $data['classifiers'];
	elseif ( isset( $data['list'] ) && is_array( $data['list'] ) )        $arr = $data['list'];
	$out = array();
	if ( is_array( $arr ) ) {
		foreach ( $arr as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$id   = isset( $row['id'] ) ? $row['id'] : ( isset( $row['campaign_id'] ) ? $row['campaign_id'] : ( isset( $row['value'] ) ? $row['value'] : null ) );
			$name = isset( $row['name'] ) ? $row['name'] : ( isset( $row['label'] ) ? $row['label'] : ( isset( $row['text'] ) ? $row['text'] : '' ) );
			if ( $id !== null ) { $out[] = array( 'id' => (string) $id, 'name' => (string) $name ); }
		}
	}
	return $out;
}

/** Zwraca id kampanii: z konfiguracji lub odnalezione po nazwie (cache 1 dzień). */
function welyo_resolve_campaign_id( $jwt ) {
	if ( WELYO_CAMPAIGN_ID !== '' ) { return (string) WELYO_CAMPAIGN_ID; }
	$cached = get_transient( 'welyo_campaign_id' );
	if ( $cached ) { return $cached; }
	$data = welyo_api_post( $jwt, '/fcc-campaigns-list', array() );
	if ( is_wp_error( $data ) ) { return $data; }
	foreach ( welyo_extract_list( $data ) as $c ) {
		if ( mb_strtolower( trim( $c['name'] ) ) === mb_strtolower( trim( WELYO_CAMPAIGN_NAME ) ) ) {
			set_transient( 'welyo_campaign_id', $c['id'], DAY_IN_SECONDS );
			return $c['id'];
		}
	}
	return new WP_Error( 'welyo_no_campaign', 'Nie znaleziono kampanii o nazwie: ' . WELYO_CAMPAIGN_NAME );
}

/** Zwraca id klasyfikatora recall: z konfiguracji lub po nazwie w danej kampanii (cache 1 dzień). */
function welyo_resolve_classifier_id( $jwt, $campaign_id ) {
	if ( WELYO_CLASSIFIER_ID !== '' ) { return (string) WELYO_CLASSIFIER_ID; }
	if ( WELYO_CLASSIFIER_NAME === '' ) {
		return new WP_Error( 'welyo_no_classifier_cfg', 'Brak WELYO_CLASSIFIER_ID i WELYO_CLASSIFIER_NAME.' );
	}
	$cached = get_transient( 'welyo_classifier_id' );
	if ( $cached ) { return $cached; }
	$data = welyo_api_post( $jwt, '/fcc-classifiers-list', array( 'campaigns_id' => (string) $campaign_id ) );
	if ( is_wp_error( $data ) ) { return $data; }
	foreach ( welyo_extract_list( $data ) as $c ) {
		if ( mb_strtolower( trim( $c['name'] ) ) === mb_strtolower( trim( WELYO_CLASSIFIER_NAME ) ) ) {
			set_transient( 'welyo_classifier_id', $c['id'], DAY_IN_SECONDS );
			return $c['id'];
		}
	}
	return new WP_Error( 'welyo_no_classifier', 'Nie znaleziono klasyfikatora: ' . WELYO_CLASSIFIER_NAME );
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

	$resp = wp_remote_post( rtrim( WELYO_BASE_URL, '/' ) . '/fcc-add-records', array(
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
   ENDPOINT REST: POST /wp-json/welyo/v1/callback
   ===================================================================== */

add_action( 'rest_api_init', function () {
	register_rest_route( 'welyo/v1', '/callback', array(
		'methods'             => 'POST',
		'callback'            => 'welyo_handle_callback',
		'permission_callback' => 'welyo_permission_check',
	) );
} );

/** Lekka ochrona: nonce + honeypot + limit zapytań na IP. */
function welyo_permission_check( WP_REST_Request $request ) {
	// nonce z widgetu (X-WP-Nonce)
	$nonce = $request->get_header( 'x_wp_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error( 'welyo_nonce', 'Nieprawidłowy token żądania.', array( 'status' => 403 ) );
	}
	// limit: max 5 zgłoszeń / 10 min / IP
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$key = 'welyo_rl_' . md5( $ip );
	$cnt = (int) get_transient( $key );
	if ( $cnt >= 5 ) {
		return new WP_Error( 'welyo_rate', 'Zbyt wiele prób. Spróbuj później.', array( 'status' => 429 ) );
	}
	set_transient( $key, $cnt + 1, 10 * MINUTE_IN_SECONDS );
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
	$cfg = array(
		'rest'        => esc_url_raw( rest_url( 'welyo/v1/callback' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'phoneDial'   => WELYO_PHONE_DIAL,
		'phonePretty' => WELYO_PHONE_PRETTY,
		'openHour'    => (int) WELYO_OPEN_HOUR,
		'closeHour'   => (int) WELYO_CLOSE_HOUR,
		'workdays'    => array_map( 'intval', array_filter( array_map( 'trim', explode( ',', WELYO_WORKDAYS ) ), 'strlen' ) ),
		'privacyUrl'  => esc_url( WELYO_PRIVACY_URL ),
	);
	$json = wp_json_encode( $cfg );

	ob_start();
	?>
<div class="wcb-root" id="wcbRoot" data-mode="call">
  <div class="wcb-panel" role="dialog" aria-modal="false" aria-labelledby="wcbTitle">
    <div class="wcb-head">
      <span class="wcb-status"><span class="wcb-dot"></span><span id="wcbStatus">Jesteśmy teraz dostępni</span></span>
      <h2 class="wcb-title" id="wcbTitle">Masz pytanie?</h2>
      <p class="wcb-sub" id="wcbSub">Zadzwoń do działu rekrutacji — pomożemy dokończyć zgłoszenie.</p>
      <button class="wcb-close" id="wcbClose" aria-label="Zamknij">&times;</button>
    </div>
    <div class="wcb-body">
      <div id="wcbModeCall">
        <a class="wcb-callbtn" id="wcbCallLink" href="tel:">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Zadzwoń teraz
        </a>
        <p class="wcb-number" id="wcbNumber"></p>
        <p class="wcb-hours" id="wcbHours"></p>
      </div>
      <div id="wcbModeCb" class="wcb-hidden">
        <div class="wcb-field"><label for="wcbName">Imię</label>
          <input type="text" id="wcbName" autocomplete="given-name" placeholder="Jak się do Ciebie zwracać?"></div>
        <div class="wcb-field"><label for="wcbPhone">Numer telefonu</label>
          <input type="tel" id="wcbPhone" autocomplete="tel" inputmode="tel" placeholder="np. 600 100 200"></div>
        <input type="text" id="wcbCompany" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">
        <p class="wcb-err" id="wcbErr"></p>
        <div class="wcb-consent">
          <input type="checkbox" id="wcbConsent">
          <label for="wcbConsent">Wyrażam zgodę na kontakt telefoniczny w sprawie mojej rekrutacji. Rozmowa może być nagrywana w celach jakościowych. <a id="wcbPrivacy" href="#" target="_blank" rel="noopener">Informacja o przetwarzaniu danych</a>.</label>
        </div>
        <button class="wcb-submit" id="wcbSubmit">Oddzwońcie do mnie</button>
      </div>
      <div id="wcbDone" class="wcb-done wcb-hidden">
        <div class="wcb-check"><svg viewBox="0 0 24 24" fill="none" stroke="#1f9d63" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <h3>Dziękujemy!</h3>
        <p id="wcbDoneMsg">Mamy Twój numer. Oddzwonimy najszybciej, jak to możliwe.</p>
      </div>
    </div>
    <div class="wcb-foot">Dział Rekrutacji</div>
  </div>
  <button class="wcb-launcher" id="wcbLauncher" aria-haspopup="dialog" aria-expanded="false">
    <span class="wcb-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
    <span id="wcbLaunchLabel">Masz pytanie? Zadzwoń</span>
  </button>
</div>

<style>
.wcb-root{--b:#2a3a86;--b2:#3650c8;--a:#ff5a3c;--ad:#e8421f;--ink:#1b2347;--soft:#5b6385;--line:#e6e9f2;
position:fixed;right:22px;bottom:22px;z-index:99999;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.wcb-launcher{display:inline-flex;align-items:center;gap:12px;border:0;cursor:pointer;background:var(--b);color:#fff;padding:14px 20px 14px 16px;border-radius:999px;font-weight:700;font-size:15px;box-shadow:0 18px 50px -12px rgba(20,28,64,.34);transition:transform .18s,background .18s}
.wcb-launcher:hover{transform:translateY(-2px);background:var(--b2)}
.wcb-launcher:focus-visible{outline:3px solid #fff;outline-offset:3px}
.wcb-ic{position:relative;width:38px;height:38px;flex:none;display:grid;place-items:center;border-radius:50%;background:rgba(255,255,255,.16)}
.wcb-ic svg{width:19px;height:19px}
.wcb-root[data-mode="call"] .wcb-ic::after{content:"";position:absolute;width:38px;height:38px;border-radius:50%;border:2px solid var(--a);animation:wcbp 2.2s ease-out infinite}
@keyframes wcbp{0%{transform:scale(1);opacity:.7}100%{transform:scale(1.55);opacity:0}}
.wcb-panel{position:absolute;right:0;bottom:calc(100% + 14px);width:340px;max-width:calc(100vw - 32px);background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:0 18px 50px -12px rgba(20,28,64,.34);overflow:hidden;transform-origin:bottom right;opacity:0;transform:translateY(8px) scale(.98);pointer-events:none;transition:opacity .2s,transform .2s}
.wcb-root.is-open .wcb-panel{opacity:1;transform:none;pointer-events:auto}
.wcb-head{background:linear-gradient(135deg,var(--b),#1a2766);color:#fff;padding:20px 20px 18px;position:relative}
.wcb-status{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;opacity:.92}
.wcb-dot{width:8px;height:8px;border-radius:50%;flex:none}
.wcb-root[data-mode="call"] .wcb-dot{background:#46e08a;box-shadow:0 0 0 4px rgba(70,224,138,.25)}
.wcb-root[data-mode="callback"] .wcb-dot{background:#ffc24b;box-shadow:0 0 0 4px rgba(255,194,75,.22)}
.wcb-title{font-size:19px;font-weight:800;line-height:1.2;margin:12px 0 4px}
.wcb-sub{font-size:13.5px;line-height:1.55;opacity:.88;margin:0}
.wcb-close{position:absolute;top:14px;right:14px;width:30px;height:30px;border:0;border-radius:50%;background:rgba(255,255,255,.14);color:#fff;cursor:pointer;font-size:18px;line-height:1;display:grid;place-items:center}
.wcb-close:hover{background:rgba(255,255,255,.26)}
.wcb-body{padding:18px 20px 20px}
.wcb-callbtn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;text-decoration:none;background:var(--a);color:#fff;padding:15px;border-radius:13px;font-weight:800;font-size:16px;transition:background .18s,transform .18s}
.wcb-callbtn:hover{background:var(--ad);transform:translateY(-1px)}
.wcb-callbtn svg{width:20px;height:20px}
.wcb-number{text-align:center;margin:12px 0 0;font-size:15px;font-weight:700;color:var(--ink)}
.wcb-hours{text-align:center;margin:4px 0 0;font-size:12.5px;color:var(--soft)}
.wcb-field{margin-bottom:12px}
.wcb-field label{display:block;font-size:12.5px;font-weight:600;color:var(--ink);margin-bottom:5px}
.wcb-field input{width:100%;border:1px solid var(--line);border-radius:11px;padding:12px 13px;font-size:15px;color:var(--ink);background:#fbfcfe;box-sizing:border-box}
.wcb-field input:focus{outline:0;border-color:var(--b2);box-shadow:0 0 0 3px rgba(54,80,200,.14);background:#fff}
.wcb-consent{display:flex;gap:9px;align-items:flex-start;margin:4px 0 14px}
.wcb-consent input{margin-top:2px;width:16px;height:16px;accent-color:var(--b);flex:none;cursor:pointer}
.wcb-consent label{font-size:11.5px;line-height:1.5;color:var(--soft);cursor:pointer}
.wcb-consent a{color:var(--b2)}
.wcb-submit{width:100%;border:0;cursor:pointer;background:var(--a);color:#fff;padding:14px;border-radius:13px;font-weight:800;font-size:15.5px;transition:background .18s,transform .18s}
.wcb-submit:hover:not(:disabled){background:var(--ad);transform:translateY(-1px)}
.wcb-submit:disabled{background:#c7ccdd;cursor:not-allowed}
.wcb-err{color:var(--ad);font-size:12px;margin:-6px 0 10px;min-height:0}
.wcb-done{text-align:center;padding:8px 4px 6px}
.wcb-check{width:56px;height:56px;margin:0 auto 14px;border-radius:50%;background:rgba(31,157,99,.12);display:grid;place-items:center}
.wcb-check svg{width:28px;height:28px}
.wcb-done h3{margin:0 0 6px;font-size:18px;font-weight:800;color:var(--ink)}
.wcb-done p{margin:0;font-size:13.5px;line-height:1.55;color:var(--soft)}
.wcb-foot{text-align:center;padding:11px;font-size:11px;color:#9aa1ba;border-top:1px solid var(--line)}
.wcb-hidden{display:none !important}
@media (prefers-reduced-motion:reduce){.wcb-launcher,.wcb-panel,.wcb-callbtn,.wcb-submit{transition:none}.wcb-root[data-mode="call"] .wcb-ic::after{animation:none}}
</style>

<script>
(function(){
  "use strict";
  var CFG = <?php echo $json; // już zakodowane przez wp_json_encode ?>;
  var root=document.getElementById("wcbRoot"),launcher=document.getElementById("wcbLauncher"),
      closeBtn=document.getElementById("wcbClose"),modeCall=document.getElementById("wcbModeCall"),
      modeCb=document.getElementById("wcbModeCb"),done=document.getElementById("wcbDone"),
      submit=document.getElementById("wcbSubmit"),err=document.getElementById("wcbErr");

  document.getElementById("wcbNumber").textContent=CFG.phonePretty;
  document.getElementById("wcbCallLink").href="tel:"+CFG.phoneDial;
  document.getElementById("wcbPrivacy").href=CFG.privacyUrl;
  var hh=(""+CFG.openHour).padStart(2,"0")+":00–"+(""+CFG.closeHour).padStart(2,"0")+":00";
  document.getElementById("wcbHours").textContent="Pon–Pt, "+hh;

  function openNow(){var n=new Date(),d=n.getDay()===0?7:n.getDay(),h=n.getHours();
    return CFG.workdays.indexOf(d)!==-1 && h>=CFG.openHour && h<CFG.closeHour;}
  function render(){
    var call=openNow();
    root.setAttribute("data-mode",call?"call":"callback");
    done.classList.add("wcb-hidden");
    if(call){
      document.getElementById("wcbLaunchLabel").textContent="Masz pytanie? Zadzwoń";
      document.getElementById("wcbStatus").textContent="Jesteśmy teraz dostępni";
      document.getElementById("wcbTitle").textContent="Masz pytanie?";
      document.getElementById("wcbSub").textContent="Zadzwoń do działu rekrutacji — pomożemy dokończyć zgłoszenie.";
      modeCall.classList.remove("wcb-hidden");modeCb.classList.add("wcb-hidden");
    }else{
      document.getElementById("wcbLaunchLabel").textContent="Masz pytanie? Oddzwonimy";
      document.getElementById("wcbStatus").textContent="Jesteśmy już po godzinach";
      document.getElementById("wcbTitle").textContent="Zostaw numer";
      document.getElementById("wcbSub").textContent="Jesteśmy już po godzinach. Zostaw numer — oddzwonimy najszybciej, jak to możliwe.";
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
    if(phone.replace(/\D/g,"").length<9){err.textContent="Podaj poprawny numer telefonu.";phEl.focus();return;}
    if(!consent){err.textContent="Potrzebujemy zgody na kontakt telefoniczny.";return;}
    submit.disabled=true;submit.textContent="Wysyłanie…";
    fetch(CFG.rest,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":CFG.nonce},
      body:JSON.stringify({name:name,phone:phone,consent:true,company:company})})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d&&d.ok){modeCb.classList.add("wcb-hidden");done.classList.remove("wcb-hidden");
          if(d.scheduled===false){document.getElementById("wcbDoneMsg").textContent="Mamy Twój numer. Oddzwaniamy teraz — odbierz proszę połączenie.";}
        }else{
          var m=d&&d.error==="phone"?"Podaj poprawny numer telefonu.":d&&d.error==="consent"?"Potrzebujemy zgody na kontakt.":"Coś poszło nie tak. Spróbuj ponownie lub zadzwoń do nas.";
          err.textContent=m;submit.disabled=false;submit.textContent="Oddzwońcie do mnie";
        }
      })
      .catch(function(){err.textContent="Coś poszło nie tak. Spróbuj ponownie lub zadzwoń do nas.";submit.disabled=false;submit.textContent="Oddzwońcie do mnie";});
  });

  render();
  setInterval(render,60000);
})();
</script>
	<?php
	return ob_get_clean();
}

/** Globalny widget w stopce (wyłącz: add_filter( 'welyo_callback_auto_footer', '__return_false' ); ). */
add_action( 'wp_footer', function () {
	if ( is_admin() || ! apply_filters( 'welyo_callback_auto_footer', true ) ) {
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
