<?php
/**
 * Mega menu walker — columns + CPT specialty sublists (mobile accordion).
 * On mobile, Oferta is split into Warszawa / Wrocław / Online.
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
     * @return string
     */
    private function column_description($item) {
        $title = isset($item->title) ? mb_strtolower((string) $item->title) : '';
        if ($title !== '' && (strpos($title, 'kandydat') !== false || strpos($title, 'candidate') !== false)) {
            return '';
        }

        $desc = !empty($item->description) ? trim(wp_strip_all_tags((string) $item->description)) : '';
        return $desc !== '' ? $desc : '';
    }

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
        $offer_types = array('bachelor', 'master', 'postgraduate', 'mba', 'courses', 'exams');

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
            'exams'        => array('/egzaminy', '/exams'),
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
     * @return string city|city_pg_mba|exam_city
     */
    private function city_taxonomy_for_post_type($post_type) {
        if (in_array($post_type, array('bachelor', 'master'), true)) {
            return 'city';
        }
        if ($post_type === 'exams') {
            return 'exam_city';
        }
        return 'city_pg_mba';
    }

    /**
     * Canonical location key from slug or label (WPML-safe).
     *
     * @param string $text
     * @return string warszawa|wroclaw|online|''
     */
    private function normalize_location_key($text) {
        $s = mb_strtolower(trim((string) $text));
        if ($s === '') {
            return '';
        }
        if (function_exists('remove_accents')) {
            $s = remove_accents($s);
        }
        $s = str_replace(array(' ', '_'), '-', $s);

        if (preg_match('/wroclaw|breslau/', $s)) {
            return 'wroclaw';
        }
        if (preg_match('/warszaw|warsaw/', $s)) {
            return 'warszawa';
        }
        if (preg_match('/online|zdaln|remote/', $s)) {
            return 'online';
        }

        return '';
    }

    /**
     * Location keys assigned to a post for a city-like taxonomy.
     *
     * @param int    $post_id
     * @param string $taxonomy
     * @return string[] warszawa|wroclaw|online
     */
    private function post_location_keys($post_id, $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'all'));
        if (is_wp_error($terms) || !is_array($terms) || $terms === array()) {
            return array();
        }

        $keys = array();
        foreach ($terms as $term) {
            $key = $this->normalize_location_key($term->slug);
            if ($key === '') {
                $key = $this->normalize_location_key($term->name);
            }
            if ($key !== '') {
                $keys[ $key ] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * Study mode flags for bachelor/master (taxonomy `mode`).
     * “Stacjonarne sobotnio-niedzielne” = campus (full), not Online.
     * Online I/II = explicitly niestacjonarne / zaoczne only.
     *
     * @param int $post_id
     * @return array{0:bool,1:bool} [has_full_time, has_part_time]
     */
    private function bachelor_master_mode_flags($post_id) {
        $has_full = false;
        $has_part = false;
        $terms    = wp_get_post_terms($post_id, 'mode', array('fields' => 'all'));
        if (is_wp_error($terms) || !is_array($terms)) {
            return array(false, false);
        }

        foreach ($terms as $term) {
            $hay = mb_strtolower((string) $term->slug . ' ' . (string) $term->name);
            if (function_exists('remove_accents')) {
                $hay = remove_accents($hay);
            }

            // Niestacjonarne BEFORE stacjonarne — “niestacjonarne” contains “stacjonarn”.
            if (
                strpos($hay, 'niestacjonarn') !== false
                || strpos($hay, 'zaoczn') !== false
                || strpos($hay, 'part-time') !== false
                || strpos($hay, 'part_time') !== false
            ) {
                $has_part = true;
                continue;
            }

            if (
                strpos($hay, 'stacjonarn') !== false
                || strpos($hay, 'dzienn') !== false
                || strpos($hay, 'full-time') !== false
                || strpos($hay, 'full_time') !== false
            ) {
                $has_full = true;
            }
        }

        return array($has_full, $has_part);
    }

    /**
     * Whether a specialty belongs in a mobile Oferta column.
     * Same groups everywhere (I/II, PG, MBA, courses, exams):
     * Warszawa / Wrocław = that campus only; Online = online / niestacjonarne.
     *
     * @param int    $post_id
     * @param string $post_type
     * @param string $city_slug warszawa|wroclaw|online
     * @return bool
     */
    private function post_matches_city($post_id, $post_type, $city_slug) {
        $want = $this->normalize_location_key($city_slug);
        if ($want === '') {
            return false;
        }

        if (in_array($post_type, array('bachelor', 'master'), true)) {
            if ($want === 'online') {
                // Online column: only niestacjonarne I/II.
                list($has_full, $has_part) = $this->bachelor_master_mode_flags($post_id);
                unset($has_full);
                return $has_part;
            }

            // City columns: all I/II for that city (any mode).
            $keys = $this->post_location_keys($post_id, 'city');
            if ($keys === array()) {
                return $want === 'warszawa';
            }
            return in_array($want, $keys, true);
        }

        if ($post_type === 'exams') {
            if ($want === 'online') {
                return false;
            }
            return in_array($want, $this->post_location_keys($post_id, 'exam_city'), true);
        }

        // PG / MBA / courses — city_pg_mba (warszawa | wroclaw | online).
        $keys      = $this->post_location_keys($post_id, $this->city_taxonomy_for_post_type($post_type));
        $is_online = in_array('online', $keys, true);

        if ($want === 'online') {
            return $is_online;
        }

        if ($is_online) {
            return false;
        }

        return in_array($want, $keys, true);
    }

    /**
     * Stable menu order for Oferta groups.
     *
     * @param array<int, object> $children
     * @return array<int, object>
     */
    private function sort_offer_children($children) {
        $order = array(
            'bachelor'     => 10,
            'master'       => 20,
            'postgraduate' => 30,
            'mba'          => 40,
            'courses'      => 50,
            'exams'        => 60,
        );

        $indexed = array();
        foreach (array_values($children) as $i => $child) {
            $pt = $this->resolve_offer_submenu_post_type($child);
            $indexed[] = array(
                'item'  => $child,
                'order' => ($pt && isset($order[ $pt ])) ? $order[ $pt ] : 100,
                'title' => isset($child->title) ? mb_strtolower((string) $child->title) : '',
                'idx'   => $i,
            );
        }

        usort(
            $indexed,
            static function ($a, $b) {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] - $b['order'];
                }
                $cmp = strcasecmp($a['title'], $b['title']);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return $a['idx'] - $b['idx'];
            }
        );

        return array_map(
            static function ($row) {
                return $row['item'];
            },
            $indexed
        );
    }

    /**
     * @param string      $post_type
     * @param string|null $city_slug warszawa|wroclaw|online|null (all)
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

        usort(
            $links,
            static function ($a, $b) {
                return strcasecmp((string) $a['title'], (string) $b['title']);
            }
        );

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
        $post_type = $this->resolve_offer_submenu_post_type($item);

        $attributes = $this->link_attributes($item);
        $subs       = $post_type ? $this->get_offer_submenu_links($post_type, $city_slug) : array();
        $has_sub    = $subs !== array();
        $title      = (string) $item->title;

        // Filtered columns: skip CPT parents with no matching specialties.
        if (
            $post_type
            && !$has_sub
            && in_array($city_slug, array('warszawa', 'wroclaw', 'online'), true)
        ) {
            return '';
        }

        $html = '<li class="mega-menu-item' . ($has_sub ? ' has-mega-sub' : '') . '">';

        if ($has_sub) {
            $html .= '<div class="mega-menu-item__row">';
            $html .= '<a' . $attributes . '>' . esc_html($title) . '</a>';
            $html .= '<button type="button" class="mega-menu-sub-toggle" aria-expanded="false" aria-label="'
                . esc_attr(sprintf(__('Pokaż listę: %s', 'akademiata'), $title))
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
            $html .= '<a' . $attributes . '>' . esc_html($title) . '</a>';
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
        foreach ($this->sort_offer_children($children) as $child) {
            $html .= $this->render_offer_child_item($child, $city_slug);
        }
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Desktop: one Oferta. Mobile: Oferta Warszawa + Wrocław + Online.
     *
     * @param object             $column_item
     * @param array<int, object> $children
     * @return string
     */
    private function render_offer_columns($column_item, $children) {
        $base = (string) $column_item->title;

        $html  = $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-desktop',
            $base,
            '',
            $children,
            null
        );

        $html .= $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-city',
            sprintf(
                /* translators: %s: menu column title (e.g. Oferta) */
                __('%s Warszawa', 'akademiata'),
                $base
            ),
            '',
            $children,
            'warszawa'
        );

        $html .= $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-city',
            sprintf(
                /* translators: %s: menu column title (e.g. Oferta) */
                __('%s Wrocław', 'akademiata'),
                $base
            ),
            '',
            $children,
            'wroclaw'
        );

        $html .= $this->render_offer_column(
            'mega-column mega-column--offer mega-column--offer-city',
            sprintf(
                /* translators: %s: menu column title (e.g. Oferta) */
                __('%s Online', 'akademiata'),
                $base
            ),
            '',
            $children,
            'online'
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

            $desc = $this->column_description($item);

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
