<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AKADEMIATA_YOUTUBE_OPTION = 'akademiata_youtube_settings';

function akademiata_youtube_default_settings() {
	return array(
		'api_key' => '',
	);
}

function akademiata_youtube_get_settings() {
	$saved = get_option( AKADEMIATA_YOUTUBE_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), akademiata_youtube_default_settings() );
}

function akademiata_youtube_is_encrypted_secret( $value ) {
	return is_string( $value ) && strpos( $value, 'enc:' ) === 0;
}

function akademiata_youtube_encrypt_secret( $plaintext ) {
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

function akademiata_youtube_decrypt_secret( $stored ) {
	$stored = (string) $stored;
	if ( $stored === '' || ! akademiata_youtube_is_encrypted_secret( $stored ) || ! function_exists( 'openssl_decrypt' ) ) {
		return $stored;
	}

	$raw = base64_decode( substr( $stored, 4 ), true );
	if ( $raw === false || strlen( $raw ) < 17 ) {
		return '';
	}

	$key   = hash( 'sha256', wp_salt( 'auth' ), true );
	$plain = openssl_decrypt( substr( $raw, 16 ), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );

	return $plain !== false ? $plain : '';
}

function akademiata_validate_youtube_api_key_format( $api_key ) {
	$api_key = trim( (string) $api_key );

	return $api_key !== '' && (bool) preg_match( '/^AIza[0-9A-Za-z_-]{10,}$/', $api_key );
}

/** Wyciąga sam klucz z wklejki (np. z youtube-proxy.php z cudzysłowami lub $apiKey = ...). */
function akademiata_youtube_normalize_api_key_input( $value ) {
	$value = trim( (string) wp_unslash( $value ) );
	if ( $value === '' ) {
		return '';
	}

	if ( preg_match( '/AIza[0-9A-Za-z_-]{10,}/', $value, $matches ) ) {
		return $matches[0];
	}

	return preg_replace( '/[^A-Za-z0-9_-]/', '', $value );
}

function akademiata_youtube_key_storage_broken() {
	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		return false;
	}

	$settings = akademiata_youtube_get_settings();
	$stored   = isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
	if ( $stored === '' ) {
		return false;
	}

	$plain = trim( akademiata_youtube_decrypt_secret( $stored ) );

	return ! akademiata_validate_youtube_api_key_format( $plain );
}

function akademiata_youtube_has_stored_secret() {
	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		return true;
	}

	$settings = akademiata_youtube_get_settings();

	return isset( $settings['api_key'] ) && (string) $settings['api_key'] !== '';
}

function akademiata_youtube_has_api_key() {
	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		return true;
	}

	return ! akademiata_youtube_key_storage_broken() && akademiata_validate_youtube_api_key_format( akademiata_get_youtube_api_key() );
}

function akademiata_get_youtube_api_key() {
	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		return trim( (string) YOUTUBE_API_KEY );
	}

	$settings = akademiata_youtube_get_settings();
	$stored   = isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
	if ( $stored === '' ) {
		return '';
	}

	$plain = trim( akademiata_youtube_decrypt_secret( $stored ) );
	if ( ! akademiata_validate_youtube_api_key_format( $plain ) ) {
		return '';
	}

	return $plain;
}

function akademiata_normalize_youtube_playlist_id( $value ) {
	$value = trim( (string) $value );
	if ( $value === '' ) {
		return '';
	}

	if ( preg_match( '/[?&]list=([A-Za-z0-9_-]+)/', $value, $matches ) ) {
		return $matches[1];
	}

	return $value;
}

function akademiata_validate_youtube_playlist_id( $playlist_id ) {
	return (bool) preg_match( '/^PL[\w-]{10,}$/', (string) $playlist_id );
}

function akademiata_validate_youtube_video_id( $video_id ) {
	return (bool) preg_match( '/^[\w-]{11}$/', (string) $video_id );
}

function akademiata_youtube_proxy_url() {
	return rest_url( 'akademiata/v1/youtube' );
}

