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
    const delayMs = parseInt(config.delayMs, 10) || 4000;
    const storagePrefix = config.storagePrefix || 'akademiata_offer_daily_interest';
    const dismissedKey = `${storagePrefix}_dismissed_${config.postId}_${todayKey()}`;

    function todayKey() {
        return new Date().toISOString().slice(0, 10);
    }

    function getSessionToken() {
        const storageKey = `${storagePrefix}_session`;
        let token = '';

        try {
            token = window.sessionStorage.getItem(storageKey) || '';
        } catch (error) {
            token = '';
        }

        if (!token) {
            token = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `sess-${Date.now()}-${Math.random().toString(16).slice(2)}`;

            try {
                window.sessionStorage.setItem(storageKey, token);
            } catch (error) {
                // Ignore private mode storage errors.
            }
        }

        return token;
    }

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

    function showNotice(message) {
        if (!message || isDismissed()) {
            return;
        }

        if (titleEl && config.title) {
            titleEl.textContent = config.title;
        }

        if (messageEl) {
            messageEl.textContent = message;
        }

        if (closeBtn && config.closeLabel) {
            closeBtn.setAttribute('aria-label', config.closeLabel);
        }

        notice.hidden = false;
        window.requestAnimationFrame(() => {
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
            body: JSON.stringify({
                post_id: config.postId,
                session_token: getSessionToken(),
                lang: config.lang,
            }),
        });

        if (!response.ok) {
            return null;
        }

        const data = await response.json();
        return data && typeof data === 'object' ? data : null;
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', dismissNotice);
    }

    window.setTimeout(async () => {
        if (isDismissed()) {
            return;
        }

        try {
            const data = await registerInterest();
            if (!data || !data.show) {
                return;
            }

            const count = typeof data.count === 'number' ? data.count : 0;
            if (count < minCount) {
                return;
            }

            showNotice(data.message || '');
        } catch (error) {
            // Silent fail — notice is optional UX.
        }
    }, delayMs);
}());
