<?php

function akademiata_admin_cpts() {
	return akademiata_theme_post_types();
}

function akademiata_admin_ensure_post_supports() {
	foreach ( akademiata_admin_cpts() as $post_type ) {
		add_post_type_support( $post_type, 'author' );
		add_post_type_support( $post_type, 'revisions' );
	}
}
add_action( 'init', 'akademiata_admin_ensure_post_supports', 99 );

function akademiata_admin_unhide_meta_boxes( $hidden, $screen ) {
	if ( empty( $screen->post_type ) || ! in_array( $screen->post_type, akademiata_admin_cpts(), true ) ) {
		return $hidden;
	}

	return array_values( array_diff( (array) $hidden, array( 'revisionsdiv', 'authordiv' ) ) );
}
add_filter( 'default_hidden_meta_boxes', 'akademiata_admin_unhide_meta_boxes', 10, 2 );
