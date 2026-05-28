/**
 * Aktualności archive: toggle “Więcej filtrów”, auto-submit date selects.
 */
export default function initNewsArchiveFilters() {
    const panel = document.querySelector('[data-news-archive-panel]');
    if (!panel) {
        return;
    }

    const toggle = panel.querySelector('[data-news-filters-toggle]');
    const more = panel.querySelector('[data-news-filters-panel]');
    const dateForm = panel.querySelector('[data-news-date-filter]');

    const setOpen = (open) => {
        if (!toggle || !more) {
            return;
        }

        const labelEl = toggle.querySelector('span');
        const label = open ? toggle.getAttribute('data-label-open') : toggle.getAttribute('data-label-closed');
        if (labelEl && label) {
            labelEl.textContent = label;
        }

        toggle.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        more.classList.toggle('is-open', open);
        if (open) {
            more.removeAttribute('hidden');
        } else {
            more.setAttribute('hidden', '');
        }
    };

    if (toggle && more) {
        setOpen(more.classList.contains('is-open'));
        toggle.addEventListener('click', () => {
            const willOpen = !more.classList.contains('is-open');
            setOpen(willOpen);
        });
    }

    if (dateForm) {
        dateForm.querySelectorAll('.news-date-filter__select').forEach((select) => {
            select.addEventListener('change', () => {
                if (typeof dateForm.requestSubmit === 'function') {
                    dateForm.requestSubmit();
                } else {
                    dateForm.submit();
                }
            });
        });
    }
}
