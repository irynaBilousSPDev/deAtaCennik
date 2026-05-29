<?php
/* Template part for single Podcast ATA (podcast-ata). Content is editable via ACF; only fixed labels keep defaults. */

if (!function_exists('akademiata_podcast_icon')) {
    /* Inline SVG icon by key; admin picks the key in ACF select fields. */
    function akademiata_podcast_icon($key, $size = 24) {
        $paths = array(
            'clock'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'youtube'   => '<path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><path d="M9.75 15.02l5.75-3.27-5.75-3.27v6.54z" fill="currentColor"/>',
            'chat'      => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
            'portfolio' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>',
            'pencil'    => '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/>',
            'building'  => '<path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>',
            'layers'    => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
        );

        if (empty($key) || !isset($paths[$key])) {
            return '';
        }

        return '<svg width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$key] . '</svg>';
    }
}

if (!function_exists('akademiata_podcast_accent')) {
    /* Wrap the first occurrence of $accent inside $text with a styled span. */
    function akademiata_podcast_accent($text, $accent) {
        $text = (string) $text;
        $accent = trim((string) $accent);

        if ($accent === '') {
            return esc_html($text);
        }

        $pos = function_exists('mb_stripos') ? mb_stripos($text, $accent) : stripos($text, $accent);

        if ($pos === false) {
            return esc_html($text);
        }

        $len    = function_exists('mb_strlen') ? mb_strlen($accent) : strlen($accent);
        $before = function_exists('mb_substr') ? mb_substr($text, 0, $pos) : substr($text, 0, $pos);
        $match  = function_exists('mb_substr') ? mb_substr($text, $pos, $len) : substr($text, $pos, $len);
        $after  = function_exists('mb_substr') ? mb_substr($text, $pos + $len) : substr($text, $pos + $len);

        return esc_html($before) . '<span class="accent">' . esc_html($match) . '</span>' . esc_html($after);
    }
}

$acf = function_exists('get_field');

/* ---------------- HERO ---------------- */
/* Fixed labels keep a default; all real content stays empty until edited in ACF. */
$hero_live_label   = ($acf ? get_field('hero_live_label') : '') ?: 'ATA LIVE';
$episode_datetime  = $acf ? get_field('episode_datetime') : '';
$hero_title        = $acf ? get_field('hero_title') : '';
$hero_title_accent = $acf ? get_field('hero_title_accent') : '';
$hero_lead         = $acf ? get_field('hero_lead') : '';
$hero_cta_text     = ($acf ? get_field('hero_cta_text') : '')   ?: 'Zapisz się';
$hero_cta_anchor   = ($acf ? get_field('hero_cta_anchor') : '') ?: '#zapisz';
$hero_sticker_count = $acf ? get_field('hero_sticker_count') : '';
$hero_sticker_sub   = $acf ? get_field('hero_sticker_subtext') : '';
$hero_meta          = $acf ? get_field('hero_meta') : array();

/* Hero image: ACF field → featured image → static fallback. */
$hero_image_url = '';
$hero_image_alt = get_the_title();
$hero_image_acf = $acf ? get_field('hero_image') : '';
if (is_array($hero_image_acf) && !empty($hero_image_acf['url'])) {
    $hero_image_url = $hero_image_acf['url'];
    $hero_image_alt = !empty($hero_image_acf['alt']) ? $hero_image_acf['alt'] : $hero_image_alt;
} elseif (has_post_thumbnail()) {
    $thumbnail_id   = get_post_thumbnail_id(get_the_ID());
    $hero_image_url = wp_get_attachment_image_src($thumbnail_id, 'program_banner')[0] ?? '';
}
if (empty($hero_image_url)) {
    $hero_image_url = get_template_directory_uri() . '/static/img/hero-podcast.png';
}

/* ---------------- TOPICS ---------------- */
$topics_eyebrow = ($acf ? get_field('topics_eyebrow') : '') ?: 'O pierwszym odcinku';
$topics_heading = $acf ? get_field('topics_heading') : '';
$topics_intro   = $acf ? get_field('topics_intro') : '';
$topics         = $acf ? get_field('topics') : array();

