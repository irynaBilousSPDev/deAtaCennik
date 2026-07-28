<?php
$accordion = get_query_var('accordion');
$title = get_query_var('title');
$sub_title = get_query_var('sub_title');
$description = get_query_var('description');
?>


<div class="accordion_universal_header">
    <?php if (!empty($title)) : ?>
        <h2 class="title_section"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <?php if (!empty($sub_title)) : ?>
        <h3 class="small_title mb-3"><?php echo esc_html($sub_title); ?></h3>
    <?php endif; ?>
    <?php if (!empty($description)) : ?>
        <div class="accordion_description mb-5"><?php echo $description; ?></div>
    <?php endif; ?>
</div>
<?php if (!empty($accordion) && is_array($accordion)) : ?>
    <div class="accordion_universal">
        <?php foreach ($accordion as $item) :
            if (!is_array($item)) continue;

            $item_title = $item['accordion_title'] ?? 'Sekcja';
            $template_path = $item['accordion_content_template'] ?? '';
            $content_data = $item['accordion_contact_content'] ?? $item['accordion_default_content'] ?? null;

            $today = current_time('Ymd');
            $notice_badge = trim((string) ($item['accordion_notice_badge'] ?? ''));
            $notice_title = trim((string) ($item['accordion_notice_title'] ?? ''));
            $notice_content = $item['accordion_notice_content'] ?? '';
            $visible_from = !empty($item['accordion_notice_visible_from'])
                ? preg_replace('/[^0-9]/', '', (string) $item['accordion_notice_visible_from'])
                : '';
            $visible_until = !empty($item['accordion_notice_visible_until'])
                ? preg_replace('/[^0-9]/', '', (string) $item['accordion_notice_visible_until'])
                : '';

            // Fallback: old notice fields lived inside contact_repeater.
            if (
                $notice_badge === ''
                && $notice_title === ''
                && empty(trim(wp_strip_all_tags((string) $notice_content)))
                && !empty($content_data['contact_repeater'][0])
                && is_array($content_data['contact_repeater'][0])
            ) {
                $legacy = $content_data['contact_repeater'][0];
                $notice_badge = trim((string) ($legacy['accordion_notice_badge'] ?? ''));
                $notice_title = trim((string) ($legacy['accordion_notice_title'] ?? ''));
                $notice_content = $legacy['accordion_notice_content'] ?? '';
                $visible_from = !empty($legacy['accordion_notice_visible_from'])
                    ? preg_replace('/[^0-9]/', '', (string) $legacy['accordion_notice_visible_from'])
                    : '';
                $visible_until = !empty($legacy['accordion_notice_visible_until'])
                    ? preg_replace('/[^0-9]/', '', (string) $legacy['accordion_notice_visible_until'])
                    : '';
            }

            $is_notice_in_range = true;
            if ($visible_from !== '' && $today < $visible_from) {
                $is_notice_in_range = false;
            }
            if ($visible_until !== '' && $today > $visible_until) {
                $is_notice_in_range = false;
            }

            $show_badge = ($notice_badge !== '' && $is_notice_in_range);
            $has_notice_box = ($notice_title !== '' || !empty(trim(wp_strip_all_tags((string) $notice_content))));
            $show_notice_box = ($has_notice_box && $is_notice_in_range);
            ?>
            <div class="accordion_item">
                <div class="accordion_header">
                    <span class="accordion_title_wrap">
                        <span class="accordion_title small_title"><?php echo esc_html($item_title); ?></span>
                        <?php if ($show_badge) : ?>
                            <span class="accordion_notice_badge"><?php echo esc_html($notice_badge); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="accordion_arrow">
                <img src="<?php echo get_template_directory_uri(); ?>/static/img/arrow_down_closed_accordion.png"
                     alt="Arrow">
            </span>
                </div>
                <div class="accordion_content">
                    <?php if ($show_notice_box) : ?>
                        <div class="contact_notice_box">
                            <?php if ($notice_title !== '') : ?>
                                <h3 class="contact_notice_box__title"><?php echo esc_html($notice_title); ?></h3>
                            <?php endif; ?>

                            <?php if (!empty(trim(wp_strip_all_tags((string) $notice_content)))) : ?>
                                <div class="contact_notice_box__content">
                                    <?php echo wp_kses_post($notice_content); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $full_template_path = locate_template('template-parts/' . $template_path);
                    if ($template_path && $full_template_path) {
                        $content = $content_data;
                        include $full_template_path;
                    } else {
                        echo '<p>' . __('Brak treści sekcji', 'akademiata') . '</p>';
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
<?php endif; ?>
