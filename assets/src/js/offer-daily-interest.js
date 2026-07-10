(function () {
    const config = window.akademiataOfferDailyInterest;
    if (!config || !config.restUrl || !config.postId) {
        return;
    }

    const notice = document.getElementById('offer-daily-interest');
    if (!notice) {
        return;
    }

    const titleEl = notice.querySelector('.offer-daily-interest__title');
    const messageEl = notice.querySelector('.offer-daily-interest__message');
    const closeBtn = notice.querySelector('.offer-daily-interest__close');
    const minCount = parseInt(config.minCount, 10) || 2;
    const storagePrefix = config.storagePrefix || 'akademiata_offer_daily_interest';
    const groupKey = config.groupKey || config.postId || '';

    function todayKey() {
        return new Date().toISOString().slice(0, 10);
    }

    const dismissedKey = `${storagePrefix}_dismissed_${groupKey}_${todayKey()}`;

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

    function applyTierClass(tier) {
        const tierNum = parseInt(tier, 10) || 0;

        notice.className = notice.className.replace(/\boffer-daily-interest--tier-\d+\b/g, '').trim();

        if (tierNum > 0) {
            notice.classList.add(`offer-daily-interest--tier-${tierNum}`);
        }
    }

    function showNotice(data) {
        if (!data || !data.show || isDismissed()) {
            return;
        }

        const count = typeof data.count === 'number' ? data.count : 0;
        if (count < minCount) {
            return;
        }

        applyTierClass(data.tier);

        if (titleEl) {
            titleEl.textContent = data.title || '';
            titleEl.hidden = !data.title;
        }

        if (messageEl) {
            messageEl.innerHTML = data.message_html || data.message || '';
            messageEl.hidden = !(data.message_html || data.message);
        }

        if (closeBtn && config.closeLabel) {
            closeBtn.setAttribute('aria-label', config.closeLabel);
        }

        notice.hidden = false;
        window.requestAnimationFrame(function () {
            notice.classList.add('is-visible');
        });
    }

    async function registerInterest() {
        const response = await fetch(config.restUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce,
            },
            credentials: 'same-origin',
            cache: 'no-store',
            body: JSON.stringify({
                post_id: config.postId,
                lang: config.lang,
            }),
        });

        if (!response.ok) {
            return null;
        }

        const data = await response.json();
        return data && typeof data === 'object' ? data : null;
    }

    if (isDismissed()) {
        dismissNotice();
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', dismissNotice);
    }

    registerInterest()
        .then(showNotice)
        .catch(function () {
            // Silent fail — notice is optional UX.
        });
}());
