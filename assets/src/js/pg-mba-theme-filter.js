/**
 * PG/MBA archive filters — toggle city + theme (AND), shareable URL params.
 */
export default function initPgMbaArchiveFilters() {
    document.querySelectorAll('[data-pg-mba-filters]').forEach((root) => {
        const cityNav = root.querySelector('.city-tabs__nav');
        const cityItems = cityNav
            ? cityNav.querySelectorAll('li[data-city]')
            : [];
        const themeItems = root.querySelectorAll('[data-pg-mba-theme-filter] .taxonomy-tabs__nav li[data-term]');
        const cards = root.querySelectorAll('.pg_mba_card');
        const noResults = root.querySelector('.pg-mba-filters__no-results');
        const archivePostType = root.dataset.archivePostType || null;
        const urlParams = new URLSearchParams(window.location.search);

        let selectedCity = null;
        let selectedTheme = null;

        const applyFilters = () => {
            let visibleCount = 0;

            cards.forEach((card) => {
                const cardCity = card.dataset.city || '';
                const cardPostType = card.dataset.postType || '';
                const themes = (card.dataset.offerTheme || '').split(',').filter(Boolean);

                const typeMatch = !archivePostType || cardPostType === archivePostType;
                const cityMatch = !selectedCity || cardCity === selectedCity;
                const themeMatch = !selectedTheme || themes.includes(selectedTheme);
                const show = typeMatch && cityMatch && themeMatch;

                card.style.display = show ? '' : 'none';

                if (show) {
                    visibleCount += 1;
                }
            });

            if (noResults) {
                if (visibleCount === 0) {
                    noResults.removeAttribute('hidden');
                } else {
                    noResults.setAttribute('hidden', '');
                }
            }
        };

        const updateUrl = () => {
            const params = new URLSearchParams(window.location.search);

            params.delete('city_pg_mba');
            if (selectedCity) {
                params.set('city_pg_mba', selectedCity);
            }

            params.delete('offer_theme_pg_mba');
            if (selectedTheme) {
                params.append('offer_theme_pg_mba', selectedTheme);
            }

            const query = params.toString();
            const url = query
                ? `${window.location.pathname}?${query}`
                : window.location.pathname;

            window.history.replaceState({}, '', url);
        };

        const setCityActive = (slug) => {
            cityItems.forEach((item) => {
                const itemCity = item.dataset.city || '';

                if (!slug) {
                    item.classList.toggle('active', itemCity === '');
                    return;
                }

                item.classList.toggle('active', itemCity === slug);
            });
        };

        const setThemeActive = (slug) => {
            themeItems.forEach((item) => {
                item.classList.toggle('active', Boolean(slug) && item.dataset.term === slug);
            });
        };

        const applyState = () => {
            setCityActive(selectedCity);
            setThemeActive(selectedTheme);
            applyFilters();
            updateUrl();
        };

        cityItems.forEach((item) => {
            const target = item.querySelector('a') || item;
            const slug = item.dataset.city || '';

            target.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();

                if (!slug) {
                    selectedCity = null;
                } else {
                    selectedCity = selectedCity === slug ? null : slug;
                }

                applyState();
            });
        });

        themeItems.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.stopImmediatePropagation();

                selectedTheme = selectedTheme === item.dataset.term ? null : item.dataset.term;
                applyState();
            });
        });

        const cityFromUrl = urlParams.get('city_pg_mba');
        if (cityFromUrl && root.querySelector(`li[data-city="${CSS.escape(cityFromUrl)}"]`)) {
            selectedCity = cityFromUrl;
        }

        const themeFromUrl = urlParams.getAll('offer_theme_pg_mba')[0] || null;
        if (themeFromUrl && root.querySelector(`li[data-term="${CSS.escape(themeFromUrl)}"]`)) {
            selectedTheme = themeFromUrl;
        }

        applyState();
    });
}
