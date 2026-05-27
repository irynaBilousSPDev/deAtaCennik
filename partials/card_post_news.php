<?php
/**
 * News card (aktualności) — archive / kierunek program blocks.
 */
?>
<div class="post_news">
    <div class="post-image" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium')); ?>');">
        <div class="city_block">
            <img class="location_icon"
                 src="<?php echo esc_url(get_template_directory_uri() . '/static/img/icon_location.png'); ?>"
                 alt="<?php esc_attr_e('Location Icon', 'akademiata'); ?>">
            <span><?php echo esc_html(akademiata_get_post_news_city_label()); ?></span>
        </div>
    </div>
    <div class="post-content">
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <time class="post-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time>
    </div>
    <a class="post-button" href="<?php the_permalink(); ?>" aria-label="<?php esc_attr_e('Czytaj więcej', 'akademiata'); ?>"></a>
</div>
