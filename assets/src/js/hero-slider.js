import Swiper from 'swiper';
import { Autoplay, A11y } from 'swiper/modules';
import 'swiper/css';

const DESKTOP_MIN = 768;
const SLIDE_SPEED = 750;

/**
 * @param {HTMLElement} root
 */
export function initHeroSlider(root) {
    const swiperEl = root.querySelector('.hero-slider__swiper');
    if (!swiperEl) {
        return;
    }

    const dotsEl = root.querySelector('.hero-slider__dots');
    const autoplayBtn = root.querySelector('.hero-slider__autoplay');
    const slideCount = parseInt(root.dataset.slideCount || '0', 10);
    const canLoop = slideCount > 1;
    const canAutoplay = slideCount > 1;

    hydrateSlideImages(swiperEl);

    const swiper = new Swiper(swiperEl, {
        modules: [Autoplay, A11y],
        slidesPerView: 1,
        spaceBetween: 0,
        centeredSlides: true,
        loop: canLoop,
        loopAdditionalSlides: 1,
        speed: SLIDE_SPEED,
        grabCursor: true,
        watchSlidesProgress: true,
        autoplay: false,
        breakpoints: {
            [DESKTOP_MIN]: {
                slidesPerView: 'auto',
                spaceBetween: 0,
                centeredSlides: true,
            },
        },
        on: {
            init(swiperInstance) {
                if (canLoop) {
                    swiperInstance.slideToLoop(0, 0, false);
                } else {
                    swiperInstance.slideTo(0, 0, false);
                }
                swiperInstance.update();
                updateHeroDots(root, swiperInstance);

                if (canAutoplay) {
                    swiperInstance.params.autoplay = {
                        delay: 5000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                        waitForTransition: true,
                    };
                    swiperInstance.autoplay.start();
                }
            },
            slideChangeTransitionEnd(swiperInstance) {
                updateHeroDots(root, swiperInstance);
            },
        },
    });

    buildHeroDots(root, swiper, slideCount);
    bindSlideLinkNavigation(swiperEl, swiper);
    preloadHeroImages(swiperEl);

    if (!autoplayBtn || !canAutoplay) {
        autoplayBtn?.setAttribute('hidden', '');
        return;
    }

    const pauseIcon = autoplayBtn.querySelector('.hero-slider__autoplay-icon--pause');
    const playIcon = autoplayBtn.querySelector('.hero-slider__autoplay-icon--play');
    let isPlaying = true;

    const setPlayingState = (playing) => {
        isPlaying = playing;
        autoplayBtn.classList.toggle('is-playing', playing);
        autoplayBtn.classList.toggle('is-paused', !playing);
        autoplayBtn.setAttribute('aria-pressed', playing ? 'false' : 'true');
        autoplayBtn.setAttribute(
            'aria-label',
            playing
                ? autoplayBtn.dataset.labelPause || 'Pause autoplay'
                : autoplayBtn.dataset.labelPlay || 'Play autoplay'
        );

        if (pauseIcon && playIcon) {
            pauseIcon.hidden = !playing;
            playIcon.hidden = playing;
        }
    };

    autoplayBtn.addEventListener('click', () => {
        if (isPlaying) {
            swiper.autoplay.stop();
            setPlayingState(false);
        } else {
            swiper.autoplay.start();
            setPlayingState(true);
        }
    });
}

/**
 * Custom 10px dots (not Swiper pagination).
 *
 * @param {HTMLElement} root
 * @param {import('swiper').Swiper} swiper
 * @param {number} slideCount
 */
function buildHeroDots(root, swiper, slideCount) {
    const dotsEl = root.querySelector('.hero-slider__dots');
    if (!dotsEl || slideCount < 2) {
        return;
    }

    dotsEl.innerHTML = '';

    for (let i = 0; i < slideCount; i += 1) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'hero-slider__dot';
        dot.setAttribute('aria-label', `Slajd ${i + 1}`);
        dot.addEventListener('click', () => {
            if (swiper.params.loop) {
                swiper.slideToLoop(i);
            } else {
                swiper.slideTo(i);
            }
        });
        dotsEl.appendChild(dot);
    }

    updateHeroDots(root, swiper);
}

/**
 * @param {HTMLElement} root
 * @param {import('swiper').Swiper} swiper
 */
function updateHeroDots(root, swiper) {
    const activeIndex = swiper.realIndex;
    root.querySelectorAll('.hero-slider__dot').forEach((dot, index) => {
        const isActive = index === activeIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
    });
}

/**
 * @param {HTMLElement} swiperEl
 */
function hydrateSlideImages(swiperEl) {
    swiperEl.querySelectorAll('.swiper-slide').forEach((slideEl) => {
        loadSlideMedia(slideEl);
    });
}

/**
 * @param {HTMLElement} swiperEl
 */
function preloadHeroImages(swiperEl) {
    swiperEl.querySelectorAll('img.hero-slider__image').forEach((img) => {
        const src = img.getAttribute('src');
        if (!src || img.complete) {
            return;
        }
        const loader = new Image();
        loader.src = src;
    });
}

/**
 * @param {HTMLElement} slideEl
 */
function loadSlideMedia(slideEl) {
    slideEl.querySelectorAll('source[data-srcset]').forEach((source) => {
        if (!source.dataset.srcset) {
            return;
        }
        source.srcset = source.dataset.srcset;
        source.removeAttribute('data-srcset');
    });

    slideEl.querySelectorAll('img.hero-slider__image[data-src]').forEach((img) => {
        const src = img.dataset.src;
        if (!src) {
            return;
        }
        img.src = src;
        img.removeAttribute('data-src');
    });
}

/**
 * @param {HTMLElement} swiperEl
 * @param {import('swiper').Swiper} swiper
 */
function bindSlideLinkNavigation(swiperEl, swiper) {
    let pointerStart = null;

    swiperEl.addEventListener('pointerdown', (event) => {
        pointerStart = { x: event.clientX, y: event.clientY };
    });

    swiperEl.addEventListener('click', (event) => {
        if (pointerStart) {
            const moved = Math.hypot(
                event.clientX - pointerStart.x,
                event.clientY - pointerStart.y
            );
            if (moved > 8) {
                pointerStart = null;
                return;
            }
        }
        pointerStart = null;

        if (event.target.closest('a[href]')) {
            return;
        }

        const slide = event.target.closest('.swiper-slide[data-href]');
        if (!slide || !slide.classList.contains('swiper-slide-active')) {
            return;
        }

        const href = slide.dataset.href;
        if (!href) {
            return;
        }

        event.preventDefault();

        if (swiper.animating) {
            return;
        }

        const target = slide.dataset.target || '_self';
        if (target === '_blank') {
            window.open(href, '_blank', 'noopener,noreferrer');
        } else {
            window.location.assign(href);
        }
    });
}
