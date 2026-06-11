/**
 * PG/MBA archive filters — toggle city + theme (AND), shareable URL params.
 */
export default function initPgMbaArchiveFilters() {
    document.querySelectorAll('[data-pg-mba-filters]').forEach((root) => {
        const fixedCity = root.dataset.fixedCity || null;
        const cityNav = root.querySelector('.city-tabs__nav');
        const cityItems = cityNav
            ? cityNav.querySelectorAll('li[data-city]')
            : [];
        const themeItems = root.querySelectorAll('[data-pg-mba-theme-filter] .taxonomy-tabs__nav li[data-term]');
        const cards = root.querySelectorAll('.pg_mba_card');

        let selectedCity = fixedCity;
        let selectedTheme = null;

        const applyFilters = () => {
            cards.forEach((card) => {
                const cardCity = card.dataset.city || '';
                const themes = (card.dataset.offerTheme || '').split(',').filter(Boolean);

                const cityMatch = !selectedCity || cardCity === selectedCity;
                const themeMatch = !selectedTheme || themes.includes(selectedTheme);

                card.style.display = cityMatch && themeMatch ? '' : 'none';
            });
        };

        const updateUrl = () => {
            const params = new URLSearchParams(window.location.search);

            if (!fixedCity) {
                params.delete('city_pg_mba');
                if (selectedCity) {
                    params.set('city_pg_mba', selectedCity);
                }
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
                item.classList.toggle('active', Boolean(slug) && item.dataset.city === slug);
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

            target.addEventListener('click', (event) => {
                event.preventDefault();

                if (item.classList.contains('active')) {
                    selectedCity = null;
                } else {
                    selectedCity = item.dataset.city;
                }

                applyState();
            });
        });

        themeItems.forEach((item) => {
            item.addEventListener('click', () => {
                if (item.classList.contains('active')) {
                    selectedTheme = null;
                } else {
                    selectedTheme = item.dataset.term;
                }

                applyState();
            });
        });

        const params = new URLSearchParams(window.location.search);

        if (!fixedCity) {
            const cityFromUrl = params.get('city_pg_mba');
            if (cityFromUrl && root.querySelector(`li[data-city="${CSS.escape(cityFromUrl)}"]`)) {
                selectedCity = cityFromUrl;
            }
        }

        const themeFromUrl = params.getAll('offer_theme_pg_mba')[0] || null;
        if (themeFromUrl && root.querySelector(`li[data-term="${CSS.escape(themeFromUrl)}"]`)) {
            selectedTheme = themeFromUrl;
        }

        applyState();
    });
}
