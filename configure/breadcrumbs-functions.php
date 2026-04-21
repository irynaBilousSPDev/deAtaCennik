<?php
/*=============================================
=            BREADCRUMBS			            =
=============================================*/
function the_breadcrumb()
{
    $sep = '<span class="breadcrumb-separator"> | </span>';

    if (!is_front_page()) {
        echo '<div class="breadcrumbs">';

        $home_url = apply_filters('wpml_home_url', home_url('/'));
        echo '<a href="' . esc_url($home_url) . '">home</a>' . $sep;

        // Safe oferta page translation
        $offer_link = '';
        $oferta_page = get_page_by_path( 'oferta' );
        if ( $oferta_page ) {
            $current_lang = apply_filters( 'wpml_current_language', null );
            $translated_id = apply_filters( 'wpml_object_id', $oferta_page->ID, 'page', false, $current_lang );
            if ( $translated_id ) {
                $translated_title = get_the_title( $translated_id );
                $offer_link = '<a href="' . esc_url( get_permalink( $translated_id ) ) . '">' . esc_html( $translated_title ) . '</a>';
            }
        }

        if (is_single() && get_post_type() === 'post') {
            $categories = get_the_category();
            if (!empty($categories)) {
                $main_category = $categories[0];

                if (function_exists('icl_object_id')) {
                    $current_lang = apply_filters('wpml_current_language', null);
                    $translated_term_id = apply_filters('wpml_object_id', $main_category->term_id, 'category', true, $current_lang);
                    if (!empty($translated_term_id) && $translated_term_id != $main_category->term_id) {
                        $translated_term = get_term($translated_term_id, 'category');
                        if ($translated_term && !is_wp_error($translated_term)) {
                            $main_category = $translated_term;
                        }
                    }
                }

                $category_link = get_term_link($main_category);
                if (!is_wp_error($category_link)) {
                    $category_link = str_replace('/category/', '/', $category_link);
                    echo '<a href="' . esc_url($category_link) . '">' . esc_html($main_category->name) . '</a>' . $sep;
                }
            }
            the_title();
        }
        elseif (is_singular(array('bachelor', 'master'))) {
            echo $offer_link . $sep;
            the_title();
        }

        // Breadcrumb for single postgraduate
        elseif (is_singular('postgraduate')) {
            $url = apply_filters('wpml_permalink', home_url('/studia-podyplomowe'), 'pl');
            $label = apply_filters('wpml_translate_single_string', 'Studia podyplomowe', 'akademiata', 'Studia podyplomowe');
            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>' . $sep;
            the_title();
        }

// Breadcrumb for single MBA
        elseif (is_singular('mba')) {
            $url = apply_filters('wpml_permalink', home_url('/studia-mba'), 'pl');
            $label = apply_filters('wpml_translate_single_string', 'Studia MBA', 'akademiata', 'Studia MBA');
            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>' . $sep;
            the_title();
        }

        // Breadcrumb for single COURSE (kursy)
        elseif (is_singular('courses')) {
            $url = apply_filters('wpml_permalink', home_url('/kursy'), 'pl');
            $label = apply_filters('wpml_translate_single_string', 'Kursy', 'akademiata', 'Kursy');
            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>' . $sep;
            the_title();
        }
// Breadcrumb for single EXAM (egzaminy)
        elseif (is_singular('exams')) {
            $url = apply_filters('wpml_permalink', home_url('/egzaminy'), 'pl');
            $label = apply_filters(
                'wpml_translate_single_string',
                'Egzaminy',
                'akademiata',
                'Egzaminy'
            );

            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>' . $sep;
            the_title();
        }
        // Breadcrumb for exams archive
        elseif (is_post_type_archive('exams')) {
            $label = apply_filters(
                'wpml_translate_single_string',
                'Egzaminy',
                'akademiata',
                'Egzaminy'
            );

            echo esc_html($label);
        }




        elseif (is_page(array(24, 26))) {
            echo $offer_link . $sep;
            the_title();
        }
        elseif (is_page()) {
            the_title();
        }

        if (is_home()) {
            $page_for_posts_id = get_option('page_for_posts');
            if ($page_for_posts_id) {
                echo esc_html(get_the_title($page_for_posts_id));
            }
        }

        echo '</div>';
    }
}


?>
