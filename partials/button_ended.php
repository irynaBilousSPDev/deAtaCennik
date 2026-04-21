<!-- DISABLED BUTTON + TOOLTIP -->
<div id="btn_ended" class="btn_ended_container">
    <div class="btn-ended-wrapper">
        <button class="btn-ended" disabled>
            <?php _e('ZAKOŃCZONA', 'akademiata'); ?>
            <span class="btn-ended__icon">?</span>
        </button>
    </div>

    <div class="btn-ended__tooltip">
        <p><?php _e('Wkrótce ruszy rekrutacja.', 'akademiata'); ?></p>
        <p>
            <a href="/kontakt">
                <?php _e('Zapisz się, aby otrzymać powiadomienie o jej starcie.', 'akademiata'); ?>
            </a>
        </p>
    </div>
</div>