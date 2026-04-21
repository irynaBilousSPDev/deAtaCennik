<?php get_header(); ?>
<?php
$category = get_queried_object();
if (isset($category->slug)) {
    $category->slug;
}
?>
<div class="news_archive category_<?php echo $category->slug; ?>">
    <div class="container">
        <!-- Breadcrumbs -->
        <?php the_breadcrumb(); ?>
        <h1 class="mb-5"><?php single_cat_title(); ?></h1>
        <?php if (category_description()) : ?>
            <div class="description mb-5"><?php echo category_description(); ?></div>
        <?php endif; ?>

        <?php if (have_posts()) : ?>
            <div class="posts_wrapper_news">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="post_news">
                        <div class="post-image" style="background-image: url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>');"></div>
                        <div class="post-content">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        </div>
                        <a class="post-button" href="<?php the_permalink(); ?>" aria-label="Read more"></a>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php _e('Nie znaleziono żadnych wyników', 'akademiata'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
