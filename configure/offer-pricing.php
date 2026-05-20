<?php
/**
 * ACF-based tuition pricing helpers (PG/MBA). Not tied to price/discount CPTs.
 *
 * @package akademiata
 */

/**
 * Column map for PG/MBA ACF price tables (6 installment columns).
 *
 * @return array<int, array{key: string, normal: string, promo: string, flag: string}>
 */
function akademiata_pg_mba_price_columns() {
    return [
        ['key' => 'col_12_rat', 'normal' => 'normal_price', 'promo' => 'promotion_price', 'flag' => 'add_promotion'],
        ['key' => 'col_semester', 'normal' => 'semester_normal_price', 'promo' => 'semester_promotion_price', 'flag' => 'add_promotion_semester'],
        ['key' => 'col_year', 'normal' => 'year_normal_price', 'promo' => 'year_promotion_price', 'flag' => 'add_promotion_year'],
        ['key' => 'col_8_rat', 'normal' => 'rat8_normal_price', 'promo' => 'rat8_promotion_price', 'flag' => 'add_promotion_rat8'],
        ['key' => 'col_6_rat', 'normal' => 'rat6_normal_price', 'promo' => 'rat6_promotion_price', 'flag' => 'add_promotion_rat6'],
        ['key' => 'col_9_rat', 'normal' => 'rat9_normal_price', 'promo' => 'rat9_promotion_price', 'flag' => 'add_promotion_rat9'],
    ];
}

/**
 * @param mixed $price_data
 */
function akademiata_pg_mba_has_price_data($price_data) {
    if (!is_array($price_data) || $price_data === []) {
        return false;
    }

    foreach ($price_data as $year_data) {
        if (!is_array($year_data)) {
            continue;
        }
        foreach (akademiata_pg_mba_price_columns() as $col) {
            $col_data = $year_data[$col['key']] ?? [];
            if (!empty($col_data[$col['normal']])) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @param array<int, array{key: string, normal: string, promo: string, flag: string}> $columns
 * @return array<int, bool>
 */
function akademiata_pg_mba_get_available_columns($price_data, $columns) {
    $available = [];

    foreach ($columns as $col_index => $col) {
        foreach ($price_data as $year_data) {
            if (!is_array($year_data)) {
                continue;
            }
            $col_data = $year_data[$col['key']] ?? [];
            if (!empty($col_data[$col['normal']])) {
                $available[$col_index] = true;
                break;
            }
        }
    }

    return $available;
}

/**
 * @param mixed  $price_data
 * @param array  $columns
 * @param array  $available_columns
 * @param string $tab_key full_time|part_time
 */
function akademiata_pg_mba_render_price_rows($price_data, $columns, $available_columns, $tab_key) {
    if (!is_array($price_data)) {
        return;
    }

    $currency = ($tab_key === 'part_time') ? '€' : 'ZŁ';

    foreach ($price_data as $row) {
        if (!is_array($row)) {
            continue;
        }
        ?>
        <tr>
            <?php foreach ($columns as $col_index => $col) :
                if (empty($available_columns[$col_index])) {
                    continue;
                }
                $col_data = $row[$col['key']] ?? [];
                $has_promo = !empty($col_data[$col['flag']])
                    && is_array($col_data[$col['flag']])
                    && in_array('promotion', $col_data[$col['flag']], true);
                ?>
                <td>
                    <div class="<?php echo $has_promo ? 'old_price' : ''; ?>">
                        <?php echo esc_html(($col_data[$col['normal']] ?? '') . ' ' . $currency); ?>
                    </div>
                    <?php if ($has_promo && !empty($col_data[$col['promo']])) : ?>
                        <div class="new_price">
                            <?php echo esc_html($col_data[$col['promo']] . ' ' . $currency); ?>
                        </div>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php
    }
}

/**
 * "From" price for PG/MBA offer header (col_8_rat on first row).
 */
function akademiata_pg_mba_get_teaser_price_text($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $full_time = get_field('full_time', $post_id);
    $part_time = get_field('part_time', $post_id);

    $source_data = [];
    $using_part_time = false;

    if (is_array($full_time) && $full_time !== []) {
        $source_data = $full_time;
    } elseif (is_array($part_time) && $part_time !== []) {
        $source_data = $part_time;
        $using_part_time = true;
    } else {
        return '';
    }

    $currency = $using_part_time ? '€/month' : 'zł';
    $row = $source_data[0] ?? [];
    $col_data = is_array($row['col_8_rat'] ?? null) ? $row['col_8_rat'] : [];

    if ($col_data === []) {
        return '';
    }

    $add_promotion = $col_data['add_promotion_rat8'] ?? [];
    $has_promo = (is_array($add_promotion) && in_array('promotion', $add_promotion, true))
        || $add_promotion === 'promotion';

    if ($has_promo && !empty($col_data['rat8_promotion_price'])) {
        return esc_html($col_data['rat8_promotion_price']) . ' ' . $currency;
    }

    if (!empty($col_data['rat8_normal_price'])) {
        return esc_html($col_data['rat8_normal_price']) . ' ' . $currency;
    }

    return '';
}

/**
 * Build tab config for PG/MBA ACF tables.
 *
 * @return array<string, array{label: string, data: mixed}>
 */
function akademiata_pg_mba_get_price_tabs($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $full_time = get_field('full_time', $post_id) ?: [];
    $part_time = get_field('part_time', $post_id) ?: [];

    $label_full_time = trim((string) get_field('tab_label_full_time', $post_id));
    $label_part_time = trim((string) get_field('tab_label_part_time', $post_id));

    $tabs = [
        'full_time' => [
            'label' => $label_full_time !== '' ? $label_full_time : __('STUDIA W JĘZYKU POLSKIM', 'akademiata'),
            'data'  => $full_time,
        ],
        'part_time' => [
            'label' => $label_part_time !== '' ? $label_part_time : __('STUDIA W JĘZYKU ANGIELSKIM', 'akademiata'),
            'data'  => $part_time,
        ],
    ];

    return array_filter(
        $tabs,
        static function ($tab) {
            return akademiata_pg_mba_has_price_data($tab['data']);
        }
    );
}
