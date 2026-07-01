const STORAGE_KEY = 'akademiata_offer_favorites';
let favoritesFilterActive = false;

function getOfferRoot() {
    return document.querySelector('.offer_wrapper--offer-page');
}

function getFilterResults() {
    return document.querySelector('#filter-results');
}

function getFavorites() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch (error) {
        return [];
    }
}

function setFavorites(ids) {
    const unique = [...new Set(ids.map(String))];
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(unique));
    updateFavoritesChipCounts();
}

function isFavorite(postId) {
    return getFavorites().includes(String(postId));
}

export function isFavoritesFilterActive() {
    return favoritesFilterActive;
}

export function deactivateFavoritesFilter() {
    favoritesFilterActive = false;
    document.querySelectorAll('.offer-favorites-chip').forEach((chip) => {
        chip.classList.remove('is-active');
    });
    applyOfferCardFilters();
}

function toggleFavorite(postId) {
    const id = String(postId);
    const favorites = getFavorites();
    const next = favorites.includes(id)
        ? favorites.filter((item) => item !== id)
        : [...favorites, id];

    setFavorites(next);
    updateHeartButton(document.querySelector(`.offer-favorite-btn[data-post-id="${id}"]`));
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
    document.querySelectorAll('.offer-favorite-btn').forEach(updateHeartButton);
}

function updateFavoritesChipCounts() {
    const count = getFavorites().length;
    const suffix = count > 0 ? ` (${count})` : '';

    document.querySelectorAll('.offer-favorites-chip__count').forEach((element) => {
        element.textContent = suffix;
    });
}

export function applyOfferCardFilters() {
    const filterResults = getFilterResults();
    if (!filterResults) {
        return;
    }

    const searchInput = document.querySelector('.offer-mobile-search__input');
    const query = searchInput?.value.trim().toLowerCase() || '';
    const favorites = new Set(getFavorites());

    filterResults.querySelectorAll('.card_post_item').forEach((card) => {
        const postId = card.dataset.postId || card.querySelector('.offer-favorite-btn')?.dataset.postId;
        const title = card.querySelector('h2')?.textContent.toLowerCase() || '';
        const searchHidden = Boolean(query) && !title.includes(query);
        const favoritesHidden = favoritesFilterActive && (!postId || !favorites.has(String(postId)));

        card.classList.toggle('is-search-hidden', searchHidden);
        card.classList.toggle('is-favorites-hidden', favoritesHidden);
    });
}

function toggleFavoritesFilter() {
    favoritesFilterActive = !favoritesFilterActive;

    document.querySelectorAll('.offer-favorites-chip').forEach((chip) => {
        chip.classList.toggle('is-active', favoritesFilterActive);
    });

    applyOfferCardFilters();
}

function bindFavoritesChips() {
    document.querySelectorAll('.offer-favorites-chip').forEach((chip) => {
        chip.addEventListener('click', (event) => {
            event.preventDefault();
            toggleFavoritesFilter();
        });
    });
}

export function initOfferFavorites() {
    if (!getOfferRoot()) {
        return;
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.offer-favorite-btn');
        if (!button || !getOfferRoot().contains(button)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        toggleFavorite(button.dataset.postId);
    });

    document.querySelector('.offer-mobile-search__input')
        ?.addEventListener('input', applyOfferCardFilters);

    document.addEventListener('akademiata:filter-results-updated', () => {
        updateAllHeartButtons();
        applyOfferCardFilters();
    });

    bindFavoritesChips();
    updateAllHeartButtons();
    updateFavoritesChipCounts();
    applyOfferCardFilters();
}
