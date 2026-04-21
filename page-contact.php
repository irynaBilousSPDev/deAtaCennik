<?php
/**
 * Template Name: Contact Page
 *
 * Textdomain: akademiata
 */

get_header();
?>
    <div class="page_contact">
        <div class="container">
            <h1 class="contact_title"><?php the_title(); ?></h1>

            <div class="contact_header">
                <?php
                $contact_header = get_field('contact_header') ?: [];
                $contact_rows   = $contact_header['contact_header_repeater'] ?? [];
                $contact_form   = $contact_header['contact_form'] ?? '';

                if (!empty($contact_rows) && is_array($contact_rows)) :
                    $theme_uri  = get_template_directory_uri();
                    $icon_addr  = $theme_uri . '/static/img/icon_contact_page_ATA_address.png';
                    $icon_phone = $theme_uri . '/static/img/icon_contact_page_ATA_phone.png';
                    $icon_mail  = $theme_uri . '/static/img/icon_contact_page_ATA_mail.png';

                    $tel_href = static function (string $raw): string {
                        return preg_replace('/[^0-9+]/', '', $raw);
                    };

                    // Timed notice (m/d/Y)
                    $notice_text  = $contact_header['contact_notice_text'] ?? '';
                    $notice_until = $contact_header['contact_notice_until'] ?? '';
                    $now          = current_time('timestamp');

                    if (!empty($notice_until) && !is_numeric($notice_until)) {
                        $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone(wp_timezone_string());
                        $dt = DateTime::createFromFormat('m/d/Y', $notice_until, $tz);
                        if ($dt instanceof DateTime) {
                            $dt->setTime(23, 59, 59);
                            $notice_until = $dt->getTimestamp();
                        } else {
                            $notice_until = 0;
                        }
                    }
                    ?>
                    <div class="contact_header_row">
                        <div class="contact_header_left">
                            <?php if (!empty($notice_text) && !empty($notice_until) && $now <= (int)$notice_until) : ?>
                                <div class="contact-notice mb-5"><?php echo wp_kses_post($notice_text); ?></div>
                            <?php endif; ?>

                            <?php foreach ($contact_rows as $row):
                                $school  = $row['school_name'] ?? '';
                                $address = $row['address'] ?? '';
                                $phone   = $row['phone'] ?? '';
                                $email   = $row['email'] ?? '';
                                $bank    = $row['bank_account_number_details'] ?? '';
                                $city_cl = $row['city_class'] ?? 'city_section';
                                if (!$school && !$address && !$phone && !$email && !$bank) continue; ?>
                                <div class="<?php echo esc_attr($city_cl); ?> mb-5">
                                    <?php if ($school): ?>
                                        <div class="school_name"><?php echo wp_kses_post($school); ?></div>
                                    <?php endif; ?>
                                    <ul class="contact_details">
                                        <?php if ($address): ?>
                                            <li>
                                        <span class="icon" aria-hidden="true">
                                            <img src="<?php echo esc_url($icon_addr); ?>" alt="Address" loading="lazy">
                                        </span><?php echo esc_html($address); ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($phone): ?>
                                            <li>
                                        <span class="icon" aria-hidden="true">
                                            <img src="<?php echo esc_url($icon_phone); ?>" alt="Phone" loading="lazy">
                                        </span><a href="tel:<?php echo esc_attr($tel_href($phone)); ?>"><?php echo esc_html($phone); ?></a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($email): ?>
                                            <li>
                                        <span class="icon" aria-hidden="true">
                                            <img src="<?php echo esc_url($icon_mail); ?>" alt="Email" loading="lazy">
                                        </span><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                    <?php if ($bank): ?>
                                        <div class="bank_details"><?php echo wp_kses_post($bank); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="contact_header_right">
                            <?php echo do_shortcode($contact_form); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            // ============================================================
            // Tabs: CPT "contact" + taxonomy "contact_city"
            // Optimized: one bulk query + grouping by term_id
            // ============================================================

            $title       = get_field('accordion_main_title');
            $sub_title   = get_field('accordion_main_sub_title');
            $description = get_field('accordion_main_description');

            $terms = get_terms([
                'taxonomy'   => 'contact_city',
                'hide_empty' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            $tabs = [];

            if (!is_wp_error($terms) && !empty($terms)) {
                // Prepare meta and term IDs
                $term_meta = [];
                $term_ids  = [];
                foreach ($terms as $term) {
                    $term_context = 'term_' . $term->term_id;
                    $label = get_field('tab_label', $term_context);
                    $slug  = get_field('tab_slug',  $term_context);

                    $term_meta[$term->term_id] = [
                        'label' => $label ? trim((string)$label) : $term->name,
                        'slug'  => $slug ? sanitize_title($slug) : $term->slug,
                    ];
                    $term_ids[] = (int)$term->term_id;
                }

                // Bulk query for all contacts
                $contact_ids = get_posts([
                    'post_type'              => 'contact',
                    'post_status'            => 'publish',
                    'numberposts'            => -1,
                    'tax_query'              => [[
                        'taxonomy' => 'contact_city',
                        'field'    => 'term_id',
                        'terms'    => $term_ids,
                    ]],
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => true,
                    'suppress_filters'       => false,
                    'orderby'                => ['menu_order' => 'DESC', 'title' => 'DESC'],
                    'order'                  => 'DESC',
                    'fields'                 => 'ids',
                ]);

                // Group contacts by city
                $bucket = array_fill_keys($term_ids, []);
                foreach ((array)$contact_ids as $pid) {
                    $post_terms = wp_get_object_terms($pid, 'contact_city', ['fields' => 'ids']);
                    if (is_wp_error($post_terms)) continue;
                    foreach ($post_terms as $tid) {
                        if (isset($bucket[$tid])) $bucket[$tid][] = (int)$pid;
                    }
                }

                // Build tabs
                foreach ($term_ids as $tid) {
                    $items = [];
                    foreach ($bucket[$tid] as $pid) {
                        $acc_rows = get_field('accordion_universal', $pid);
                        if (is_array($acc_rows) && !empty($acc_rows)) {
                            foreach ($acc_rows as $row) {
                                if (!is_array($row)) continue;
                                $row['accordion_content_template'] = 'accordion_contact_content.php';
                                $row['contact_title'] = get_the_title($pid);
                                $row['contact_id']    = $pid;
                                $items[] = $row;
                            }
                        }
                    }

                    $tabs[] = [
                        'label' => $term_meta[$tid]['label'],
                        'slug'  => $term_meta[$tid]['slug'],
                        'items' => $items,
                    ];
                }
            }

            // Fallback: page-level accordion if no contact posts
            $has_any_items = false;
            foreach ($tabs as $t) {
                if (!empty($t['items'])) {
                    $has_any_items = true;
                    break;
                }
            }

            if (!$has_any_items) {
                $accordion_universal = get_field('accordion_universal');
                if (!empty($accordion_universal) && is_array($accordion_universal)) {
                    foreach ($accordion_universal as $i => $item) {
                        if (is_array($item)) {
                            $item['accordion_content_template'] = 'accordion_contact_content.php';
                            $accordion_universal[$i] = $item;
                        }
                    }
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

            // Render tabs
            if (!empty($tabs)) :
                $first_slug = $tabs[0]['slug'];
                foreach ($tabs as $t) {
                    if (!empty($t['items'])) {
                        $first_slug = $t['slug'];
                        break;
                    }
                }
                ?>
                <div class="accordion_universal_header text-center">
                    <?php if ($title): ?><h2 class="title_section"><?php echo esc_html($title); ?></h2><?php endif; ?>
                    <?php if ($sub_title): ?><h3 class="small_title primary_color mb-3"><?php echo esc_html($sub_title); ?></h3><?php endif; ?>
                    <?php if ($description): ?><div class="accordion_description mb-5"><?php echo wp_kses_post($description); ?></div><?php endif; ?>
                </div>

                <div class="contact_city_tabs" role="tablist" aria-label="<?php echo esc_attr__('Wybierz miasto', 'akademiata'); ?>">
                    <ul class="contact_city_tab">
                        <?php foreach ($tabs as $t): ?>
                            <li class="<?php echo $t['slug'] === $first_slug ? 'active' : ''; ?>">
                                <a href="#cct-<?php echo esc_attr($t['slug']); ?>" role="tab" aria-selected="<?php echo $t['slug'] === $first_slug ? 'true' : 'false'; ?>">
                                    <?php echo esc_html($t['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php foreach ($tabs as $t): ?>
                        <div id="cct-<?php echo esc_attr($t['slug']); ?>" class="contact_city_tab_content <?php echo $t['slug'] === $first_slug ? 'active' : ''; ?>" role="tabpanel">
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
<?php get_footer();
