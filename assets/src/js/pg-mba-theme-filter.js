/**
 * PG/MBA archive theme filter — sync ?offer_theme_pg_mba= slugs in URL (shareable links).
 */
export default function initPgMbaThemeFilter() {
    const root = document.querySelector('[data-pg-mba-theme-filter]');

    if (!root) {
        return;
    }

    const form = root.querySelector('.pg-mba-theme-filter__form');

    if (!form) {
        return;
    }

    const navigateWithThemes = () => {
        const params = new URLSearchParams();
        form.querySelectorAll('input[type="checkbox"]:checked').forEach((input) => {
            params.append('offer_theme_pg_mba', input.value);
        });

        const query = params.toString();
        const hash = window.location.hash || '';
        const url = query
            ? `${window.location.pathname}?${query}${hash}`
            : `${window.location.pathname}${hash}`;

        window.location.assign(url);
    };

    form.addEventListener('change', (event) => {
        if (event.target.matches('input[type="checkbox"]')) {
            navigateWithThemes();
        }
    });

    const clearBtn = root.querySelector('[data-pg-mba-theme-clear]');

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            form.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                input.checked = false;
            });
            navigateWithThemes();
        });
    }
}
