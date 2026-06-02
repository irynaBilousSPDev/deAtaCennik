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
        watchOverflow: false,
        loopPreventsSliding: false,
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
                preloadAllHeroImages(swiperEl);
                preloadAdjacentHeroImages(swiperEl);

                buildHeroDots(root, swiperInstance, slideCount);

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
            slideChange(swiperInstance) {
                preloadAdjacentHeroImages(swiperEl);
                updateHeroDots(root, swiperInstance);
            },
        },
    });

    bindSlideLinkNavigation(swiperEl, swiper);
    preloadAllHeroImages(swiperEl);
    preloadAdjacentHeroImages(swiperEl);
    if (dotsEl) {
        dotsEl.hidden = slideCount < 2;
    }
    updateHeroDots(root, swiper);

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
 * @param {HTMLElement} root
 * @param {import('swiper').Swiper} swiper
 * @param {number} slideCount
 */
function buildHeroDots(root, swiper, slideCount) {
    const dotsEl = root.querySelector('.hero-slider__dots');
    if (!dotsEl || slideCount < 2) {
        return;
    }

    // Avoid duplicate builds when Swiper re-inits.
    if (dotsEl.childElementCount === slideCount) {
        return;
    }

    dotsEl.innerHTML = '';

    for (let i = 0; i < slideCount; i += 1) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'hero-slider__dot';
        dot.setAttribute('aria-label', `Slajd ${i + 1}`);
        dot.addEventListener('click', () => {
            goToHeroIndex(swiper, i);
            setHeroDotsActive(root, i);
        });
        dotsEl.appendChild(dot);
    }
}

/**
 * @param {import('swiper').Swiper} swiper
 * @param {number} heroIndex
 */
function goToHeroIndex(swiper, heroIndex) {
    const target = findClosestSlideIndexByHeroIndex(swiper, heroIndex);
    if (target === null) {
        return;
    }
    swiper.slideTo(target, SLIDE_SPEED);
}

/**
 * Pick the closest DOM slide index that matches heroIndex.
 * Works even when markup contains duplicated slides for loop stability.
 *
 * @param {import('swiper').Swiper} swiper
 * @param {number} heroIndex
 * @returns {number|null}
 */
function findClosestSlideIndexByHeroIndex(swiper, heroIndex) {
    const active = swiper.activeIndex ?? 0;
    let bestIndex = null;
    let bestDistance = Infinity;

    swiper.slides.forEach((slideEl, idx) => {
        const raw = slideEl?.dataset?.heroIndex;
        if (raw === undefined) {
            return;
        }
        const val = parseInt(raw, 10);
        if (Number.isNaN(val) || val !== heroIndex) {
            return;
        }

        const dist = Math.abs(idx - active);
        if (dist < bestDistance) {
            bestDistance = dist;
            bestIndex = idx;
        }
    });

    return bestIndex;
}

/**
 * @param {HTMLElement} root
 * @param {number} activeIndex
 */
function setHeroDotsActive(root, activeIndex) {
    root.querySelectorAll('.hero-slider__dot').forEach((dot, index) => {
        const isActive = index === activeIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
    });
}

/**
 * @param {import('swiper').Swiper} swiper
 * @returns {number|null}
 */
function getActiveHeroIndex(swiper) {
    const activeSlide = swiper.slides?.[swiper.activeIndex];
    const raw = activeSlide?.dataset?.heroIndex;
    if (raw === undefined) {
        return null;
    }
    const idx = parseInt(raw, 10);
    return Number.isNaN(idx) ? null : idx;
}

/**
 * @param {HTMLElement} root
 * @param {import('swiper').Swiper} swiper
 */
function updateHeroDots(root, swiper) {
    const dotsEl = root.querySelector('.hero-slider__dots');
    if (!dotsEl || dotsEl.hidden) {
        return;
    }
    const idx = getActiveHeroIndex(swiper);
    if (idx === null) {
        return;
    }
    setHeroDotsActive(root, idx);
}

/**
 * Rebuild loop clones after DOM / breakpoint changes.
 *
 * @param {import('swiper').Swiper} swiper
 */
function refreshHeroLoop(swiper) {
    if (!swiper.params.loop) {
        return;
    }

    swiper.loopDestroy();
    swiper.loopCreate();
    swiper.update();
}

/**
 * @param {HTMLElement} swiperEl
 */
function preloadAllHeroImages(swiperEl) {
    swiperEl.querySelectorAll('img.hero-slider__image').forEach((img) => {
        eagerLoadHeroImage(img);
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
 * Eager-load banner images for active and peek slides (fixes empty side gaps on prod).
 *
 * @param {HTMLElement} swiperEl
 */
function preloadAdjacentHeroImages(swiperEl) {
    const selector =
        '.swiper-slide-active img.hero-slider__image, ' +
        '.swiper-slide-prev img.hero-slider__image, ' +
        '.swiper-slide-next img.hero-slider__image, ' +
        '.swiper-slide-duplicate-active img.hero-slider__image, ' +
        '.swiper-slide-duplicate-prev img.hero-slider__image, ' +
        '.swiper-slide-duplicate-next img.hero-slider__image';

    swiperEl.querySelectorAll(selector).forEach((img) => {
        eagerLoadHeroImage(img);
    });
}

/**
 * @param {HTMLImageElement} img
 */
function eagerLoadHeroImage(img) {
    if (img.dataset.src) {
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
    }

    img.loading = 'eager';

    const src = img.currentSrc || img.getAttribute('src');

    if (!src || img.complete) {
        return;
    }

    const loader = new Image();
    loader.src = src;
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
