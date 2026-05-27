<?php
/**
 * Front-page-style aktualności grid (1 featured + 1 medium + list).
 *
 * @package akademiata
 *
 * Expects $args['query'] (WP_Query). Optional: see_all_url, section_title, section_class.
 */

$query = isset($args['query']) && $args['query'] instanceof WP_Query ? $args['query'] : null;

if (!$query || !$query->have_posts()) {
    return;
}

$see_all_url    = !empty($args['see_all_url']) ? $args['see_all_url'] : akademiata_get_aktualnosci_page_url();
$section_title  = !empty($args['section_title']) ? $args['section_title'] : __('AKTUALNOŚCI', 'akademiata');
$section_class  = !empty($args['section_class']) ? $args['section_class'] : 'section_aktualnosci mb-5';
$index          = 0;
?>
<section class="<?php echo esc_attr($section_class); ?>">
    <div class="container">
        <div class="aktualnosci-header-row">
            <h2 class="small_title"><?php echo esc_html($section_title); ?></h2>
            <?php
            get_template_part(
                'partials/aktualnosci',
                'header-actions',
                array(
                    'see_all_url'       => $see_all_url,
                    'current_city_slug' => '',
                )
            );
            ?>
        </div>

        <div class="front-aktualnosci-grid">
            <?php
            while ($query->have_posts()) :
                $query->the_post();
                if ($index === 0) :
                    ?>
                    <div class="aktualnosci-post aktualnosci-first">
                        <a href="<?php the_permalink(); ?>">
                            <div class="post-image"
                                 style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>');">
                                <div class="post-title-overlay"><?php the_title(); ?></div>
                            </div>
                        </a>
                    </div>
                <?php elseif ($index === 1) : ?>
                    <div class="aktualnosci-post aktualnosci-second">
                        <a href="<?php the_permalink(); ?>">
                            <div class="post-image"
                                 style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium_large')); ?>');">
                                <div class="post-title-overlay"><?php the_title(); ?></div>
                            </div>
                        </a>
                    </div>
                <?php elseif ($index === 2) : ?>
                    <div class="aktualnosci-post aktualnosci-list">
                    <ul>
                        <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                <?php else : ?>
                        <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                <?php
                endif;
                $index++;
            endwhile;
            if ($index >= 3) :
                ?>
                    </ul>
                    </div>
                <?php
            endif;
            ?>
        </div>
    </div>
</section>
<?php
wp_reset_postdata();
