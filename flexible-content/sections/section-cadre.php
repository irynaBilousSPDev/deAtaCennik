<?php
/**
 * Template part: Cadre Section (ACF Flexible layout: cadre_section)
 */

$title = (string)get_sub_field('title');
$title_tag = (string)get_sub_field('title_tag');
$subtitle = (string)get_sub_field('subtitle');
$subtitle_tag = (string)get_sub_field('subtitle_tag');
$text_align = (string)get_sub_field('text_align');
$people_ids = get_sub_field('people') ?: [];

if (empty($people_ids) || !is_array($people_ids)) {
    return;
}

$title_tag = in_array($title_tag, ['h1', 'h2'], true) ? $title_tag : 'h2';
$subtitle_tag = in_array($subtitle_tag, ['h3'], true) ? $subtitle_tag : 'h3';

$text_align = in_array($text_align, ['left', 'center', 'right'], true) ? $text_align : 'center';
$section_align_class = 'cadre-section--align-' . $text_align;

$count = count($people_ids);
$grid_class = ($count <= 3) ? 'cadre-grid--centered' : 'cadre-grid--four';

// Unique per flexible row to avoid duplicate modal IDs across multiple sections
$section_uid = 'cadre-sec-' . (int)get_row_index();
?>

<section class="cadre-section <?php echo esc_attr($section_align_class); ?>" data-cadre-section>
    <div class="container">

        <?php if (!empty($title)) : ?>
            <?php printf('<%1$s class="cadre-section__title">%2$s</%1$s>', $title_tag, esc_html($title)); ?>
        <?php endif; ?>

        <?php if (!empty($subtitle)) : ?>
            <?php printf('<%1$s class="cadre-section__subtitle">%2$s</%1$s>', $subtitle_tag, esc_html($subtitle)); ?>
        <?php endif; ?>

        <div class="cadre-grid <?php echo esc_attr($grid_class); ?>">
            <?php foreach ($people_ids as $person_id) : ?>
                <?php
                $person_id = (int)$person_id;

                $name = (string)get_the_title($person_id);
                $role = (string)get_field('cadre_role', $person_id);

                $bio_acf = (string)get_field('cadre_bio', $person_id);
                $post_obj = get_post($person_id);
                $bio_editor = $post_obj ? (string)$post_obj->post_content : '';

                $bio = !empty(trim(wp_strip_all_tags($bio_acf))) ? $bio_acf : $bio_editor;
                $has_bio = !empty(trim(wp_strip_all_tags($bio)));

                $thumb_id = get_post_thumbnail_id($person_id);
                $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '';

                // Unique modal ID (per-section + person)
                $modal_id = 'cadre-modal-' . $section_uid . '-' . $person_id;
                ?>

                <article class="cadre-card">
                    <?php if (!empty($thumb_url)) : ?>
                        <div class="cadre-card__media">
                            <img
                                    class="cadre-card__img"
                                    src="<?php echo esc_url($thumb_url); ?>"
                                    alt="<?php echo esc_attr($name); ?>"
                                    loading="lazy"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="cadre-card__meta">
                        <?php if (!empty($role)) : ?>
                            <div class="cadre-card__role"><?php echo esc_html($role); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($name)) : ?>
                            <div class="cadre-card__name"><?php echo esc_html($name); ?></div>
                        <?php endif; ?>

                        <?php if ($has_bio) : ?>
                            <button
                                    type="button"
                                    class="cadre-card__bio-btn"
                                    data-cadre-open
                                    data-target="#<?php echo esc_attr($modal_id); ?>"
                            >
                                BIO &gt;&gt;&gt;
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($has_bio) : ?>
                        <div class="cadre-modal" id="<?php echo esc_attr($modal_id); ?>" aria-hidden="true"
                             data-cadre-modal>
                            <div class="cadre-modal__overlay" data-cadre-close></div>

                            <div class="cadre-modal__dialog" role="dialog" aria-modal="true"
                                 aria-label="<?php echo esc_attr($name); ?>">
                                <button class="cadre-modal__close" type="button" data-cadre-close aria-label="Close">×
                                </button>

                                <div class="cadre-modal__content">
                                    <div class="cadre-modal__inner">
                                        <div class="cadre-modal__text">
                                            <?php if (!empty($role)) : ?>
                                                <div class="cadre-modal__role"><?php echo esc_html($role); ?></div>
                                            <?php endif; ?>

                                            <?php if (!empty($name)) : ?>
                                                <div class="cadre-modal__name"><?php echo esc_html($name); ?></div>
                                            <?php endif; ?>

                                            <div class="cadre-modal__bio">
                                                <?php echo wp_kses_post($bio); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endif; ?>
                </article>

            <?php endforeach; ?>
        </div>
    </div>
</section>