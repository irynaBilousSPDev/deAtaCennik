<?php

function akademiata_admin_cpts() {
	return array(
		'bachelor',
		'master',
		'postgraduate',
		'mba',
		'courses',
		'exams',
		'contact',
		'cadre',
		'faq',
		'youtube_shorts',
	);
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

function akademiata_admin_register_edit_info_metabox() {
	foreach ( akademiata_admin_cpts() as $post_type ) {
		add_meta_box(
			'akademiata_edit_info',
			__( 'Autor i historia', 'akademiata' ),
			'akademiata_admin_render_edit_info_metabox',
			$post_type,
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'akademiata_admin_register_edit_info_metabox' );

function akademiata_admin_render_edit_info_metabox( $post ) {
	$author_id = (int) $post->post_author;
	$author    = $author_id ? get_userdata( $author_id ) : null;

	$last_id = (int) get_post_meta( $post->ID, '_edit_last', true );
	if ( ! $last_id ) {
		$last_id = $author_id;
	}
	$last_user = $last_id ? get_userdata( $last_id ) : null;

	$revisions = wp_get_post_revisions( $post->ID, array( 'order' => 'DESC' ) );
	$rev_count = is_array( $revisions ) ? count( $revisions ) : 0;

	echo '<p><strong>' . esc_html__( 'Autor wpisu', 'akademiata' ) . '</strong><br>';
	echo esc_html( $author ? $author->display_name : '—' ) . '</p>';

	echo '<p><strong>' . esc_html__( 'Ostatni zapis', 'akademiata' ) . '</strong><br>';
	echo esc_html( $last_user ? $last_user->display_name : '—' );
	echo '<br><span class="description">' . esc_html( get_the_modified_date( 'Y-m-d H:i', $post ) ) . '</span></p>';

	echo '<p><strong>' . esc_html__( 'Rewizje', 'akademiata' ) . '</strong><br>';
	if ( $rev_count > 0 && wp_revisions_enabled( $post ) ) {
		$revision_ids = array_keys( $revisions );
		$browse_url   = admin_url(
			'revision.php?action=browse&posts=' . $post->ID . '&revision=' . (int) $revision_ids[0]
		);
		echo esc_html( sprintf( _n( '%d wersja', '%d wersji', $rev_count, 'akademiata' ), $rev_count ) );
		echo ' — <a href="' . esc_url( $browse_url ) . '">' . esc_html__( 'Przeglądaj', 'akademiata' ) . '</a>';
	} else {
		esc_html_e( 'Brak — zapisz wpis ponownie po zmianach ACF.', 'akademiata' );
	}
	echo '</p>';
}
