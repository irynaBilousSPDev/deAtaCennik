import { closeOfferFilterPanel } from './__customFunctions';

const TABLET_MAX_WIDTH = 990;
let lastChipTouchAt = 0;

function isPgMbaToolbarActive() {
    return Boolean(document.querySelector('.pg-mba-mobile-toolbar'))
        && window.matchMedia(`(max-width: ${TABLET_MAX_WIDTH}px)`).matches;
}

function getFilterForm() {
    return document.getElementById('ajax-filter-pg-mba-form');
}

function triggerFilterChange(input) {
    if (window.jQuery) {
        window.jQuery(input).trigger('change');
        return;
    }

    input.dispatchEvent(new Event('change', { bubbles: true }));
}

export function applyPgMbaListingSearch() {
    const filterResults = document.querySelector('.offer_wrapper--pg-mba #filter-results');
    if (!filterResults) {
        return;
    }

    const searchInput = document.querySelector('.pg-mba-mobile-toolbar .offer-mobile-search__input');
    const query = searchInput?.value.trim().toLowerCase() || '';
    let visibleCount = 0;

    filterResults.querySelectorAll('.pg_mba_card').forEach((card) => {
        const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
        const searchHidden = Boolean(query) && !title.includes(query);
        card.classList.toggle('is-search-hidden', searchHidden);
        if (!searchHidden) {
            visibleCount += 1;
        }
    });

    const noResults = document.getElementById('no-results-message');
    const totalCards = filterResults.querySelectorAll('.pg_mba_card').length;

    if (!noResults) {
        return;
    }

    if (totalCards === 0) {
        return;
    }

    if (query && visibleCount === 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
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

    return Array.from(document.querySelectorAll('#ajax-filter-pg-mba-form input[type="checkbox"]'))
        .filter((input) => input.name === fieldName);
}

function syncChipStates() {
    const form = getFilterForm();
    const allChip = document.querySelector('.pg-mba-mobile-toolbar .offer-mobile-chip[data-tax="all"]');
    let hasAnyFilter = false;

    if (form && allChip) {
        document.querySelectorAll('.pg-mba-mobile-toolbar .offer-mobile-chip--dropdown').forEach((chip) => {
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

    syncActionsBar(hasAnyFilter);
}

function syncActionsBar(hasTaxonomyFilters = false) {
    const clearBtn = document.getElementById('pg-mba-mobile-clear-filters');
    const form = getFilterForm();
    let hasFilters = hasTaxonomyFilters;

    if (!hasFilters && form) {
        hasFilters = form.querySelectorAll('input[type="checkbox"]:checked').length > 0;
    }

    if (!hasFilters) {
        hasFilters = document.querySelectorAll('.pg-mba-mobile-toolbar .filter-tag').length > 0
            || Boolean(document.querySelector('.pg-mba-mobile-toolbar .offer-mobile-search__input')?.value.trim());
    }

    if (clearBtn) {
        clearBtn.hidden = !hasFilters;
    }
}

function syncSearchFieldState(toolbar) {
    const searchPanel = toolbar.querySelector('#pg-mba-mobile-search-panel');
    const searchClear = toolbar.querySelector('.offer-mobile-search__clear');
    const isOpen = searchPanel?.classList.contains('is-open');

    if (searchClear) {
        searchClear.hidden = !isOpen;
    }
}

function setPgMbaMobileSearchOpen(toolbar, isOpen) {
    const searchPanel = toolbar.querySelector('#pg-mba-mobile-search-panel');
    const searchToggle = toolbar.querySelector('.offer-mobile-search-toggle');
    const searchEnd = toolbar.querySelector('.offer-mobile-actions__end');
    const searchInput = toolbar.querySelector('.offer-mobile-search__input');

    if (!searchPanel || !searchToggle) {
        return;
    }

    searchPanel.classList.toggle('is-open', isOpen);
    searchToggle.classList.toggle('is-active', isOpen);
    searchToggle.hidden = isOpen;
    searchEnd?.classList.toggle('is-search-open', isOpen);
    searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    if (isOpen) {
        searchPanel.removeAttribute('hidden');
        syncSearchFieldState(toolbar);
        window.setTimeout(() => searchInput?.focus(), 50);
        return;
    }

    searchToggle.hidden = false;
    if (!searchInput?.value.trim()) {
        searchPanel.setAttribute('hidden', '');
    }
}

function closePgMbaMobileSearch(toolbar) {
    setPgMbaMobileSearchOpen(toolbar, false);
}

function getDropdownElements() {
    return {
        root: document.getElementById('pg-mba-mobile-dropdown'),
        title: document.querySelector('#pg-mba-mobile-dropdown .offer-mobile-dropdown__title-dynamic'),
        list: document.querySelector('#pg-mba-mobile-dropdown .offer-mobile-dropdown__list'),
        backdrop: document.querySelector('#pg-mba-mobile-dropdown .offer-mobile-dropdown__backdrop'),
        close: document.querySelector('#pg-mba-mobile-dropdown .offer-mobile-dropdown__close'),
    };
}

function mountPgMbaDropdownPortal() {
    const { root } = getDropdownElements();

    if (!root || root.parentElement === document.body) {
        return;
    }

    document.body.appendChild(root);
}

function closePgMbaDropdown() {
    const { root } = getDropdownElements();

    if (!root) {
        return;
    }

    root.classList.remove('is-open');
    root.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('offer-dropdown-open');
}

function openPgMbaDropdown(taxonomy, label) {
    const { root, title, list } = getDropdownElements();

    if (!root || !title || !list || !isPgMbaToolbarActive()) {
        return;
    }

    title.textContent = label || taxonomy;
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
        empty.textContent = (window.akademiataOffer && window.akademiataOffer.filterNoOptions) || 'Brak opcji filtra.';
        list.appendChild(empty);
        return;
    }

    inputs.forEach((input) => {
        const sourceLabel = input.closest('label');
        const option = document.createElement('button');

        option.type = 'button';
        option.className = 'offer-mobile-dropdown__option';
        option.dataset.value = input.value;
        option.classList.toggle('is-selected', input.checked);
        option.setAttribute('aria-pressed', input.checked ? 'true' : 'false');
        option.textContent = sourceLabel?.textContent.trim() || input.value;

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

function openPgMbaFilterPanel() {
    const sidebar = document.querySelector('.offer_wrapper--pg-mba #sidebar');
    const overlay = document.querySelector('.offer_wrapper--pg-mba .filter-overlay');
    const header = document.querySelector('.offer_wrapper--pg-mba .mobile-filter-header');

    closePgMbaDropdown();

    if (!sidebar) {
        return;
    }

    sidebar.classList.add('open');
    overlay?.classList.add('active');
    document.body.classList.add('filter-open');
    header?.classList.add('visible');

    window.setTimeout(() => {
        if (window.closeAllAccordions) {
            window.closeAllAccordions();
        }

        const firstHeader = document.querySelector('.offer_wrapper--pg-mba .filter_accordion_header');
        if (firstHeader && window.jQuery) {
            window.jQuery(firstHeader).addClass('active');
            window.jQuery(firstHeader).next('.accordion-content').slideDown(300);
        }
    }, 10);
}

function handleToolbarChipTap(event) {
    if (!isPgMbaToolbarActive()) {
        return;
    }

    const moreChip = event.target.closest('.offer-mobile-chip--more');
    if (moreChip) {
        event.preventDefault();
        closePgMbaDropdown();
        openPgMbaFilterPanel();
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
        closePgMbaDropdown();
        return;
    }

    if (root) {
        root.dataset.activeTax = chip.dataset.tax;
    }

    openPgMbaDropdown(chip.dataset.tax, chip.dataset.label || '');
}

function getPgMbaHeaderOffsetPx() {
    const header = document.querySelector('.site-header');
    return header ? Math.ceil(header.getBoundingClientRect().height) : 0;
}

function scrollPgMbaListingToStart() {
    if (!isPgMbaToolbarActive()) {
        return;
    }

    const chips = document.querySelector('.pg-mba-mobile-toolbar .offer-mobile-chips');
    const results = document.querySelector('.offer_wrapper--pg-mba #filter-results');
    const toolbar = document.querySelector('.pg-mba-mobile-toolbar');
    const target = results || toolbar;

    if (!target) {
        return;
    }

    const headerH = getPgMbaHeaderOffsetPx();
    const chipsPinned = chips?.classList.contains('offer-mobile-chips--is-fixed')
        && !chips.classList.contains('offer-mobile-chips--is-hidden');
    const chipsH = chipsPinned ? chips.offsetHeight : 0;
    const y = target.getBoundingClientRect().top + window.scrollY - headerH - chipsH - 8;

    window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
}

function initPgMbaMobileChipsSticky() {
    const toolbar = document.querySelector('.pg-mba-mobile-toolbar');
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
    const DIR_DELTA = 8;
    let chipsOffsetTop = 0;
    let chipsHeight = 0;
    let scrollRaf = null;
    let lastScrollY = window.scrollY;
    let chipsVisible = true;

    const alignFixedChips = () => {
        chips.style.left = '0';
        chips.style.width = '100%';
    };

    const clearFixedChipsPosition = () => {
        chips.style.left = '';
        chips.style.width = '';
    };

    const setChipsHidden = (hidden, { instant = false } = {}) => {
        if (instant) {
            chips.classList.add('offer-mobile-chips--no-anim');
        }

        chips.classList.toggle('offer-mobile-chips--is-hidden', hidden);
        chipsVisible = !hidden;

        if (instant) {
            // eslint-disable-next-line no-unused-expressions
            chips.offsetHeight;
            chips.classList.remove('offer-mobile-chips--no-anim');
        }
    };

    const releaseFixedChips = () => {
        chips.classList.remove('offer-mobile-chips--is-fixed');
        chips.classList.remove('offer-mobile-chips--is-hidden');
        chips.classList.remove('offer-mobile-chips--no-anim');
        placeholder.classList.remove('is-active');
        placeholder.style.height = '';
        clearFixedChipsPosition();
        chipsVisible = true;
    };

    const pinFixedChips = () => {
        if (!chips.classList.contains('offer-mobile-chips--is-fixed')) {
            chips.classList.add('offer-mobile-chips--is-fixed');
            placeholder.classList.add('is-active');
            placeholder.style.height = `${chipsHeight}px`;
        }
        alignFixedChips();
    };

    const measure = () => {
        const wasFixed = chips.classList.contains('offer-mobile-chips--is-fixed');
        const wasHidden = chips.classList.contains('offer-mobile-chips--is-hidden');

        if (wasFixed) {
            releaseFixedChips();
        }

        chips.style.setProperty('--offer-chips-fixed-top', `${getPgMbaHeaderOffsetPx()}px`);
        const chipsRect = chips.getBoundingClientRect();
        chipsOffsetTop = chipsRect.top + window.scrollY;
        chipsHeight = chips.offsetHeight;
        lastScrollY = window.scrollY;

        if (wasFixed && mobileMq.matches && window.scrollY + getPgMbaHeaderOffsetPx() >= chipsOffsetTop) {
            pinFixedChips();
            if (wasHidden) {
                setChipsHidden(true, { instant: true });
            }
        }
    };

    const updateFixedChips = () => {
        if (!mobileMq.matches) {
            releaseFixedChips();
            lastScrollY = window.scrollY;
            return;
        }

        const fixedTop = getPgMbaHeaderOffsetPx();
        const scrollY = window.scrollY;
        chips.style.setProperty('--offer-chips-fixed-top', `${fixedTop}px`);

        if (scrollY + fixedTop < chipsOffsetTop + 2) {
            releaseFixedChips();
            lastScrollY = scrollY;
            return;
        }

        const wasPinned = chips.classList.contains('offer-mobile-chips--is-fixed');
        const delta = scrollY - lastScrollY;

        if (document.body.classList.contains('filter-open')
            || document.body.classList.contains('offer-dropdown-open')) {
            pinFixedChips();
            setChipsHidden(false);
            lastScrollY = scrollY;
            return;
        }

        if (!wasPinned) {
            pinFixedChips();
            if (delta >= 0) {
                setChipsHidden(true, { instant: true });
            } else {
                setChipsHidden(false, { instant: true });
            }
            lastScrollY = scrollY;
            return;
        }

        if (Math.abs(delta) >= DIR_DELTA) {
            if (delta > 0) {
                setChipsHidden(true);
            } else {
                setChipsHidden(false);
            }
            lastScrollY = scrollY;
        }
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

    document.addEventListener('akademiata:filter-results-updated', (event) => {
        window.setTimeout(() => {
            measure();
            updateFixedChips();
            if (event?.detail?.reset) {
                setChipsHidden(false);
                scrollPgMbaListingToStart();
            }
        }, 0);
    });
}

export function initPgMbaMobileToolbar() {
    const toolbar = document.querySelector('.pg-mba-mobile-toolbar');
    if (!toolbar) {
        return;
    }

    mountPgMbaDropdownPortal();
    initPgMbaMobileChipsSticky();

    const searchInput = toolbar.querySelector('.offer-mobile-search__input');
    const searchClear = toolbar.querySelector('.offer-mobile-search__clear');
    const searchToggle = toolbar.querySelector('.offer-mobile-search-toggle');
    const allChip = toolbar.querySelector('.offer-mobile-chip[data-tax="all"]');
    const clearBtn = document.getElementById('pg-mba-mobile-clear-filters');
    const filterForm = getFilterForm();
    const { backdrop, close } = getDropdownElements();

    searchInput?.addEventListener('input', () => {
        syncSearchFieldState(toolbar);
        applyPgMbaListingSearch();
        syncActionsBar();
    });

    searchClear?.addEventListener('click', () => {
        if (searchInput) {
            searchInput.value = '';
        }
        applyPgMbaListingSearch();
        syncSearchFieldState(toolbar);
        closePgMbaMobileSearch(toolbar);
        syncActionsBar();
    });

    searchToggle?.addEventListener('click', () => {
        const isOpen = searchToggle.classList.contains('is-active');
        setPgMbaMobileSearchOpen(toolbar, !isOpen);
    });

    if (searchInput?.value.trim()) {
        setPgMbaMobileSearchOpen(toolbar, true);
    }

    allChip?.addEventListener('click', () => {
        closePgMbaDropdown();
        document.getElementById('clear-filters')?.click();
        if (searchInput) {
            searchInput.value = '';
        }
        applyPgMbaListingSearch();
        syncSearchFieldState(toolbar);
        closePgMbaMobileSearch(toolbar);
        syncChipStates();
        scrollPgMbaListingToStart();
    });

    clearBtn?.addEventListener('click', () => {
        closePgMbaDropdown();
        closeOfferFilterPanel();

        if (searchInput) {
            searchInput.value = '';
        }

        syncSearchFieldState(toolbar);
        applyPgMbaListingSearch();
        closePgMbaMobileSearch(toolbar);
        document.getElementById('clear-filters')?.click();
        syncChipStates();
        scrollPgMbaListingToStart();
    });

    toolbar.addEventListener('click', (event) => {
        if (event.target.closest('.offer-view-toggle, .offer-mobile-clear, .offer-mobile-search-toggle, .offer-mobile-search')) {
            return;
        }
        if (Date.now() - lastChipTouchAt < 400) {
            return;
        }
        handleToolbarChipTap(event);
    });

    toolbar.addEventListener('touchend', (event) => {
        if (event.target.closest('.offer-view-toggle, .offer-mobile-clear, .offer-mobile-search-toggle, .offer-mobile-search')) {
            return;
        }
        const chip = event.target.closest('.offer-mobile-chip--dropdown, .offer-mobile-chip--more');
        if (!chip || !toolbar.contains(chip)) {
            return;
        }
        lastChipTouchAt = Date.now();
        handleToolbarChipTap(event);
    }, { passive: false });

    backdrop?.addEventListener('click', closePgMbaDropdown);
    close?.addEventListener('click', closePgMbaDropdown);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePgMbaDropdown();
            closePgMbaMobileSearch(toolbar);
        }
    });

    if (filterForm) {
        filterForm.addEventListener('change', () => {
            window.setTimeout(syncChipStates, 0);
        });
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('.offer_wrapper--pg-mba .filter-tag')) {
            window.setTimeout(syncChipStates, 0);
        }
    });

    document.addEventListener('akademiata:filter-results-updated', () => {
        applyPgMbaListingSearch();
        syncChipStates();
    });

    syncChipStates();
    applyPgMbaListingSearch();
}
