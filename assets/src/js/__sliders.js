import $ from 'jquery';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import 'slick-carousel';


export function initializeMainSlider(container) {
    // Wait until Slick is fully loaded
    function waitForSlick(callback) {
        const checkSlick = setInterval(() => {
            if (typeof $.fn.slick !== "undefined") {
                clearInterval(checkSlick);
                callback();
            }
        }, 100);
    }

    waitForSlick(() => {
        $(container).each(function () {
            const $thisContainer = $(this);
            const $sliderFor = $thisContainer.find('.main_slider_active');
            let $sliderNav;
            const controlsWrapper = $thisContainer.find('.main_slider_controls');

            // Check if $sliderFor exists before initializing
            if (!$sliderFor.length) {
                console.warn("Warning: Missing '.main_slider_active' inside the container.");
                return;
            }
            // console.log('yes');

            function initializeSliders() {
                if ($(window).width() > 480) {

                    $sliderNav = $thisContainer.find('.main_slider_nav');
                    if ($sliderNav.length && !$sliderNav.hasClass('slick-initialized')) {
                        $sliderNav.slick({
                            slidesToShow: 3,
                            slidesToScroll: 1,
                            asNavFor: $sliderFor,
                            dots: true,
                            centerMode: true,
                            focusOnSelect: true,
                            variableWidth: true,
                            lazyLoad: 'progressive',
                            arrows: false,
                            autoplay: true,
                            autoplaySpeed: 5000,
                            appendDots: controlsWrapper,
                            adaptiveHeight: true
                        });
                    }
                } else if ($sliderNav && $sliderNav.length && $sliderNav.hasClass('slick-initialized')) {
                    $sliderNav.slick('unslick');
                }
            }

            if (!$sliderFor.hasClass('slick-initialized')) {
                $sliderFor.slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: false,
                    fade: true,
                    cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
                    touchThreshold: 100,
                    lazyLoad: 'ondemand',
                    lazyLoadBuffer: 0,
                    centerMode: true,
                    adaptiveHeight: true,
                    asNavFor: $(window).width() > 480 && $sliderNav && $sliderNav.length ? $sliderNav : null,
                    autoplay: true,
                    autoplaySpeed: 5000,
                    responsive: [
                        {breakpoint: 480, settings: {slidesToShow: 1, dots: true, appendDots: controlsWrapper,}}
                    ]
                });
            }

            initializeSliders();
            $(window).on('resize', initializeSliders);

            // Accessibility fix: Prevent focus on hidden slides
            function updateAccessibility() {
                $thisContainer.find('.slick-slide').each(function () {
                    const isHidden = $(this).attr('aria-hidden') === 'true';
                    if (isHidden) {
                        $(this).attr('inert', '');
                        $(this).find(':focus').blur();
                    } else {
                        $(this).removeAttr('inert');
                    }
                });
            }

            $sliderFor.on('afterChange', updateAccessibility);
            if ($sliderNav && $sliderNav.length) {
                $sliderNav.on('afterChange', updateAccessibility);
            }
            updateAccessibility();

            // Play/Pause button functionality
            const playPauseButton = $('<button class="slick-play-pause">❚❚</button>');
            controlsWrapper.append(playPauseButton);
            let isPlaying = true;

            playPauseButton.on('click', function () {
                if (isPlaying) {
                    $sliderFor.slick('slickPause');
                    if ($sliderNav && $sliderNav.length) {
                        $sliderNav.slick('slickPause');
                    }
                    $(this).text('▶');
                } else {
                    $sliderFor.slick('slickPlay');
                    if ($sliderNav && $sliderNav.length) {
                        $sliderNav.slick('slickPlay');
                    }
                    $(this).text('❚❚');
                }
                isPlaying = !isPlaying;
            });

            // click to button if clik image
            $sliderFor.on('click', '.slick-slide.slick-current', function (e) {
                // Prevent default if clicking directly on the <a> link
                if (e.target.tagName.toLowerCase() === 'a') return;

                const link = $(this).find('a[href]').first();
                if (link.length) {
                    const href = link.attr('href');
                    const target = link.attr('target');

                    if (target === '_blank') {
                        window.open(href, '_blank');
                    } else {
                        window.location.href = href;
                    }
                }
            });


        });
    });
}


export function initializeImageContentSlider(container) {
    function waitForSlick(callback) {
        const checkInterval = setInterval(() => {
            if (typeof $ !== 'undefined' && $.fn && $.fn.slick) {
                clearInterval(checkInterval);
                callback();
            }
        }, 100);
    }

    waitForSlick(() => {
        if (!container || !(container instanceof Element || typeof container === 'string')) {
            console.error('Error: Invalid container. It should be a DOM element or a selector string.');
            return;
        }

        const $container = $(container instanceof Element ? container : document.querySelector(container));
        if (!$container.length) {
            console.warn(`Warning: Container ${container} not found, skipping initialization.`);
            return;
        }

        const $sliderFor = $container.find('.slider_for_images');
        const $sliderNav = $container.find('.slider_nav_content');

        if (!$sliderFor.length || !$sliderNav.length) {
            console.warn(`Warning: Sliders not found inside ${container}, skipping initialization.`);
            return;
        }
        const controlsWrapper = $('.controls_wrapper');

        $sliderFor.slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: true,
            fade: true,
            asNavFor: $sliderNav,
            autoplay: true,
            autoplaySpeed: 5000
        });

        $sliderNav.slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            asNavFor: $sliderFor,
            dots: true,
            appendDots: controlsWrapper,//Move dots to your custom div
            arrows: false,
            // centerMode: true,
            // focusOnSelect: true,
            autoplay: true,
            autoplaySpeed: 5000
        });

        const playPauseButton = $('<button class="slick-play-pause">❚❚</button>');
        controlsWrapper.append(playPauseButton);

        let isPlaying = true;

        playPauseButton.on('click', function () {
            if (isPlaying) {
                $sliderFor.slick('slickPause');
                $sliderNav.slick('slickPause');
                $(this).text('▶');
            } else {
                $sliderFor.slick('slickPlay');
                $sliderNav.slick('slickPlay');
                $(this).text('❚❚');
            }
            isPlaying = !isPlaying;
        });
    });
}


