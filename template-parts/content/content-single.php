<?php
/**
 * The template for displaying single posts
 *
 * @package  akademiata
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="container">
        <!-- Start breadcrumbs -->
        <?php if (function_exists('the_breadcrumb')) {
            the_breadcrumb();
        } ?>
        <!-- End breadcrumbs -->
    </div>
    <div class="container">
        <header class="entry-header">
            <?php
            // Display the post title
            the_title('<h1 class="entry-title">', '</h1>');
            ?>
        </header><!-- .entry-header -->

        <div class="entry-meta">
            <?php
            // Display post metadata (author, date, categories, etc.)
            echo 'Posted by ' . get_the_author() . ' on ' . get_the_date();
            ?>
        </div><!-- .entry-meta -->

        <div class="entry-content">
            <?php
            // Display the post content
            the_content();

            // Paginate content if using <!--nextpage--> in the post
            wp_link_pages(array(
                'before' => '<div class="page-links">' . __('Pages:', 'akademiata'),
                'after' => '</div>',
            ));
            ?>
        </div><!-- .entry-content -->

        <footer class="entry-footer">
            <?php
            // Display categories, tags, and edit link
            the_category(', ');
            the_tags('<span class="tags-links">', ', ', '</span>');
            ?>
        </footer><!-- .entry-footer -->
    </div>
</article><!-- #post-<?php the_ID(); ?> -->

