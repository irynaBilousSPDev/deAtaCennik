const STORAGE_KEY = 'akademiata_offer_view';
const TABLET_MAX_WIDTH = 990;

function isMobileTabletViewport() {
    return window.matchMedia(`(max-width: ${TABLET_MAX_WIDTH}px)`).matches;
}

function getStoredView() {
    try {
        return localStorage.getItem(STORAGE_KEY) === 'list' ? 'list' : 'grid';
    } catch (e) {
        return 'grid';
    }
}

function storeView(view) {
    try {
        localStorage.setItem(STORAGE_KEY, view);
    } catch (e) {
        // ignore
    }
}

export function initOfferViewToggle(filterResultsSelector = '#filter-results') {
    const toggle = document.querySelector('.offer-view-toggle');
    const filterResults = document.querySelector(filterResultsSelector);

    if (!toggle || !filterResults) {
        return;
    }

    const buttons = toggle.querySelectorAll('[data-view]');
    let resizeTimer = null;

    function applyView(view) {
        const useList = view === 'list' && isMobileTabletViewport();

        filterResults.classList.toggle('filter-results--list', useList);
        filterResults.classList.toggle('filter-results--grid', !useList);

        buttons.forEach((btn) => {
            const isActive = btn.dataset.view === (useList ? 'list' : 'grid');
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (isMobileTabletViewport()) {
            storeView(useList ? 'list' : 'grid');
        }
    }

    function syncViewToViewport() {
        if (!isMobileTabletViewport()) {
            applyView('grid');
            return;
        }

        applyView(getStoredView());
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!isMobileTabletViewport()) {
                return;
            }
            applyView(btn.dataset.view === 'list' ? 'list' : 'grid');
        });
    });

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(syncViewToViewport, 150);
    });

    syncViewToViewport();
}
