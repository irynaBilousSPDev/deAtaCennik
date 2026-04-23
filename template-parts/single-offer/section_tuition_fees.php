<?php
$acf_fields = get_query_var('acf_fields', []);
$tuition_fees = $acf_fields['tuition_fees'] ?? [];

// Default subtitles and titles
$sub_title = $tuition_fees['sub_title'] ?: __('Opłaty za studia', 'akademiata');
$section_title = $tuition_fees['title'] ?: __('W ATA to Ty decydujesz, jak chcesz zaplanować wydatki na studia!', 'akademiata');
$table_price_title = $tuition_fees['table_price_title'] ?? __('Elastyczne płatności dla Twojej wygody', 'akademiata');

// Get current post ID and taxonomies
$post_id = get_the_ID();
$logical_sync_key = (string) get_post_meta($post_id, 'logical_sync_key', true);
$page_lang = (string) apply_filters('wpml_current_language', null);
$show_calculator = !empty(trim($logical_sync_key));

// Pricing/post data is only needed when using the legacy payments + table view.
$full_time_price = [];
$part_time_price = [];
$more_info = '';
$payments = [];
$is_warsaw = false;

if (!$show_calculator) {
	$taxonomies = ['program', 'degree', 'city'];

	// Get slugs of current post
	$current_slugs = [];

	foreach ($taxonomies as $taxonomy) {
		$terms = get_the_terms($post_id, $taxonomy);
		if (!empty($terms) && !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$current_slugs[$taxonomy][] = $term->slug;
			}
		}
	}

	if (empty($current_slugs)) return '';

	// Build tax_query for WP_Query
	$tax_query = [];

	foreach ($taxonomies as $taxonomy) {
		if (!empty($current_slugs[$taxonomy])) {
			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => $current_slugs[$taxonomy],
			];
		}
	}

	// Run query for price post
	$query = new WP_Query([
		'post_type' => 'price',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'tax_query' => [
			'relation' => 'AND',
			...$tax_query,
		],
	]);

	if (!$query->have_posts()) return '';

	// Loop through results to find exact match
	$matched_post_id = null;

	foreach ($query->posts as $price_post) {
		$match = true;
		foreach ($taxonomies as $taxonomy) {
			$price_terms = get_the_terms($price_post->ID, $taxonomy);
			$price_slugs = !empty($price_terms) && !is_wp_error($price_terms) ? wp_list_pluck($price_terms, 'slug') : [];

			// Sort both arrays to ensure order doesn't affect comparison
			sort($price_slugs);
			$post_slugs = $current_slugs[$taxonomy] ?? [];
			sort($post_slugs);

			if ($price_slugs !== $post_slugs) {
				$match = false;
				break;
			}
		}

		if ($match) {
			$matched_post_id = $price_post->ID;
			break;
		}
	}

	if (!$matched_post_id) return ''; // No exact match found

	// Load ACF fields from matched price post
	$full_time_price = get_field('full_time', $matched_post_id) ?: [];
	$part_time_price = get_field('part_time', $matched_post_id) ?: [];
	$more_info = get_field('more_info', $matched_post_id) ?: '';
	$payments = get_field('payments', $matched_post_id) ?: [];

	$taxonomy = 'recruitment_date';

	$march_2026_slugs = [
		'marzec-2026',   // PL
		'march-2026',    // EN
		'mart-2026',     // RU
		'berezen-2026',  // UA
	];

	$has_march_2026 = false;

	$terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'slugs']);

	if (!is_wp_error($terms) && !empty($terms)) {
		foreach ($terms as $slug) {
			if (in_array($slug, $march_2026_slugs, true)) {
				$has_march_2026 = true;
				break;
			}
		}
	}
	set_query_var('has_march_2026', $has_march_2026);

	// Warsaw slug variants (fallback only)
	$warsaw_variants = [
		'warszawa',     // PL
		'varshava',     // EN/RU translit
		'varshava-ru',  // RU custom
		'варшава',      // RU native
	];

	// Get assigned "city" terms
	$city_terms = wp_get_post_terms($post_id, 'city', ['fields' => 'all']);
	$is_warsaw  = false;

	if (!is_wp_error($city_terms) && !empty($city_terms)) {
		foreach ($city_terms as $term) {

			$pl_term_id = apply_filters(
				'wpml_object_id',
				$term->term_id,
				'city',
				false,
				'pl'
			);

			if ($pl_term_id) {
				$pl_term = get_term($pl_term_id, 'city');
				if ($pl_term && !is_wp_error($pl_term) && $pl_term->slug === 'warszawa') {
					$is_warsaw = true;
					break;
				}
			}

			if (in_array($term->slug, $warsaw_variants, true)) {
				$is_warsaw = true;
				break;
			}
		}
	}

	// Pass to partials / templates
	set_query_var('is_warsaw', $is_warsaw);
}
?>

