<?php
/**
 * Mega menu walker — columns + CPT specialty sublists (mobile accordion).
 * On mobile, Oferta is split into Warszawa / Wrocław (online offers in both).
 */
class WP_Mega_Menu_Walker extends Walker_Nav_Menu {
    private $depth0_open = false;

    /** @var bool */
    private $capturing_offer = false;

    /** @var object|null */
    private $offer_column_item = null;

    /** @var array<int, object> */
    private $offer_children = array();

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
     * City taxonomy for a CPT.
     *
     * @param string $post_type
     * @return string city|city_pg_mba
     */
    private function city_taxonomy_for_post_type($post_type) {
        return in_array($post_type, array('bachelor', 'master'), true) ? 'city' : 'city_pg_mba';
    }

    /**
     * Whether a specialty belongs in a city menu column.
     * Online (PG/MBA/courses) appears in both cities for now.
     * Bachelor/master without city → Warszawa.
     *
     * @param int    $post_id
     * @param string $post_type
     * @param string $city_slug warszawa|wroclaw
     * @return bool
     */
    private function post_matches_city($post_id, $post_type, $city_slug) {
        $tax   = $this->city_taxonomy_for_post_type($post_type);
        $terms = wp_get_post_terms($post_id, $tax, array('fields' => 'slugs'));
        if (is_wp_error($terms) || !is_array($terms)) {
            $terms = array();
        }

        if ($tax === 'city_pg_mba') {
            if (in_array('online', $terms, true)) {
                return true;
            }
            return in_array($city_slug, $terms, true);
        }

        if ($terms === array()) {
            return $city_slug === 'warszawa';
        }

        return in_array($city_slug, $terms, true);
    }

    /**
     * @param string      $post_type
     * @param string|null $city_slug warszawa|wroclaw|null (all)
     * @return array<int, array{title:string,url:string,thumb:string,id:int}>
     */
    private function get_offer_submenu_links($post_type, $city_slug = null) {
        static $cache = array();

        $cache_key = $post_type . '|' . ($city_slug ? $city_slug : 'all');
        if (isset($cache[ $cache_key ])) {
            return $cache[ $cache_key ];
        }

        // Base list once per CPT, then filter by city in PHP.
        $base_key = $post_type . '|all';
        if (!isset($cache[ $base_key ])) {
            $query = new WP_Query(
                array(
                    'post_type'              => $post_type,
                    'post_status'            => 'publish',
                    'posts_per_page'         => 150,
                    'orderby'                => 'title',
                    'order'                  => 'ASC',
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => true,
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
                    'id'    => (int) $post->ID,
                    'title' => get_the_title($post),
                    'url'   => get_permalink($post),
                    'thumb' => $thumb,
                );
            }
            wp_reset_postdata();
            $cache[ $base_key ] = $links;
        }

        $links = $cache[ $base_key ];
        if ($city_slug) {
            $filtered = array();
            foreach ($links as $link) {
                if ($this->post_matches_city($link['id'], $post_type, $city_slug)) {
                    $filtered[] = $link;
                }
            }
            $links = $filtered;
        }

        $cache[ $cache_key ] = $links;

        return $links;
    }

    /**
     * @param object $item
     * @return string
     */
    private function link_attributes($item) {
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

        return $attributes;
    }

