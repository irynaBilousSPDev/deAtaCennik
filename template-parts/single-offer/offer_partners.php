<?php
$offer_partners = get_query_var('offer_partners', []);
$offer_partners = is_array($offer_partners) ? $offer_partners : [];

$partners_logo = $offer_partners['partners_logo'] ?? [];
$partners_logo = is_array($partners_logo) ? $partners_logo : [];

$is_bachelor_master = in_array(get_post_type(), array('bachelor', 'master'), true);
$all_logos = array();

if ($is_bachelor_master) {
    $all_logos[] = array(
        'url' => get_template_directory_uri() . '/assets/dist/img/ranking-perspektywy-2026-1-miejsce.png',
        'alt' => akademiata_get_theme_lang_string('offer_ranking_perspektywy_alt'),
        'class' => 'partners_logo__achievement',
        'has_tooltip' => true,
        'tooltip_full' => akademiata_get_theme_lang_string('offer_ranking_perspektywy_tooltip_full'),
        'tooltip_short' => akademiata_get_theme_lang_string('offer_ranking_perspektywy_tooltip_short'),
    );
}

foreach ($partners_logo as $logo) {
    $image = !empty($logo['image']) && is_array($logo['image']) ? $logo['image'] : [];
    $image_url = isset($image['sizes']['partner_logo']) ? ($image['url'] ?? '') : '';

    if (empty($image_url)) {
        continue;
    }

    $all_logos[] = array(
        'url' => $image_url,
        'alt' => !empty($image['alt']) ? $image['alt'] : __('Partner Logo', 'akademiata'),
        'class' => '',
        'has_tooltip' => false,
    );
}

if (!$is_bachelor_master && empty($all_logos)) {
    return;
}

$logo_count = count($all_logos);
$use_slider = $logo_count > 3;
$display_logos = $use_slider ? array_merge($all_logos, $all_logos) : $all_logos;

$section_title = $is_bachelor_master
    ? akademiata_get_theme_lang_string('offer_achievements_partners_title')
    : ($offer_partners['title'] ?? __('PARTNERZY KIERUNKU', 'akademiata'));
?>

<div class="offer_partners <?php echo $use_slider ? 'has-scroll' : ''; ?> my-5">
    <h2><?php echo esc_html($section_title); ?></h2>

    <div class="partners_logo">
        <?php foreach ($display_logos as $logo) :
            if (!empty($logo['has_tooltip'])) : ?>
                <span class="ranking-badge-tooltip" tabindex="0">
                    <img src="<?php echo esc_url($logo['url']); ?>"
                         alt="<?php echo esc_attr($logo['alt']); ?>"
                         class="<?php echo esc_attr($logo['class']); ?>">
                    <span class="ranking-badge-tooltip__popup ranking-badge-tooltip__popup--full" role="tooltip">
                        <ul class="ranking-badge-tooltip__list">
                            <?php foreach (array_filter(array_map('trim', explode("\n", $logo['tooltip_full']))) as $line) : ?>
                                <li><?php echo esc_html($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </span>
                    <span class="ranking-badge-tooltip__popup ranking-badge-tooltip__popup--short" role="tooltip">
                        <ul class="ranking-badge-tooltip__list ranking-badge-tooltip__list--compact">
                            <?php foreach (array_filter(array_map('trim', explode("\n", $logo['tooltip_short']))) as $line) : ?>
                                <li><?php echo esc_html($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </span>
                </span>
            <?php else : ?>
                <img src="<?php echo esc_url($logo['url']); ?>"
                     alt="<?php echo esc_attr($logo['alt']); ?>"
                     <?php echo !empty($logo['class']) ? 'class="' . esc_attr($logo['class']) . '"' : ''; ?>>
            <?php endif;
        endforeach; ?>
    </div>
</div>
