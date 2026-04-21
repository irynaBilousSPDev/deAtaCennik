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
            ?>
            <div class="accordion_item">
                <div class="accordion_header">
                    <span class="accordion_title small_title"><?php echo esc_html($item_title); ?></span>
                    <span class="accordion_arrow">
                <img src="<?php echo get_template_directory_uri(); ?>/static/img/arrow_down_closed_accordion.png"
                     alt="Arrow">
            </span>
                </div>
                <div class="accordion_content">
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
