<?php
/**
 * Template Name: FAQ Page
 *
 * Textdomain: akademiata
 */

get_header();

// Page header (ACF fields on the PAGE)
$title = get_field('accordion_main_title');
$sub_title = get_field('accordion_main_sub_title');
$description = get_field('accordion_main_description');

// Build tabs from taxonomy
$terms = get_terms([
    'taxonomy' => 'faq_topics',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
]);

$tabs = [];

if (!is_wp_error($terms) && !empty($terms)) {
    $term_meta = [];
    $term_ids = [];

    foreach ($terms as $term) {
        $term_context = 'term_' . $term->term_id;
        $label = function_exists('get_field') ? get_field('tab_label', $term_context) : '';
        $slug = function_exists('get_field') ? get_field('tab_slug', $term_context) : '';

        $term_meta[$term->term_id] = [
            'label' => $label ? trim((string)$label) : $term->name,
            'slug' => $slug ? sanitize_title($slug) : $term->slug,
        ];
        $term_ids[] = (int)$term->term_id;
    }

    // All FAQ posts for these terms (bulk)
    $faq_ids = get_posts([
        'post_type' => 'faq',
        'post_status' => 'publish',
        'numberposts' => -1,
        'tax_query' => [[
            'taxonomy' => 'faq_topics',
            'field' => 'term_id',
            'terms' => $term_ids,
        ]],
        'no_found_rows' => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'suppress_filters' => false,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'order' => 'ASC',
        'fields' => 'ids',
    ]);

    // Group posts by term
    $bucket = array_fill_keys($term_ids, []);
    foreach ((array)$faq_ids as $pid) {
        $post_terms = wp_get_object_terms($pid, 'faq_topics', ['fields' => 'ids']);
        if (is_wp_error($post_terms)) continue;
        foreach ($post_terms as $tid) {
            if (isset($bucket[$tid])) $bucket[$tid][] = (int)$pid;
        }
    }


    foreach ($term_ids as $tid) {
        $items = [];

        foreach ($bucket[$tid] as $pid) {
            $acc_rows = function_exists('get_field') ? get_field('accordion_universal', $pid) : [];


            if (!is_array($acc_rows) || empty($acc_rows)) {
                $body = apply_filters('the_content', get_post_field('post_content', $pid));
                if (trim(wp_strip_all_tags((string)$body)) !== '') {
                    $items[] = [
                        'accordion_title' => get_the_title($pid),
                        'accordion_default_content' => ['content' => $body], // ONLY this key
                        'accordion_is_open' => false,
                        'accordion_content_template' => 'accordion_default_content.php',
                    ];
                }
                continue;
            }


            foreach ($acc_rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $title = $row['accordion_title'] ?? $row['title'] ?? get_the_title($pid);

                // Derive body from common shapes in your rows
                $body = '';
                if (isset($row['accordion_default_content']['content'])) {
                    $body = $row['accordion_default_content']['content'];
                } elseif (isset($row['accordion_content']['content'])) {
                    $body = $row['accordion_content']['content'];
                } elseif (!empty($row['content']) && is_string($row['content'])) {
                    $body = $row['content'];
                } elseif (!empty($row['text']) && is_string($row['text'])) {
                    $body = $row['text'];
                } elseif (!empty($row['wysiwyg']) && is_string($row['wysiwyg'])) {
                    $body = $row['wysiwyg'];
                }


                if (trim(wp_strip_all_tags((string)$body)) === '') {
                    $body = apply_filters('the_content', get_post_field('post_content', $pid));
                }
                if (trim(wp_strip_all_tags((string)$body)) === '') {
                    continue;
                }

                // Final normalized item — ONLY default content key
                $row['accordion_title'] = $title;
                $row['accordion_default_content'] = ['content' => $body];
                unset($row['accordion_contact_content']); // ensure it's not present
                $row['accordion_content_template'] = 'accordion_default_content.php';

                $items[] = $row;
            }
        }

        $tabs[] = [
            'label' => $term_meta[$tid]['label'],
            'slug' => $term_meta[$tid]['slug'],
            'items' => $items,
        ];
    }


}


$has_any_items = false;
foreach ($tabs as $t) {
    if (!empty($t['items'])) {
        $has_any_items = true;
        break;
    }
}

if (!$has_any_items) {
    $accordion_universal = function_exists('get_field') ? get_field('accordion_universal') : [];
    if (!empty($accordion_universal) && is_array($accordion_universal)) {
        set_query_var('accordion', $accordion_universal);
        set_query_var('title', $title);
        set_query_var('sub_title', $sub_title);
        set_query_var('description', $description);

        locate_template('template-parts/accordion_universal.php', true, false);
        echo '</div></div>';
        get_footer();
        return;
    }
}


$first_slug = '';
if (!empty($tabs)) {
    $first_slug = $tabs[0]['slug'];
    foreach ($tabs as $t) {
        if (!empty($t['items'])) {
            $first_slug = $t['slug'];
            break;
        }
    }
}
?>

<div class="faq-page">

    <div class="faq-page__inner container">
        <!-- Breadcrumbs -->
        <?php the_breadcrumb(); ?>
        <div class="faq-page__grid">
            <!-- LEFT: Header +  Accordions -->
            <div class="faq-page__main my-5">
                <div class="faq-header">
                    <h1 class="faq-title"><?php echo esc_html($title ?: get_the_title()); ?></h1>
                    <?php if ($sub_title): ?>
                        <div class="faq-subtitle mb-5"><?php echo esc_html($sub_title); ?></div>
                    <?php endif; ?>
                    <?php if ($description): ?>
                        <div class="faq-desc"><?php echo wp_kses_post($description); ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($tabs)) : ?>
                    <div class="contact_city_tabs" role="tablist"
                         aria-label="<?php echo esc_attr__('Choose topic', 'akademiata'); ?>">
                        <ul class="contact_city_tab">
                            <?php foreach ($tabs as $t): ?>
                                <li class="<?php echo $t['slug'] === $first_slug ? 'active' : ''; ?>">
                                    <a href="#faq-<?php echo esc_attr($t['slug']); ?>" role="tab"
                                       aria-selected="<?php echo $t['slug'] === $first_slug ? 'true' : 'false'; ?>">
                                        <?php echo esc_html($t['label']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php foreach ($tabs as $t): ?>
                            <div id="faq-<?php echo esc_attr($t['slug']); ?>"
                                 class="contact_city_tab_content <?php echo $t['slug'] === $first_slug ? 'active' : ''; ?>"
                                 role="tabpanel">
                                <?php
                                set_query_var('accordion', $t['items']);
                                set_query_var('title', '');
                                set_query_var('sub_title', '');
                                set_query_var('description', '');
                                locate_template('template-parts/accordion_universal.php', true, false);
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<?php get_footer(); ?>