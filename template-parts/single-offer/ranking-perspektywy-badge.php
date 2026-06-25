<?php
$context = get_query_var('ranking_badge_context', 'offer-header');
$context = in_array($context, array('offer-header', 'partners'), true) ? $context : 'offer-header';

$variant       = akademiata_get_ranking_perspektywy_badge_variant();
$tooltip_lines = array_filter(array_map('trim', explode("\n", akademiata_get_ranking_perspektywy_lang_string('tooltip_short', $variant))));
$tooltip_hint  = akademiata_get_theme_lang_string('offer_ranking_perspektywy_tooltip_hint');
$image_url     = akademiata_get_ranking_perspektywy_badge_image_url($variant);
$image_class   = $context === 'partners' ? 'partners_logo__achievement' : 'ranking-badge-tooltip__image';
?>
<span class="ranking-badge-tooltip ranking-badge-tooltip--<?php echo esc_attr($context); ?>"
      tabindex="0"
      aria-label="<?php echo esc_attr($tooltip_hint); ?>">
    <?php if ($image_url) : ?>
        <img src="<?php echo esc_url($image_url); ?>"
             alt="<?php echo esc_attr(akademiata_get_ranking_perspektywy_lang_string('alt', $variant)); ?>"
             class="<?php echo esc_attr($image_class); ?>">
    <?php else : ?>
        <span class="ranking-badge-tooltip__fallback">
            <strong><?php echo esc_html(akademiata_get_theme_lang_string('offer_ranking_perspektywy_headline')); ?></strong>
            <span><?php echo esc_html(akademiata_get_ranking_perspektywy_lang_string('subline', $variant)); ?></span>
        </span>
    <?php endif; ?>
    <span class="ranking-badge-tooltip__trigger" aria-hidden="true">
        <span class="ranking-badge-tooltip__icon">i</span>
        <span class="ranking-badge-tooltip__hint"><?php echo esc_html($tooltip_hint); ?></span>
    </span>
    <span class="ranking-badge-tooltip__popup" role="tooltip">
        <ul class="ranking-badge-tooltip__list">
            <?php foreach ($tooltip_lines as $line) : ?>
                <li><?php echo esc_html($line); ?></li>
            <?php endforeach; ?>
        </ul>
    </span>
</span>
