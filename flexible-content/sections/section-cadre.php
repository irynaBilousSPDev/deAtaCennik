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

                $modal_photo = get_field('cadre_modal_photo', $person_id);

                if (is_array($modal_photo)) {
                    $modal_photo_id = $modal_photo['ID'];
                } else {
                    $modal_photo_id = (int)$modal_photo;
                }

                $modal_photo_url = $modal_photo_id
                    ? wp_get_attachment_image_url($modal_photo_id, 'full')
                    : '';
                


                // Unique modal ID (per-section + person)
                $modal_id = 'cadre-modal-' . $section_uid . '-' . $person_id;

                // Socials
                $socials = get_field('cadre_socials', $person_id);
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
                                        <div class="cadre-modal__photo-wrap">
                                            <?php if (!empty($modal_photo_url)) : ?>
                                                <img
                                                        class="cadre-modal__photo"
                                                        src="<?php echo esc_url($modal_photo_url); ?>"
                                                        alt="<?php echo esc_attr($name); ?>"
                                                        loading="lazy"
                                                >
                                            <?php endif; ?>
                                            <?php if (!empty($socials) && is_array($socials)) : ?>
                                                <div class="cadre-modal__icons">
                                                    <?php foreach ($socials as $row) :
                                                        $type = $row['type'] ?? '';
                                                        $url = $row['url'] ?? '';
                                                        if (!$url) continue;
                                                        ?>
                                                        <a
                                                                class="cadre-icon cadre-icon--<?php echo esc_attr($type); ?>"
                                                                href="<?php echo esc_url($url); ?>"
                                                                target="_blank"
                                                                rel="noopener"
                                                                aria-label="<?php echo esc_attr($type); ?>"
                                                        >
                                                            <?php
                                                            switch ($type) {
                                                                case 'linkedin':
                                                                    echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.11 1 2.5 1 4.98 2.12 4.98 3.5zM0 8h5v16H0V8zm7.5 0h4.7v2.2h.07c.65-1.2 2.25-2.47 4.63-2.47 4.95 0 5.87 3.26 5.87 7.5V24h-5V15.6c0-2-.03-4.57-2.78-4.57-2.78 0-3.2 2.17-3.2 4.42V24h-5V8z"/></svg>';
                                                                    break;

                                                                case 'email':
                                                                    echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M0 4v16h24V4H0zm12 9L2 6h20l-10 7z"/></svg>';
                                                                    break;

                                                                case 'website':
                                                                    echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm7.93 9h-3.18a15.7 15.7 0 00-1.38-5.01A8.03 8.03 0 0119.93 11zM12 4c1.38 1.77 2.36 4.17 2.64 7H9.36C9.64 8.17 10.62 5.77 12 4zM4.07 13h3.18c.25 1.81.8 3.53 1.63 5.01A8.03 8.03 0 014.07 13zm3.18-2H4.07a8.03 8.03 0 014.81-5.01A15.7 15.7 0 007.25 11zm4.75 9c-1.38-1.77-2.36-4.17-2.64-7h5.28c-.28 2.83-1.26 5.23-2.64 7zm3.12-1.99A15.7 15.7 0 0016.75 13h3.18a8.03 8.03 0 01-4.81 5.01z"/></svg>';
                                                                    break;

                                                                default:
                                                                    echo esc_html(mb_substr((string)$type, 0, 1));
                                                            }
                                                            ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

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