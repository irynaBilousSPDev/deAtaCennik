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
?>





