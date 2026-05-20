<?php
/**
 * PG/MBA one-off fees (ACF payments repeater on the offer post).
 *
 * No promotion checkbox — optional description per row.
 * (Bachelor/Master used promotion on price CPT; that path was removed.)
 *
 * Expects query var: tuition_payments (array)
 *
 * @package akademiata
 */

$payments = get_query_var('tuition_payments', []);

if (empty($payments) || !is_array($payments)) {
    return;
}
?>

<?php foreach ($payments as $key => $item) :
    $title = $item['title'] ?? '';
    $price = $item['price'] ?? '';
    $currency = $item['currency'] ?? '';
    $description = $item['description'] ?? '';
    ?>
    <div class="row payments_item mb-5">
        <div class="small_title d-flex align-items-center mr-5">
            <?php
            if ($title) {
                echo esc_html($title);
            } elseif ((int) $key === 0) {
                echo wp_kses_post(__('Opłata rekrutacyjna', 'akademiata') . '<br>' . __('(opłata jednorazowa)', 'akademiata'));
            } elseif ((int) $key === 1) {
                echo wp_kses_post(__('Wpisowe', 'akademiata') . '<br>' . __('(opłata jednorazowa)', 'akademiata'));
            }
            ?>
        </div>

        <?php if ($price !== '' && $price !== null) : ?>
            <div class="d-flex align-items-center mr-5">
                <div class="normal_price">
                    <?php echo esc_html($price . ' ' . $currency); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($description)) : ?>
            <div class="d-flex align-items-center mr-5">
                <div class="description">
                    <?php echo wp_kses_post($description); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