function akademiata_youtube_fetch_data( $playlist_id = '', $video_id = '' ) {
	$api_key = akademiata_get_youtube_api_key();
	if ( $api_key === '' ) {
		return new WP_Error(
			'akademiata_youtube_no_key',
			__( 'YouTube API key is not configured.', 'akademiata' ),
			array( 'status' => 503 )
		);
	}

	if ( $playlist_id !== '' ) {
		if ( ! akademiata_validate_youtube_playlist_id( $playlist_id ) ) {
			return new WP_Error(
				'akademiata_youtube_invalid_playlist',
				__( 'Invalid YouTube playlist ID.', 'akademiata' ),
				array( 'status' => 400 )
			);
		}

		$url = add_query_arg(
			array(
				'part'       => 'snippet,contentDetails',
				'maxResults' => 10,
				'playlistId' => $playlist_id,
				'key'        => $api_key,
			),
			'https://www.googleapis.com/youtube/v3/playlistItems'
		);
	} elseif ( $video_id !== '' ) {
		if ( ! akademiata_validate_youtube_video_id( $video_id ) ) {
			return new WP_Error(
				'akademiata_youtube_invalid_video',
				__( 'Invalid YouTube video ID.', 'akademiata' ),
				array( 'status' => 400 )
			);
		}

		$url = add_query_arg(
			array(
				'part' => 'snippet,contentDetails,statistics',
				'id'   => $video_id,
				'key'  => $api_key,
			),
			'https://www.googleapis.com/youtube/v3/videos'
		);
	} else {
		return new WP_Error(
			'akademiata_youtube_missing_param',
			__( 'No valid parameters provided.', 'akademiata' ),
			array( 'status' => 400 )
		);
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/json',
				'User-Agent' => 'AkademiataYouTube/1.0',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'akademiata_youtube_request_failed',
			__( 'Failed to fetch YouTube data.', 'akademiata' ),
			array(
				'status'  => 502,
				'details' => $response->get_error_message(),
			)
		);
	}

	$http_code = (int) wp_remote_retrieve_response_code( $response );
	$body      = wp_remote_retrieve_body( $response );
	$data      = json_decode( $body, true );

	if ( $http_code !== 200 || $body === '' ) {
		$google_message = '';
		if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
			$google_message = (string) $data['error']['message'];
		}

		return new WP_Error(
			'akademiata_youtube_upstream_error',
			$google_message !== '' ? $google_message : __( 'Failed to fetch YouTube data.', 'akademiata' ),
			array(
				'status'  => $http_code ?: 502,
				'details' => $google_message,
			)
		);
	}

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return new WP_Error(
			'akademiata_youtube_invalid_json',
			__( 'Invalid JSON response from YouTube API.', 'akademiata' ),
			array( 'status' => 502 )
		);
	}

	return $data;
}

