const STORAGE_KEY = 'akademiata_pg_mba_favorites_v1';
const SCOPES = ['postgraduate', 'mba'];

let favoritesFilterActive = false;
let lastFavoriteTouchAt = 0;

function getPgMbaConfig() {
    return window.ajax_filter_pg_mba_params || {};
}

function getPgMbaRoot() {
    return document.querySelector('.offer_wrapper--pg-mba');
}

function getFilterResults() {
    return document.querySelector('.offer_wrapper--pg-mba #filter-results');
}

function getFavoritesScope() {
    const scope = getPgMbaConfig().favoritesScope;
    return SCOPES.includes(scope) ? scope : 'postgraduate';
}

function emptyStorageData() {
    return {
        postgraduate: [],
        mba: [],
    };
}

function readStorageData() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const parsed = JSON.parse(raw);
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                return {
                    ...emptyStorageData(),
                    ...parsed,
                };
            }
        }
    } catch (error) {
        // ignore
    }

    const data = emptyStorageData();
    writeStorageData(data);
    return data;
}

function writeStorageData(data) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function getPostTypeForId(postId) {
    const id = String(postId);
    const card = document.querySelector(`.pg_mba_card[data-post-id="${id}"]`);
    const fromCard = card?.dataset.postType;
    if (SCOPES.includes(fromCard)) {
        return fromCard;
    }

    const button = document.querySelector(`.pg-mba-favorite-btn[data-post-id="${id}"]`);
    const fromButton = button?.dataset.postType;
    if (SCOPES.includes(fromButton)) {
        return fromButton;
    }

    return null;
}

function resolveStorageScopeForPost(postId) {
    return getPostTypeForId(postId) || getFavoritesScope();
}

function getFavorites() {
    const scope = getFavoritesScope();
    const data = readStorageData();
    return Array.isArray(data[scope]) ? data[scope].map(String) : [];
}

function isFavorite(postId) {
    const id = String(postId);
    const storageScope = resolveStorageScopeForPost(id);
    const data = readStorageData();
    return (data[storageScope] || []).map(String).includes(id);
}

export function isPgMbaFavoritesFilterActive() {
    return favoritesFilterActive;
}

export function deactivatePgMbaFavoritesFilter() {
    favoritesFilterActive = false;
    syncFavoritesFilterUI();

    const noResults = document.getElementById('no-results-message');
    if (noResults) {
        noResults.style.display = 'none';
    }

    applyPgMbaCardFilters();
}

function syncFavoritesFilterUI() {
    document.querySelectorAll('.pg-mba-favorites-chip').forEach((chip) => {
        chip.classList.toggle('is-active', favoritesFilterActive);
    });

    document.querySelectorAll('.pg-mba-favorites-filter__toggle').forEach((input) => {
        input.checked = favoritesFilterActive;
    });
}

function toggleFavorite(postId) {
    const id = String(postId);
    const storageScope = resolveStorageScopeForPost(id);

    if (!SCOPES.includes(storageScope)) {
        return;
    }

    const data = readStorageData();
    const list = (data[storageScope] || []).map(String);
    const next = list.includes(id)
        ? list.filter((item) => item !== id)
        : [...list, id];

    data[storageScope] = next;
    writeStorageData(data);
    updateFavoritesChipCounts();
    document.querySelectorAll(`.pg-mba-favorite-btn[data-post-id="${id}"]`).forEach(updateHeartButton);
    applyPgMbaCardFilters();
}

function getFavoriteAriaLabel(isActive) {
    const config = getPgMbaConfig();
    return isActive ? config.favoriteRemove : config.favoriteAdd;
}

function updateHeartButton(button) {
    if (!button) {
        return;
    }

    const postId = button.dataset.postId;
    const active = isFavorite(postId);

    button.classList.toggle('is-active', active);
    button.setAttribute('aria-pressed', active ? 'true' : 'false');

    const label = getFavoriteAriaLabel(active);
    if (label) {
        button.setAttribute('aria-label', label);
    }
}

function updateAllHeartButtons() {
    document.querySelectorAll('.pg-mba-favorite-btn').forEach(updateHeartButton);
}

function updateFavoritesChipCounts() {
    const count = getFavorites().length;
    const suffix = count > 0 ? ` (${count})` : '';

    document.querySelectorAll('.pg-mba-favorites-chip__count').forEach((element) => {
        element.textContent = suffix;
    });

    document.querySelectorAll('.pg-mba-favorites-filter__count').forEach((element) => {
        element.textContent = suffix;
    });

    document.querySelectorAll('.pg-mba-favorites-chip').forEach((chip) => {
        chip.hidden = count === 0;
        chip.disabled = count === 0;
    });

    const desktopFilter = document.getElementById('pg-mba-favorites-filter-desktop');
    if (desktopFilter) {
        desktopFilter.hidden = count === 0;
        desktopFilter.classList.toggle('has-favorites', count > 0);

        if (count === 0 && favoritesFilterActive) {
            deactivatePgMbaFavoritesFilter();
        }
    }
}

