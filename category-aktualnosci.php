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
                    <?php get_template_part('partials/card_post_news'); ?>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php _e('Nie znaleziono żadnych wyników', 'akademiata'); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
