<?php
/**
 * Mega menu walker — columns + CPT specialty sublists (mobile accordion).
 */
class WP_Mega_Menu_Walker extends Walker_Nav_Menu {
    private $depth0_open = false;

    /**
     * @param object $item
     * @return bool
     */
    private function is_offer_column($item) {
        $title = isset($item->title) ? mb_strtolower((string) $item->title) : '';
        if ($title === '') {
            return false;
        }

        return (strpos($title, 'oferta') !== false || strpos($title, 'offer') !== false);
    }

    /**
     * Map a depth-1 menu item to a CPT that should get an auto submenu.
     *
     * @param object $item
     * @return string|null bachelor|master|postgraduate|mba|courses
     */
    private function resolve_offer_submenu_post_type($item) {
        $offer_types = array('bachelor', 'master', 'postgraduate', 'mba', 'courses');

        if (!empty($item->type) && $item->type === 'post_type_archive' && !empty($item->object)) {
            $object = (string) $item->object;
            if (in_array($object, $offer_types, true)) {
                return $object;
            }
        }

        $url = isset($item->url) ? strtolower(untrailingslashit((string) $item->url)) : '';
        if ($url === '') {
            return null;
        }

        $patterns = array(
            'bachelor'     => array('studia-1-stopnia', '/bachelor'),
            'master'       => array('studia-2-stopnia', '/master'),
            'postgraduate' => array('studia-podyplomowe', 'postgraduate', 'post-graduate'),
            'mba'          => array('studia-mba', '/mba/', '/mba'),
            'courses'      => array('/kursy', '/courses'),
        );

        foreach ($patterns as $post_type => $needles) {
            foreach ($needles as $needle) {
                if (strpos($url, $needle) !== false) {
                    return $post_type;
                }
            }
        }

        return null;
    }

    /**
     * @param string $post_type
     * @return array<int, array{title:string,url:string,thumb:string}>
     */
    private function get_offer_submenu_links($post_type) {
        static $cache = array();

        if (isset($cache[ $post_type ])) {
            return $cache[ $post_type ];
        }

        $query = new WP_Query(
            array(
                'post_type'              => $post_type,
                'post_status'            => 'publish',
                'posts_per_page'         => 150,
                'orderby'                => 'title',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
                'lang'                   => apply_filters('wpml_current_language', null),
            )
        );

        $links = array();
        foreach ($query->posts as $post) {
            $thumb = '';
            $thumb_id = get_post_thumbnail_id($post);
            if ($thumb_id) {
                $thumb = (string) wp_get_attachment_image_url($thumb_id, 'thumbnail');
            }

            $links[] = array(
                'title' => get_the_title($post),
                'url'   => get_permalink($post),
                'thumb' => $thumb,
            );
        }
        wp_reset_postdata();

        $cache[ $post_type ] = $links;

        return $links;
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        if ($depth > 0) {
            $output .= '<ul class="mega-menu-sub">';
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        if ($depth > 0) {
            $output .= '</ul>';
        }
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        if ($depth === 0) {
            $is_offer = $this->is_offer_column($item);
            $classes  = 'mega-column' . ($is_offer ? ' mega-column--offer' : '');
            $desc     = !empty($item->description) ? trim(wp_strip_all_tags((string) $item->description)) : '';

            $output .= '<div class="' . esc_attr($classes) . '">';
            $output .= '<button type="button" class="mega_menu_title" aria-expanded="false">';
            $output .= '<span class="mega_menu_title-main">';
            $output .= '<span class="mega_menu_title-text">' . esc_html($item->title) . '</span>';
            if ($desc !== '') {
                $output .= '<span class="mega_menu_title-desc">' . esc_html($desc) . '</span>';
            }
            $output .= '</span>';
            $output .= '<span class="mega_menu_title-arr" aria-hidden="true"></span>';
            $output .= '</button>';
            $output .= '<ul class="mega-column__list">';
            $this->depth0_open = true;
            return;
        }

        $attributes = '';
        $atts       = array(
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'href'   => !empty($item->url) ? $item->url : '',
        );

        foreach ($atts as $attr => $value) {
            if ($value !== '' && $value !== null) {
                $attributes .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
        }

        $post_type = $this->resolve_offer_submenu_post_type($item);
        $subs      = $post_type ? $this->get_offer_submenu_links($post_type) : array();
        $has_sub   = $subs !== array();

        $li_class = $has_sub ? ' class="mega-menu-item has-mega-sub"' : ' class="mega-menu-item"';
        $output  .= '<li' . $li_class . '>';

        if ($has_sub) {
            $output .= '<div class="mega-menu-item__row">';
            $output .= '<a' . $attributes . '>' . esc_html($item->title) . '</a>';
            $output .= '<button type="button" class="mega-menu-sub-toggle" aria-expanded="false" aria-label="'
                . esc_attr(sprintf(__('Pokaż listę: %s', 'akademiata'), $item->title))
                . '"><span aria-hidden="true"></span></button>';
            $output .= '</div>';
            $output .= '<ul class="mega-menu-sub" hidden>';
            foreach ($subs as $sub) {
                $output .= '<li>';
                $output .= '<a class="mega-menu-sub__link" href="' . esc_url($sub['url']) . '">';
                $output .= '<span class="mega-menu-sub__thumb' . (empty($sub['thumb']) ? ' is-empty' : '') . '">';
                if (!empty($sub['thumb'])) {
                    $output .= '<img src="' . esc_url($sub['thumb']) . '" alt="" loading="lazy" decoding="async" width="48" height="48">';
                }
                $output .= '</span>';
                $output .= '<span class="mega-menu-sub__label">' . esc_html($sub['title']) . '</span>';
                $output .= '</a>';
                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<a' . $attributes . '>' . esc_html($item->title) . '</a>';
        }
    }

    public function end_el(&$output, $item, $depth = 0, $args = null) {
        if ($depth === 0 && $this->depth0_open) {
            $output .= '</ul></div>';
            $this->depth0_open = false;
            return;
        }

        if ($depth > 0) {
            $output .= '</li>';
        }
    }
}
