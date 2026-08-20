import { initOfferViewToggle } from './offer-view-toggle';

(function ($) {
    if (typeof ajax_filter_pg_mba_params === 'undefined') {
        return;
    }

    const ajaxUrl = ajax_filter_pg_mba_params.ajax_url;
    const filterAction = ajax_filter_pg_mba_params.filter_action;
    const currentLang = ajax_filter_pg_mba_params.lang;

    const form = $('#ajax-filter-pg-mba-form');
    const filterResults = $('#filter-results');

    if (!form.length || !filterResults.length) {
        return;
    }

    let loading = false;
    let currentAjax = null;
    let activeRequestId = 0;
    let debounceTimer = null;

    function triggerFilterUpdate() {
        if (currentAjax && currentAjax.readyState !== 4) {
            currentAjax.abort();
        }

        const thisRequestId = ++activeRequestId;
        loading = true;
        $('#ajax-loader').show();
        $('#no-results-message').hide();

        currentAjax = $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: {
                action: filterAction,
                lang: currentLang,
                form_data: form.serialize(),
            },
            success(response) {
                if (thisRequestId !== activeRequestId) {
                    return;
                }

                if (response.success && response.data && response.data.html !== undefined) {
                    const newHtml = response.data.html.trim();
                    filterResults.html(newHtml);

                    if (newHtml === '') {
                        $('#no-results-message').fadeIn(200);
                    } else {
                        $('#no-results-message').hide();
                    }
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

    function getFilterUrlKeysSorted() {
        const keys = [];
        const seen = {};
        form.find('input[type="checkbox"][name$="[]"]').each(function () {
            const name = ($(this).attr('name') || '').replace('[]', '');
            if (name && !seen[name]) {
                seen[name] = true;
                keys.push(name);
            }
        });
        keys.sort((a, b) => b.length - a.length);
        return keys;
    }

    function buildCampaignSafeFilterQuery() {
        const tokens = [];
        form.find('input[type="checkbox"]:checked').each(function () {
            const name = ($(this).attr('name') || '').replace('[]', '');
            const value = $(this).val();
            if (name && value) {
                tokens.push(`${name}-${value}`);
            }
        });
        return tokens.join(',');
    }

    function parseCampaignSafeFilterTokens(queryString) {
        const selected = {};
        const keys = getFilterUrlKeysSorted();
        if (!queryString) {
            return selected;
        }

        queryString.split('&').forEach((pair) => {
            if (!pair) {
                return;
            }
            if (pair.indexOf('=') !== -1) {
                const parts = pair.split('=');
                const key = decodeURIComponent((parts[0] || '').replace(/\+/g, ' ')).replace(/\[\]$/, '');
                const value = decodeURIComponent((parts[1] || '').replace(/\+/g, ' '));
                if (!key || !value) {
                    return;
                }
                if (!selected[key]) {
                    selected[key] = [];
                }
                selected[key].push(value);
                return;
            }

            decodeURIComponent(pair.replace(/\+/g, ' ')).split(',').forEach((token) => {
                token = token.trim();
                if (!token) {
                    return;
                }
                for (let i = 0; i < keys.length; i++) {
                    const key = keys[i];
                    const prefix = `${key}-`;
                    if (token.indexOf(prefix) !== 0) {
                        continue;
                    }
                    const value = token.slice(prefix.length);
                    if (!value) {
                        break;
                    }
                    if (!selected[key]) {
                        selected[key] = [];
                    }
                    selected[key].push(value);
                    break;
                }
            });
        });

        return selected;
    }

    function updateBrowserUrl() {
        const query = buildCampaignSafeFilterQuery();
        const newUrl = query
            ? `${window.location.pathname}?${query}`
            : window.location.pathname;

        window.history.replaceState({}, '', newUrl);
    }

    function clearAllFilters() {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        debouncedFilterUpdate();
        updateBrowserUrl();
    }

    function initializeFiltersFromURL() {
        const selected = parseCampaignSafeFilterTokens(window.location.search.replace(/^\?/, ''));
        let hasFilters = false;

        Object.keys(selected).forEach((key) => {
            (selected[key] || []).forEach((value) => {
                const checkbox = form.find(`input[name="${key}[]"][value="${value}"]`);
                if (checkbox.length) {
                    checkbox.prop('checked', true);
                    const label = checkbox.closest('label').text().trim();
                    addTag(label, value);
                    hasFilters = true;
                }
            });
        });

        triggerFilterUpdate();

        if (!hasFilters && $('.selected_tags_container .filter-tag').length === 0) {
            $('#tags-container').hide();
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

    $(document).on('click', '.offer_wrapper--pg-mba .filter-tag', function () {
        const tag = $(this);
        const tagValue = tag.data('value');

        form.find(`input[value="${tagValue}"]`).prop('checked', false);
        removeTag(tagValue);

        debouncedFilterUpdate();
        updateBrowserUrl();
    });

    $(document).on('click', '.offer_wrapper--pg-mba #clear-filters, .offer_wrapper--pg-mba .button_clear_filters', clearAllFilters);

    form.on('clear-filters', clearAllFilters);

    initializeFiltersFromURL();
    initOfferViewToggle();
})(jQuery);
