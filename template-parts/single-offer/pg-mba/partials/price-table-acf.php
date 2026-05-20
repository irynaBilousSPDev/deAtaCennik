<?php
/**
 * PG/MBA ACF price table tabs (not price CPT).
 *
 * @package akademiata
 */

$post_id = get_the_ID();
$columns = akademiata_pg_mba_price_columns();
$tabs = akademiata_pg_mba_get_price_tabs($post_id);

$title_first_column = get_field('title_first_column', $post_id) ?: __('1 RATA', 'akademiata');
$title_second_column = get_field('title_second_column', $post_id) ?: __('2 RATY', 'akademiata');
$title_third_column = get_field('title_third_column', $post_id) ?: __('4 RATY', 'akademiata');
$title_fourth_column = get_field('title_fourth_column', $post_id) ?: __('8 RAT', 'akademiata');
$title_fifth_column = get_field('title_fifth_column', $post_id) ?: __('6 RAT', 'akademiata');
$title_sixth_column = get_field('title_sixth_column', $post_id) ?: __('9 RAT', 'akademiata');
?>

<div class="tabs_container tabs_container--pg-mba pb-md-5 mb-md-5 mb-3">
    <?php if (!empty($tabs)) : ?>
        <div class="tabs-header">
            <?php $first = true; ?>
            <?php foreach ($tabs as $key => $tab) : ?>
                <div class="tab<?php echo $first ? ' active' : ''; ?>" data-tab="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($tab['label']); ?>
                </div>
                <?php $first = false; ?>
            <?php endforeach; ?>
        </div>

        <div class="tabs-content">
            <?php $first = true; ?>
            <?php foreach ($tabs as $key => $tab) :
                $available_columns = akademiata_pg_mba_get_available_columns($tab['data'], $columns);
                ?>
                <div id="<?php echo esc_attr($key); ?>" class="tab-content<?php echo $first ? ' active' : ''; ?>">
                    <table>
                        <thead>
                        <tr>
                            <?php if (!empty($available_columns[0])) : ?>
                                <th><span><?php echo esc_html($title_first_column); ?></span></th>
                            <?php endif; ?>
                            <?php if (!empty($available_columns[1])) : ?>
                                <th><span><?php echo esc_html($title_second_column); ?></span></th>
                            <?php endif; ?>
                            <?php if (!empty($available_columns[2])) : ?>
                                <th><span><?php echo esc_html($title_third_column); ?></span></th>
                            <?php endif; ?>
                            <?php if (!empty($available_columns[3])) : ?>
                                <th><span><?php echo esc_html($title_fourth_column); ?></span></th>
                            <?php endif; ?>
                            <?php if (!empty($available_columns[4])) : ?>
                                <th><span><?php echo esc_html($title_fifth_column); ?></span></th>
                            <?php endif; ?>
                            <?php if (!empty($available_columns[5])) : ?>
                                <th><span><?php echo esc_html($title_sixth_column); ?></span></th>
                            <?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php akademiata_pg_mba_render_price_rows($tab['data'], $columns, $available_columns, $key); ?>
                        </tbody>
                    </table>
                </div>
                <?php $first = false; ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('Brak dostępnych cenników w tym momencie.', 'akademiata'); ?></p>
    <?php endif; ?>
</div>
