<?php
/**
 * News card (aktualności) — archive / kierunek program blocks.
 */
$permalink = get_the_permalink();
$thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
?>
<article class="post_news">
    <a class="post_news__media" href="<?php echo esc_url($permalink); ?>" tabindex="-1" aria-hidden="true">
        <div class="post_news__image"<?php echo $thumb_url ? ' style="background-image: url(\'' . esc_url($thumb_url) . '\');"' : ''; ?>></div>
    </a>
    <div class="post_news__body">
        <h2 class="post_news__title">
            <a href="<?php echo esc_url($permalink); ?>"><?php the_title(); ?></a>
        </h2>
        <div class="post_news__footer">
            <div class="post_news__meta">
                <span class="post_news__date">
                    <span class="post_news__date-icon" aria-hidden="true"></span>
                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d.m.Y')); ?></time>
                </span>
                <?php get_template_part('partials/news', 'city-block', array('variant' => 'inline')); ?>
            </div>
            <a class="post_news__arrow" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr(akademiata_get_theme_lang_string('news_read_more')); ?>"></a>
        </div>
    </div>
</article>
