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
    <a href="<?php echo esc_url( $cta_url ); ?>" class="nav-cta-button">
		<?php echo esc_html( $config['title'] ); ?>
    </a>
<?php endif; ?>
