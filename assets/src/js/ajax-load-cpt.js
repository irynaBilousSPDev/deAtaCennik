import $ from 'jquery';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import 'slick-carousel';

// Function to preload remaining posts (except the first 6)
function preloadRemainingCptItems(container, postType) {
    let offset = 6; // Start offset from 6 to skip first 6 posts

    $.ajax({
        url: ajax_data.ajaxurl,
        type: 'POST',
        data: {
            action: 'load_remaining_cpt_posts',
            post_type: postType,
            offset: offset,
            lang: ajax_data.lang //  include current WPML language
        },
        beforeSend: function () {
            // console.log('Loading remaining posts in the background...');
        },
        success: function (response) {
            if (response.success) {
                let newSlides = $(response.data.html);

                // Add all new slides to the slider
                if (newSlides.length > 0) {
                    newSlides.each(function () {
                        container.slick('slickAdd', $(this));
                    });

                    // console.log('Remaining posts successfully loaded into the slider');
                }
            } else {
                console.warn(response.data.message);
            }
        },
        error: function () {
            // console.error('Error loading additional posts');
        }
    });
}

// Function to initialize the slider
export function initializeOffersCategoriesSlider(selector) {
    const $slider = $(selector);

    if ($slider.length && $.fn.slick) {
        $slider.each(function () {
            let container = $(this);
            let postType = container.closest('.our_offer').data('post-type');

            // Show only first 6 items
            container.children().slice(6).hide();

            // Initialize Slick slider with only the first 6 posts
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
                    { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1, arrows: false, dots: false } }
                ]
            });

            // Load remaining posts in the background
            setTimeout(() => preloadRemainingCptItems(container, postType), 1000);

            // Ensure Slick refreshes when window resizes
            $(window).on('resize', function () {
                container.slick('refresh');
            });
        });
    }
}

// Run the function when the document is ready
$(document).ready(function () {
    initializeOffersCategoriesSlider('.offer_category_slider');
});
