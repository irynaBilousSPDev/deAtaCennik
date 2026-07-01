const STORAGE_KEY = 'akademiata_offer_favorites_v2';
const LEGACY_STORAGE_KEY = 'akademiata_offer_favorites';
const SCOPES = ['bachelor', 'master', 'offer'];
const DEGREE_SCOPES = ['bachelor', 'master'];

let favoritesFilterActive = false;
let lastFavoriteTouchAt = 0;

function getOfferRoot() {
    return document.querySelector('.offer_wrapper--offer-page');
}

function getFilterResults() {
    return document.querySelector('#filter-results');
}

function getFavoritesScope() {
    const scope = window.akademiataOffer?.favoritesScope;
    return SCOPES.includes(scope) ? scope : 'offer';
}

function isCombinedOfferScope() {
    return getFavoritesScope() === 'offer';
}

function emptyStorageData() {
    return {
        bachelor: [],
        master: [],
        offer: [],
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
                    _legacy: Array.isArray(parsed._legacy) ? parsed._legacy.map(String) : [],
                };
            }
        }
    } catch (error) {
        // ignore
    }

    const data = emptyStorageData();

    try {
        const legacyRaw = window.localStorage.getItem(LEGACY_STORAGE_KEY);
        const legacy = legacyRaw ? JSON.parse(legacyRaw) : [];
        if (Array.isArray(legacy) && legacy.length) {
            data._legacy = legacy.map(String);
        }
    } catch (error) {
        // ignore
    }

    writeStorageData(data);
    return data;
}

function writeStorageData(data) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    if (data._legacy && data._legacy.length === 0) {
        window.localStorage.removeItem(LEGACY_STORAGE_KEY);
    }
}

function getPostTypeForId(postId) {
    const id = String(postId);
    const card = document.querySelector(`.card_post_item[data-post-id="${id}"]`);
    const fromCard = card?.dataset.postType;
    if (DEGREE_SCOPES.includes(fromCard)) {
        return fromCard;
    }

    const button = document.querySelector(`.offer-favorite-btn[data-post-id="${id}"]`);
    const fromButton = button?.dataset.postType;
    if (DEGREE_SCOPES.includes(fromButton)) {
        return fromButton;
    }

    return null;
}

function resolveStorageScopeForPost(postId) {
    const pageScope = getFavoritesScope();
    if (pageScope === 'bachelor' || pageScope === 'master') {
        return pageScope;
    }

    return getPostTypeForId(postId);
}

function getVisiblePostIds() {
    const filterResults = getFilterResults();
    if (!filterResults) {
        return new Set();
    }

    const ids = new Set();
    filterResults.querySelectorAll('.card_post_item').forEach((card) => {
        const postId = card.dataset.postId || card.querySelector('.offer-favorite-btn')?.dataset.postId;
        if (postId) {
            ids.add(String(postId));
        }
    });

    return ids;
}

function migrateIdsToDegreeBuckets(data, ids) {
    ids.forEach((id) => {
        const postType = getPostTypeForId(id);
        if (!DEGREE_SCOPES.includes(postType)) {
            return;
        }

        data[postType] = [...new Set([...(data[postType] || []), String(id)])];
    });
}

function migratePendingFavorites(data, scope) {
    if (!data._legacy?.length && !data.offer?.length) {
        return data;
    }

    const visible = getVisiblePostIds();
    const pending = [...new Set([...(data._legacy || []), ...(data.offer || [])].map(String))];
    const toMigrate = pending.filter((id) => visible.has(id));

    if (!toMigrate.length) {
        return data;
    }

    if (scope === 'offer') {
        migrateIdsToDegreeBuckets(data, toMigrate);
    } else {
        data[scope] = [...new Set([...(data[scope] || []), ...toMigrate])];
    }

    const migrated = new Set(toMigrate);
    data._legacy = (data._legacy || []).filter((id) => !migrated.has(String(id)));
    data.offer = (data.offer || []).filter((id) => !migrated.has(String(id)));
    writeStorageData(data);

    return data;
}

function getCombinedFavorites(data) {
    return [...new Set([
        ...(data.bachelor || []),
        ...(data.master || []),
        ...(data.offer || []),
    ].map(String))];
}

function getFavorites() {
    const scope = getFavoritesScope();
    const data = migratePendingFavorites(readStorageData(), scope);

    if (isCombinedOfferScope()) {
        return getCombinedFavorites(data);
    }

    return Array.isArray(data[scope]) ? data[scope].map(String) : [];
}

function isFavorite(postId) {
    const id = String(postId);
    const data = readStorageData();

    if (isCombinedOfferScope()) {
        return getCombinedFavorites(data).includes(id);
    }

    const storageScope = resolveStorageScopeForPost(id);
    if (!storageScope) {
        return false;
    }

    return (data[storageScope] || []).map(String).includes(id);
}

export function isFavoritesFilterActive() {
    return favoritesFilterActive;
}

export function deactivateFavoritesFilter() {
    favoritesFilterActive = false;
    syncFavoritesFilterUI();

    const noResults = document.getElementById('no-results-message');
    if (noResults) {
        noResults.style.display = 'none';
    }

    applyOfferCardFilters();
}

