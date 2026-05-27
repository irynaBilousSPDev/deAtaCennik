<?php
/**
 * Dynamically Render ACF Components.
 *
 * @param string $component_type The type of ACF component.
 * @param array $data The component's ACF data.
 */
function render_acf_component($component_type, $data = array()) {
    $component_path = get_template_directory() . "/acf-components/acf-{$component_type}.php";

    if (file_exists($component_path)) {
        include $component_path;
    } else {
        echo "<!-- Component {$component_type} not found: {$component_path} -->";
    }
}

/**
 * Automatically Load All ACF Components
 */
function load_acf_components() {
    $component_dir = get_template_directory() . "/acf-components/";

    if (!is_dir($component_dir)) {
        return;
    }

    foreach (glob($component_dir . "acf-*.php") as $file) {
        require_once $file;
    }
}

add_action('init', 'load_acf_components');

/**
 * Button: Component is reused in sliders/offers — not rendered on single posts.
 */
function akademiata_is_button_component_field_group($group) {
    $title = isset($group['title']) ? (string) $group['title'] : '';

    return ($title === 'Button: Component');
}

function akademiata_should_hide_button_component_on_post_screen() {
    if (!is_admin() || !function_exists('get_current_screen')) {
        return false;
    }

    $screen = get_current_screen();

    return ($screen && $screen->base === 'post' && $screen->post_type === 'post');
}

function akademiata_hide_button_component_on_posts($field_groups) {
    if (!akademiata_should_hide_button_component_on_post_screen()) {
        return $field_groups;
    }

    foreach ($field_groups as $index => $group) {
        if (akademiata_is_button_component_field_group($group)) {
            unset($field_groups[ $index ]);
        }
    }

    return array_values($field_groups);
}

add_filter('acf/load_field_groups', 'akademiata_hide_button_component_on_posts');
?>





