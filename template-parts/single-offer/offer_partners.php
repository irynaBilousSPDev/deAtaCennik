<?php
// Fetch `offer_partners` data efficiently
$offer_partners = get_query_var('offer_partners', []);
$offer_partners = is_array($offer_partners) ? $offer_partners : [];

// Get Partner Logos (Ensure it's an array)
$partners_logo = $offer_partners['partners_logo'] ?? [];
$partners_logo = is_array($partners_logo) ? $partners_logo : [];
?>

<div class="offer_partners <?php echo (count($partners_logo) >= 5) ? 'has-scroll' : ''; ?> my-5">
    <?php if (!empty($partners_logo)) : ?>
        <h2><?php echo $offer_partners['title'] ?? __('PARTNERZY KIERUNKU', 'akademiata'); ?></h2>

        <div class="partners_logo">
            <?php foreach ($partners_logo as $logo) :
                $image = !empty($logo['image']) && is_array($logo['image']) ? $logo['image'] : [];

                // Get Image URL & ALT Text
                $image_url = isset($image['sizes']['partner_logo']) ? esc_url($image['url']) : '';
                $alt_text = !empty($image['alt']) ? esc_attr($image['alt']) : __('Partner Logo', 'akademiata');

                // Output image only if a valid URL exists
                if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>"
                         alt="<?php echo $alt_text; ?>">
                <?php endif;
            endforeach; ?>
        </div>
    <?php endif; ?>
</div>
