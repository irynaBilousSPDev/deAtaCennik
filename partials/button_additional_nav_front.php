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

	$map = [
		'pl' => [
			// 'path'  => '/dzien-otwarty-warszawa-2026/',
			'path'  => '/oferta/',
			'title' => 'Oferta',
			// 'title' => 'Dzień Otwarty',
		],
		'en' => [
			// 'path'  => '/dzien-otwarty-warszawa-2026/',
			'path'  => '/en/offer/',
			'title' => 'Study Offer',
			// 'title' => 'Open Day',
		],
		'uk' => [
			// 'path'  => '/dzien-otwarty-warszawa-2026/',
			'path'  => '/uk/propozyciya/',
			'title' => 'Пропозиція',
			// 'title' => 'День відкритих дверей',
		],
		'ru' => [
			// 'path'  => '/dzien-otwarty-warszawa-2026/',
			'path'  => '/ru/predlozhenie/',
			'title' => 'Предложение',
			// 'title' => 'День открытых дверей',
		],
	];

	$config = $map[ $lang ] ?? $map['pl'];
	?>
    <a href="<?php echo esc_url(home_url( $config['path'] ) ); ?>" class="nav-cta-button">
		<?php echo esc_html( $config['title'] ); ?>
    </a>
<?php endif; ?>
