<?php

require_once get_template_directory() . '/configure/front-page-defaults/home-promos/fields.php';

$acf_group = get_query_var( 'home_promos' );
$section   = akademiata_home_promos_fields( is_array( $acf_group ) ? $acf_group : null );

if ( empty( $section['show'] ) ) {
	return;
}

$cards = is_array( $section['cards'] ?? null ) ? $section['cards'] : [];
$cards = akademiata_home_promos_normalize_card_areas( $cards );
if ( $cards === [] ) {
	return;
}

$title       = trim( (string) ( $section['title'] ?? '' ) );
$arrow_label = __( 'Zniżki i promocje', 'akademiata' );
$allowed     = akademiata_home_promos_allowed_tags();
?>
<section class="home-promos" aria-labelledby="home-promos-title">
	<div class="container">
		<?php if ( $title !== '' ) : ?>
			<h2 id="home-promos-title" class="small_title mb-5"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<div class="home-promos__grid">
			<?php foreach ( $cards as $card ) :
				if ( ! is_array( $card ) ) {
					continue;
				}

				$layout   = ( ( $card['layout'] ?? '' ) === 'solid' ) ? 'solid' : 'media';
				$area     = preg_replace( '/[^a-f]/', '', (string) ( $card['area'] ?? 'a' ) ) ?: 'a';
				$color    = (string) ( $card['color'] ?? 'peach' );
				$bg       = akademiata_home_promos_card_bg( $card );
				$badge    = trim( (string) ( $card['badge'] ?? '' ) );
				$badge_bg = trim( (string) ( $card['badge_bg'] ?? '' ) );
				$img_url  = akademiata_home_promos_card_image_url( $card );
				$headline = (string) ( $card['headline'] ?? '' );
				$value    = trim( (string) ( $card['value'] ?? '' ) );
				$text     = trim( (string) ( $card['text'] ?? '' ) );
				$meta     = trim( (string) ( $card['meta'] ?? '' ) );
				$card_url = akademiata_home_promos_card_url( $card );

				if ( $layout === 'media' && $img_url === '' && $headline === '' && $text === '' && $value === '' ) {
					continue;
				}

				$classes = [
					'home-promo',
					'home-promo--' . $layout,
					'home-promo--area-' . $area,
				];
				if ( $color !== 'custom' && $color !== '' ) {
					$classes[] = 'home-promo--' . sanitize_html_class( $color );
				}
				?>
				<a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
				   style="<?php echo esc_attr( '--hp-card-bg:' . $bg . ';' ); ?>"
				   href="<?php echo esc_url( $card_url ); ?>">
					<?php if ( $layout === 'media' ) : ?>
						<div class="home-promo__media">
							<?php if ( $img_url !== '' ) : ?>
								<img src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy">
							<?php endif; ?>
							<?php if ( $badge !== '' ) : ?>
								<span class="home-promo__badge"<?php echo $badge_bg !== '' ? ' style="background-color:' . esc_attr( $badge_bg ) . ';"' : ''; ?>>
									<?php echo esc_html( $badge ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php elseif ( $badge !== '' ) : ?>
						<span class="home-promo__eyebrow"><?php echo esc_html( $badge ); ?></span>
					<?php endif; ?>

					<div class="home-promo__body">
						<?php if ( $value !== '' ) : ?>
							<div class="home-promo__row">
								<?php if ( $headline !== '' ) : ?>
									<p class="home-promo__headline"><?php echo wp_kses( $headline, $allowed ); ?></p>
								<?php endif; ?>
								<p class="home-promo__value"><strong><?php echo esc_html( $value ); ?></strong></p>
							</div>
						<?php elseif ( $headline !== '' ) : ?>
							<p class="home-promo__headline"><?php echo wp_kses( $headline, $allowed ); ?></p>
						<?php endif; ?>

						<?php if ( $text !== '' ) : ?>
							<p class="home-promo__text"><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>
						<?php if ( $meta !== '' ) : ?>
							<p class="home-promo__meta"><?php echo esc_html( $meta ); ?></p>
						<?php endif; ?>
					</div>

					<span class="home-promo__arrow" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false">
							<path d="M4 12h12.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
							<path d="M12.5 6.5 19 12l-6.5 5.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<span class="sr-only"><?php echo esc_html( $arrow_label ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
