<?php
/* Template part for single Podcast ATA (podcast-ata) */

$is_mobile = wp_is_mobile();
$subtitle  = get_field('podcast_subtitle');
$date      = get_field('podcast_date');
$listen_url = get_field('podcast_listen_url');
?>

<section class="section_header right_space mb-5">
    <div class="container">

        <?php if ($is_mobile) : ?>
            <div class="offer_header my-3 mobile_visible">
                <?php if (function_exists('the_breadcrumb')) : ?>
                    <?php the_breadcrumb(); ?>
                <?php endif; ?>

                <?php if ($date) : ?>
                    <div class="top_details">
                        <div class="row">
                            <div class="offer_details_wrapper">
                                <div class="taxonomy_info">
                                    <div class="row">
                                        <div class="col-5 col-md-4 item"><?php echo esc_html(__('DATE', 'akademiata')); ?>:</div>
                                        <div class="col-7 col-md-8 item"><?php echo esc_html($date); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($subtitle) : ?>
                    <div class="exam-subtitle">
                        <?php echo wp_kses_post($subtitle); ?>
                    </div>
                <?php endif; ?>

                <div class="main_title">
                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="offer_wrapper d-flex flex-column-reverse flex-lg-row-reverse">
            <div class="col-lg-6">
                <div class="offer_body">

                    <?php if (!$is_mobile) : ?>
                        <div class="offer_header my-3 desktop_visible">
                            <?php if (function_exists('the_breadcrumb')) : ?>
                                <?php the_breadcrumb(); ?>
                            <?php endif; ?>

                            <?php if ($date) : ?>
                                <div class="top_details">
                                    <div class="row">
                                        <div class="offer_details_wrapper">
                                            <div class="taxonomy_info">
                                                <div class="row">
                                                    <div class="col-5 col-md-4 item"><?php echo esc_html(__('DATE', 'akademiata')); ?>:</div>
                                                    <div class="col-7 col-md-8 item"><?php echo esc_html($date); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($subtitle) : ?>
                                <div class="exam-subtitle">
                                    <?php echo wp_kses_post($subtitle); ?>
                                </div>
                            <?php endif; ?>

                            <div class="main_title">
                                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (has_excerpt()) : ?>
                        <div class="wysiwyg">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($listen_url)) : ?>
                        <a href="<?php echo esc_url($listen_url); ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="button-sing_up"><?php _e('LISTEN', 'akademiata'); ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <?php if (has_post_thumbnail()) :
                    $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                    $desktop_size = 'program_banner';
                    $mobile_size  = 'specialization_card_thumb';

                    $image_url_mobile  = wp_get_attachment_image_src($thumbnail_id, $mobile_size)[0] ?? '';
                    $image_url_desktop = wp_get_attachment_image_src($thumbnail_id, $desktop_size)[0] ?? '';
                    ?>
                    <div class="image_bg responsive-image" role="img"
                         data-mobile="<?php echo esc_url($image_url_mobile); ?>"
                         data-desktop="<?php echo esc_url($image_url_desktop); ?>"
                         style="background-image: url('<?php echo esc_url($image_url_desktop); ?>');">
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<section class="single-podcast-ata-content my-5">
    <div class="container">
        <div class="wysiwyg">
            <?php the_content(); ?>
        </div>
    </div>
</section>

