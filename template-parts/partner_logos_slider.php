<?php
// Get repeater rows from ACF Footer Settings options page
$partner_logos = get_field('partner_logos', 'option');
?>

<section class="section_partner_logos left_spacee" style="background-color: white">
    <div class="partner_logos_slider">
        <?php if (!empty($partner_logos) && is_array($partner_logos)) : ?>
            <?php foreach ($partner_logos as $row) :
                $logo = $row['logo_image'] ?? null;
                if (!$logo || empty($logo['url'])) {
                    continue;
                }

                $src   = esc_url($logo['url']);
                $alt   = esc_attr($logo['alt'] ?? '');
                $width = !empty($logo['width']) ? (int) $logo['width'] : '';
                $height = !empty($logo['height']) ? (int) $logo['height'] : '';
                ?>
                <div class="logo-slide">
                    <img
                            src="<?php echo $src; ?>"
                            alt="<?php echo $alt; ?>"
                            <?php if ($width) : ?>width="<?php echo $width; ?>"<?php endif; ?>
                            <?php if ($height) : ?>height="<?php echo $height; ?>"<?php endif; ?>
                            loading="lazy"
                            decoding="async"
                    />
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>