function akademiata_youtube_test_api_key() {
	$api_key = akademiata_get_youtube_api_key();
	if ( $api_key === '' ) {
		if ( akademiata_youtube_key_storage_broken() ) {
			return new WP_Error(
				'akademiata_youtube_key_broken',
				__( 'Zapisany klucz nie może zostać odczytany na tym środowisku. Usuń go i wklej ponownie.', 'akademiata' )
			);
		}

		return new WP_Error(
			'akademiata_youtube_no_key',
			__( 'Brak zapisanego klucza API.', 'akademiata' )
		);
	}

	$url = add_query_arg(
		array(
			'part' => 'id',
			'id'   => 'jNQXAC9IVRw',
			'key'  => $api_key,
		),
		'https://www.googleapis.com/youtube/v3/videos'
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/json',
				'User-Agent' => 'AkademiataYouTube/1.0',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$http_code = (int) wp_remote_retrieve_response_code( $response );
	$body      = wp_remote_retrieve_body( $response );
	$data      = json_decode( $body, true );

	if ( $http_code !== 200 ) {
		$google_message = is_array( $data ) && isset( $data['error']['message'] )
			? (string) $data['error']['message']
			: __( 'Google odrzuciło klucz API.', 'akademiata' );

		return new WP_Error( 'akademiata_youtube_test_failed', $google_message );
	}

	return true;
}

function akademiata_youtube_rest_permission( WP_REST_Request $request ) {
	$playlist_id = akademiata_normalize_youtube_playlist_id( (string) $request->get_param( 'id' ) );
	$video_id    = sanitize_text_field( (string) $request->get_param( 'videoId' ) );

	if ( $playlist_id !== '' ) {
		return akademiata_validate_youtube_playlist_id( $playlist_id );
	}

	if ( $video_id !== '' ) {
		return akademiata_validate_youtube_video_id( $video_id );
	}

	return false;
}

function akademiata_youtube_rest_handler( WP_REST_Request $request ) {
	$playlist_id = akademiata_normalize_youtube_playlist_id( (string) $request->get_param( 'id' ) );
	$video_id    = sanitize_text_field( (string) $request->get_param( 'videoId' ) );

	$result = akademiata_youtube_fetch_data( $playlist_id, $video_id );
	if ( is_wp_error( $result ) ) {
		$data   = $result->get_error_data();
		$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 500;

		return new WP_REST_Response(
			array(
				'error'   => $result->get_error_code(),
				'message' => $result->get_error_message(),
				'details' => is_array( $data ) && isset( $data['details'] ) ? $data['details'] : '',
			),
			$status
		);
	}

	return rest_ensure_response( $result );
}

function akademiata_youtube_register_rest_route() {
	register_rest_route(
		'akademiata/v1',
		'/youtube',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'akademiata_youtube_rest_handler',
			'permission_callback' => 'akademiata_youtube_rest_permission',
			'args'                => array(
				'id'      => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'videoId' => array(
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'akademiata_youtube_register_rest_route' );

function akademiata_youtube_sanitize_settings( $input ) {
	$current = akademiata_youtube_get_settings();
	$out     = $current;
	$input   = is_array( $input ) ? $input : array();
	$api_key = isset( $input['api_key'] ) ? akademiata_youtube_normalize_api_key_input( $input['api_key'] ) : '';

	if ( $api_key !== '' ) {
		if ( ! akademiata_validate_youtube_api_key_format( $api_key ) ) {
			add_settings_error(
				AKADEMIATA_YOUTUBE_OPTION,
				'akademiata_youtube_invalid_key_format',
				__( 'Nieprawidłowy format klucza API. Wklej sam klucz z Google Cloud (zaczyna się od AIza), bez cudzysłowów i kodu PHP.', 'akademiata' ),
				'error'
			);
			$out['api_key'] = $current['api_key'];
		} else {
			$out['api_key'] = akademiata_youtube_encrypt_secret( $api_key );
		}
	} elseif ( ! empty( $input['api_key_clear'] ) ) {
		$out['api_key'] = '';
	} elseif ( $out['api_key'] !== '' && ! akademiata_youtube_is_encrypted_secret( $out['api_key'] ) ) {
		$out['api_key'] = akademiata_youtube_encrypt_secret( akademiata_youtube_decrypt_secret( $out['api_key'] ) );
	}

	return $out;
}

function akademiata_youtube_register_admin_menu() {
	add_submenu_page(
		'theme-general-settings',
		__( 'YouTube API', 'akademiata' ),
		__( 'YouTube API', 'akademiata' ),
		'manage_options',
		'akademiata-youtube-api',
		'akademiata_youtube_render_admin_page'
	);
}

function akademiata_youtube_persist_settings_errors() {
	$errors = get_settings_errors();
	if ( $errors ) {
		set_transient( 'settings_errors', $errors, 30 );
	}
}

function akademiata_youtube_handle_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Brak uprawnień.', 'akademiata' ) );
	}

	check_admin_referer( 'akademiata_youtube_save_settings', 'akademiata_youtube_nonce' );

	$redirect = admin_url( 'admin.php?page=akademiata-youtube-api' );

	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		add_settings_error(
			AKADEMIATA_YOUTUBE_OPTION,
			'akademiata_youtube_env_blocks_panel',
			__( 'Klucz nadal jest ustawiony w wp-config.php (YOUTUBE_API_KEY). Usuń lub zakomentuj tę stałą, aby zapisywać klucz z panelu.', 'akademiata' ),
			'error'
		);
		akademiata_youtube_persist_settings_errors();
		wp_safe_redirect( $redirect );
		exit;
	}

	$raw_input = isset( $_POST[ AKADEMIATA_YOUTUBE_OPTION ] ) && is_array( $_POST[ AKADEMIATA_YOUTUBE_OPTION ] )
		? wp_unslash( $_POST[ AKADEMIATA_YOUTUBE_OPTION ] )
		: array();

	$normalized_key = isset( $raw_input['api_key'] ) ? akademiata_youtube_normalize_api_key_input( $raw_input['api_key'] ) : '';
	$clear_key      = ! empty( $raw_input['api_key_clear'] );

	$sanitized = akademiata_youtube_sanitize_settings( $raw_input );
	$errors    = get_settings_errors( AKADEMIATA_YOUTUBE_OPTION );
	$failed    = false;

	foreach ( $errors as $error ) {
		if ( isset( $error['code'] ) && $error['code'] === 'akademiata_youtube_invalid_key_format' ) {
			$failed = true;
			break;
		}
	}

	update_option( AKADEMIATA_YOUTUBE_OPTION, $sanitized );

	if ( ! $failed ) {
		if ( $normalized_key !== '' ) {
			add_settings_error(
				AKADEMIATA_YOUTUBE_OPTION,
				'akademiata_youtube_saved',
				__( 'Klucz API został zapisany.', 'akademiata' ),
				'success'
			);
		} elseif ( $clear_key ) {
			add_settings_error(
				AKADEMIATA_YOUTUBE_OPTION,
				'akademiata_youtube_cleared',
				__( 'Zapisany klucz został usunięty.', 'akademiata' ),
				'success'
			);
		}
	}

	akademiata_youtube_persist_settings_errors();
	wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $redirect ) );
	exit;
}

