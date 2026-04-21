<?php
/**
 * Use ACF Component: Button field to emulate FSE Button blocks.
 *
 * @param array $data ACF fields.
 * @return void
 */
function render_acf_button($data = array()) {

    // Ensure 'button' exists and is an array (ACF Group)
    $button = isset($data['button']) && is_array($data['button']) ? $data['button'] : [];

    // Check if required fields exist
    if (empty($button) || empty($button['button_text']) || empty($button['button_link'])) {
        echo "<!-- Missing Button Data -->";
        return;
    }

    // Sanitize Output
    $button_text  = esc_html($button['button_text']);
    $button_link  = esc_url($button['button_link']);
    $button_style = !empty($button['button_style']) ? esc_attr($button['button_style']) : 'primary';
    $button_target = !empty($button['button_target']) ? ' target=\"_blank\" rel=\"noopener noreferrer\"' : '';

    ?>
    <a href="<?php echo $button_link; ?>" class="button-<?php echo $button_style; ?>" <?php echo $button_target; ?>>
        <?php echo $button_text; ?>
    </a>
    <?php
}
?>
