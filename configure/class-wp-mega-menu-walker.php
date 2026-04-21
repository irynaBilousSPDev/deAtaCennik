<?php
class WP_Mega_Menu_Walker extends Walker_Nav_Menu {
    private $depth0_open = false;

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        // Only open the <ul> if it's not top-level
        if ($depth > 0) {
            $output .= '<ul>';
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        // Only close the <ul> if it's not top-level
        if ($depth > 0) {
            $output .= '</ul>';
        }
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        if ($depth === 0) {
            // Start mega column and title
            $output .= '<div class="mega-column">';
            $output .= '<div class="mega_menu_title">' . esc_html($item->title) . '</div>';
            $output .= '<ul>';
            $this->depth0_open = true;
        } else {
            // Build <a> with all relevant attributes
            $attributes = '';

            $atts = [
                'title'  => !empty($item->attr_title) ? $item->attr_title : '',
                'target' => !empty($item->target) ? $item->target : '',
                'rel'    => !empty($item->xfn) ? $item->xfn : '',
                'href'   => !empty($item->url) ? $item->url : '',
            ];

            foreach ($atts as $attr => $value) {
                if (!empty($value)) {
                    $attributes .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
                }
            }

            $output .= '<li><a' . $attributes . '>' . esc_html($item->title) . '</a></li>';
        }
    }


    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        if ($depth === 0 && $this->depth0_open) {
            $output .= '</ul></div>';
            $this->depth0_open = false;
        }
        // No closing needed for depth > 0
    }
}
