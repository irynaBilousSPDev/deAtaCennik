import $ from 'jquery';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import 'slick-carousel';

function getInitialSlideCount(container) {
    const fromData = parseInt(container.data('initial-count'), 10);
    if (!Number.isNaN(fromData) && fromData > 0) {
        return fromData;
    }

    const fromGlobal = typeof ajax_data !== 'undefined' ? parseInt(ajax_data.initial_count, 10) : NaN;
    return !Number.isNaN(fromGlobal) && fromGlobal > 0 ? fromGlobal : 8;
}

function preloadRemainingCptItems(container, postType, offset) {
    $.ajax({
        url: ajax_data.ajaxurl,
        type: 'POST',
        data: {
            action: 'load_remaining_cpt_posts',
            post_type: postType,
            offset: offset,
            lang: ajax_data.lang,
        },
        success: function (response) {
            if (!response.success || !response.data || !response.data.html) {
                return;
            }

            const newSlides = $(response.data.html);
            if (newSlides.length > 0) {
                newSlides.each(function () {
                    container.slick('slickAdd', $(this));
                });
            }

            if (response.data.has_more && response.data.next_offset) {
                preloadRemainingCptItems(container, postType, response.data.next_offset);
            }
        },
    });
}

function scheduleBackgroundLoad(callback) {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(callback, { timeout: 2000 });
        return;
    }

    callback();
}

export function initializeOffersCategoriesSlider(selector) {
    const $slider = $(selector);

    if (!$slider.length || !$.fn.slick) {
        return;
    }

    $slider.each(function () {
        const container = $(this);
        const postType = container.closest('.our_offer').data('post-type');
        const initialCount = getInitialSlideCount(container);

        container.slick({
            slidesToShow: 5,
            slidesToScroll: 5,
            infinite: false,
            arrows: true,
            prevArrow: '<button class="slick-prev"></button>',
            nextArrow: '<button class="slick-next"></button>',
            dots: true,
            autoplay: false,
            variableWidth: true,
            swipe: true,
            touchMove: true,
            responsive: [
                { breakpoint: 1366, settings: { slidesToShow: 3, slidesToScroll: 1 } },
                { breakpoint: 1025, settings: { slidesToShow: 2, slidesToScroll: 1 } },
                { breakpoint: 768, settings: { slidesToShow: 2, slidesToScroll: 1 } },
                { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1, arrows: false, dots: false } },
            ],
        });

        if (postType && container.children().length >= initialCount) {
            scheduleBackgroundLoad(() => preloadRemainingCptItems(container, postType, initialCount));
        }

        $(window).on('resize', function () {
            container.slick('refresh');
        });
    });
}

$(document).ready(function () {
    initializeOffersCategoriesSlider('.offer_category_slider');
});
