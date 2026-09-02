import { initOfferViewToggle } from './offer-view-toggle';

(function ($) {
    if (typeof ajax_filter_params === 'undefined') {
        return;
    }

    const ajaxUrl = ajax_filter_params.ajax_url;
    const currentLang = ajax_filter_params.lang;
    const filterAction = ajax_filter_params.filter_action || 'filter_posts';
    const initialLimit = parseInt(ajax_filter_params.initial_limit, 10);
    const loadAllResults = Number.isNaN(initialLimit) || initialLimit < 0;
    const requestLimit = loadAllResults ? -1 : (initialLimit || 24);
    const loadMoreLimit = loadAllResults ? -1 : (parseInt(ajax_filter_params.load_more_limit, 10) || 18);

    const form = $('#ajax-filter-form');
    const filterResults = $('#filter-results');
    let loadSentinel = $('#filter-load-sentinel');

    if (!form.length || !filterResults.length) {
        return;
    }

    let offset = filterResults.children('.card_post_item').length;
    let noMorePosts = loadAllResults;
    let loading = false;
    let currentAjax = null;
    let activeRequestId = 0;
    let debounceTimer = null;
    let prefetchTimer = null;
    let loadObserver = null;

    function dispatchResultsUpdated(detail = {}) {
        document.dispatchEvent(new CustomEvent('akademiata:filter-results-updated', { detail }));
    }

    function ensureLoadSentinel() {
        if (!loadSentinel.length) {
            loadSentinel = $('<div id="filter-load-sentinel" class="filter-load-sentinel" aria-hidden="true"></div>');
            filterResults.after(loadSentinel);
        }
    }

    function setLoadSentinelVisible(visible) {
        if (visible) {
            ensureLoadSentinel();
            loadSentinel.show();
        } else if (loadSentinel.length) {
            loadSentinel.hide();
        }
    }

    function applyPaginationState(count, limit, startOffset, append) {
        if (count <= 0) {
            if (!append) {
                $('#no-results-message').fadeIn(200);
            }
            noMorePosts = true;
            setLoadSentinelVisible(false);
            return;
        }

        $('#no-results-message').hide();
        offset = startOffset + count;
        noMorePosts = count < limit;
        filterResults.attr('data-next-offset', String(offset));
        filterResults.attr('data-has-more', noMorePosts ? '0' : '1');
        setLoadSentinelVisible(!noMorePosts);
    }

    function schedulePrefetch() {
        if (noMorePosts || loading) {
            return;
        }

        clearTimeout(prefetchTimer);

        const runPrefetch = () => {
            if (!noMorePosts && !loading) {
                loadMorePosts({ background: true });
            }
        };

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(runPrefetch, { timeout: 1500 });
        } else {
            prefetchTimer = setTimeout(runPrefetch, 400);
        }
    }

    function triggerFilterUpdate() {
        if (currentAjax && currentAjax.readyState !== 4) {
            currentAjax.abort();
        }

        clearTimeout(prefetchTimer);

        const thisRequestId = ++activeRequestId;
        loading = true;
        offset = 0;
        noMorePosts = false;
        $('#ajax-loader').show();
        $('#no-results-message').hide();
        setLoadSentinelVisible(false);

        currentAjax = $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: {
                action: filterAction,
                lang: currentLang,
                offset: 0,
                limit: requestLimit,
                form_data: form.serialize(),
            },
            success(response) {
                if (thisRequestId !== activeRequestId) {
                    return;
                }

                if (!response.success || !response.data || response.data.html === undefined) {
                    noMorePosts = true;
                    setLoadSentinelVisible(false);
                    return;
                }

                const newHtml = response.data.html.trim();
                filterResults.html(newHtml);
                offset = filterResults.children('.card_post_item').length;
                noMorePosts = loadAllResults || !response.data.has_more;
                setLoadSentinelVisible(false);

                if (newHtml === '') {
                    $('#no-results-message').fadeIn(200);
                } else {
                    $('#no-results-message').hide();
                }

                dispatchResultsUpdated({ reset: true });
            },
            error(jqXHR, textStatus) {
                if (textStatus === 'abort' || thisRequestId !== activeRequestId) {
                    return;
                }
            },
            complete() {
                if (thisRequestId === activeRequestId) {
                    loading = false;
                    $('#ajax-loader').hide();
                }
            },
        });
    }

    function debouncedFilterUpdate() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(triggerFilterUpdate, 200);
    }

    function loadMorePosts(options = {}) {
        if (loadAllResults) {
            return;
        }

        const background = options.background === true;

        if (loading || noMorePosts) {
            return;
        }

        if (currentAjax && currentAjax.readyState !== 4) {
            currentAjax.abort();
        }

        const thisRequestId = ++activeRequestId;
        const startOffset = offset;
        loading = true;

        if (!background) {
            $('#ajax-loader').show();
        }

        currentAjax = $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: {
                action: filterAction,
                lang: currentLang,
                offset: startOffset,
                limit: loadMoreLimit,
                form_data: form.serialize(),
            },
            success(response) {
                if (thisRequestId !== activeRequestId) {
                    return;
                }

                if (!response.success || !response.data || response.data.html === undefined) {
                    noMorePosts = true;
                    setLoadSentinelVisible(false);
                    return;
                }

                const newHtml = response.data.html.trim();
                if (newHtml !== '') {
                    filterResults.append(newHtml);
                }

                const count = typeof response.data.count === 'number'
                    ? response.data.count
                    : (newHtml ? loadMoreLimit : 0);

                if (typeof response.data.has_more === 'boolean') {
                    noMorePosts = !response.data.has_more;
                    offset = response.data.next_offset ?? (startOffset + count);
                    filterResults.attr('data-next-offset', String(offset));
                    filterResults.attr('data-has-more', noMorePosts ? '0' : '1');
                    setLoadSentinelVisible(!noMorePosts);
                } else {
                    applyPaginationState(count, loadMoreLimit, startOffset, true);
                }

                if (newHtml !== '') {
                    dispatchResultsUpdated({ append: true });
                }

                if (!noMorePosts) {
                    schedulePrefetch();
                }
            },
            error(jqXHR, textStatus) {
                if (textStatus === 'abort' || thisRequestId !== activeRequestId) {
                    return;
                }
            },
            complete() {
                if (thisRequestId === activeRequestId) {
                    loading = false;
                    $('#ajax-loader').hide();
                }
            },
        });
    }

    function initLoadObserver() {
        if (loadAllResults || !('IntersectionObserver' in window)) {
            return;
        }

        ensureLoadSentinel();

        if (loadObserver) {
            loadObserver.disconnect();
        }

        loadObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !loading && !noMorePosts) {
                    loadMorePosts();
                }
            });
        }, {
            root: null,
            rootMargin: '600px 0px',
            threshold: 0,
        });

        if (loadSentinel.length && loadSentinel.is(':visible')) {
            loadObserver.observe(loadSentinel[0]);
        }
    }

    function addTag(label, tagValue) {
        $('.selected_tags_container').each(function () {
            const $container = $(this);
            if ($container.find(`[data-value="${tagValue}"]`).length === 0) {
                $('<span>')
                    .addClass('filter-tag')
                    .attr('data-value', tagValue)
                    .text(`${label} ✕`)
                    .appendTo($container);
            }
        });

        if ($('.selected_tags_container .filter-tag').length > 0) {
            $('#tags-container').show();
        }
        syncDesktopFilterBarVisibility();
    }

    function isMobileOfferListing() {
        return window.matchMedia('(max-width: 990px)').matches
            && document.querySelector('.page-template-page-offer .offer-mobile-toolbar');
    }

    function syncMobilePromoTags() {
        if (!isMobileOfferListing()) {
            return;
        }

        const promoIds = form.find('input[name="promotions[]"]').map(function () {
            return $(this).val();
        }).get();

        // Promos use expandable #offer-promo-info chips (name + short + tag), not plain filter-tags.
        $('.offer-mobile-toolbar .selected_tags_container .filter-tag').each(function () {
            const val = $(this).attr('data-value');
            if (promoIds.includes(val)) {
                $(this).remove();
            }
        });

        placePromoInfoInFilterBar();
        syncDesktopFilterBarVisibility();
    }

    function getCheckboxTagLabel(checkbox) {
        const custom = checkbox.attr('data-tag-label');
        if (custom) {
            return custom.trim();
        }

        const nameEl = checkbox.closest('label').find('.filter-promo-card__name, .filter-promo-label__name').first();
        if (nameEl.length) {
            return nameEl.text().trim();
        }

        return checkbox.closest('label').text().trim();
    }

    function getPromoStack(input) {
        const cached = input.data('promoStack');
        if (Array.isArray(cached)) {
            return cached;
        }

        const raw = input.attr('data-promo-stack');
        if (!raw) {
            return [];
        }

        try {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                input.data('promoStack', parsed);
                return parsed;
            }
        } catch (error) {
            return [];
        }

        return [];
    }

    function reconcilePromoSelection(changedInput) {
        const $changed = $(changedInput);
        if ($changed.attr('name') !== 'promotions[]' || !$changed.is(':checked')) {
            return;
        }

        const promoId = $changed.val();
        const stack = getPromoStack($changed);

        form.find('input[name="promotions[]"]:checked').each(function () {
            const $other = $(this);
            const otherId = $other.val();

            if (otherId === promoId) {
                return;
            }

            if (stack.indexOf(otherId) < 0) {
                $other.prop('checked', false);
            }
        });
    }

    let openPromoInfoIds = {};

    function placePromoInfoInFilterBar() {
        const isMobile = window.matchMedia('(max-width: 990px)').matches;
        let $wrap = isMobile
            ? $('.offer-mobile-toolbar .filter_tags_wrapper').first()
            : $('.offer-listing-selection .filter_tags_wrapper').first();
        if (!$wrap.length) {
            $wrap = $('.offer-listing-selection .filter_tags_wrapper').first();
        }
        const $promo = $('#offer-promo-info');
        const $clear = $wrap.find('.button_clear_filters').first();
        if (!$wrap.length || !$promo.length) {
            return;
        }
        if ($clear.length) {
            $promo.insertBefore($clear);
        } else if (!$promo.parent().is($wrap)) {
            $wrap.append($promo);
        }
    }

    function syncDesktopFilterBarVisibility() {
        const $bar = $('.offer-listing-selection #tags-container').first();
        if (!$bar.length) {
            return;
        }
        const hasTags = $bar.find('.selected_tags_container .filter-tag').length > 0;
        const hasPromos = !$('#offer-promo-info').hasClass('is-empty');
        if (hasTags || hasPromos) {
            $bar.show();
        } else if (!hasTags) {
            // Keep sidebar tags container behavior separate — only hide when this bar has nothing.
            $bar.hide();
        }
    }

    function updatePromoInfoPanel() {
        const $panel = $('#offer-promo-info');
        if (!$panel.length) {
            return;
        }

        $panel.find('.offer-promo-info__item.is-open').each(function () {
            openPromoInfoIds[$(this).attr('data-promo-id')] = true;
        });

        $panel.empty();

        const selected = form.find('input[name="promotions[]"]:checked').toArray();
        if (!selected.length) {
            openPromoInfoIds = {};
            $panel.addClass('is-empty');
            placePromoInfoInFilterBar();
            syncDesktopFilterBarVisibility();
            return;
        }

        const expandLabel = (window.akademiataOffer && akademiataOffer.promoExpand) || 'Pokaż szczegóły promocji';
        const removeLabel = (window.akademiataOffer && akademiataOffer.promoRemove)
            || (window.akademiataOffer && akademiataOffer.clearFilters)
            || 'Usuń promocję';

        selected.forEach((input) => {
            const $input = $(input);
            const promoId = $input.val();
            const name = ($input.attr('data-promo-name') || $input.attr('data-tag-label') || promoId || '').trim();
            const short = ($input.attr('data-promo-short') || '').trim();
            const full = ($input.attr('data-promo-full') || '').trim();
            const tag = ($input.attr('data-promo-tag') || '').trim();
            const hasFull = full !== '';
            const isOpen = !!(hasFull && openPromoInfoIds[promoId]);

            const $item = $('<div>')
                .addClass('offer-promo-info__item')
                .attr('data-promo-id', promoId);

            if (!hasFull) {
                $item.addClass('offer-promo-info__item--no-body');
            }
            if (isOpen) {
                $item.addClass('is-open');
            }

            const $toggle = $('<button>')
                .attr({
                    type: 'button',
                    class: 'offer-promo-info__toggle',
                    'aria-expanded': isOpen ? 'true' : 'false',
                    'aria-label': expandLabel,
                });

            if (!hasFull) {
                $toggle.prop('disabled', true);
            }

            const $main = $('<span>').addClass('offer-promo-info__main');
            $main.append($('<strong>').addClass('offer-promo-info__name').text(name));
            if (short) {
                $main.append($('<span>').addClass('offer-promo-info__short').text(short));
            }
            $toggle.append($main);

            if (tag) {
                $toggle.append($('<span>').addClass('offer-promo-info__tag').text(tag));
            }

            if (hasFull) {
                $toggle.append(
                    $('<span>')
                        .addClass('offer-promo-info__arr')
                        .attr('aria-hidden', 'true')
                        .text(isOpen ? '▴' : '▾')
                );
            }

            const $remove = $('<button>')
                .attr({
                    type: 'button',
                    class: 'offer-promo-info__remove',
                    'aria-label': `${removeLabel}: ${name}`,
                })
                .text('✕');

            $item.append($toggle);
            $item.append($remove);

            if (hasFull) {
                const $body = $('<div>').addClass('offer-promo-info__body').html(full);
                if (!isOpen) {
                    $body.attr('hidden', true);
                }
                $item.append($body);
            }

            $panel.append($item);
        });

        $panel.removeClass('is-empty');
        placePromoInfoInFilterBar();
        syncDesktopFilterBarVisibility();
    }

    function isZarzadzaniePromoBlockEnabled() {
        const flag = form.data('zarzadzaniePromoBlock');
        return flag === 1 || flag === '1';
    }

    function isZarzadzanieProgramSelected() {
        const slugsRaw = form.data('zarzadzanieProgramSlugs');
        const slugs = Array.isArray(slugsRaw) && slugsRaw.length
            ? slugsRaw
            : [form.data('zarzadzanieProgramSlug') || 'zarzadzanie'];
        return slugs.some((slug) => form.find(`input[name="program[]"][value="${slug}"]:checked`).length > 0);
    }

    function updateZarzadzaniePromoBlock() {
        if (!isZarzadzaniePromoBlockEnabled()) {
            $('.taxonomy_group--promotions').removeClass('is-zarzadzanie-blocked');
            form.find('input[name="promotions[]"]').prop('disabled', false);
            form.find('.filter-promo-card').removeClass('is-disabled');
            return;
        }

        const blocked = isZarzadzanieProgramSelected();
        const $group = $('.taxonomy_group--promotions');
        $group.toggleClass('is-zarzadzanie-blocked', blocked);

        form.find('input[name="promotions[]"]').each(function () {
            const $input = $(this);
            const $card = $input.closest('.filter-promo-card');
            if (blocked) {
                $input.prop('checked', false).prop('disabled', true);
                $card.addClass('is-disabled');
            } else {
                $input.prop('disabled', false);
                $card.removeClass('is-disabled');
            }
        });

        if (blocked) {
            $('#offer-promo-info').empty().addClass('is-empty');
            Object.keys(openPromoInfoIds).forEach((key) => {
                delete openPromoInfoIds[key];
            });
        }
    }

    function updatePromoStackStates() {
        updateZarzadzaniePromoBlock();
        const checkedIds = [];

        form.find('input[name="promotions[]"]:checked').each(function () {
            checkedIds.push($(this).val());
        });

        form.find('input[name="promotions[]"]').each(function () {
            const $input = $(this);
            const stack = getPromoStack($input);
            let canSelect = true;

            if ($input.is(':disabled') || $input.closest('.filter-promo-card').hasClass('is-disabled')) {
                canSelect = false;
            } else if (!$input.is(':checked')) {
                checkedIds.forEach((oid) => {
                    if (stack.indexOf(oid) < 0) {
                        canSelect = false;
                    }
                });
            }

            $input.closest('.filter-promo-card').toggleClass('is-disabled', !canSelect && !$input.is(':checked'));
        });

        updatePromoInfoPanel();
        syncMobilePromoTags();
    }

    function sanitizePromoSelectionFromUrl() {
        const checked = form.find('input[name="promotions[]"]:checked').toArray();

        if (checked.length <= 1) {
            updatePromoStackStates();
            return;
        }

        const keptIds = [];

        checked.forEach((input) => {
            const $input = $(input);
            const stack = getPromoStack($input);
            const canKeep = keptIds.every((oid) => stack.indexOf(oid) >= 0);

            if (canKeep) {
                keptIds.push($input.val());
                return;
            }

            $input.prop('checked', false);
            removeTag($input.val());
        });

        updatePromoStackStates();
    }

    function removeTag(tagValue) {
        $('.selected_tags_container').find(`[data-value="${tagValue}"]`).remove();

        if ($('.selected_tags_container .filter-tag').length === 0) {
            // Don't hide the whole bar if expandable promo chips are still active.
            syncDesktopFilterBarVisibility();
            if ($('.offer-listing-selection #offer-promo-info').hasClass('is-empty')
                && $('.selected_tags_container .filter-tag').length === 0) {
                $('#tags-container').hide();
            }
        } else {
            syncDesktopFilterBarVisibility();
        }
    }

    function updateBrowserUrl() {
        const params = new URLSearchParams();
        form.find('input[type="checkbox"]:checked').each(function () {
            let name = $(this).attr('name').replace('[]', '');
            // Public listing links use ?promo= (same as calculator; avoids WP taxonomy clash).
            if (name === 'promotions') {
                name = 'promo';
            }
            params.append(name, $(this).val());
        });
        const query = params.toString();
        const newUrl = query
            ? `${window.location.pathname}?${query}`
            : window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }

    function initializeFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        let hasPromoInUrl = false;

        urlParams.forEach((value, key) => {
            const formKey = (key === 'promo') ? 'promotions' : key;
            if (formKey === 'promotions') {
                hasPromoInUrl = true;
            }
            const checkbox = form.find(`input[name="${formKey}[]"][value="${value}"]`);
            if (checkbox.length) {
                checkbox.prop('checked', true);
                // Promotions only appear in #offer-promo-info (expandable), not as Filtry pills.
                if (formKey !== 'promotions') {
                    addTag(getCheckboxTagLabel(checkbox), value);
                }
            }
        });

        if ($('.selected_tags_container .filter-tag').length === 0) {
            $('#tags-container').hide();
        }

        sanitizePromoSelectionFromUrl();

        if (isMobileOfferListing()) {
            syncMobilePromoTags();
        } else {
            // Desktop promos use expandable #offer-promo-info chips, not Filtry pills.
            form.find('input[name="promotions[]"]').each(function () {
                $('.selected_tags_container').find(`[data-value="${$(this).val()}"]`).remove();
            });
        }
        syncDesktopFilterBarVisibility();

        noMorePosts = loadAllResults;
        setLoadSentinelVisible(false);

        if (!loadAllResults && (hasPromoInUrl || filterResults.children('.card_post_item').length === 0)) {
            triggerFilterUpdate();
        } else if (filterResults.children('.card_post_item').length > 0) {
            dispatchResultsUpdated();
        }
    }

    $(document).on('click', '#offer-promo-info .offer-promo-info__toggle', function (event) {
        event.preventDefault();
        const $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }

        const $item = $btn.closest('.offer-promo-info__item');
        const $body = $item.find('.offer-promo-info__body');
        const willOpen = !$item.hasClass('is-open');
        const promoId = $item.attr('data-promo-id');

        $item.toggleClass('is-open', willOpen);
        $btn.attr('aria-expanded', willOpen ? 'true' : 'false');
        $item.find('.offer-promo-info__arr').text(willOpen ? '▴' : '▾');

        if (willOpen) {
            $body.removeAttr('hidden');
            openPromoInfoIds[promoId] = true;
            if (window.matchMedia('(max-width: 990px)').matches) {
                window.requestAnimationFrame(() => {
                    $item[0]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                });
            }
        } else {
            $body.attr('hidden', true);
            delete openPromoInfoIds[promoId];
        }
    });

    $(document).on('click', '#offer-promo-info .offer-promo-info__remove', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const promoId = $(this).closest('.offer-promo-info__item').attr('data-promo-id');
        if (!promoId) {
            return;
        }
        const $input = form.find(`input[name="promotions[]"][value="${promoId}"]`);
        if ($input.length) {
            $input.prop('checked', false);
        }
        updatePromoStackStates();
        triggerFilterUpdate();
        updateBrowserUrl();
    });

    form.on('change', 'input[type="checkbox"]', function () {
        const checkbox = $(this);

        if (checkbox.attr('name') === 'promotions[]') {
            if (checkbox.is(':checked')) {
                if (checkbox.closest('.filter-promo-card').hasClass('is-disabled')) {
                    checkbox.prop('checked', false);
                    return;
                }
                reconcilePromoSelection(checkbox);
            }
            updatePromoStackStates();
            triggerFilterUpdate();
            updateBrowserUrl();
            return;
        }

        const label = getCheckboxTagLabel(checkbox);
        const tagValue = checkbox.val();

        if (checkbox.is(':checked')) {
            addTag(label, tagValue);
        } else {
            removeTag(tagValue);
        }

        updatePromoStackStates();
        triggerFilterUpdate();
        updateBrowserUrl();
    });

    $(document).on('click', '.offer_wrapper--offer-page .filter-tag', function () {
        const tag = $(this);
        const tagValue = tag.data('value');

        form.find(`input[value="${tagValue}"]`).prop('checked', false);
        removeTag(tagValue);
        updatePromoStackStates();

        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    $('#clear-filters, .offer_wrapper--offer-page .clear-filters').on('click', () => {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        updatePromoStackStates();
        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    form.on('clear-filters', function () {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        updatePromoStackStates();
        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    initializeFiltersFromURL();
    updatePromoStackStates();
    initOfferViewToggle();

})(jQuery);
