<?php
$show_cta =
	is_front_page()
	|| ( is_page() && (
			in_array( 'page-template-page-aktualnosci', get_body_class(), true )
			|| in_array( 'page-template-page-contact', get_body_class(), true )
		)
	)
	|| is_singular('post');

if ( $show_cta ) :
	get_template_part( 'partials/smartapply-login-link' );
endif;
