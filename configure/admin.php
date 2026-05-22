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

function akademiata_admin_remove_broken_author_metabox() {
	foreach ( akademiata_admin_cpts() as $post_type ) {
		remove_meta_box( 'authordiv', $post_type, 'normal' );
		remove_meta_box( 'authordiv', $post_type, 'side' );
		remove_meta_box( 'authordiv', $post_type, 'advanced' );
	}
}
add_action( 'add_meta_boxes', 'akademiata_admin_remove_broken_author_metabox', 99 );

function akademiata_admin_unhide_meta_boxes( $hidden, $screen ) {
	if ( empty( $screen->post_type ) || ! in_array( $screen->post_type, akademiata_admin_cpts(), true ) ) {
		return $hidden;
	}

	return array_values( array_diff( (array) $hidden, array( 'revisionsdiv' ) ) );
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
	wp_nonce_field( 'akademiata_edit_info', 'akademiata_edit_info_nonce' );

	echo '<p><strong>' . esc_html__( 'Autor wpisu', 'akademiata' ) . '</strong></p>';
	wp_dropdown_users(
		array(
			'name'             => 'post_author',
			'selected'         => (int) $post->post_author,
			'include_selected' => true,
			'show_option_none' => false,
		)
	);

	$last_id = (int) get_post_meta( $post->ID, '_edit_last', true );
	if ( ! $last_id ) {
		$last_id = (int) $post->post_author;
	}
	$last_user = $last_id ? get_userdata( $last_id ) : null;

	echo '<p style="margin-top:12px;"><strong>' . esc_html__( 'Ostatni zapis', 'akademiata' ) . '</strong><br>';
	echo esc_html( $last_user ? $last_user->display_name : '—' );
	echo '<br><span class="description">' . esc_html( get_the_modified_date( 'Y-m-d H:i', $post ) ) . '</span></p>';

	$revisions = wp_get_post_revisions( $post->ID, array( 'order' => 'DESC' ) );
	$rev_count = is_array( $revisions ) ? count( $revisions ) : 0;

	echo '<p><strong>' . esc_html__( 'Rewizje', 'akademiata' ) . '</strong><br>';
	if ( $rev_count > 0 && wp_revisions_enabled( $post ) ) {
		$revision_ids = array_keys( $revisions );
		$browse_url   = admin_url(
			'revision.php?action=browse&posts=' . $post->ID . '&revision=' . (int) $revision_ids[0]
		);
		echo esc_html( sprintf( _n( '%d wersja', '%d wersji', $rev_count, 'akademiata' ), $rev_count ) );
		echo ' — <a href="' . esc_url( $browse_url ) . '">' . esc_html__( 'Przeglądaj', 'akademiata' ) . '</a>';
	} else {
		esc_html_e( 'Brak — zapisz ponownie po zmianach ACF.', 'akademiata' );
	}
	echo '</p>';
}

function akademiata_admin_save_post_author( $post_id ) {
	if ( ! isset( $_POST['akademiata_edit_info_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['akademiata_edit_info_nonce'] ) ), 'akademiata_edit_info' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['post_type'] ) || ! in_array( $_POST['post_type'], akademiata_admin_cpts(), true ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['post_author'] ) ) {
		return;
	}

	remove_action( 'save_post', 'akademiata_admin_save_post_author' );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_author' => (int) $_POST['post_author'],
		)
	);
	add_action( 'save_post', 'akademiata_admin_save_post_author' );
}
add_action( 'save_post', 'akademiata_admin_save_post_author' );
