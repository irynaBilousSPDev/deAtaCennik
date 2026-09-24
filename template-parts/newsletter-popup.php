<?php
$key = get_query_var('nl_popup_key');
$config = get_query_var('nl_popup_config');
$form_html = get_query_var('nl_popup_form');
$auto = (bool) get_query_var('nl_popup_auto');

if (!is_string($key) || $key === '' || !is_array($config) || !is_string($form_html) || $form_html === '') {
    return;
}

$id = 'nl-popup-' . $key;
$title_id = $id . '-title';
$title_html = akademiata_nl_popup_format_title($config['title'] ?? '', $config['amount'] ?? '');
$promo_html = akademiata_nl_popup_format_promo(
    $config['promo_note'] ?? '',
    $config['promo_link'] ?? '',
    $config['promo_url'] ?? ''
);
$delay = isset($config['delay']) ? max(0, (int) $config['delay']) : 8;
$remember = isset($config['remember_days']) ? max(1, (int) $config['remember_days']) : 14;
?>
<div
    class="nl-popup"
    id="<?php echo esc_attr($id); ?>"
    data-nl-popup="<?php echo esc_attr($key); ?>"
    data-nl-auto="<?php echo $auto ? '1' : '0'; ?>"
    data-nl-delay="<?php echo esc_attr((string) $delay); ?>"
    data-nl-remember="<?php echo esc_attr((string) $remember); ?>"
    hidden
>
    <div class="nl-popup__backdrop" data-nl-close tabindex="-1"></div>
    <div
        class="nl-popup__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="<?php echo esc_attr($title_id); ?>"
    >
        <button class="nl-popup__close" type="button" data-nl-close aria-label="<?php esc_attr_e('Zamknij', 'akademiata'); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
        <?php if (!empty($config['badge'])) : ?>
            <div class="nl-popup__badge"><?php echo esc_html($config['badge']); ?></div>
        <?php endif; ?>
        <?php if ($title_html !== '') : ?>
            <h2 class="nl-popup__title" id="<?php echo esc_attr($title_id); ?>"><?php echo $title_html; ?></h2>
        <?php endif; ?>
        <div class="nl-popup__form-wrap">
            <?php if (!empty($config['lead'])) : ?>
                <p class="nl-popup__lead"><?php echo esc_html($config['lead']); ?></p>
            <?php endif; ?>
            <div class="nl-popup__cf7">
                <?php echo $form_html; ?>
            </div>
        </div>
        <?php if (!empty($config['thank_you'])) : ?>
            <p class="nl-popup__thanks" hidden><?php echo esc_html($config['thank_you']); ?></p>
        <?php endif; ?>
        <?php if ($promo_html !== '') : ?>
            <p class="nl-popup__promo"><?php echo $promo_html; ?></p>
        <?php endif; ?>
    </div>
</div>
