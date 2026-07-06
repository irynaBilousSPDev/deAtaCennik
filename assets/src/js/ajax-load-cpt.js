import $ from 'jquery';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import 'slick-carousel';

export function initializeOffersCategoriesSlider(selector) {
    const $slider = $(selector);

    if (!$slider.length || !$.fn.slick) {
        return;
    }

    $slider.each(function () {
        const container = $(this);

        if (!container.children().length) {
            return;
        }

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

        $(window).on('resize', function () {
            container.slick('refresh');
        });
    });
}

$(document).ready(function () {
    initializeOffersCategoriesSlider('.offer_category_slider');
});
