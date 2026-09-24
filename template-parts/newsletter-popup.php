<?php
$key = get_query_var('nl_popup_key');
$config = get_query_var('nl_popup_config');
$form_html = get_query_var('nl_popup_form');
$auto = (bool) get_query_var('nl_popup_auto');

if (!is_string($key) || $key === '' || !is_array($config)) {
    return;
}
$form_html = is_string($form_html) ? $form_html : '';

$id = 'nl-popup-' . $key;
$title_id = $id . '-title';
$title_html = akademiata_nl_popup_format_title($config['title'] ?? '', $config['amount'] ?? '');
$thanks_html = akademiata_nl_popup_format_thanks($config['thank_you'] ?? '', $config['amount'] ?? '');
$promo_html = akademiata_nl_popup_format_promo(
    $config['promo_note'] ?? '',
    $config['promo_link'] ?? '',
    $config['promo_url'] ?? ''
);
$delay = isset($config['delay']) ? max(0, (int) $config['delay']) : 8;
$remember = isset($config['remember_days']) ? max(1, (int) $config['remember_days']) : 14;
$browse_url = akademiata_nl_popup_browse_url($key);
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
        </button>
        <div class="nl-popup__offer">
            <div class="nl-popup__head">
                <?php if (!empty($config['badge'])) : ?>
                    <span class="nl-popup__badge"><?php echo esc_html($config['badge']); ?></span>
                <?php endif; ?>
                <?php if ($title_html !== '') : ?>
                    <h2 class="nl-popup__title" id="<?php echo esc_attr($title_id); ?>"><?php echo $title_html; ?></h2>
                <?php endif; ?>
                <?php if (!empty($config['lead'])) : ?>
                    <p class="nl-popup__lead"><?php echo esc_html($config['lead']); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($form_html !== '') : ?>
                <div class="nl-popup__form-wrap">
                    <div class="nl-popup__cf7">
                        <?php echo $form_html; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($promo_html !== '') : ?>
                <p class="nl-popup__promo"><?php echo $promo_html; ?></p>
            <?php endif; ?>
        </div>
        <div class="nl-popup__thanks" hidden>
            <div class="nl-popup__thanks-icon" aria-hidden="true">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"></path></svg>
            </div>
            <h2 class="nl-popup__thanks-title">
                <?php echo esc_html__('Dziękujemy!', 'akademiata'); ?><br>
                <?php echo esc_html__('Mamy Twoje zgłoszenie.', 'akademiata'); ?>
            </h2>
            <?php if ($thanks_html !== '') : ?>
                <p class="nl-popup__thanks-text"><?php echo $thanks_html; ?></p>
            <?php endif; ?>
            <?php if ($browse_url !== '') : ?>
                <a class="nl-popup__thanks-cta" href="<?php echo esc_url($browse_url); ?>">
                    <span><?php esc_html_e('Przeglądaj kierunki', 'akademiata'); ?></span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