// After ACF registers Theme Settings (admin_menu priority 99).
add_action( 'admin_menu', 'akademiata_youtube_register_admin_menu', 100 );
add_action( 'admin_post_akademiata_youtube_save_settings', 'akademiata_youtube_handle_save_settings' );

function akademiata_youtube_admin_field_secret( $settings ) {
	$has_value = akademiata_youtube_has_stored_secret();
	$key_works = akademiata_youtube_has_api_key();
	$from_env  = defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '';
	?>
	<tr>
		<th scope="row">
			<label for="akademiata_youtube_api_key"><?php esc_html_e( 'Klucz API YouTube', 'akademiata' ); ?></label>
		</th>
		<td>
			<?php if ( $from_env ) : ?>
				<p>
					<span class="akademiata-youtube-secret-status akademiata-youtube-secret-status--saved">
						<?php esc_html_e( 'Używany klucz z wp-config.php (YOUTUBE_API_KEY).', 'akademiata' ); ?>
					</span>
				</p>
				<p class="description">
					<?php esc_html_e( 'Pole poniżej jest ignorowane, dopóki stała YOUTUBE_API_KEY jest ustawiona.', 'akademiata' ); ?>
				</p>
			<?php endif; ?>
			<input
				type="text"
				class="large-text akademiata-youtube-secret-input"
				id="akademiata_youtube_api_key"
				name="<?php echo esc_attr( AKADEMIATA_YOUTUBE_OPTION ); ?>[api_key]"
				value=""
				autocomplete="off"
				spellcheck="false"
				inputmode="verbatim"
				<?php disabled( $from_env ); ?>
				<?php if ( $has_value && ! $from_env ) : ?>
					placeholder="<?php echo esc_attr( str_repeat( '•', 16 ) ); ?>"
				<?php endif; ?>
			>
			<p class="description">
				<?php if ( $has_value && $key_works && ! $from_env ) : ?>
					<span class="akademiata-youtube-secret-status akademiata-youtube-secret-status--saved">
						<?php esc_html_e( 'Zapisano — wartość jest ukryta.', 'akademiata' ); ?>
					</span>
					<?php esc_html_e( 'Wpisz nowy klucz tylko przy zmianie.', 'akademiata' ); ?>
				<?php elseif ( $has_value && ! $key_works && ! $from_env ) : ?>
					<span class="akademiata-youtube-secret-status akademiata-youtube-secret-status--error">
						<?php esc_html_e( 'Zapisany klucz jest nieprawidłowy lub nieczytelny — usuń i wklej ponownie.', 'akademiata' ); ?>
					</span>
				<?php else : ?>
					<?php esc_html_e( 'Klucz z Google Cloud Console (YouTube Data API v3).', 'akademiata' ); ?>
				<?php endif; ?>
			</p>
			<?php if ( $has_value && ! $from_env ) : ?>
				<p class="description">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( AKADEMIATA_YOUTUBE_OPTION ); ?>[api_key_clear]" value="1">
						<?php esc_html_e( 'Usuń zapisany klucz', 'akademiata' ); ?>
					</label>
				</p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function akademiata_youtube_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = akademiata_youtube_get_settings();

	if ( isset( $_GET['akademiata_youtube_test'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'akademiata_youtube_test' ) ) {
		$test_result = akademiata_youtube_test_api_key();
		if ( is_wp_error( $test_result ) ) {
			add_settings_error(
				AKADEMIATA_YOUTUBE_OPTION,
				'akademiata_youtube_test_failed',
				$test_result->get_error_message(),
				'error'
			);
		} else {
			add_settings_error(
				AKADEMIATA_YOUTUBE_OPTION,
				'akademiata_youtube_test_ok',
				__( 'Połączenie z YouTube API działa poprawnie.', 'akademiata' ),
				'success'
			);
		}
	}

	settings_errors( AKADEMIATA_YOUTUBE_OPTION );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'YouTube API', 'akademiata' ); ?></h1>
		<?php if ( akademiata_youtube_key_storage_broken() ) : ?>
			<div class="notice notice-error">
				<p>
					<?php esc_html_e( 'Zapisany klucz jest nieczytelny na tym środowisku (np. po skopiowaniu bazy z produkcji). Zaznacz „Usuń zapisany klucz”, zapisz, a następnie wklej klucz ponownie.', 'akademiata' ); ?>
				</p>
			</div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Klucz jest szyfrowany w bazie i nie jest wyświetlany po zapisaniu. Slider na stronie głównej i inne sekcje z playlistami używają go przez endpoint REST.', 'akademiata' ); ?></p>
		<p class="description">
			<?php esc_html_e( 'ID playlisty ustawiasz w ACF — na stronie głównej w polu „our students → youtube playlist code”, nie tutaj.', 'akademiata' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'akademiata_youtube_save_settings', 'akademiata_youtube_nonce' ); ?>
			<input type="hidden" name="action" value="akademiata_youtube_save_settings">
			<table class="form-table" role="presentation">
				<?php akademiata_youtube_admin_field_secret( $settings ); ?>
			</table>
			<?php submit_button(); ?>
		</form>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=akademiata-youtube-api&akademiata_youtube_test=1' ), 'akademiata_youtube_test' ) ); ?>">
				<?php esc_html_e( 'Testuj klucz API', 'akademiata' ); ?>
			</a>
			<span class="description" style="margin-left: 8px;">
				<?php esc_html_e( 'Najpierw zapisz klucz, potem testuj.', 'akademiata' ); ?>
			</span>
		</p>
		<p class="description">
			<?php esc_html_e( 'W Google Cloud włącz „YouTube Data API v3” i użyj klucza bez ograniczenia HTTP referrer (serwer łączy się bez przeglądarki).', 'akademiata' ); ?>
		</p>
	</div>
	<style>
		.akademiata-youtube-secret-input { max-width: 36rem; font-family: Consolas, Monaco, monospace; }
		.akademiata-youtube-secret-status--saved {
			display: inline-block;
			margin-right: 6px;
			padding: 2px 8px;
			border-radius: 999px;
			background: #edfaef;
			color: #1f6b3a;
			font-weight: 600;
		}
		.akademiata-youtube-secret-status--error {
			display: inline-block;
			margin-right: 6px;
			padding: 2px 8px;
			border-radius: 999px;
			background: #fcf0f1;
			color: #8a1f2d;
			font-weight: 600;
		}
	</style>
	<?php
}
