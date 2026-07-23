import { openOfferFilterPanel } from './__customFunctions';
import { applyOfferCardFilters, deactivateFavoritesFilter, isFavoritesFilterActive } from './offer-favorites';

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
    applyOfferCardFilters();
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

    allChip.classList.toggle('is-active', !hasAnyFilter && !isFavoritesFilterActive());
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
        empty.textContent = (window.akademiataOffer && akademiataOffer.filterNoOptions) || 'Brak opcji filtra.';
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

function getOfferHeaderOffsetPx() {
    const header = document.querySelector('.site-header');
    return header ? Math.ceil(header.getBoundingClientRect().height) : 0;
}

function initOfferMobileChipsSticky() {
    const toolbar = document.querySelector('.offer-mobile-toolbar');
    const chips = toolbar?.querySelector('.offer-mobile-chips');

    if (!toolbar || !chips) {
        return;
    }

    let placeholder = chips.previousElementSibling;

    if (!placeholder?.classList.contains('offer-mobile-chips-placeholder')) {
        placeholder = document.createElement('div');
        placeholder.className = 'offer-mobile-chips-placeholder';
        placeholder.setAttribute('aria-hidden', 'true');
        chips.before(placeholder);
    }

    const mobileMq = window.matchMedia(`(max-width: ${TABLET_MAX_WIDTH}px)`);
    let chipsOffsetTop = 0;
    let chipsHeight = 0;
    let scrollRaf = null;

    const alignFixedChips = () => {
        chips.style.left = '0';
        chips.style.width = '100%';
    };

    const clearFixedChipsPosition = () => {
        chips.style.left = '';
        chips.style.width = '';
    };

    const releaseFixedChips = () => {
        chips.classList.remove('offer-mobile-chips--is-fixed');
        placeholder.classList.remove('is-active');
        placeholder.style.height = '';
        clearFixedChipsPosition();
    };

    const measure = () => {
        const wasFixed = chips.classList.contains('offer-mobile-chips--is-fixed');

        if (wasFixed) {
            releaseFixedChips();
        }

        chips.style.setProperty('--offer-chips-fixed-top', `${getOfferHeaderOffsetPx()}px`);
        const chipsRect = chips.getBoundingClientRect();
        chipsOffsetTop = chipsRect.top + window.scrollY;
        chipsHeight = chips.offsetHeight;
    };

    const updateFixedChips = () => {
        if (!mobileMq.matches) {
            releaseFixedChips();
            return;
        }

        const fixedTop = getOfferHeaderOffsetPx();
        chips.style.setProperty('--offer-chips-fixed-top', `${fixedTop}px`);

        if (window.scrollY + fixedTop >= chipsOffsetTop) {
            if (!chips.classList.contains('offer-mobile-chips--is-fixed')) {
                chips.classList.add('offer-mobile-chips--is-fixed');
                placeholder.classList.add('is-active');
                placeholder.style.height = `${chipsHeight}px`;
            }

            alignFixedChips();
            return;
        }

        releaseFixedChips();
    };

    const onScroll = () => {
        if (scrollRaf) {
            return;
        }

        scrollRaf = window.requestAnimationFrame(() => {
            scrollRaf = null;
            updateFixedChips();
        });
    };

    measure();
    updateFixedChips();

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', () => {
        measure();
        updateFixedChips();
    });
    mobileMq.addEventListener('change', () => {
        measure();
        updateFixedChips();
    });

    // Re-measure after layout shifts from filter/results updates.
    document.addEventListener('akademiata:filter-results-updated', () => {
        window.setTimeout(() => {
            measure();
            updateFixedChips();
        }, 0);
    });
}

export function initOfferMobileToolbar() {
    const toolbar = document.querySelector('.offer-mobile-toolbar');
    if (!toolbar) {
        return;
    }

    mountOfferDropdownPortal();
    initOfferMobileChipsSticky();

    const searchInput = toolbar.querySelector('.offer-mobile-search__input');
    const allChip = toolbar.querySelector('.offer-mobile-chip[data-tax="all"]');
    const clearBtn = document.getElementById('offer-mobile-clear-filters');
    const filterForm = getFilterForm();
    const { backdrop, close } = getDropdownElements();

    searchInput?.addEventListener('input', applyOfferSearchQuery);

    allChip?.addEventListener('click', () => {
        closeOfferDropdown();
        deactivateFavoritesFilter();
        document.getElementById('clear-filters')?.click();
        syncChipStates();
    });

    clearBtn?.addEventListener('click', () => {
        closeOfferDropdown();
        deactivateFavoritesFilter();

        if (searchInput) {
            searchInput.value = '';
        }

        applyOfferSearchQuery();
        document.getElementById('clear-filters')?.click();
        syncChipStates();
    });

    toolbar.addEventListener('click', (event) => {
        if (event.target.closest('.offer-view-toggle, .offer-mobile-clear, .offer-favorites-chip')) {
            return;
        }
        if (Date.now() - lastChipTouchAt < 400) {
            return;
        }
        handleToolbarChipTap(event);
    });

    toolbar.addEventListener('touchend', (event) => {
        if (event.target.closest('.offer-view-toggle, .offer-mobile-clear, .offer-favorites-chip')) {
            return;
        }
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
