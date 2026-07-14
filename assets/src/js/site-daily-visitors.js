(function () {
    const config = window.akademiataSiteDailyVisitors;
    if (!config || !config.restUrl) {
        return;
    }

    const minCount = parseInt(config.minCount, 10) || 2;

    function updateStatusBar(data) {
        const block = document.querySelector('[data-site-daily-visitors]');
        if (!block) {
            return;
        }

        const countEl = block.querySelector('[data-site-daily-visitors-count]');
        const wordEl = block.querySelector('[data-site-daily-visitors-word]');
        const count = typeof data?.count === 'number' ? data.count : 0;

        if (!data || !data.show || count < minCount) {
            block.hidden = true;
            return;
        }

        if (countEl && data.count_formatted) {
            countEl.textContent = data.count_formatted;
        }

        if (wordEl && data.count_word) {
            wordEl.textContent = data.count_word;
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
            const block = document.querySelector('[data-site-daily-visitors]');
            if (block) {
                block.hidden = true;
            }
        });
}());
