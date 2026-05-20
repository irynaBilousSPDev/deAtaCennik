<?php
/**
 * Postgraduate / MBA tuition — ACF payments + price table (legacy cennik on offer post).
 *
 * @package akademiata
 */

$acf_fields = get_query_var('acf_fields', []);
$payments = $acf_fields['payments'] ?? [];
$more_info = $acf_fields['more_info'] ?? '';

$sub_title = __('Opłaty za studia', 'akademiata');
$section_title = __('W ATA to Ty decydujesz, jak chcesz zaplanować wydatki na studia!', 'akademiata');
$table_price_title = __('Elastyczne płatności dla Twojej wygody', 'akademiata');

set_query_var('pg_mba_wroclaw_slug', get_query_var('pg_mba_wroclaw_slug', ''));
set_query_var('pg_mba_warszawa_slug', get_query_var('pg_mba_warszawa_slug', ''));
set_query_var('pg_mba_online_slug', get_query_var('pg_mba_online_slug', ''));
?>

<section id="tuition_fees" class="section_tuition_fees section_tuition_fees--acf">
    <div class="container">
        <h2 class="small_title primary_color mb-3"><?php echo esc_html($sub_title); ?></h2>
        <h3 class="title_section col-xl-10 p-0 mb-3"><?php echo esc_html($section_title); ?></h3>
    </div>

    <div class="container">
        <div class="small_container py-md-5 py-3">
            <?php
            set_query_var('tuition_payments', $payments);
            get_template_part('template-parts/single-offer/pg-mba/partials/tuition-payments');
            ?>
        </div>

        <?php if (!empty($more_info)) : ?>
            <div class="description py-3 mb-md-5 mb-3">
                <?php echo wp_kses_post($more_info); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="table_header small_container">
        <div class="container">
            <?php if (!empty($table_price_title)) : ?>
                <h4 class="small_title pb-3 mb-md-5 mb-3">
                    <?php echo esc_html($table_price_title); ?>
                </h4>
            <?php endif; ?>
        </div>
    </div>

    <div class="price_table price_table--acf">
        <div class="container">
            <div class="small_container">
                <?php get_template_part('template-parts/single-offer/pg-mba/partials/price-table-acf'); ?>
                <?php get_template_part('template-parts/single-offer/pg-mba/partials/bank-transfer-info'); ?>
            </div>
        </div>
    </div>
</section>
