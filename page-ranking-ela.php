<?php
/**
 * Template Name: Ranking Ela Page
 */
get_header();
$acf_fields = get_fields();// Get all ACF fields
?>
<div class="ranking_ela_page">

    <?php
    $investment = $acf_fields['investment_section'];
    if ($investment): ?>
        <section class="investment_section py-5">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="investment_section__content">
                            <h1 class="title_section investment_section__title mb-5">
                                <strong><?php the_title(); ?></strong></h1>

                            <div class="investment_section__text">
                                <?php if (!empty($investment['headline_1'])): ?>
                                    <h3><?php echo esc_html($investment['headline_1']); ?></h3>
                                <?php endif; ?>

                                <?php if (!empty($investment['text'])): ?>
                                    <div class="mb-3">
                                        <?php echo $investment['text']; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($investment['headline_2'])): ?>
                                    <h3><?php echo esc_html($investment['headline_2']); ?></h3>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="investment_section__image text-center">
                            <?php if (!empty($investment['image'])): ?>
                                <img src="<?php echo esc_url($investment['image']['url']); ?>"
                                     alt="<?php echo esc_attr($investment['image']['alt'] ?? ''); ?>"
                                     class="img-fluid rounded">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php
    $ranking = $acf_fields['ranking_section'];
    if ($ranking) :
        ?>
        <section class="ranking_section py-5">
            <div class="container">

                <?php if (!empty($ranking['title'])) : ?>
                    <h2 class="ranking_section__title text-center title_section">
                        <?php echo $ranking['title']; ?>
                    </h2>
                <?php endif; ?>

                <div class="row mt-5 align-items-start">

                    <?php if (!empty($ranking['positions'])) : ?>
                        <?php foreach ($ranking['positions'] as $key => $position) : ?>

                            <?php
                            if ($key === 0) {
                                $colClass = 'col-lg-6 left';
                            } elseif ($key === 1) {
                                $colClass = 'col-lg-6 right';
                            }
                            ?>

                            <div class="<?php echo esc_attr($colClass); ?>">

                                <?php if (!empty($position['item'])) : ?>
                                    <?php foreach ($position['item'] as $item) :

                                        $place = !empty($item['place']) ? $item['place'] : '';
                                        $programs = !empty($item['programs']) ? $item['programs'] : []; ?>

                                        <div class="ranking_section__item text-center">
                                            <div class="ranking_section__position_wrapper mr-5">
                                                <p class="ranking_section__position"><?php echo _e('MIEJSCE', 'akademiata'); ?></p>
                                                <div class="ranking_section__badge">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/static/img/ranking_left.png"
                                                         alt="">
                                                    <span><?php echo esc_html($place); ?></span>
                                                    <img src="<?php echo get_template_directory_uri(); ?>/static/img/ranging_right.png"
                                                         alt="">
                                                </div>
                                                <p class="ranking_section__sub"><?php echo _e('W POLSCE', 'akademiata'); ?>:</p>
                                            </div>
                                            <?php if (!empty($programs)) : ?>
                                                <div class="ranking_section__col w-100" style="max-width: 430px;">
                                                    <?php foreach ($programs as $program) : ?>

                                                        <?php
                                                        $programTitle = !empty($program['title']) ? $program['title'] : '';
                                                        $programUrl = !empty($program['url']) ? $program['url'] : '';
                                                        ?>

                                                        <?php if (!empty($programTitle)) : ?>
                                                            <a href="<?php echo esc_url($programUrl); ?>">
                                                                <?php echo str_replace(['<p>', '</p>'], '', $programTitle); ?>
                                                            </a>
                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </div>
                                            <?php endif; ?>
                                        </div>

                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
                <div class="ranking_section__footer mt-5 text-center">
                    <?php if (!empty($ranking['logo'])) : ?>
                        <img src="<?php echo esc_url($ranking['logo']['url']); ?>"
                             alt="ELA Logo"
                             height="40"
                             class="mb-2">
                    <?php endif; ?>

                    <?php if (!empty($ranking['note'])) : ?>
                        <p class="ranking_section__note text-left">
                            <?php echo nl2br(esc_html($ranking['note'])); ?>
                        </p>
                    <?php endif; ?>
                </div>

            </div>
        </section>
    <?php endif; ?>

    <?php
    $features = $acf_fields['features_section'];
    if ($features) :
        $items = $features['features'] ?? [];
        ?>
        <section class="features_section my-5 py-5">
            <div class="container">

                <?php if (!empty($features['title'])) : ?>
                    <h2 class="title_section text-center mb-5">
                        <?php echo esc_html($features['title']); ?>
                    </h2>
                <?php endif; ?>

                <?php if (!empty($items)) : ?>
                    <div class="row g-4">
                        <?php foreach ($items as $item) :
                            $image = $item['image'] ?? null;
                            $title = $item['title'] ?? '';
                            $text = $item['text'] ?? '';
                            ?>
                            <div class="col-md-6 col-lg-4 mb-5">
                                <div class="features_section__item">
                                    <?php if (!empty($image)) : ?>
                                        <img src="<?php echo esc_url($image['url']); ?>"
                                             alt="<?php echo esc_attr($image['alt']); ?>"
                                             class="img-fluid">
                                    <?php endif; ?>

                                    <?php if (!empty($title)) : ?>
                                        <h3>
                                            <?php echo esc_html($title); ?>
                                        </h3>
                                    <?php endif; ?>
                                    <?php if (!empty($text)) : ?>
                                        <p class="features_section__text">
                                            <?php echo esc_html($text); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    <?php endif; ?>

    <?php
    $value = $acf_fields['value_section'];
    if ($value) :
        $image = $value['image'] ?? null;
        $title_right = $value['title_right'] ?? '';
        $text_right = $value['text_right'] ?? '';
        $title_bottom = $value['title_bottom'] ?? '';
        ?>
        <section class="value_section py-5 mb-5">
            <div class="container">
                <div class="row align-items-center g-5">

                    <div class="col-md-6 mb-5">
                        <div class="value_section__image">
                            <?php if (!empty($image)) : ?>
                                <img src="<?php echo esc_url($image['url']); ?>"
                                     alt="<?php echo esc_attr($image['alt']); ?>"
                                     class="img-fluid rounded">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="value_section__content p-5">
                            <?php if (!empty($title_right)) : ?>
                                <h2 class="value_section__title title_section mb-5">
                                    <?php echo $title_right; ?>
                                </h2>
                            <?php endif; ?>

                            <?php if (!empty($text_right)) : ?>
                                <div class="value_section__text">
                                    <?php echo $text_right; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <?php if (!empty($title_bottom)) : ?>
                    <h2 class="title_section text-center">
                        <?php echo $title_bottom; ?>
                    </h2>
                <?php endif; ?>

            </div>
        </section>
    <?php endif; ?>
    <!-- offers sliders -->

    <?php
    $post_types = ['bachelor', 'master'];
    set_query_var('post_types', $post_types);
    locate_template('./template-parts/offers_sliders.php', true, true);

    ?>
</div>
<?php get_footer(); ?>