export function initializePartnerLogosSlider(selector) {
    const $slider = $(selector);
    if ($slider.length && $.fn.slick) {
        $slider.slick({
            speed: 10000,
            autoplay: true,
            arrows: false,
            autoplaySpeed: 0,
            cssEase: 'linear',
            slidesToShow: 9,
            slidesToScroll: 1,
            infinite: true,
            swipeToSlide: true,
            centerMode: true,
            focusOnSelect: true,
            responsive: [
                {
                    breakpoint: 750,
                    settings: {
                        slidesToShow: 3,
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 2,
                    }
                }
            ]
        });
    }
}


function equalizeCardHeights(selector) {
    let maxHeight = 0;
    const $elements = $(selector);

    $elements.css('height', 'auto').each(function () {
        const height = $(this).outerHeight();
        if (height > maxHeight) maxHeight = height;
    });

    $elements.css('height', maxHeight + 'px');
}

export function initializeNewWhyStudySlider(selector) {
    const $slider = $(selector);

    if ($slider.length && $.fn.slick) {
        // Equalize heights after slick positions elements
        $slider.on('setPosition', function () {
            equalizeCardHeights('.card_body');
        });

        // Initialize slick slider
        $slider.slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            infinite: true,
            arrows: true,
            adaptiveHeight: false,
            prevArrow: '<button class="slick-prev"></button>',
            nextArrow: '<button class="slick-next"></button>',
            dots: false,
            autoplay: false,
            responsive: [
                {
                    breakpoint: 990,
                    settings: { slidesToShow: 2 }
                },
                {
                    breakpoint: 580,
                    settings: {
                        slidesToShow: 1,
                        arrows: false,
                        dots: true,
                        centerMode: false,
                        variableWidth: true,
                    }
                }
            ]
        });

        // Ensure equal height on init and on resize
        setTimeout(() => equalizeCardHeights('.card_body'), 500);
        $(window).on('resize', () => equalizeCardHeights('.card_body'));

        // Click to activate specific slide
        $slider.on('click', '.slick-slide', function () {
            const index = $(this).attr("data-slick-index");
            if (index !== undefined) {
                $slider.slick('slickGoTo', parseInt(index));
            }
        });

        // Mousemove to scroll left/right near slider edges
        let scrollInterval = null;
        const edgeZone = 80; // px from left/right
        const scrollDelay = 300;

        $slider.on('mousemove', function (e) {
            const offset = $slider.offset();
            const width = $slider.width();
            const x = e.pageX - offset.left;

            clearInterval(scrollInterval);

            if (x < edgeZone) {
                scrollInterval = setInterval(() => $slider.slick('slickPrev'), scrollDelay);
                $slider.css('cursor', 'url(../../../wp-content/themes/devata/static/img/small_slider_arrow_left.png), auto');
            } else if (x > width - edgeZone) {
                scrollInterval = setInterval(() => $slider.slick('slickNext'), scrollDelay);
                $slider.css('cursor', 'url(../../../wp-content/themes/devata/static/img/small_slider_arrow_right.png), auto');
            } else {
                $slider.css('cursor', 'default');
            }
        });

        $slider.on('mouseleave', function () {
            clearInterval(scrollInterval);
            $slider.css('cursor', 'default');
        });
    }
}


export function initializeDiscountsSlider(selector) {
    const $slider = $(selector);
    if ($slider.length && $.fn.slick) {
        $slider.on('setPosition', function () {
            equalizeCardHeights('.discount_card');
        });

        $slider.slick({
            slidesToShow: 4,
            slidesToScroll: 1,
            infinite: false,
            centerMode: false,
            arrows: true,
            adaptiveHeight: false,
            prevArrow: '<button class="slick-prev"></button>',
            nextArrow: '<button class="slick-next"></button>',
            dots: true,
            autoplay: false,
            swipe: true,
            touchMove: true,
            responsive: [
                {breakpoint: 1440, settings: {slidesToShow: 3, arrows: false}},
                {breakpoint: 990, settings: {slidesToShow: 2, arrows: false}},
                {
                    breakpoint: 580,
                    settings: {
                        slidesToShow: 1,
                        arrows: false,
                        adaptiveHeight: false
                    }
                }
            ]
        });

        setTimeout(() => equalizeCardHeights('.discount_card'), 200);
        $(window).on('resize', () => equalizeCardHeights('.discount_card'));
    }
}

