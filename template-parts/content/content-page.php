<?php
/**
 * Template part for displaying page
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
    <section>
       <div class="container">
           <?php the_title('<h1>', '</h1>'); ?>

           <?php
           the_content();
           ?>
       </div>
    </section>
</article>