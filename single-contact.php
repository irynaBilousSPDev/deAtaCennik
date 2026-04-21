<?php
/**
 * Single template for CPT: contact (render ONLY inner accordion contents)
 * File: single-contact.php
 *
 * - Reads ACF repeater "accordion_universal" from the CONTACT post
 * - For each row, includes its content template just like the inner part of
 *   accordion_universal.php (i.e., only the <div class="accordion_content"> ... </div>)
 * - No headers, no extra wrappers, no set_query_var().
 *
 * Text Domain: akademiata
 */

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();

        // Get rows from ACF repeater on this contact post
        $accordion_rows = get_field('accordion_universal', get_the_ID());


        $city_terms = get_the_terms(get_the_ID(), 'contact_city');
        $labels = '';

        if (!is_wp_error($city_terms) && !empty($city_terms)) {
            $labels = implode(', ', array_map(static fn($t) => $t->name, $city_terms));
        }
        ?>
        <div class="single_contact">
            <div class="container">
                <?php if ($labels): ?>
                    <div class="small_title primary_color"><?php echo esc_html($labels); ?></div>
                <?php endif; ?>
                <h1 class="contact_title"><?php the_title(); ?></h1>

                <?php
                if (is_array($accordion_rows) && !empty($accordion_rows)) {
                    foreach ($accordion_rows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }

                        // Determine template and data for this row (same keys your partial expects)
                        $template_path = !empty($row['accordion_content_template'])
                            ? $row['accordion_content_template']
                            : 'accordion_contact_content.php';

                        $content_data = $row['accordion_contact_content']
                            ?? ($row['accordion_default_content'] ?? null);

                        // Resolve the template path; allow both "template-parts/{file}" and bare filename
                        $template_candidates = [
                            'template-parts/' . ltrim($template_path, '/'),
                            ltrim($template_path, '/'),
                        ];
                        $full_template_path = locate_template($template_candidates, false, false);
                        ?>
                        <div class="accordion_content">
                            <?php
                            if ($template_path && $full_template_path) {
                                // Provide $content to the included template (as in your original include)
                                $content = $content_data;
                                include $full_template_path;
                            } else {
                                echo '<p>' . esc_html__('Brak treści sekcji', 'akademiata') . '</p>';
                            }
                            ?>
                        </div>
                        <?php
                    }
                } else {
                    // Fallback: show post content if no rows exist
                    echo '<div class="contact_content">';
                    the_content();
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    <?php

    endwhile;
endif;

get_footer();
