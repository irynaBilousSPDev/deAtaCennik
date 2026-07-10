(function () {
    const config = window.akademiataOfferDailyInterest;
    if (!config) {
        return;
    }

    const notice = document.getElementById('offer-daily-interest');
    if (!notice) {
        return;
    }

    const closeBtn = notice.querySelector('.offer-daily-interest__close');
    const storagePrefix = config.storagePrefix || 'akademiata_offer_daily_interest';
    const postId = config.postId || notice.closest('.single-offer')?.id?.replace('post-', '') || '';

    function todayKey() {
        return new Date().toISOString().slice(0, 10);
    }

    const dismissedKey = `${storagePrefix}_dismissed_${postId}_${todayKey()}`;

    function isDismissed() {
        try {
            return window.sessionStorage.getItem(dismissedKey) === '1';
        } catch (error) {
            return false;
        }
    }

    function dismissNotice() {
        notice.hidden = true;
        notice.classList.remove('is-visible');

        try {
            window.sessionStorage.setItem(dismissedKey, '1');
        } catch (error) {
            // Ignore private mode storage errors.
        }
    }

    if (isDismissed()) {
        dismissNotice();
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', dismissNotice);
    }
}());
