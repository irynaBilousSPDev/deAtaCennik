/**
 * PG/MBA archive theme filter — taxonomy-tabs UI + shareable ?offer_theme_pg_mba= URL.
 */
export default function initPgMbaThemeFilter() {
    const root = document.querySelector('[data-pg-mba-theme-filter]');

    if (!root) {
        return;
    }

    const navItems = root.querySelectorAll('.taxonomy-tabs__nav li[data-term]');

    const navigateWithThemes = (slugs) => {
        const params = new URLSearchParams();
        slugs.forEach((slug) => {
            params.append('offer_theme_pg_mba', slug);
        });

        const query = params.toString();
        const hash = window.location.hash || '';
        const url = query
            ? `${window.location.pathname}?${query}${hash}`
            : `${window.location.pathname}${hash}`;

        window.location.assign(url);
    };

    navItems.forEach((item) => {
        item.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            const selectedSlug = item.dataset.term;

            if (isActive) {
                navigateWithThemes([]);
                return;
            }

            navigateWithThemes([selectedSlug]);
        });
    });

    const clearBtn = root.querySelector('[data-pg-mba-theme-clear]');

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            navigateWithThemes([]);
        });
    }
}
