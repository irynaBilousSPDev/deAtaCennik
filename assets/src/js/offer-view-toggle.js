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

    if (!toggle || !filterResults || toggle.dataset.welyoViewInit === '1') {
        return;
    }

    toggle.dataset.welyoViewInit = '1';

    let resizeTimer = null;
    let lastTouchAt = 0;
    let currentView = 'grid';

    function applyView(view) {
        const useList = view === 'list' && isMobileTabletViewport();
        currentView = useList ? 'list' : 'grid';

        filterResults.classList.toggle('filter-results--list', useList);
        filterResults.classList.toggle('filter-results--grid', !useList);

        toggle.querySelectorAll('[data-view]').forEach((btn) => {
            const isActive = btn.dataset.view === currentView;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (isMobileTabletViewport()) {
            storeView(currentView);
        }
    }

    function syncViewToViewport() {
        if (!isMobileTabletViewport()) {
            applyView('grid');
            return;
        }

        applyView(getStoredView());
    }

    function handleViewSelect(btn) {
        if (!btn || !isMobileTabletViewport()) {
            return;
        }

        applyView(btn.dataset.view === 'list' ? 'list' : 'grid');
    }

    toggle.addEventListener('touchend', (event) => {
        const btn = event.target.closest('[data-view]');
        if (!btn || !toggle.contains(btn)) {
            return;
        }

        lastTouchAt = Date.now();
        event.preventDefault();
        handleViewSelect(btn);
    }, { passive: false });

    toggle.addEventListener('click', (event) => {
        if (Date.now() - lastTouchAt < 400) {
            return;
        }

        const btn = event.target.closest('[data-view]');
        if (!btn || !toggle.contains(btn)) {
            return;
        }

        event.preventDefault();
        handleViewSelect(btn);
    });

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(syncViewToViewport, 150);
    });

    document.addEventListener('akademiata:filter-results-updated', () => {
        applyView(currentView);
    });

    syncViewToViewport();
}
