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

	$lang = apply_filters( 'wpml_current_language', null );

	// Smart Apply login — same URL for all langs (locale switch is inside the app).
	$map = [
		'pl' => ['title' => 'Zaloguj się'],
		'en' => ['title' => 'Log in'],
		'uk' => ['title' => 'Увійти'],
		'ru' => ['title' => 'Войти'],
	];

	// Oferta CTA (restore when needed):
	// 'pl' => ['path' => '/oferta/', 'title' => 'Oferta'],
	// 'en' => ['path' => '/en/offer/', 'title' => 'Study Offer'],
	// 'uk' => ['path' => '/uk/propozyciya/', 'title' => 'Пропозиція'],
	// 'ru' => ['path' => '/ru/predlozhenie/', 'title' => 'Предложение'],

	$config = $map[ $lang ] ?? $map['pl'];
	$cta_url = 'https://smartapply.akademiata.pl/login';
	?>
    <a href="<?php echo esc_url( $cta_url ); ?>" class="nav-cta-button" target="_blank" rel="noopener noreferrer">
		<svg class="nav-cta-button__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
			<path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
		</svg>
		<?php echo esc_html( $config['title'] ); ?>
    </a>
<?php endif; ?>
