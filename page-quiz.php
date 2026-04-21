<?php
/**
 * Template Name: Quiz Page
 */
get_header();
$acf_fields = get_fields();// Get all ACF fields
?>

<div class="quiz_page">
    <div class="container">
        <div class="quiz_body">
            <div class="row quiz_body__row">
                <div class="col-lg-6">
                    <div class="quiz_body__baner sticky-top">
                        <h1 class="title_section mb-5 d-none d-lg-block">
                           <?php echo $acf_fields['main_title']; ?>
                        </h1>
                        <img src="<?php echo $acf_fields['quiz_image']['url']; ?>">
                    </div>
                </div>
                <div class="col-lg-6">
                    <?php $quiz_shortcode = $acf_fields['quiz_shortcode'];
                    echo do_shortcode($quiz_shortcode); ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php get_footer(); ?>


