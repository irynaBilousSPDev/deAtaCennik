/**
 * PG/MBA theme filter — city-tabs toggle UX + shareable ?offer_theme_pg_mba= URL.
 */
export default function initPgMbaThemeFilter() {
    const root = document.querySelector('[data-pg-mba-theme-filter]');

    if (!root) {
        return;
    }

    const navItems = root.querySelectorAll('.taxonomy-tabs__nav li[data-term]');

    const filterCards = (selectedTerm) => {
        document.querySelectorAll('.pg_mba_card').forEach((card) => {
            if (!selectedTerm) {
                card.style.display = '';
                return;
            }

            const themes = (card.dataset.offerTheme || '').split(',').filter(Boolean);
            card.style.display = themes.includes(selectedTerm) ? '' : 'none';
        });
    };

    const updateUrl = (selectedTerm) => {
        const params = new URLSearchParams(window.location.search);
        params.delete('offer_theme_pg_mba');

        if (selectedTerm) {
            params.append('offer_theme_pg_mba', selectedTerm);
        }

        const query = params.toString();
        const hash = window.location.hash || '';
        const url = query
            ? `${window.location.pathname}?${query}${hash}`
            : `${window.location.pathname}${hash}`;

        window.history.replaceState({}, '', url);
    };

    const applySelection = (selectedTerm) => {
        navItems.forEach((item) => {
            item.classList.toggle('active', item.dataset.term === selectedTerm);
        });
        filterCards(selectedTerm);
        updateUrl(selectedTerm);
    };

    navItems.forEach((item) => {
        item.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            applySelection(isActive ? null : item.dataset.term);
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    const selectedFromUrl = urlParams.getAll('offer_theme_pg_mba')[0] || null;

    if (selectedFromUrl) {
        applySelection(selectedFromUrl);
    }
}
