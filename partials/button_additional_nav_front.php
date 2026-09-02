<?php
/**
 * Header Smart Apply login — everywhere except offer/product singles (registration CTA there).
 *
 * @package akademiata
 */

if ( akademiata_schema_is_offer_single_view() ) {
	return;
}

get_template_part( 'partials/smartapply-login-link' );
