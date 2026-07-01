import { openOfferFilterPanel } from './__customFunctions';

const TABLET_MAX_WIDTH = 990;
let lastChipTouchAt = 0;

function isOfferMobileToolbarActive() {
    return Boolean(document.querySelector('.offer-mobile-toolbar'))
        && window.matchMedia(`(max-width: ${TABLET_MAX_WIDTH}px)`).matches;
}

function getFilterResults() {
    return document.querySelector('#filter-results');
}

function getFilterForm() {
    return document.getElementById('ajax-filter-form');
}

function triggerFilterChange(input) {
    if (window.jQuery) {
        window.jQuery(input).trigger('change');
        return;
    }

    input.dispatchEvent(new Event('change', { bubbles: true }));
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

function getTaxonomyInputs(taxonomy) {
    const fieldName = `${taxonomy}[]`;
    const form = getFilterForm();

    if (window.jQuery && form) {
        const $inputs = window.jQuery(form).find(`input[name="${taxonomy}[]"]`);
        if ($inputs.length) {
            return $inputs.toArray();
        }
    }

    if (form) {
        const fromForm = Array.from(form.elements).filter(
            (element) => element.tagName === 'INPUT' && element.name === fieldName
        );
        if (fromForm.length) {
            return fromForm;
        }
    }

    return Array.from(document.querySelectorAll('#ajax-filter-form input[type="checkbox"]'))
        .filter((input) => input.name === fieldName);
}

function syncChipStates() {
    const form = getFilterForm();
    const allChip = document.querySelector('.offer-mobile-chip[data-tax="all"]');

    if (!form || !allChip) {
        return;
    }

    let hasAnyFilter = false;

    document.querySelectorAll('.offer-mobile-chip--dropdown').forEach((chip) => {
        const tax = chip.dataset.tax;
        const checkedCount = getTaxonomyInputs(tax).filter((input) => input.checked).length;
        const hasFilter = checkedCount > 0;

        chip.classList.toggle('has-filter', hasFilter);
        if (hasFilter) {
            hasAnyFilter = true;
        }
    });

    allChip.classList.toggle('is-active', !hasAnyFilter);
}

function getDropdownElements() {
    return {
        root: document.getElementById('offer-mobile-dropdown'),
        title: document.querySelector('.offer-mobile-dropdown__title-dynamic'),
        list: document.querySelector('.offer-mobile-dropdown__list'),
        backdrop: document.querySelector('.offer-mobile-dropdown__backdrop'),
        close: document.querySelector('.offer-mobile-dropdown__close'),
    };
}

function mountOfferDropdownPortal() {
    const { root } = getDropdownElements();

    if (!root || root.parentElement === document.body) {
        return;
    }

    document.body.appendChild(root);
}

function closeOfferDropdown() {
    const { root } = getDropdownElements();

    if (!root) {
        return;
    }

    root.classList.remove('is-open');
    root.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('offer-dropdown-open');
}

function openOfferDropdown(taxonomy, label) {
    const { root, title, list } = getDropdownElements();

    if (!root || !title || !list || !isOfferMobileToolbarActive()) {
        return;
    }

    const resolvedLabel = label || taxonomy;
    title.textContent = resolvedLabel;
    list.innerHTML = '';

    root.classList.add('is-open');
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('offer-dropdown-open');

    const inputs = getTaxonomyInputs(taxonomy);

    if (!inputs.length) {
        const empty = document.createElement('p');
        empty.className = 'offer-mobile-dropdown__empty';
        empty.style.padding = '14px 20px';
        empty.style.margin = '0';
        empty.textContent = 'Brak opcji filtra.';
        list.appendChild(empty);
        return;
    }

    inputs.forEach((input) => {
        const sourceLabel = input.closest('label');
        const option = document.createElement('button');

        option.type = 'button';
        option.className = 'offer-mobile-dropdown__option';
        option.dataset.value = input.value;
        option.textContent = sourceLabel?.textContent.trim() || input.value;
        option.classList.toggle('is-selected', input.checked);
        option.setAttribute('aria-pressed', input.checked ? 'true' : 'false');

        option.addEventListener('click', () => {
            input.checked = !input.checked;
            option.classList.toggle('is-selected', input.checked);
            option.setAttribute('aria-pressed', input.checked ? 'true' : 'false');
            triggerFilterChange(input);
            syncChipStates();
        });

        list.appendChild(option);
    });
}

function handleToolbarChipTap(event) {
    if (!isOfferMobileToolbarActive()) {
        return;
    }

    const moreChip = event.target.closest('.offer-mobile-chip--more');
    if (moreChip) {
        event.preventDefault();
        closeOfferDropdown();
        openOfferFilterPanel();
        return;
    }

    const chip = event.target.closest('.offer-mobile-chip--dropdown');
    if (!chip) {
        return;
    }

    event.preventDefault();

    const { root } = getDropdownElements();
    const isSameChipOpen = root?.classList.contains('is-open')
        && root.dataset.activeTax === chip.dataset.tax;

    if (isSameChipOpen) {
        closeOfferDropdown();
        return;
    }

    if (root) {
        root.dataset.activeTax = chip.dataset.tax;
    }

    openOfferDropdown(chip.dataset.tax, chip.dataset.label || '');
}

export function initOfferMobileToolbar() {
    const toolbar = document.querySelector('.offer-mobile-toolbar');
    if (!toolbar) {
        return;
    }

    mountOfferDropdownPortal();

    const searchInput = toolbar.querySelector('.offer-mobile-search__input');
    const allChip = toolbar.querySelector('.offer-mobile-chip[data-tax="all"]');
    const clearBtn = document.getElementById('offer-mobile-clear-filters');
    const filterForm = getFilterForm();
    const { backdrop, close } = getDropdownElements();

    searchInput?.addEventListener('input', applyOfferSearchQuery);

    allChip?.addEventListener('click', () => {
        closeOfferDropdown();
        document.getElementById('clear-filters')?.click();
        syncChipStates();
    });

    clearBtn?.addEventListener('click', () => {
        closeOfferDropdown();

        if (searchInput) {
            searchInput.value = '';
        }

        applyOfferSearchQuery();
        document.getElementById('clear-filters')?.click();
        syncChipStates();
    });

    toolbar.addEventListener('click', (event) => {
        if (Date.now() - lastChipTouchAt < 400) {
            return;
        }
        handleToolbarChipTap(event);
    });

    toolbar.addEventListener('touchend', (event) => {
        const chip = event.target.closest('.offer-mobile-chip--dropdown, .offer-mobile-chip--more');
        if (!chip || !toolbar.contains(chip)) {
            return;
        }
        lastChipTouchAt = Date.now();
        handleToolbarChipTap(event);
    }, { passive: false });

    backdrop?.addEventListener('click', closeOfferDropdown);
    close?.addEventListener('click', closeOfferDropdown);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeOfferDropdown();
        }
    });

    if (filterForm) {
        filterForm.addEventListener('change', () => {
            window.setTimeout(syncChipStates, 0);
        });
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('.filter-tag')) {
            window.setTimeout(syncChipStates, 0);
        }
    });

    document.addEventListener('akademiata:filter-results-updated', () => {
        applyOfferSearchQuery();
        syncChipStates();
    });

    syncChipStates();
}
