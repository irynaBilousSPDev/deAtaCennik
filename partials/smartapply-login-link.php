<?php
/**
 * Smart Apply login CTA — header (nav-cta-button) or footer (footer-login-cta).
 *
 * @package akademiata
 */

$variant = isset( $args['variant'] ) ? (string) $args['variant'] : 'header';
$class   = $variant === 'footer' ? 'footer-login-cta' : 'nav-cta-button';
$link    = akademiata_get_smartapply_login_link();

if ( empty( $link['url'] ) || empty( $link['title'] ) ) {
	return;
}
?>
<a href="<?php echo esc_url( $link['url'] ); ?>" class="<?php echo esc_attr( $class ); ?>" title="<?php echo esc_attr( $link['hint'] ); ?>" target="_blank" rel="noopener noreferrer">
	<svg class="<?php echo esc_attr( $class ); ?>__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
	</svg>
	<?php echo esc_html( $link['title'] ); ?>
</a>
