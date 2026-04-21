(function ($) {
    const ajaxUrl = ajax_filter_params.ajax_url;
    const pageId = wpData.page_id;
    // console.log('Ajax URL:', ajax_filter_params.ajax_url);
    // console.log('Current Page ID:', pageId);
    const filter_bachelor = {
        13: 'filter_bachelor',
        15: 'filter_master'
    }[pageId] || 'filter_posts';

    const form = $('#ajax-filter-form');
    const tagsContainer = $('#selected-tags');
    const tagsWrapper = $('#tags-container');
    const filterResults = $('#filter-results');
    // Initialize: Load all posts on page load
    $.ajax({
        url: ajaxUrl,
        method: 'POST',
        data: { action: filter_bachelor },
        success(response) {
            filterResults.html(response);
        },
    });

    // Function: Reload posts via AJAX
    function reloadPosts(data = {}) {
        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: { action: filter_bachelor, ...data },
            success(response) {
                filterResults.html(response);
            },
        });
    }

    // Function: Add a tag to the tags container
    function addTag(label, tagValue) {
        $('<span>')
            .addClass('filter-tag')
            .attr('data-value', tagValue)
            .text(`${label} ✕`)
            .appendTo(tagsContainer);
        tagsWrapper.show(); // Show the tags container
    }

    // Function: Remove a tag by its value
    function removeTag(tagValue) {
        tagsContainer.find(`[data-value="${tagValue}"]`).remove();
        if (tagsContainer.children().length === 0) {
            tagsWrapper.hide(); // Hide the tags container if empty
        }
    }

    // Function: Initialize filters from the URL query
    function initializeFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const data = {};

        // Pre-check filters and add corresponding tags
        urlParams.forEach((value, key) => {
            const checkbox = form.find(`input[name="${key}[]"][value="${value}"]`);
            if (checkbox.length) {
                checkbox.prop('checked', true); // Check the checkbox
                const label = checkbox.closest('label').text().trim();
                addTag(label, value); // Add the tag
                data[key] = data[key] || [];
                data[key].push(value);
            }
        });

        // Reload posts based on the query parameters
        if (Object.keys(data).length > 0) {
            reloadPosts({ form_data: form.serialize() });
        }
    }


    // Handle checkbox state changes
    form.on('change', 'input[type="checkbox"]', function () {
        const checkbox = $(this);
        const label = checkbox.closest('label').text().trim();
        const tagValue = checkbox.val();

        if (checkbox.is(':checked')) {
            addTag(label, tagValue); // Add tag when checkbox is checked
        } else {
            removeTag(tagValue); // Remove tag when checkbox is unchecked
        }

        // Serialize form data and reload posts
        const data = form.serialize();
        reloadPosts({ form_data: data });

        // Update browser URL with the current filters
        const params = new URLSearchParams();
        form.find('input[type="checkbox"]:checked').each(function () {
            const name = $(this).attr('name').replace('[]', ''); // Remove array brackets
            const value = $(this).val();
            params.append(name, value);
        });

        window.history.pushState({}, '', window.location.pathname);
        // window.history.pushState({}, '', '?' + params.toString());
    });

    // Handle tag removal
    tagsContainer.on('click', '.filter-tag', function () {
        const tag = $(this);
        const tagValue = tag.data('value');

        // Uncheck associated checkbox and remove the tag
        form.find(`input[value="${tagValue}"]`).prop('checked', false);
        removeTag(tagValue);

        // Serialize form data and reload posts
        const data = form.serialize();
        reloadPosts({ form_data: data });
    });

    // Handle clear filters button
    $('#clear-filters').on('click', () => {
        // Uncheck all checkboxes, clear tags, and reload all posts
        form.find('input[type="checkbox"]').prop('checked', false);

        tagsContainer.empty();
        tagsWrapper.hide();
        reloadPosts();

        window.history.pushState({}, '', window.location.pathname);
    });

    // Initialize filters on page load
    initializeFiltersFromURL();
}(jQuery));