<section id="tuition_fees" class="section_tuition_fees">
    <div class="container">
        <h2 class="small_title primary_color mb-3"><?php echo esc_html($sub_title); ?></h2>
        <h3 class="title_section col-xl-10 p-0 mb-3"><?php echo $section_title; ?></h3>
    </div>
    <div class="container"> 
        <?php if (!$show_calculator) : ?>
			<div class="small_container py-md-5 py-3">
				<?php if (!empty($payments)) : ?>

			    <?php
			    $is_warsaw = isset($is_warsaw) ? (bool) $is_warsaw : false;
			    ?>

			    <?php foreach ($payments as $key => $item) :

				    $title       = $item['title'] ?? '';
				    $price       = $item['price'] ?? '';
				    $currency    = $item['currency'] ?? '';
				    $description = $item['description'] ?? '';

				    // TRUE / FALSE or Checkbox (1 option) — works for both
				    $has_promo = !empty($item['promotion']);

				    ?>
                    <div class="row payments_item mb-5">

                        <!-- TITLE -->
                        <div class="small_title d-flex align-items-center mr-5">
						    <?php
						    if ($title) {
							    echo esc_html($title);
						    } elseif ((int) $key === 0) {
							    echo wp_kses_post(
								    __('Opłata rekrutacyjna', 'akademiata') . '<br>' .
								    __('(opłata jednorazowa)', 'akademiata')
							    );
						    } elseif ((int) $key === 1) {
							    echo wp_kses_post(
								    __('Wpisowe', 'akademiata') . '<br>' .
								    __('(opłata jednorazowa)', 'akademiata')
							    );
						    }
						    ?>
                        </div>

                        <!-- PRICE -->
					    <?php if ($price !== '' && $price !== null) : ?>
                            <div class="d-flex align-items-center mr-5">
                                <div class="normal_price <?php echo $has_promo ? 'promotion' : ''; ?>">
								    <?php echo esc_html($price . ' ' . $currency); ?>
                                </div>
                            </div>
					    <?php endif; ?>

                        <!-- PROMOTION / DESCRIPTION -->
					    <?php if ($has_promo) : ?>
                            <div class="d-flex align-items-center mr-5">
                                <div class="description">
								    <?php
								    if ($is_warsaw) {

									    $has_march_2026 = (bool) get_query_var('has_march_2026', false);
									    $is_second      = ((int) $key === 1);

									    if ($is_second && $has_march_2026) {
										    echo esc_html__('Promocja 0 zł wpisowego do końca lutego.', 'akademiata');
									    }
//                                        elseif (!empty($description)) {
//										    echo wp_kses_post($description);
//									    }

								    } else {

//									    if (!empty($description)) {
//										    echo wp_kses_post($description);
//									    } else {
										    echo esc_html__('Zapisz się do końca miesiąca', 'akademiata') . ' ';

										    echo ((int) $key === 0)
											    ? esc_html__('- zapłacisz 0 zł opłaty rekrutacyjnej', 'akademiata')
											    : esc_html__('- zapłacisz 0 zł wpisowego', 'akademiata');
//									    }
								    }
								    ?>
                                </div>
                            </div>
					    <?php endif; ?>

                    </div>
			    <?php endforeach; ?>
		    <?php endif; ?>
			</div>
		<?php endif; ?>


        <?php if (!empty($more_info)) : ?>
            <div class="description py-3 mb-md-5 mb-3">
                <?php echo wp_kses_post($more_info); ?>
            </div>
        <?php endif; ?>
    </div>

	<?php if ($show_calculator) : ?>
		<div class="container">
			<?php
			set_query_var('prices_calculator_fixed_key', $logical_sync_key);
			set_query_var('prices_calculator_fixed_lang', ($page_lang ?: 'pl'));
			set_query_var('prices_calculator_hide_more_btn', true);
			set_query_var('prices_calculator_layout', 'single-offer');
			locate_template('template-parts/prices/calculator.php', true, true);
			?>
		</div>
	<?php endif; ?>

	<?php if (!$show_calculator) : ?>
		<div class="table_header small_container">
			<div class="container">
				<?php if (!empty($table_price_title)) : ?>
					<h4 class="small_title pb-3 mb-md-5 mb-3">
						<?php echo esc_html($table_price_title); ?>
					</h4>
				<?php endif; ?>
			</div>
		</div>

		<div class="price_table<?php echo $is_warsaw ? ' warsaw' : '' ?>">
			<div class="container">
				<div class="small_container">
					<?php
					set_query_var('full_time', is_array($full_time_price) ? $full_time_price : []);
					set_query_var('part_time', is_array($part_time_price) ? $part_time_price : []);
					locate_template('./template-parts/tabs_container.php', true, true);
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>
