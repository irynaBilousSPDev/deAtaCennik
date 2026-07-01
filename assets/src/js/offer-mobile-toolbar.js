const TABLET_MAX_WIDTH = 990;

function isOfferMobileToolbarActive() {
    return Boolean(document.querySelector('.offer-mobile-toolbar'))
        && window.matchMedia(`(max-width: ${TABLET_MAX_WIDTH}px)`).matches;
}

function getFilterResults() {
    return document.querySelector('#filter-results');
}

function applyOfferSearchQuery() {
    const input = document.querySelector('.offer-mobile-search__input');
    const filterResults = getFilterResults();

    if (!input || !filterResults) {
        return;
    }

    const query = input.value.trim().toLowerCase();

    filterResults.querySelectorAll('.card_post_item').forEach((card) => {
        const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
        card.classList.toggle('is-search-hidden', Boolean(query) && !title.includes(query));
    });
}

function setActiveChip(tax) {
    document.querySelectorAll('.offer-mobile-chip').forEach((chip) => {
        chip.classList.toggle('is-active', chip.dataset.tax === tax);
    });
}

function syncChipWithFilters() {
    const tags = document.querySelectorAll('.selected_tags_container .filter-tag');
    if (tags.length === 0) {
        setActiveChip('all');
    }
}

export function initOfferMobileToolbar() {
    const toolbar = document.querySelector('.offer-mobile-toolbar');
    if (!toolbar) {
        return;
    }

    const searchInput = toolbar.querySelector('.offer-mobile-search__input');
    const allChip = toolbar.querySelector('.offer-mobile-chip[data-tax="all"]');
    const clearBtn = document.getElementById('offer-mobile-clear-filters');
    const filterForm = document.querySelector('#ajax-filter-form');

    searchInput?.addEventListener('input', applyOfferSearchQuery);

    allChip?.addEventListener('click', () => {
        setActiveChip('all');
        document.getElementById('clear-filters')?.click();
    });

    clearBtn?.addEventListener('click', () => {
        if (searchInput) {
            searchInput.value = '';
        }
        applyOfferSearchQuery();
        setActiveChip('all');
        document.getElementById('clear-filters')?.click();
    });

    toolbar.querySelectorAll('.offer-mobile-chip[data-tax]:not([data-tax="all"])').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!isOfferMobileToolbarActive()) {
                return;
            }
            setActiveChip(chip.dataset.tax);
        });
    });

    if (filterForm) {
        filterForm.addEventListener('change', () => {
            window.setTimeout(syncChipWithFilters, 0);
        });
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('.filter-tag')) {
            window.setTimeout(syncChipWithFilters, 0);
        }
    });

    document.addEventListener('akademiata:filter-results-updated', () => {
        applyOfferSearchQuery();
        syncChipWithFilters();
    });

    syncChipWithFilters();
}
