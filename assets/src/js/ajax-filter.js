(function ($) {
    const ajaxUrl = ajax_filter_params.ajax_url;
    const pageId = ajax_filter_params.page_id;
    const currentLang = ajax_filter_params.lang;

    const filter_bachelor = {
        122: 'filter_bachelor',
        17712: 'filter_bachelor',
        17904: 'filter_bachelor',
        17905: 'filter_bachelor',
        120: 'filter_master',
        17713: 'filter_master',
        17906: 'filter_master',
        17907: 'filter_master',
    }[pageId] || 'filter_posts';

    const form = $('#ajax-filter-form');
    const filterResults = $('#filter-results');
    let updateURL = false;

    let offset = 0;
    const limit = 5;
    let loading = false;
    let noMorePosts = false;
    let currentAjax = null;
    let activeRequestId = 0;
    let debounceTimer = null;


    function triggerFilterUpdate() {
        offset = 0;
        noMorePosts = false;

        if (currentAjax && currentAjax.readyState !== 4) {
            currentAjax.abort(); // cancel previous request
        }

        const thisRequestId = ++activeRequestId;
        loading = true;
        $('#ajax-loader').show();
        $('#no-results-message').hide();

        const currentLimit = 9;
        const formData = $('#ajax-filter-form').serialize();

        currentAjax = $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: {
                action: filter_bachelor,
                lang: currentLang,
                offset: 0,
                limit: currentLimit,
                form_data: formData,
            },
            success(response) {
                if (thisRequestId !== activeRequestId) return; // ️Ignore outdated response

                if (response.success && response.data && response.data.html !== undefined) {
                    const newHtml = response.data.html.trim();
                    filterResults.html(newHtml);

                    if (newHtml === '') {
                        $('#no-results-message').fadeIn(200);
                        noMorePosts = true;
                    } else {
                        $('#no-results-message').hide();
                        offset = currentLimit;
                    }
                } else {
                    noMorePosts = true;
                }
            },
            error(jqXHR, textStatus) {
                if (textStatus === 'abort' || thisRequestId !== activeRequestId) return;
            },
            complete() {
                if (thisRequestId === activeRequestId) {
                    loading = false;
                    $('#ajax-loader').hide();
                }
            }
        });
    }


    function debouncedFilterUpdate() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            requestAnimationFrame(() => {
                setTimeout(() => {
                    triggerFilterUpdate();
                }, 0);
            });
        }, 200);
    }
    function loadMorePosts(reset = false) {
        if (loading || noMorePosts) return;

        const thisRequestId = ++activeRequestId;

        if (currentAjax && currentAjax.readyState !== 4) {
            currentAjax.abort();
        }

        loading = true;
        $('#ajax-loader').show();
        $('#no-results-message').hide();

        let currentLimit = reset ? 9 : 5;
        if (reset) {
            offset = 0;
            noMorePosts = false;
            filterResults.html('');
        }

        currentAjax = $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: {
                action: filter_bachelor,
                lang: currentLang,
                offset: offset,
                limit: currentLimit,
                form_data: $('#ajax-filter-form').serialize()
            },
            success(response) {
                if (thisRequestId !== activeRequestId) return;

                if (response.success && response.data && response.data.html !== undefined) {
                    const newHtml = response.data.html.trim();

                    if (reset || offset === 0) {
                        filterResults.html(newHtml);
                    } else {
                        filterResults.append(newHtml);
                    }

                    if (newHtml === '') {
                        if (offset === 0 && reset) {
                            $('#no-results-message').fadeIn(200);
                        }
                        noMorePosts = true;
                    } else {
                        $('#no-results-message').hide();
                        offset += currentLimit;
                    }
                } else {
                    noMorePosts = true;
                }
            },
            error(jqXHR, textStatus) {
                if (textStatus === 'abort' || thisRequestId !== activeRequestId) return;
            },
            complete() {
                if (thisRequestId === activeRequestId) {
                    loading = false;
                    $('#ajax-loader').hide();
                }
            }
        });
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

    function initializeFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const data = {};

        urlParams.forEach((value, key) => {
            const checkbox = form.find(`input[name="${key}[]"][value="${value}"]`);
            if (checkbox.length) {
                checkbox.prop('checked', true);
                const label = checkbox.closest('label').text().trim();
                addTag(label, value);
                data[key] = data[key] || [];
                data[key].push(value);
            }
        });

        if (Object.keys(data).length > 0) {
            triggerFilterUpdate();
            updateURL = true;
        } else {
            loadMorePosts(true);
        }

        if ($('.selected_tags_container .filter-tag').length === 0) {
            $('#tags-container').hide();
        }
    }

    let wheelTimeout;

    $(window).on('wheel', function () {
        clearTimeout(wheelTimeout);
        wheelTimeout = setTimeout(() => {
            if (!loading) handleScrollLoad();
        }, 150);
    });

    function handleScrollLoad() {
        if (loading || noMorePosts) return;

        const scrollTop = $(window).scrollTop();
        const windowHeight = $(window).height();
        const documentHeight = $(document).height();

        const scrollPosition = scrollTop + windowHeight;
        const halfwayPoint = documentHeight * 0.5;

        if (scrollPosition >= halfwayPoint) {
            loadMorePosts();
        }
    }
    let filterChangeTimeout;

    form.on('change', 'input[type="checkbox"]', function () {
        const checkbox = $(this);
        const label = checkbox.closest('label').text().trim();
        const tagValue = checkbox.val();

        if (checkbox.is(':checked')) {
            addTag(label, tagValue);
        } else {
            removeTag(tagValue);
        }

        triggerFilterUpdate(); //  replace debounce

        if (updateURL) {
            const params = new URLSearchParams();
            form.find('input[type="checkbox"]:checked').each(function () {
                const name = $(this).attr('name').replace('[]', '');
                const value = $(this).val();
                params.append(name, value);
            });
            window.history.pushState({}, '', '?' + params.toString());
        }
    });



    $(document).on('click', '.filter-tag', function () {
        const tag = $(this);
        const tagValue = tag.data('value');

        form.find(`input[value="${tagValue}"]`).prop('checked', false);
        removeTag(tagValue);

        debouncedFilterUpdate();

        if (updateURL) {
            const params = new URLSearchParams();
            form.find('input[type="checkbox"]:checked').each(function () {
                const name = $(this).attr('name').replace('[]', '');
                const value = $(this).val();
                params.append(name, value);
            });
            window.history.pushState({}, '', '?' + params.toString());
        }
    });

    $('#clear-filters').on('click', () => {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        debouncedFilterUpdate();

        if (updateURL) {
            window.history.pushState({}, '', window.location.pathname);
        }
    });

    form.on('clear-filters', function () {
        form.find('input[type="checkbox"]').prop('checked', false);
        $('.selected_tags_container').empty();
        $('#tags-container').hide();
        debouncedFilterUpdate();

        if (updateURL) {
            window.history.pushState({}, '', window.location.pathname);
        }
    });

    $(window).on('scroll', handleScrollLoad);
    initializeFiltersFromURL();

})(jQuery);