export function applyPgMbaCardFilters() {
    const filterResults = getFilterResults();
    if (!filterResults) {
        return;
    }

    const searchInput = document.querySelector('.pg-mba-mobile-toolbar .offer-mobile-search__input');
    const query = searchInput?.value.trim().toLowerCase() || '';
    const favorites = new Set(getFavorites());
    let visibleCount = 0;

    filterResults.querySelectorAll('.pg_mba_card').forEach((card) => {
        const postId = card.dataset.postId || card.querySelector('.pg-mba-favorite-btn')?.dataset.postId;
        const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
        const searchHidden = Boolean(query) && !title.includes(query);
        const favoritesHidden = favoritesFilterActive && (!postId || !favorites.has(String(postId)));

        card.classList.toggle('is-search-hidden', searchHidden);
        card.classList.toggle('is-favorites-hidden', favoritesHidden);

        if (!searchHidden && !favoritesHidden) {
            visibleCount += 1;
        }
    });

    const noResults = document.getElementById('no-results-message');
    if (!noResults) {
        return;
    }

    const totalCards = filterResults.querySelectorAll('.pg_mba_card').length;

    if (totalCards === 0) {
        return;
    }

    if ((query || favoritesFilterActive) && visibleCount === 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
}

function toggleFavoritesFilter() {
    if (getFavorites().length === 0) {
        return;
    }

    favoritesFilterActive = !favoritesFilterActive;
    syncFavoritesFilterUI();
    updateAllHeartButtons();
    applyPgMbaCardFilters();
    document.dispatchEvent(new CustomEvent('akademiata:filter-results-updated', { detail: { reset: true } }));
}

function bindFavoriteHeartEvents() {
    const root = getPgMbaRoot();
    if (!root) {
        return;
    }

    root.addEventListener('touchend', (event) => {
        const button = event.target.closest('.pg-mba-favorite-btn');
        if (!button || !root.contains(button)) {
            return;
        }

        lastFavoriteTouchAt = Date.now();
        event.preventDefault();
        event.stopPropagation();
        toggleFavorite(button.dataset.postId);
    }, { passive: false });

    root.addEventListener('click', (event) => {
        const button = event.target.closest('.pg-mba-favorite-btn');
        if (!button || !root.contains(button)) {
            return;
        }

        if (Date.now() - lastFavoriteTouchAt < 400) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        toggleFavorite(button.dataset.postId);
    });
}

function bindFavoritesChips() {
    let lastChipTouchAt = 0;

    function handleChipTap(event) {
        if (getFavorites().length === 0) {
            return;
        }

        event.preventDefault();
        toggleFavoritesFilter();
    }

    document.querySelectorAll('.pg-mba-favorites-chip').forEach((chip) => {
        chip.addEventListener('touchend', (event) => {
            if (chip.hidden || chip.disabled) {
                return;
            }

            lastChipTouchAt = Date.now();
            event.preventDefault();
            handleChipTap(event);
        }, { passive: false });

        chip.addEventListener('click', (event) => {
            if (chip.hidden || chip.disabled) {
                return;
            }

            if (Date.now() - lastChipTouchAt < 400) {
                return;
            }

            handleChipTap(event);
        });
    });

    document.querySelectorAll('.pg-mba-favorites-filter__toggle').forEach((input) => {
        input.addEventListener('change', () => {
            if (input.checked && getFavorites().length === 0) {
                input.checked = false;
                return;
            }

            favoritesFilterActive = input.checked;
            syncFavoritesFilterUI();
            updateAllHeartButtons();
            applyPgMbaCardFilters();
            document.dispatchEvent(new CustomEvent('akademiata:filter-results-updated', { detail: { reset: true } }));
        });
    });
}

export function initPgMbaFavorites() {
    if (!getPgMbaRoot()) {
        return;
    }

    bindFavoriteHeartEvents();
    bindFavoritesChips();

    document.querySelector('.pg-mba-mobile-toolbar .offer-mobile-search__input')
        ?.addEventListener('input', applyPgMbaCardFilters);

    document.addEventListener('akademiata:filter-results-updated', () => {
        updateAllHeartButtons();
        updateFavoritesChipCounts();
        applyPgMbaCardFilters();
    });

    updateAllHeartButtons();
    updateFavoritesChipCounts();
    applyPgMbaCardFilters();
}
