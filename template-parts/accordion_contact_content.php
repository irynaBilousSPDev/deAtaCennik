<?php
$contact_repeater = $content['contact_repeater'] ?? [];

if (empty($contact_repeater) || !is_array($contact_repeater)) {
    return;
}
?>

<?php foreach ($contact_repeater as $block): ?>
    <?php
    $notice_title = trim((string) ($block['accordion_notice_title'] ?? ''));
    $notice_content = $block['accordion_notice_content'] ?? '';
    $visible_from = !empty($block['accordion_notice_visible_from'])
        ? preg_replace('/[^0-9]/', '', (string) $block['accordion_notice_visible_from'])
        : '';
    $visible_until = !empty($block['accordion_notice_visible_until'])
        ? preg_replace('/[^0-9]/', '', (string) $block['accordion_notice_visible_until'])
        : '';
    $today = current_time('Ymd');
    $has_notice_content = ($notice_title !== '' || !empty(trim(wp_strip_all_tags((string) $notice_content))));
    $is_notice_visible = $has_notice_content;

    if ($is_notice_visible && $visible_from !== '' && $today < $visible_from) {
        $is_notice_visible = false;
    }

    if ($is_notice_visible && $visible_until !== '' && $today > $visible_until) {
        $is_notice_visible = false;
    }
    ?>
    <div class="contact_content">
        <?php if ($is_notice_visible): ?>
            <div class="contact_notice_box">
                <?php if ($notice_title !== ''): ?>
                    <h3 class="contact_notice_box__title"><?php echo esc_html($notice_title); ?></h3>
                <?php endif; ?>

                <?php if (!empty(trim(wp_strip_all_tags((string) $notice_content)))): ?>
                    <div class="contact_notice_box__content">
                        <?php echo wp_kses_post($notice_content); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="contact_top mb-5">
            <div class="contact_columns">
                <div class="contact_info">
                    <?php
                    $photo = $block['photo'] ?? null; // ACF image array or URL string
                    $photo_url = '';
                    $photo_alt = '';

                    if (is_array($photo) && !empty($photo['url'])) {
                        $photo_url = esc_url($photo['url']);
                        $photo_alt = esc_attr($photo['alt'] ?? ($block['title_name'] ?? ''));
                    }

                    if (!empty($photo_url)) : ?>
                        <div class="photo">
                            <img src="<?php echo $photo_url; ?>" alt="<?php echo $photo_alt; ?>" loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="content">
                        <?php if (!empty($block['title_position'])): ?>
                            <h3 class="title_position"><?php echo esc_html($block['title_position']); ?></h3>
                        <?php endif; ?>

                        <?php if (!empty($block['title_name'])): ?>
                            <h3 class="small_title title_position_name">
                                <strong><?php echo esc_html($block['title_name']); ?></strong></h3>
                        <?php endif; ?>

                        <?php if (!empty($block['address'])): ?>
                            <div class="address"><?php echo esc_html($block['address']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($block['phones'])): ?>
                            <div class="contact_row">
                                <div class="icon">
                                    <img src="<?php echo get_template_directory_uri(); ?>/static/img/icon_contact_page_ATA_phone.png"
                                         alt="Phone">
                                </div>
                                <div class="text">
                                    <?php foreach ($block['phones'] as $phone): ?>
                                        <?php
                                        $raw_phone = $phone['phone'] ?? '';
                                        $tel_href = preg_replace('/[^0-9+]/', '', $raw_phone);
                                        ?>
                                        <a href="tel:<?php echo esc_attr($tel_href); ?>">
                                            <?php echo esc_html($raw_phone); ?>
                                        </a> <br>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($block['emails'])): ?>
                            <div class="contact_row">
                                <div class="icon">
                                    <img src="<?php echo get_template_directory_uri(); ?>/static/img/icon_contact_page_ATA_mail.png"
                                         alt="Email">
                                </div>
                                <div class="text">
                                    <?php foreach ($block['emails'] as $email): ?>
                                        <a href="mailto:<?php echo esc_html($email['email']); ?>">
                                            <?php echo esc_html($email['email']); ?>
                                        </a> <br>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($block['work_hours'])): ?>
                    <div class="contact_hours">
                        <div class="contact_hours_wrapper">
                            <h3 class="small_title"><?php _e('Godziny pracy:', 'akademiata'); ?></h3>

                            <ul>
                                <?php
                                // Fixed ordered days in Polish
                                $fixed_days = [
                                    'Poniedziałek',
                                    'Wtorek',
                                    'Środa',
                                    'Czwartek',
                                    'Piątek',
                                    'Sobota',
                                    'Niedziela'
                                ];

                                // Used to track which fixed day to assign next
                                $day_pointer = 0;

                                if (!empty($block['work_hours']) && is_array($block['work_hours'])) {
                                    foreach ($block['work_hours'] as $row) {
                                        $hours = isset($row['hours']) ? trim($row['hours']) : '';
                                        $custom_day = isset($row['day']) ? trim($row['day']) : '';

                                        // Skip rows without hours
                                        if (empty($hours)) continue;

                                        // Determine day: ACF value or next from fixed list
                                        if (!empty($custom_day)) {
                                            $day_label = __($custom_day, 'akademiata');
                                        } else {
                                            $day_label = isset($fixed_days[$day_pointer]) ? __($fixed_days[$day_pointer], 'akademiata') : '';
                                        }

                                        // Output
                                        if (!empty($day_label)) {
                                            echo '<li>' . esc_html($day_label) . ': <strong>' . esc_html($hours) . '</strong></li>';
                                        }

                                        $day_pointer++;
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <div class="contact_purpose mb-5">

            <div class="contact_purpose_cols">
                <div class="col col--left">
                    <?php if (!empty($block['additional_phones'])): ?>
                        <h3 class="small_title mb-3">
                            <?php _e('Dodatkowe nr', 'akademiata'); ?>
                        </h3>
                        <div class="contact_row">
                            <div class="icon">
                                <img src="<?php echo get_template_directory_uri(); ?>/static/img/icon_contact_page_ATA_phone.png"
                                     alt="Phone">
                            </div>
                            <div class="text">
                                <?php foreach ($block['additional_phones'] as $phone): ?>
                                    <?php
                                    $raw_phone = $phone['phone'] ?? '';
                                    $tel_href = preg_replace('/[^0-9+]/', '', $raw_phone);
                                    ?>
                                    <a href="tel:<?php echo esc_attr($tel_href); ?>">
                                        <?php echo esc_html($raw_phone); ?>
                                    </a> <br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($block['additional_description'])): ?>
                        <div class="text">
                            <?php echo $block['additional_description'] ?>
                        </div>
                    <?php endif; ?>
                </div>


                <?php if (!empty($block['additional_description_right'])): ?>
                    <div class="col col--right">
                        <?php echo wp_kses_post($block['additional_description_right']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($block['contact_purpose'])): ?>
            <?php
            $left  = $block['contact_purpose']['contact_purpose_left'] ?? '';
            $right = $block['contact_purpose']['contact_purpose_right'] ?? '';
            ?>
            <?php if ($left || $right): ?>
                <div class="contact_purpose mb-5">
                    <h3 class="small_title primary_color mb-3">
                        <?php _e('Co załatwisz w tym miejscu?', 'akademiata'); ?>
                    </h3>
                    <div class="contact_purpose_cols">
                        <?php if ($left): ?>
                            <div class="col col--left">
                                <?php echo wp_kses_post($left); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($right): ?>
                            <div class="col col--right">
                                <?php echo wp_kses_post($right); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>


    </div>
<?php endforeach; ?>
