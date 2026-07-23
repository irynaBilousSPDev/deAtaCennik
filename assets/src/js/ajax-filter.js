import { initOfferViewToggle } from './offer-view-toggle';

(function ($) {
    if (typeof ajax_filter_params === 'undefined') {
        return;
    }

    const ajaxUrl = ajax_filter_params.ajax_url;
    const currentLang = ajax_filter_params.lang;
    const filterAction = ajax_filter_params.filter_action || 'filter_posts';
    const initialLimit = parseInt(ajax_filter_params.initial_limit, 10) || 24;
    const loadMoreLimit = parseInt(ajax_filter_params.load_more_limit, 10) || 18;

    const form = $('#ajax-filter-form');
    const filterResults = $('#filter-results');
    let loadSentinel = $('#filter-load-sentinel');

    if (!form.length || !filterResults.length) {
        return;
    }

    let offset = parseInt(filterResults.data('next-offset'), 10);
    if (Number.isNaN(offset)) {
        offset = filterResults.children('.card_post_item').length || initialLimit;
    }

    let noMorePosts = filterResults.data('has-more') === 0 || filterResults.data('has-more') === '0';
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
                limit: initialLimit,
                form_data: form.serialize(),
            },
            success(response) {
                if (thisRequestId !== activeRequestId) {
                    return;
                }

                if (!response.success || !response.data || response.data.html === undefined) {
                    noMorePosts = true;
                    return;
                }

                const newHtml = response.data.html.trim();
                filterResults.html(newHtml);
                applyPaginationState(
                    response.data.count ?? (newHtml ? initialLimit : 0),
                    initialLimit,
                    0,
                    false
                );
                dispatchResultsUpdated({ reset: true });
                if (!noMorePosts) {
                    initLoadObserver();
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

    function debouncedFilterUpdate() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(triggerFilterUpdate, 200);
    }

    function loadMorePosts(options = {}) {
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
        if (!('IntersectionObserver' in window)) {
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
    }

    function removeTag(tagValue) {
        $('.selected_tags_container').find(`[data-value="${tagValue}"]`).remove();

        if ($('.selected_tags_container .filter-tag').length === 0) {
            $('#tags-container').hide();
        }
    }

    function updateBrowserUrl() {
        const params = new URLSearchParams();
        form.find('input[type="checkbox"]:checked').each(function () {
            const name = $(this).attr('name').replace('[]', '');
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

        urlParams.forEach((value, key) => {
            const checkbox = form.find(`input[name="${key}[]"][value="${value}"]`);
            if (checkbox.length) {
                checkbox.prop('checked', true);
                const label = checkbox.closest('label').text().trim();
                addTag(label, value);
            }
        });

        if ($('.selected_tags_container .filter-tag').length === 0) {
            $('#tags-container').hide();
        }

        if (filterResults.children('.card_post_item').length === 0) {
            triggerFilterUpdate();
        } else {
            dispatchResultsUpdated();
            if (!noMorePosts) {
                setLoadSentinelVisible(true);
                initLoadObserver();
                schedulePrefetch();
            }
        }
    }

    form.on('change', 'input[type="checkbox"]', function () {
        const checkbox = $(this);
        const label = checkbox.closest('label').text().trim();
        const tagValue = checkbox.val();

        if (checkbox.is(':checked')) {
            addTag(label, tagValue);
        } else {
            removeTag(tagValue);
        }

        triggerFilterUpdate();
        updateBrowserUrl();
    });

    $(document).on('click', '.offer_wrapper--offer-page .filter-tag', function () {
        const tag = $(this);
        const tagValue = tag.data('value');

        form.find(`input[value="${tagValue}"]`).prop('checked', false);
        removeTag(tagValue);

        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    $('#clear-filters, .offer_wrapper--offer-page .clear-filters').on('click', () => {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    form.on('clear-filters', function () {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    initializeFiltersFromURL();
    initOfferViewToggle();

})(jQuery);