/* ---------------- GUESTS ---------------- */
$guests_eyebrow = ($acf ? get_field('guests_eyebrow') : '') ?: 'Nasi goście';
$guests_heading = $acf ? get_field('guests_heading') : '';
$guests_intro   = $acf ? get_field('guests_intro') : '';
$guests         = $acf ? get_field('guests') : array();

/* ---------------- SIGN-UP ---------------- */
$signup_eyebrow        = ($acf ? get_field('signup_eyebrow') : '') ?: 'Dobry kierunek';
$signup_heading        = $acf ? get_field('signup_heading') : '';
$signup_heading_accent = $acf ? get_field('signup_heading_accent') : '';
$signup_text           = $acf ? get_field('signup_text') : '';
$signup_form_heading   = ($acf ? get_field('signup_form_heading') : '') ?: 'Zapisz się na ATA LIVE';
$signup_form_note      = $acf ? get_field('signup_form_note') : '';
$signup_form_shortcode = ($acf ? get_field('signup_form_shortcode') : '') ?: '[contact-form-7 id="f764c85" title="ATA LIVE"]';
$signup_perks          = $acf ? get_field('signup_perks') : array();

/* Form subtitle = single date field + optional note, joined by a dot. */
$signup_form_sub_parts = array_filter(array($episode_datetime, $signup_form_note), 'strlen');
$signup_form_sub       = implode(' · ', $signup_form_sub_parts);
?>