function syncFavoritesFilterUI() {
    document.querySelectorAll('.offer-favorites-chip').forEach((chip) => {
        chip.classList.toggle('is-active', favoritesFilterActive);
    });

    document.querySelectorAll('.offer-favorites-filter__toggle').forEach((input) => {
        input.checked = favoritesFilterActive;
    });
}

function toggleFavorite(postId) {
    const id = String(postId);
    const storageScope = resolveStorageScopeForPost(id);

    if (!DEGREE_SCOPES.includes(storageScope)) {
        return;
    }

    const data = readStorageData();
    const list = (data[storageScope] || []).map(String);
    const next = list.includes(id)
        ? list.filter((item) => item !== id)
        : [...list, id];

    data[storageScope] = next;

    if (data.offer?.includes(id)) {
        data.offer = data.offer.filter((item) => String(item) !== id);
    }

    writeStorageData(data);
    updateFavoritesChipCounts();
    document.querySelectorAll(`.offer-favorite-btn[data-post-id="${id}"]`).forEach(updateHeartButton);
    applyOfferCardFilters();
}

function getFavoriteAriaLabel(isActive) {
    const config = window.akademiataOffer || {};

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
    migratePendingFavorites(readStorageData(), getFavoritesScope());
    document.querySelectorAll('.offer-favorite-btn').forEach(updateHeartButton);
}

function updateFavoritesChipCounts() {
    const count = getFavorites().length;
    const suffix = count > 0 ? ` (${count})` : '';

    document.querySelectorAll('.offer-favorites-chip__count').forEach((element) => {
        element.textContent = suffix;
    });

    document.querySelectorAll('.offer-favorites-filter__count').forEach((element) => {
        element.textContent = suffix;
    });

    document.querySelectorAll('.offer-favorites-chip').forEach((chip) => {
        chip.hidden = count === 0;
        chip.disabled = count === 0;
    });

    const desktopFilter = document.getElementById('offer-favorites-filter-desktop');
    if (desktopFilter) {
        desktopFilter.hidden = count === 0;
        desktopFilter.classList.toggle('has-favorites', count > 0);

        if (count === 0 && favoritesFilterActive) {
            deactivateFavoritesFilter();
        }
    }
}

export function applyOfferCardFilters() {
    const filterResults = getFilterResults();
    if (!filterResults) {
        return;
    }

    const searchInput = document.querySelector('.offer-mobile-search__input');
    const query = searchInput?.value.trim().toLowerCase() || '';
    const favorites = new Set(getFavorites());
    let visibleFavoriteCount = 0;

    filterResults.querySelectorAll('.card_post_item').forEach((card) => {
        const postId = card.dataset.postId || card.querySelector('.offer-favorite-btn')?.dataset.postId;
        const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
        const searchHidden = Boolean(query) && !title.includes(query);
        const favoritesHidden = favoritesFilterActive && (!postId || !favorites.has(String(postId)));

        card.classList.toggle('is-search-hidden', searchHidden);
        card.classList.toggle('is-favorites-hidden', favoritesHidden);

        if (!searchHidden && !favoritesHidden) {
            visibleFavoriteCount += 1;
        }
    });

    const noResults = document.getElementById('no-results-message');
    if (noResults && favoritesFilterActive) {
        const showEmpty = visibleFavoriteCount === 0;
        noResults.style.display = showEmpty ? 'block' : 'none';
    }
}

function toggleFavoritesFilter() {
    if (getFavorites().length === 0) {
        return;
    }

    favoritesFilterActive = !favoritesFilterActive;
    syncFavoritesFilterUI();
    updateAllHeartButtons();
    applyOfferCardFilters();
}

function bindFavoriteHeartEvents() {
    const root = getOfferRoot();
    if (!root) {
        return;
    }

    root.addEventListener('touchend', (event) => {
        const button = event.target.closest('.offer-favorite-btn');
        if (!button || !root.contains(button)) {
            return;
        }

        lastFavoriteTouchAt = Date.now();
        event.preventDefault();
        event.stopPropagation();
        toggleFavorite(button.dataset.postId);
    }, { passive: false });

    root.addEventListener('click', (event) => {
        const button = event.target.closest('.offer-favorite-btn');
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

    document.querySelectorAll('.offer-favorites-chip').forEach((chip) => {
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

    document.querySelectorAll('.offer-favorites-filter__toggle').forEach((input) => {
        input.addEventListener('change', () => {
            if (input.checked && getFavorites().length === 0) {
                input.checked = false;
                return;
            }

            favoritesFilterActive = input.checked;
            syncFavoritesFilterUI();
            updateAllHeartButtons();
            applyOfferCardFilters();
        });
    });
}

export function initOfferFavorites() {
    if (!getOfferRoot()) {
        return;
    }

    bindFavoriteHeartEvents();
    bindFavoritesChips();

    document.querySelector('.offer-mobile-search__input')
        ?.addEventListener('input', applyOfferCardFilters);

    document.addEventListener('akademiata:filter-results-updated', () => {
        updateAllHeartButtons();
        updateFavoritesChipCounts();
        applyOfferCardFilters();
    });

    updateAllHeartButtons();
    updateFavoritesChipCounts();
    applyOfferCardFilters();
}
