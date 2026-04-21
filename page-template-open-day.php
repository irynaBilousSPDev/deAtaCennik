<?php
/*
Template Name: Open Day Page
*/

get_header();

$page_id = get_the_ID();

$event_date = get_field('open_day_event_date', $page_id);
$info_text = get_field('open_day_info_text', $page_id);
$hero_image = get_field('open_day_hero_image', $page_id);
$schedule_title = get_field('open_day_schedule_title', $page_id);
$schedule_items = get_field('open_day_schedule_items', $page_id);
$form_shortcode = get_field('open_day_form_shortcode', $page_id);

$theme_uri = get_template_directory_uri();
$top_heading_image = $theme_uri . '/static/img/open-day-heading.svg';
$bottom_heading_image = $theme_uri . '/static/img/open-day-form-heading.svg';
?>

    <section class="open-day">
        <div class="container">
            <div class="row top-row">
                <div class="col-12 col-lg-5">
                    <div class="left-col">
                        <h1 class="open-day-title">
                            <span class="sr-only">Dzień Otwarty</span>
                            <img
                                    src="<?php echo esc_url(get_template_directory_uri() . '/static/img/open-day-heading.svg'); ?>"
                                    alt=""
                                    aria-hidden="true"
                            >
                        </h1>

                        <?php if ($event_date) : ?>
                            <div class="date-text">
                                <svg viewBox="0 0 700 127" preserveAspectRatio="xMinYMid meet">
                                    <text
                                            x="0"
                                            y="100"
                                            fill="#f5682c"
                                            stroke="#f5682c"
                                            stroke-width="1"
                                            paint-order="stroke"
                                            font-size="127"
                                            font-family="'Nunito Sans', sans-serif"
                                            font-weight="900"
                                    >
                                        <?php echo $event_date; ?>
                                    </text>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <?php if ($info_text) : ?>
                            <div class="info-text">
                                <?php echo $info_text; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="right-col">
                        <?php if (!empty($hero_image)) : ?>
                            <div class="hero-box">
                                <?php
                                echo wp_get_attachment_image(
                                        $hero_image['ID'],
                                        'full',
                                        false,
                                        [
                                                'class' => 'img-fluid',
                                                'alt'   => esc_attr($hero_image['alt'] ?: __('Open Day image', 'akademiata')),
                                        ]
                                );
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($schedule_title || !empty($schedule_items)) : ?>
                            <div class="schedule">
                                <?php if ($schedule_title) : ?>
                                    <h2 class="schedule-title">
                                        <?php echo $schedule_title; ?>
                                    </h2>
                                <?php endif; ?>

                                <?php if (!empty($schedule_items)) : ?>
                                    <div class="timeline">
                                        <?php foreach ($schedule_items as $item) :
                                            $text = $item['text'] ?? '';

                                            if (empty(trim(wp_strip_all_tags($text)))) {
                                                continue;
                                            }
                                            ?>
                                            <div class="timeline-item">
                                                <div class="dot"></div>
                                                <div class="timeline-text">
                                                    <?php echo $text; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row bottom-row">
                <div class="col-12 col-lg-5">
                    <div class="left-col bottom-left">
                        <div class="heading heading-bottom">
                            <img
                                    src="<?php echo esc_url($bottom_heading_image); ?>"
                                    alt="<?php esc_attr_e('Zapisz się na dzień otwarty', 'akademiata'); ?>"
                                    class="img-fluid"
                            >
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="form-side">
                        <div class="surface"></div>

                        <div class="form-inner">
                            <?php if ($form_shortcode) : ?>
                                <?php echo do_shortcode($form_shortcode); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>