    /**
     * One depth-1 row (+ optional CPT specialty list).
     *
     * @param object      $item
     * @param string|null $city_slug
     * @return string
     */
    private function render_offer_child_item($item, $city_slug = null) {
        $attributes = $this->link_attributes($item);
        $post_type  = $this->resolve_offer_submenu_post_type($item);
        $subs       = $post_type ? $this->get_offer_submenu_links($post_type, $city_slug) : array();
        $has_sub    = $subs !== array();

        $html = '<li class="mega-menu-item' . ($has_sub ? ' has-mega-sub' : '') . '">';

        if ($has_sub) {
            $html .= '<div class="mega-menu-item__row">';
            $html .= '<a' . $attributes . '>' . esc_html($item->title) . '</a>';
            $html .= '<button type="button" class="mega-menu-sub-toggle" aria-expanded="false" aria-label="'
                . esc_attr(sprintf(__('Pokaż listę: %s', 'akademiata'), $item->title))
                . '"><span aria-hidden="true"></span></button>';
            $html .= '</div>';
            $html .= '<ul class="mega-menu-sub" hidden>';
            foreach ($subs as $sub) {
                $html .= '<li>';
                $html .= '<a class="mega-menu-sub__link" href="' . esc_url($sub['url']) . '">';
                $html .= '<span class="mega-menu-sub__thumb' . (empty($sub['thumb']) ? ' is-empty' : '') . '">';
                if (!empty($sub['thumb'])) {
                    $html .= '<img src="' . esc_url($sub['thumb']) . '" alt="" loading="lazy" decoding="async" width="48" height="48">';
                }
                $html .= '</span>';
                $html .= '<span class="mega-menu-sub__label">' . esc_html($sub['title']) . '</span>';
                $html .= '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        } else {
            // CPT parent with zero matches for this city — still show archive link, no toggle.
            $html .= '<a' . $attributes . '>' . esc_html($item->title) . '</a>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * @param string      $classes
     * @param string      $title
     * @param string      $desc
     * @param array       $children
     * @param string|null $city_slug
     * @return string
     */
    private function render_offer_column($classes, $title, $desc, $children, $city_slug = null) {
        $html  = '<div class="' . esc_attr($classes) . '">';
        $html .= '<button type="button" class="mega_menu_title" aria-expanded="false">';
        $html .= '<span class="mega_menu_title-main">';
        $html .= '<span class="mega_menu_title-text">' . esc_html($title) . '</span>';
        if ($desc !== '') {
            $html .= '<span class="mega_menu_title-desc">' . esc_html($desc) . '</span>';
        }
        $html .= '</span>';
        $html .= '<span class="mega_menu_title-arr" aria-hidden="true"></span>';
        $html .= '</button>';
        $html .= '<ul class="mega-column__list">';
        foreach ($children as $child) {
            $html .= $this->render_offer_child_item($child, $city_slug);
        }
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Desktop: one Oferta. Mobile: Oferta Warszawa + Oferta Wrocław.
     *
     * @param object             $column_item
     * @param array<int, object> $children
     * @return string
     */
    private function render_offer_columns($column_item, $children) {
        $desc = !empty($column_item->description) ? trim(wp_strip_all_tags((string) $column_item->description)) : '';
        $base = (string) $column_item->title;

        $html  = $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-desktop',
            $base,
            $desc,
            $children,
            null
        );

        $html .= $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-city',
            sprintf(
                /* translators: %s: city name */
                __('%s Warszawa', 'akademiata'),
                $base
            ),
            $desc,
            $children,
            'warszawa'
        );

        $html .= $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-city',
            sprintf(
                /* translators: %s: city name */
                __('%s Wrocław', 'akademiata'),
                $base
            ),
            $desc,
            $children,
            'wroclaw'
        );

        return $html;
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        if ($this->capturing_offer) {
            return;
        }
        if ($depth > 0) {
            $output .= '<ul class="mega-menu-sub">';
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        if ($this->capturing_offer) {
            return;
        }
        if ($depth > 0) {
            $output .= '</ul>';
        }
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        if ($depth === 0) {
            if ($this->is_offer_column($item)) {
                $this->capturing_offer   = true;
                $this->offer_column_item = $item;
                $this->offer_children    = array();
                $this->depth0_open       = true;
                return;
            }

            $desc = !empty($item->description) ? trim(wp_strip_all_tags((string) $item->description)) : '';

            $output .= '<div class="mega-column">';
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

        if ($this->capturing_offer) {
            $this->offer_children[] = $item;
            return;
        }

        $attributes = $this->link_attributes($item);
        $post_type  = $this->resolve_offer_submenu_post_type($item);
        $subs       = $post_type ? $this->get_offer_submenu_links($post_type) : array();
        $has_sub    = $subs !== array();

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
            if ($this->capturing_offer && $this->offer_column_item) {
                $output .= $this->render_offer_columns($this->offer_column_item, $this->offer_children);
                $this->capturing_offer   = false;
                $this->offer_column_item = null;
                $this->offer_children    = array();
                $this->depth0_open       = false;
                return;
            }

            $output .= '</ul></div>';
            $this->depth0_open = false;
            return;
        }

        if ($depth > 0 && !$this->capturing_offer) {
            $output .= '</li>';
        }
    }
}
