<div <?php post_class('aktualnosci-article'); ?>>
    <div class="container">

        <!-- Start breadcrumbs -->
        <?php if (function_exists('the_breadcrumb')) {
            the_breadcrumb();
        } ?>
        <!-- End breadcrumbs -->

        <?php
        // Dynamically get the first category slug
        $categories = get_the_category();
        $category_slug = '';

        if (!empty($categories) && !is_wp_error($categories)) {
            $category_slug = $categories[0]->slug;
        }

        // Check if sidebar should be shown based on the current category
        $should_show_sidebar = false;

        if (!empty($category_slug)) {
            $category_post_count = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => 2, // Just check if more than one
                'post_status'    => 'publish',
                'category_name'  => $category_slug,
            ]);

            $should_show_sidebar = ($category_post_count->found_posts > 1);
            wp_reset_postdata();
        }
        ?>

        <div class="aktualnosci-grid">
            <div class="aktualnosci-main">
                <header class="entry-header aktualnosci-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-meta-row mb-3">
                        <span class="date"><?php echo get_the_date('d.m.Y'); ?></span>

                        <?php
                        if (!empty($categories)) {
                            echo '<span class="category-name">' . esc_html($categories[0]->name) . '</span>';
                        }
                        ?>
                    </div>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <?php $bg_url = get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>
                    <div class="entry-thumbnail aktualnosci-thumbnail"
                         style="background-image: url('<?php echo esc_url($bg_url); ?>');"></div>

                    <div class="share-row">
                        <span class="share-label"><?php _e('UDOSTĘPNIJ WPIS', 'akademiata'); ?></span>
                        <div class="addtoany-wrapper">
                            <?php echo do_shortcode('[addtoany]'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="entry-content">
                    <div class="aktualnosci-content">
                        <?php the_content(); ?>
                    </div>

                    <?php
                    $images = get_field('gallery_news');

                    if (!empty($images) && is_array($images)) :
                        $large_image = $images[0] ?? null;
                        $top_right = array_slice($images, 1, 4);
                        $bottom_images = array_slice($images, 5);
                        ?>
                        <div class="custom-gallery-grid">
                            <!-- Left large image -->
                            <?php if (!empty($large_image)) : ?>
                                <div class="gallery-item gallery-large">
                                    <a href="<?php echo esc_url($large_image['url']); ?>" data-fancybox="gallery" data-caption="<?php echo esc_attr($large_image['alt']); ?>">
                                        <img src="<?php echo esc_url($large_image['sizes']['large']); ?>"
                                             alt="<?php echo esc_attr($large_image['alt']); ?>"/>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <!-- Top right 2x2 small images -->
                            <div class="gallery-right-grid">
                                <?php foreach ($top_right as $image) : ?>
                                    <div class="gallery-item gallery-small">
                                        <a href="<?php echo esc_url($image['url']); ?>" data-fancybox="gallery" data-caption="<?php echo esc_attr($image['alt']); ?>">
                                            <img src="<?php echo esc_url($image['sizes']['medium']); ?>"
                                                 alt="<?php echo esc_attr($image['alt']); ?>"/>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bottom 4-column gallery -->
                        <?php if (!empty($bottom_images)) : ?>
                        <div class="gallery-bottom-grid">
                            <?php foreach ($bottom_images as $image) : ?>
                                <div class="gallery-item gallery-bottom">
                                    <a href="<?php echo esc_url($image['url']); ?>" data-fancybox="gallery" data-caption="<?php echo esc_attr($image['alt']); ?>">
                                        <img src="<?php echo esc_url($image['sizes']['medium']); ?>"
                                             alt="<?php echo esc_attr($image['alt']); ?>"/>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($should_show_sidebar) : ?>
                <aside class="aktualnosci-sidebar">
                    <div class="sidebar_content">
                        <h2 class="sidebar-title"><?php _e('OSTATNIE WPISY', 'akademiata'); ?></h2>
                        <ul class="sidebar-posts mb-5">
                            <?php
                            if (!empty($category_slug)) {
                                $recent_posts = new WP_Query([
                                    'posts_per_page' => 4,
                                    'post__not_in'   => [get_the_ID()],
                                    'post_status'    => 'publish',
                                    'category_name'  => $category_slug,
                                ]);

                                if ($recent_posts->have_posts()) :
                                    while ($recent_posts->have_posts()) : $recent_posts->the_post(); ?>
                                        <li class="sidebar-post">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </li>
                                    <?php endwhile;
                                    wp_reset_postdata();
                                endif;
                            }
                            ?>
                        </ul>

                        <?php
                        /**
                         * ARCHIVE: Years -> Months (optionally filtered by the current post's first category)
                         * Language-aware links to custom NEWS date archives.
                         */
                        global $wpdb;

                        // Current language (WPML)
                        $current_lang = 'pl';
                        if (function_exists('apply_filters')) {
                            $maybe_lang = apply_filters('wpml_current_language', null);
                            if (!empty($maybe_lang)) $current_lang = $maybe_lang;
                        } elseif (defined('ICL_LANGUAGE_CODE')) {
                            $current_lang = ICL_LANGUAGE_CODE;
                        }

                        // Use the first category of the current post (same as you had)
                        $archive_category_slug = '';
                        if (!empty($categories) && !is_wp_error($categories)) {
                            $archive_category_slug = $categories[0]->slug;
                        }

                        // Cache key MUST include language so PL/EN do not mix
                        $archive_cache_key = 'archive_year_month_' . ($archive_category_slug ?: 'all') . '_lang_' . $current_lang;
                        $archive_groups = get_transient($archive_cache_key);

                        if (false === $archive_groups) :
                            // Prepare SQL depending on whether we filter by category
                            if (!empty($archive_category_slug)) {
                                // Query archives for posts within the current category
                                $sql = $wpdb->prepare(
                                    "
            SELECT
                YEAR(p.post_date) AS y,
                MONTH(p.post_date) AS m,
                COUNT(p.ID) AS count_posts
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE p.post_type = 'post'
              AND p.post_status = 'publish'
              AND tt.taxonomy = 'category'
              AND t.slug = %s
            GROUP BY y, m
            ORDER BY y DESC, m DESC
            ",
                                    $archive_category_slug
                                );
                            } else {
                                // Query archives for all published posts
                                $sql = "
            SELECT
                YEAR(post_date) AS y,
                MONTH(post_date) AS m,
                COUNT(ID) AS count_posts
            FROM {$wpdb->posts}
            WHERE post_type = 'post' AND post_status = 'publish'
            GROUP BY y, m
            ORDER BY y DESC, m DESC
        ";
                            }

                            $rows = $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

                            // Group rows into [year => ['count' => X, 'months' => [ [m, count], ... ] ] ]
                            $archive_groups = [];
                            if ($rows) {
                                foreach ($rows as $r) {
                                    $y = (int) $r->y;
                                    $m = (int) $r->m;
                                    $c = (int) $r->count_posts;

                                    if (!isset($archive_groups[$y])) {
                                        $archive_groups[$y] = [
                                            'count'  => 0,
                                            'months' => [],
                                        ];
                                    }
                                    $archive_groups[$y]['count']   += $c;
                                    $archive_groups[$y]['months'][] = [$m, $c];
                                }
                            }

                            // Cache for 12 hours
                            set_transient($archive_cache_key, $archive_groups, HOUR_IN_SECONDS * 12);
                        endif;
                        ?>

                        <div class="sidebar_content sidebar-archive">
                            <h2 class="sidebar-title"><?php _e('ARCHIWUM', 'akademiata'); ?></h2>

                            <?php if (!empty($archive_groups)) : ?>
                                <ul class="archive-years">
                                    <?php foreach ($archive_groups as $year => $data) : ?>
                                        <li class="archive-year">
                                            <!-- Year toggle (collapsed by default) -->
                                            <button class="archive-year-toggle" type="button" aria-expanded="false" aria-controls="months-<?php echo esc_attr($year); ?>">
                                                <?php echo esc_html($year); ?> (<?php echo (int) $data['count']; ?>)
                                            </button>

                                            <ul id="months-<?php echo esc_attr($year); ?>" class="archive-months" hidden>
                                                <?php
                                                // Sort months descending (12..1)
                                                usort($data['months'], static function($a, $b){ return $b[0] <=> $a[0]; });

                                                foreach ($data['months'] as [$month, $count]) :
                                                    // Language-aware month link for custom NEWS archives
                                                    $month_link = ata_news_month_link_lang((int)$year, (int)$month, $current_lang);

                                                    // Month name localized
                                                    $month_name = date_i18n('F', mktime(0, 0, 0, (int)$month, 1));
                                                    ?>
                                                    <li class="archive-month">
                                                        <a href="<?php echo esc_url($month_link); ?>">
                                                            <?php echo esc_html(sprintf('%s %d', $month_name, (int)$year)); ?>
                                                        </a>
                                                        <span class="archive-count">(<?php echo (int)$count; ?>)</span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="archive-empty"><?php _e('Brak wpisów в archiwum.', 'akademiata'); ?></p>
                            <?php endif; ?>
                        </div>
 

                    </div>


                </aside>
            <?php endif; ?>
        </div>
    </div>
</div>
