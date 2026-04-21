<?php
$full_time_price = get_query_var( 'full_time', [] );
$part_time_price = get_query_var( 'part_time', [] );
$is_warsaw       = (bool) get_query_var( 'is_warsaw', false );
// Check if price data contains at least one actual price
function has_price_data( $price_data ) {
	$columns = [ 'col_12_rat', 'col_semester', 'col_year' ];

	foreach ( $price_data as $year_data ) {
		foreach ( $columns as $col ) {
			if ( ! empty( $year_data[ $col ] ) ) {
				return true;
			}
		}
	}

	return false;
}

// Render price row helper

function get_available_columns( $price_data, $columns ) {
	$available = [];

	foreach ( $columns as $col_index => $col ) {
		foreach ( $price_data as $year_data ) {
			$col_data = $year_data[ $col['key'] ] ?? [];
			if ( ! empty( $col_data[ $col['normal'] ] ) ) {
				$available[ $col_index ] = true;
				break;
			}
		}
	}

	return $available;
}


function render_price_row( $price_data, $columns, $available_columns, $tab_key ) {
	$years        = [ 'I', 'II', 'III', 'IV', 'V' ];
	$current_lang = apply_filters( 'wpml_current_language', null );
	$currency     = ( $current_lang === 'en' ) ? '€' : 'ZŁ';

	foreach ( $price_data as $key => $year_data ):
		// Skip empty rows
		$has_any_price = false;
		foreach ( $columns as $col_index => $col ) {
			if ( ! empty( $available_columns[ $col_index ] ) && ! empty( $year_data[ $col['key'] ] ) ) {
				$has_any_price = true;
				break;
			}
		}
		if ( ! $has_any_price ) {
			continue;
		}

		// Custom label for first year based on language and tab
		if ( $key === 0 && $current_lang === 'en' ) {
			if ( $tab_key === 'full_time' ) {
				$year_label = 'EU/CIS/ OECD - All EU countries, all Balkan countries, Ukraine, Belarus, Moldova, Mongolia, Georgia, Armenia, Kazakhstan, Turkmenistan, Tajikistan, Uzbekistan, Azerbaijan, Kyrgyzstan, Turkey.';
			} elseif ( $tab_key === 'part_time' ) {
				$year_label = 'For non-EU countries';
			} else {
				$year_label = $years[ $key ] ?? '';
			}
		} else {
			$year_label = $years[ $key ] ?? '';
		}
		?>
        <tr>
            <td><?php echo esc_html( $year_label ); ?></td>

			<?php foreach ( $columns as $col_index => $col ):
				if ( empty( $available_columns[ $col_index ] ) ) {
					continue;
				}

				$col_data = $year_data[ $col['key'] ] ?? [];
				if ( empty( $col_data ) || empty( $col_data[ $col['normal'] ] ) ) {
					echo '<td></td>';
					continue;
				}

				$has_promo = ! empty( $col_data[ $col['flag'] ] ) && in_array( 'promotion', $col_data[ $col['flag'] ] );
				?>
                <td>
                    <div class="<?php echo $has_promo ? 'old_price' : ''; ?>">
						<?php echo esc_html( $col_data[ $col['normal'] ] ) . ' ' . $currency; ?>
                    </div>
					<?php if ( $has_promo ): ?>
                        <div class="new_price">
							<?php echo ! empty( $col_data[ $col['promo'] ] ) ? esc_html( $col_data[ $col['promo'] ] ) . ' ' . $currency : ''; ?>

							<?php
							$has_march_2026 = get_query_var( 'has_march_2026', false );

							$tooltip_map = [
								'col_12_rat'   => 'tooltip_text',
								'col_semester' => 'semester_tooltip_text',
								'col_year'     => 'year_tooltip_text',
							];

							$tooltip_key  = $tooltip_map[$col['key']] ?? '';
							$tooltip_text = ($tooltip_key && !empty($col_data[$tooltip_key])) ? $col_data[$tooltip_key] : '';

							if ( $has_march_2026 && ! empty( $tooltip_text ) ): ?>
                                <span class="price-tooltip">
                                    <span class="tooltip-icon">?</span>
                                    <span class="tooltip-content">
                                        <?php echo $tooltip_text; ?>
                                    </span>
                                </span>
                                <style>
                                    .price-tooltip {
                                        position: relative;
                                        display: inline-block;
                                        margin-right: -40px;
                                    }

                                    .tooltip-icon {
                                        position: absolute;
                                        top: -16px;
                                        width: 18px;
                                        height: 18px;
                                        border-radius: 50%;
                                        background: #6b6b6b;
                                        color: #fff !important;
                                        font-size: 12px;
                                        font-weight: bold;
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        cursor: pointer;
                                    }

                                    .tooltip-content {
                                        position: absolute;
                                        bottom: 24px;
                                        left: 45%;
                                        transform: translateX(-45%);
                                        background: #3f3f3f;
                                        color: #fff !important;
                                        padding: 2rem 2.5rem;
                                        border-radius: 8px;
                                        font-size: 14px;
                                        line-height: 1.4;
                                        white-space: normal;
                                        width: 200px;
                                        text-align: left;
                                        opacity: 0;
                                        visibility: hidden;
                                        transition: opacity 0.2s ease;
                                        z-index: 999;
                                    }

                                    /* Arrow */
                                    .tooltip-content::after {
                                        content: '';
                                        position: absolute;
                                        top: 100%;
                                        left: 50%;
                                        transform: translateX(-50%);
                                        border-width: 6px;
                                        border-style: solid;
                                        border-color: #3f3f3f transparent transparent transparent;
                                    }

                                    .price-tooltip:hover .tooltip-content {
                                        opacity: 1;
                                        visibility: visible;
                                    }

                                </style>
							<?php endif; ?>
                        </div>
					<?php endif; ?>
                </td>
			<?php endforeach; ?>
        </tr>
	<?php endforeach;
}