<div class="podcast-ata-page">
    <!-- ===================== HERO ===================== -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content">
                <div class="tags">
                    <?php if (!empty($hero_live_label)) : ?>
                        <span class="pill pill-outline pill-live"><?php echo esc_html($hero_live_label); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($episode_datetime)) : ?>
                        <span class="pill pill-filled"><?php echo esc_html($episode_datetime); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($hero_title)) : ?>
                    <h1><?php echo akademiata_podcast_accent($hero_title, $hero_title_accent); ?></h1>
                <?php endif; ?>
                <?php if (!empty($hero_lead)) : ?>
                    <p class="hero-lead"><?php echo esc_html($hero_lead); ?></p>
                <?php endif; ?>
                <?php if (!empty($hero_cta_text)) : ?>
                    <a href="<?php echo esc_url($hero_cta_anchor); ?>" class="cta-btn">
                        <?php echo esc_html($hero_cta_text); ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($hero_meta)) : ?>
                    <div class="hero-meta">
                        <?php foreach ($hero_meta as $meta) :
                            $meta_text = $meta['text'] ?? '';
                            $meta_url  = $meta['url'] ?? '';
                            $meta_icon = akademiata_podcast_icon($meta['icon'] ?? '', 14);
                            ?>
                            <span>
                                <?php echo $meta_icon; ?>
                                <?php if (!empty($meta_url)) : ?>
                                    <a href="<?php echo esc_url($meta_url); ?>"><?php echo esc_html($meta_text); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($meta_text); ?>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="hero-visual" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                <?php if (!empty($hero_image_url)) : ?>
                    <img src="<?php echo esc_url($hero_image_url); ?>" alt="<?php echo esc_attr($hero_image_alt); ?>" class="hero-photo-img">
                <?php endif; ?>
                <?php if (!empty($hero_sticker_count) || !empty($hero_sticker_sub)) : ?>
                    <div class="hero-sticker" aria-hidden="true">
                        <div class="hero-sticker-avatars">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="hero-sticker-text">
                            <?php echo esc_html($hero_sticker_count); ?>
                            <?php if (!empty($hero_sticker_sub)) : ?>
                                <small><?php echo esc_html($hero_sticker_sub); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===================== TOPICS ===================== -->
    <section class="topics" id="o-czym">
        <div class="container">
            <div class="section-head">
                <?php if (!empty($topics_eyebrow)) : ?>
                    <span class="section-eyebrow"><?php echo esc_html($topics_eyebrow); ?></span>
                <?php endif; ?>
                <?php if (!empty($topics_heading)) : ?>
                    <h2><?php echo esc_html($topics_heading); ?></h2>
                <?php endif; ?>
                <?php if (!empty($topics_intro)) : ?>
                    <p><?php echo esc_html($topics_intro); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($topics)) : ?>
                <div class="topics-grid">
                    <?php foreach ($topics as $topic) : ?>
                        <article class="topic-tile">
                            <?php $topic_icon = akademiata_podcast_icon($topic['icon'] ?? '', 26); ?>
                            <?php if (!empty($topic_icon)) : ?>
                                <div class="topic-icon" aria-hidden="true"><?php echo $topic_icon; ?></div>
                            <?php endif; ?>
                            <?php if (!empty($topic['title'])) : ?>
                                <h3><?php echo esc_html($topic['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($topic['text'])) : ?>
                                <p><?php echo esc_html($topic['text']); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===================== GUESTS ===================== -->
    <section class="guests" id="goscie">
        <div class="container">
            <div class="guests-head">
                <div>
                    <?php if (!empty($guests_eyebrow)) : ?>
                        <span class="section-eyebrow"><?php echo esc_html($guests_eyebrow); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($guests_heading)) : ?>
                        <h2><?php echo nl2br(esc_html($guests_heading)); ?></h2>
                    <?php endif; ?>
                </div>
                <?php if (!empty($guests_intro)) : ?>
                    <p><?php echo esc_html($guests_intro); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($guests)) : ?>
                <div class="guests-scroll" role="list">
                    <?php foreach ($guests as $guest) :
                        $guest_photo = $guest['photo'] ?? '';
                        $guest_photo_url = is_array($guest_photo) ? ($guest_photo['url'] ?? '') : '';
                        $guest_photo_alt = is_array($guest_photo) && !empty($guest_photo['alt']) ? $guest_photo['alt'] : ($guest['name'] ?? '');
                        ?>
                        <article class="guest-card" role="listitem">
                            <div class="guest-photo">
                                <?php if (!empty($guest_photo_url)) : ?>
                                    <img src="<?php echo esc_url($guest_photo_url); ?>" alt="<?php echo esc_attr($guest_photo_alt); ?>">
                                <?php else : ?>
                                    Zdjęcie 1:1
                                <?php endif; ?>
                            </div>
                            <div class="guest-info">
                                <?php if (!empty($guest['name'])) : ?>
                                    <h3 class="guest-name"><?php echo esc_html($guest['name']); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($guest['role'])) : ?>
                                    <p class="guest-role"><?php echo esc_html($guest['role']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($guest['bio'])) : ?>
                                    <p class="guest-bio"><?php echo esc_html($guest['bio']); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===================== SIGN-UP ===================== -->
    <section class="signup" id="zapisz">
        <div class="container signup-inner">
            <div class="signup-about">
                <?php if (!empty($signup_eyebrow)) : ?>
                    <span class="section-eyebrow"><?php echo esc_html($signup_eyebrow); ?></span>
                <?php endif; ?>
                <?php if (!empty($signup_heading)) : ?>
                    <h2><?php echo akademiata_podcast_accent($signup_heading, $signup_heading_accent); ?></h2>
                <?php endif; ?>
                <?php if (!empty($signup_text)) : ?>
                    <div class="signup-text"><?php echo wp_kses_post($signup_text); ?></div>
                <?php endif; ?>

                <?php if (!empty($signup_perks)) : ?>
                    <ul class="signup-perks">
                        <?php foreach ($signup_perks as $perk) : ?>
                            <?php if (!empty($perk['text'])) : ?>
                                <li><span class="check" aria-hidden="true">✓</span><?php echo esc_html($perk['text']); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="signup-form">
                <?php if (!empty($signup_form_heading)) : ?>
                    <h3><?php echo esc_html($signup_form_heading); ?></h3>
                <?php endif; ?>
                <?php if (!empty($signup_form_sub)) : ?>
                    <p class="form-sub"><?php echo esc_html($signup_form_sub); ?></p>
                <?php endif; ?>

                <?php if (!empty($signup_form_shortcode)) : ?>
                    <div class="wysiwyg">
                        <?php echo do_shortcode($signup_form_shortcode); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
