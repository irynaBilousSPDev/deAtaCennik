<?php

/**
 * 1. BUDOWA KLUCZA RELACYJNEGO: Stopień + Miasto+ Slug 
 */
function ata_build_smart_key($post_id, $post) {
    // 1. Stopień
    $stopien = ($post->post_type === 'bachelor') ? '1' : '2';

    // 2. Miasto (Pobierane poprawnie z taksonomii 'city')
    $city_code = 'uni'; 
    $terms = get_the_terms($post_id, 'city');
    
    if ($terms && !is_wp_error($terms)) {
        $city_name = $terms[0]->name; 
        if (stripos($city_name, 'warszawa') !== false) {
            $city_code = 'wwa';
        } elseif (stripos($city_name, 'wrocław') !== false || stripos($city_name, 'wroclaw') !== false) {
            $city_code = 'wro';
        }
    }

    // 3. Unikalny Slug posta (adres URL)
    $slug = $post->post_name;
    if (empty($slug)) return ''; 

    // 4. Złożenie całości (np. 1_wwa_architektura-wnetrz)
    return strtolower($stopien . '_' . $city_code . '_' . $slug);
}

/**
 * 2. ZAPIS POSTA (DLA NOWYCH): Generuje tylko, gdy klucz jest pusty
 */
add_action('save_post', 'ata_auto_save_smart_key', 10, 2);
function ata_auto_save_smart_key($post_id, $post) {
    if (!in_array($post->post_type, ['bachelor', 'master'])) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $meta_key = 'logical_sync_key';
    $stored_key = get_post_meta($post_id, $meta_key, true);

    if (empty($stored_key)) {
        $new_key = ata_build_smart_key($post_id, $post);
        // Sprawdzamy czy miasto zdążyło się zapisać (brak 'uni')
        if (!empty($new_key) && strpos($new_key, 'uni') === false) { 
            update_post_meta($post_id, $meta_key, $new_key);
        }
    }
}

/**
 * 3. JEDNORAZOWY AUTOMAT W TLE 
 */
// add_action('admin_init', 'ata_background_key_generator');
// function ata_background_key_generator() {
//     if (!get_option('ata_smart_keys_generated_v8')) {
        
//         $posts = get_posts(['post_type' => ['bachelor', 'master'], 'numberposts' => -1, 'post_status' => 'any']);
//         foreach ($posts as $p) {
//             $key = ata_build_smart_key($p->ID, $p);
//             if ($key) update_post_meta($p->ID, 'logical_sync_key', $key);
//         }
        
//         add_option('ata_smart_keys_generated_v8', '1');
//     }
// }

/**
 * 4. PANEL BOCZNY W EDYCJI: Pokazuje gotowy klucz i przycisk do skopiowania
 */
add_action('add_meta_boxes', function() {
    foreach (['bachelor', 'master'] as $cpt) {
        add_meta_box('ata_key_box', 'Klucz do Google Sheets (Ceny)', 'ata_show_key_metabox', $cpt, 'side', 'high');
    }
});

function ata_show_key_metabox($post) {
    $key = get_post_meta($post->ID, 'logical_sync_key', true);
    
    echo '<div style="background:#f0f6fb; padding:12px; border:1px solid #007cba; border-radius:4px; text-align:center;">';
    if ($key) {
        echo '<code id="ata_key" style="font-weight:bold; font-size:12px; display:block; margin-bottom:12px; color:#d63638; word-break:break-all;">' . esc_html($key) . '</code>';
        echo '<button type="button" class="button button-primary" style="width:100%" onclick="ataCopyKey()">📋 Kopiuj do Tabeli</button>';
        echo '<p style="font-size:11px; color:#666; margin-top:10px; text-align:left;">Ten klucz jest stały. Wklej ten sam klucz do Excela we wszystkie wiersze (Stacjonarne i Niestacjonarne), które dotyczą tej strony.</p>';
    } else {
        echo '<div style="text-align:left; font-size:12px; line-height:1.4;">';
        echo '<p style="color:#d63638; font-weight:bold; margin-bottom:8px;">⚠️ Klucz oczekuje na dane</p>';
        echo '<p style="margin-bottom:8px;">Klucz zostanie wygenerowany automatycznie po naciśnięciu <strong>Opublikuj</strong> lub <strong>Aktualizuj</strong>.</p>';
        echo '<p style="font-size:11px; color:#444; font-weight:bold;">Upewnij się, że wybrałeś/aś:</p>';
        echo '<ul style="font-size:11px; color:#444; margin-left:15px; list-style:disc;">';
        echo '<li><strong>Miasto</strong> (w panelu Cities),</li>';
        echo '<li><strong>Stopień</strong> (Bachelor / Master),</li>';
        echo '<li>Oraz wpisałeś/aś <strong>Tytuł specjalności</strong>.</li>';
        echo '</ul>';
        echo '</div>';
    }
    echo '</div>';
    ?>
    <script>
    function ataCopyKey() {
        var t = document.getElementById('ata_key').innerText;
        navigator.clipboard.writeText(t).then(() => {
            alert('Gotowe! Skopiowano: \n' + t);
        });
    }
    </script>
    <?php
}