?>

<div class="tabs_container pb-md-5 mb-md-5 mb-3">
	<?php
	$columns = [
		[ 'key' => 'col_12_rat', 'normal' => 'normal_price', 'promo' => 'promotion_price', 'flag' => 'add_promotion' ],
		[
			'key'    => 'col_semester',
			'normal' => 'semester_normal_price',
			'promo'  => 'semester_promotion_price',
			'flag'   => 'add_promotion_semester'
		],
		[
			'key'    => 'col_year',
			'normal' => 'year_normal_price',
			'promo'  => 'year_promotion_price',
			'flag'   => 'add_promotion_year'
		],
	];

	$tabs = [
		'full_time' => [
			'label' => __( 'TRYB STACJONARNY', 'akademiata' ),
			'data'  => $full_time_price
		],
		'part_time' => [
			'label' => __( 'TRYB NIESTACJONARNY ', 'akademiata' ),
			'data'  => $part_time_price
		]
	];

	$tabs = array_filter( $tabs, fn( $tab ) => has_price_data( $tab['data'] ) );
	?>

	<?php if ( ! empty( $tabs ) ): ?>
        <div class="tabs-header">
			<?php $first = true; ?>
			<?php foreach ( $tabs as $key => $tab ): ?>
                <div class="tab<?php echo $first ? ' active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $tab['label'] ); ?>
                </div>
				<?php $first = false; ?>
			<?php endforeach; ?>
        </div>

        <div class="tabs-content">
			<?php $first = true; ?>
			<?php foreach ( $tabs as $key => $tab ): ?>
				<?php
				$available_columns = get_available_columns( $tab['data'], $columns );

				if ( $is_warsaw ) {
					$available_columns[2] = false; // hide ROCZNIE
				}
				$current_lang = apply_filters( 'wpml_current_language', null );
				$style_en     = ( $current_lang === 'en' ) ? 'style="width: 75%"' : '';
				?>
                <div id="<?php echo esc_attr( $key ); ?>" class="tab-content<?php echo $first ? ' active' : ''; ?>">
                    <table>
                        <thead>
                        <tr>
                            <th <?php echo $style_en; ?> ><span><?php _e( 'ROK STUDIÓW', 'akademiata' ); ?></span></th>

							<?php if ( ! empty( $available_columns[0] ) ): ?>
                                <th>
                                    <span><?php _e( '12 RAT', 'akademiata' ); ?></span><br>
									<?php _e( 'WYGODNA NISKA STAŁA PŁATNOŚĆ', 'akademiata' ); ?>
                                </th>
							<?php endif; ?>

							<?php if ( ! empty( $available_columns[1] ) ): ?>
                                <th>
                                    <span><?php _e( 'SEMESTRALNIE', 'akademiata' ); ?></span><br>
									<?php _e( 'PŁATNOŚĆ CO 6 MIESIĘCY', 'akademiata' ); ?>
                                </th>
							<?php endif; ?>

							<?php if ( ! empty( $available_columns[2] ) ): ?>
                                <th>
                                    <span><?php _e( 'ROCZNIE', 'akademiata' ); ?></span><br>
									<?php _e( 'RAZ W ROKU I Z GŁOWY', 'akademiata' ); ?>
                                </th>
							<?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
						<?php render_price_row( $tab['data'], $columns, $available_columns, $key ); ?>
                        </tbody>
                    </table>
                </div>
				<?php $first = false; ?>
			<?php endforeach; ?>
        </div>
	<?php else: ?>
        <p><?php _e( 'Brak dostępnych cenników w tym momencie.', 'akademiata' ); ?></p>
	<?php endif; ?>
</div>
