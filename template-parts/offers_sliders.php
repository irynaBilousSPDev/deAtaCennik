<?php
$post_types = get_query_var('post_types', []);
if (!empty($post_types)) :
    $initial_count = akademiata_offer_slider_initial_count();
    foreach ($post_types as $post_type) :
        $cpt_object = get_post_type_object($post_type);
        if ($cpt_object) :
            $cpt_title = esc_html($cpt_object->labels->name);
            $cpt_slug = !empty($cpt_object->rewrite['slug']) ? esc_html($cpt_object->rewrite['slug']) : '';
            $query = new WP_Query(akademiata_get_offer_slider_query_args($post_type));
            ?>
            <section class="our_offer offer_<?php echo esc_attr($post_type); ?> pb-3"
                     data-post-type="<?php echo esc_attr($post_type); ?>">
                <div class="container">
                    <div class="offer_category_title d-flex py-3">
                        <h2 class="small_title"><?php echo esc_html($cpt_title); ?></h2>
                        <a title="<?php echo esc_attr($cpt_title); ?>"
                           href="<?php echo get_home_url(); ?>/<?php echo $cpt_slug; ?>">
                            <?php _e('Zobacz wszystkie', 'akademiata'); ?>
                        </a>
                    </div>
                </div>
                <div class="offer_category_slider py-3 slick-slider"
                     id="ajax-container-<?php echo esc_attr($post_type); ?>"
                     data-initial-count="<?php echo esc_attr((string) $initial_count); ?>">
                    <?php
                    if ($query->have_posts()) :
                        while ($query->have_posts()) :
                            $query->the_post();
                            akademiata_render_offer_slider_slide($post_type);
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </section>

        <?php endif;
    endforeach;
else : ?>
    <div id="no-results"><?php _e('Nie znaleziono żadnych wyników', 'akademiata'); ?></div>
<?php endif; ?>
