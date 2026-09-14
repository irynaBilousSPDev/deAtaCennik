<?php
/**
 * The template for displaying single posts
 *
 * @package  akademiata
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="container">
        <?php if (function_exists('the_breadcrumb')) {
            the_breadcrumb();
        } ?>
    </div>
    <div class="container">
        <header class="entry-header">
            <?php
            the_title('<h1 class="entry-title">', '</h1>');
            ?>
        </header><!-- .entry-header -->

        <div class="entry-meta">
            <?php
            echo 'Posted by ' . get_the_author() . ' on ' . get_the_date();
            ?>
        </div><!-- .entry-meta -->

        <div class="entry-content">
            <?php
            the_content();

            wp_link_pages(array(
                'before' => '<div class="page-links">' . __('Pages:', 'akademiata'),
                'after' => '</div>',
            ));
            ?>
        </div><!-- .entry-content -->

        <footer class="entry-footer">
            <?php
            the_category(', ');
            the_tags('<span class="tags-links">', ', ', '</span>');
            ?>
        </footer><!-- .entry-footer -->
    </div>
</article><!-- #post-<?php the_ID(); ?> -->

