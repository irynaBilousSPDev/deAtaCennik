<?php

/**
 * Legacy entry point — bootstraps WordPress and delegates to shared handler.
 * Prefer /wp-json/akademiata/v1/youtube (used by theme JS).
 */

$wp_load = dirname( __DIR__, 3 ) . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	header( 'Content-Type: application/json; charset=utf-8' );
	http_response_code( 500 );
	echo wp_json_encode( array( 'error' => 'WordPress bootstrap failed.' ) );
	exit;
}

require_once $wp_load;

header( 'Content-Type: application/json; charset=utf-8' );

$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] )
	? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) )
	: '';

if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
	http_response_code( 403 );
	echo wp_json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

$playlist_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
$video_id    = isset( $_GET['videoId'] ) ? sanitize_text_field( wp_unslash( $_GET['videoId'] ) ) : '';

$result = akademiata_youtube_fetch_data( $playlist_id, $video_id );

if ( is_wp_error( $result ) ) {
	$data   = $result->get_error_data();
	$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 500;
	http_response_code( $status );
	echo wp_json_encode(
		array(
			'error'   => $result->get_error_code(),
			'message' => $result->get_error_message(),
		),
		JSON_UNESCAPED_SLASHES
	);
	exit;
}

echo wp_json_encode( $result, JSON_UNESCAPED_SLASHES );
