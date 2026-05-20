<?php
/**
 * Bachelor / Master tuition — Google Sheets calculator (kalkulator-wse).
 *
 * @package akademiata
 */

$post_id = get_the_ID();
$logical_sync_key = trim((string) get_post_meta($post_id, 'logical_sync_key', true));

if ($logical_sync_key === '') {
    return;
}

$page_lang = (string) apply_filters('wpml_current_language', null);

$acf_fields = get_query_var('acf_fields', []);
$tuition_fees = is_array($acf_fields['tuition_fees'] ?? null) ? $acf_fields['tuition_fees'] : [];

$sub_title = !empty($tuition_fees['sub_title']) ? $tuition_fees['sub_title'] : __('Opłaty za studia', 'akademiata');
$section_title = !empty($tuition_fees['title']) ? $tuition_fees['title'] : __('W ATA to Ty decydujesz, jak chcesz zaplanować wydatki na studia!', 'akademiata');
?>

<section id="tuition_fees" class="section_tuition_fees section_tuition_fees--calculator">
    <div class="container">
        <h2 class="small_title primary_color mb-3"><?php echo esc_html($sub_title); ?></h2>
        <h3 class="title_section col-xl-10 p-0 mb-3"><?php echo esc_html($section_title); ?></h3>
    </div>

    <div class="container">
        <?php
        set_query_var('prices_calculator_fixed_key', $logical_sync_key);
        set_query_var('prices_calculator_fixed_lang', $page_lang ?: 'pl');
        set_query_var('prices_calculator_hide_more_btn', true);
        set_query_var('prices_calculator_layout', 'single-offer');
        locate_template('template-parts/prices/calculator.php', true, true);
        ?>
    </div>
</section>
