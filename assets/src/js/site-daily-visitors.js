(function () {
    const config = window.akademiataSiteDailyVisitors;
    if (!config || !config.restUrl) {
        return;
    }

    const minCount = parseInt(config.minCount, 10) || 2;

    function updateStatusBar(data) {
        const block = document.querySelector('[data-site-daily-visitors]');
        if (!block || !data || !data.show) {
            return;
        }

        const count = typeof data.count === 'number' ? data.count : 0;
        if (count < minCount) {
            return;
        }

        const textEl = block.querySelector('[data-site-daily-visitors-text]');
        if (textEl && (data.message_html || data.message)) {
            textEl.innerHTML = data.message_html || data.message;
        }

        block.hidden = false;

        const statusBar = block.closest('.home-decision__status');
        if (statusBar) {
            const divider = statusBar.querySelector('.home-decision__status-divider');
            if (divider) {
                divider.hidden = false;
            }
        }
    }

    async function registerView() {
        const response = await fetch(config.restUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce,
            },
            credentials: 'same-origin',
            cache: 'no-store',
            body: JSON.stringify({}),
        });

        if (!response.ok) {
            return null;
        }

        const data = await response.json();
        return data && typeof data === 'object' ? data : null;
    }

    registerView()
        .then(updateStatusBar)
        .catch(function () {
            // Silent fail — counter is optional UX.
        });
}());
