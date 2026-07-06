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

function akademiata_youtube_has_api_key() {
	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		return true;
	}

	$settings = akademiata_youtube_get_settings();
	return isset( $settings['api_key'] ) && (string) $settings['api_key'] !== '';
}

function akademiata_get_youtube_api_key() {
	if ( defined( 'YOUTUBE_API_KEY' ) && YOUTUBE_API_KEY !== '' ) {
		return (string) YOUTUBE_API_KEY;
	}

	$settings = akademiata_youtube_get_settings();
	$stored   = isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';

	return akademiata_youtube_decrypt_secret( $stored );
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

	if ( $http_code !== 200 || $body === '' ) {
		return new WP_Error(
			'akademiata_youtube_upstream_error',
			__( 'Failed to fetch YouTube data.', 'akademiata' ),
			array(
				'status' => $http_code ?: 502,
			)
		);
	}

	$data = json_decode( $body, true );
	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return new WP_Error(
			'akademiata_youtube_invalid_json',
			__( 'Invalid JSON response from YouTube API.', 'akademiata' ),
			array( 'status' => 502 )
		);
	}

	return $data;
}

function akademiata_youtube_rest_permission( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce ) {
		$nonce = $request->get_param( '_wpnonce' );
	}

	return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

function akademiata_youtube_rest_handler( WP_REST_Request $request ) {
	$playlist_id = sanitize_text_field( (string) $request->get_param( 'id' ) );
	$video_id    = sanitize_text_field( (string) $request->get_param( 'videoId' ) );

	$result = akademiata_youtube_fetch_data( $playlist_id, $video_id );
	if ( is_wp_error( $result ) ) {
		$data   = $result->get_error_data();
		$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 500;

		return new WP_REST_Response(
			array(
				'error'   => $result->get_error_code(),
				'message' => $result->get_error_message(),
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

	if ( ! empty( $input['api_key_clear'] ) ) {
		$out['api_key'] = '';
	} elseif ( isset( $input['api_key'] ) ) {
		$api_key = sanitize_text_field( wp_unslash( $input['api_key'] ) );
		if ( $api_key !== '' ) {
			$out['api_key'] = akademiata_youtube_encrypt_secret( $api_key );
		} else {
			$out['api_key'] = $current['api_key'];
			if ( $out['api_key'] !== '' && ! akademiata_youtube_is_encrypted_secret( $out['api_key'] ) ) {
				$out['api_key'] = akademiata_youtube_encrypt_secret( akademiata_youtube_decrypt_secret( $out['api_key'] ) );
			}
		}
	}

	return $out;
}

function akademiata_youtube_register_admin() {
	add_submenu_page(
		'theme-general-settings',
		__( 'YouTube API', 'akademiata' ),
		__( 'YouTube API', 'akademiata' ),
		'manage_options',
		'akademiata-youtube-api',
		'akademiata_youtube_render_admin_page'
	);

	register_setting(
		'akademiata_youtube_settings_group',
		AKADEMIATA_YOUTUBE_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'akademiata_youtube_sanitize_settings',
			'default'           => akademiata_youtube_default_settings(),
		)
	);
}
add_action( 'admin_menu', 'akademiata_youtube_register_admin', 20 );

function akademiata_youtube_admin_field_secret( $settings ) {
	$has_value = akademiata_youtube_has_api_key();
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
				type="password"
				class="large-text akademiata-youtube-secret-input"
				id="akademiata_youtube_api_key"
				name="<?php echo esc_attr( AKADEMIATA_YOUTUBE_OPTION ); ?>[api_key]"
				value=""
				autocomplete="new-password"
				spellcheck="false"
				<?php disabled( $from_env ); ?>
				<?php if ( $has_value && ! $from_env ) : ?>
					placeholder="<?php echo esc_attr( str_repeat( '•', 16 ) ); ?>"
				<?php endif; ?>
			>
			<p class="description">
				<?php if ( $has_value && ! $from_env ) : ?>
					<span class="akademiata-youtube-secret-status akademiata-youtube-secret-status--saved">
						<?php esc_html_e( 'Zapisano — wartość jest ukryta.', 'akademiata' ); ?>
					</span>
					<?php esc_html_e( 'Wpisz nowy klucz tylko przy zmianie.', 'akademiata' ); ?>
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
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'YouTube API', 'akademiata' ); ?></h1>
		<p><?php esc_html_e( 'Klucz jest szyfrowany w bazie i nie jest wyświetlany po zapisaniu. Slider „Nasi studenci” i inne sekcje z playlistami używają go przez zabezpieczony endpoint REST.', 'akademiata' ); ?></p>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'akademiata_youtube_settings_group' );
			?>
			<table class="form-table" role="presentation">
				<?php akademiata_youtube_admin_field_secret( $settings ); ?>
			</table>
			<?php submit_button(); ?>
		</form>
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
	</style>
	<?php
}
