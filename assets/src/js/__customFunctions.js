import $ from 'jquery';
import { Fancybox } from "@fancyapps/ui";


export function updateHeaderLink(sourceSelector, targetSelector) {
    $(document).ready(function () {
        const dynamicUrl = $(sourceSelector).attr('href');
        if (dynamicUrl) {
            $(targetSelector).each(function () {
                $(this).attr('href', dynamicUrl);
            });
        }
    });
}

export function initMegaMenu(toggleSelector, menuSelector) {
    const $toggles = jQuery(toggleSelector);
    const $menu = jQuery(menuSelector);
    const $siteNav = jQuery('#site-navigation');
    const $menuOffer = jQuery('#menu-offer');
    const $btnEnded = jQuery('#btn_ended');
    const $customLink = jQuery('#customLink');

    if (!$toggles.length || !$menu.length) return;

    function openMenu() {
        $menu.addClass('open');
        $toggles.find('.menu-icon').addClass('open');
        $toggles.find('.menu-toggle-label').text('×');

        if ($siteNav.length) $siteNav.hide();
        if ($menuOffer.length) $menuOffer.hide();
        if ($btnEnded.length) $btnEnded.hide();
        if ($customLink.length) $customLink.hide();
    }

    function closeMenu() {
        $menu.removeClass('open');
        $toggles.find('.menu-icon').removeClass('open');
        $toggles.find('.menu-toggle-label').text('Menu');

        if ($siteNav.length) $siteNav.show();
        if ($menuOffer.length) $menuOffer.show();
        if ($btnEnded.length) $btnEnded.show();
        if ($customLink.length) $customLink.show();
    }

    $toggles.on('click', function (e) {
        e.stopPropagation();
        if ($menu.hasClass('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
}




// counter front start

export function initializeCounterWrapper(selector = '.counter_wrapper') {
    const $wrapper = $(selector);
    if (!$wrapper.length) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                runCounters($(entry.target));
                observer.unobserve(entry.target); // run once
            }
        });
    }, {threshold: 0.4});

    $wrapper.each((_, el) => observer.observe(el));
}

function runCounters($wrapper) {
    $wrapper.find('.counter').each(function () {
        const $el = $(this);
        const originalText = $el.text().trim();
        const hasPlus = originalText.includes('+');
        const numberOnly = parseInt(originalText.replace(/\D/g, ''), 10);

        $el.text('0');

        $({countNum: 0}).animate({countNum: numberOnly}, {
            duration: 2000,
            easing: 'swing',
            step: function () {
                $el.text(Math.floor(this.countNum) + (hasPlus ? '+' : ''));
            },
            complete: function () {
                $el.text(numberOnly + (hasPlus ? '+' : ''));
            }
        });
    });
}

// counter front end

// filter start
export function filterAccordion(accordionHeaderSelector) {
    if (typeof jQuery === 'undefined') return;

    const $headers = jQuery(accordionHeaderSelector);

    function closeAllAccordions() {
        $headers.removeClass('active');
        $headers.next('.accordion-content').slideUp(0);
    }

    function openAccordionByTaxonomy(taxonomy) {
        // Optional: close others
        closeAllAccordions();

        const $targetHeader = $headers.filter(`[data-tax="${taxonomy}"]`);
        if ($targetHeader.length) {
            $targetHeader.addClass('active');
            $targetHeader.next('.accordion-content').slideDown(300);
        }
    }

    // Optional: expose globally
    window.closeAllAccordions = closeAllAccordions;
    window.openAccordionByTaxonomy = openAccordionByTaxonomy;

    // Toggle logic (independent toggling)
    $headers.on('click', function () {
        const $header = jQuery(this);
        const $content = $header.next('.accordion-content');

        $header.toggleClass('active');

        if ($header.hasClass('active')) {
            $content.slideDown(300);
        } else {
            $content.slideUp(300);
        }
    });
}

export function initMobileFilterToggle() {
    const sidebar = document.querySelector('#sidebar');
    const filterButtons = document.querySelectorAll('.taxonomy-filter-toggle');
    const closeButton = document.querySelector('.go-back');
    const resultsButton = document.querySelector('.filter_results');
    const overlay = document.querySelector('.filter-overlay');
    const header = document.querySelector('.mobile-filter-header');
    const clearButton = document.querySelector('.clear-filters');
    const filterForm = document.querySelector('#ajax-filter-form');

    if (!sidebar || filterButtons.length === 0) return;

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const taxonomy = button.dataset.tax;

            sidebar.classList.add('open');
            overlay?.classList.add('active');
            document.body.classList.add('filter-open');
            header?.classList.add('visible');

            setTimeout(() => {
                if (window.closeAllAccordions) window.closeAllAccordions();
                if (window.openAccordionByTaxonomy) window.openAccordionByTaxonomy(taxonomy);

                const target = document.querySelector(`.filter_accordion_header[data-tax="${taxonomy}"]`);
                const scroller = document.querySelector('#scroller');

                if (target && scroller) {
                    setTimeout(() => {
                        const scrollerRect = scroller.getBoundingClientRect();
                        const targetRect = target.getBoundingClientRect();
                        const top = scroller.scrollTop + (targetRect.top - scrollerRect.top) - 12;

                        scroller.scrollTo({
                            top,
                            behavior: 'smooth'
                        });
                    }, 350);
                }
            }, 10);
        });
    });

    const closeSidebar = () => {
        sidebar.classList.remove('open');
        overlay?.classList.remove('active');
        document.body.classList.remove('filter-open');
        header?.classList.remove('visible');
    };

    closeButton?.addEventListener('click', closeSidebar);
    resultsButton?.addEventListener('click', closeSidebar);

    clearButton?.addEventListener('click', () => {
        if (!filterForm) return;
        const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => (cb.checked = false));
        filterForm.dispatchEvent(new Event('clear-filters'));
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('filter-overlay')) {
            closeSidebar();
        }
    });
}



// filter end

export function initializeAccordion(accordionSelector) {
    $(document).ready(function ($) {
        // Close all accordion items except the first one
        const $accordionItems = $(accordionSelector).find(".accordion-item");
        $accordionItems.removeClass("active").find(".accordion-content").hide();
        $accordionItems.find(".accordion-arrow").css("transform", "rotate(45deg)");

        // Open only the first accordion item by default
        const $firstItem = $accordionItems.first();
        $firstItem.addClass("active");
        $firstItem.find(".accordion-content").show();
        $firstItem.find(".accordion-arrow").css("transform", "rotate(-135deg)");

        $(accordionSelector).find(".accordion-header").on("click", function (event) {
            event.preventDefault(); // Prevent page jump
            const $accordionItem = $(this).parent();
            const $content = $(this).next();
            const $arrow = $(this).find(".accordion-arrow");
            const isActive = $accordionItem.hasClass("active");

            // Close all other accordions
            $accordionItems.not($accordionItem).removeClass("active").find(".accordion-content").slideUp(300);
            $accordionItems.not($accordionItem).find(".accordion-arrow").css("transform", "rotate(45deg)");

            // Toggle the clicked accordion item
            if (!isActive) {
                $accordionItem.addClass("active");
                $content.slideDown(300);
                $arrow.css("transform", "rotate(-135deg)");
            } else {
                $accordionItem.removeClass("active");
                $content.slideUp(300);
                $arrow.css("transform", "rotate(45deg)");
            }
        });
    });
}

export function initializeTabsContainer(tabsSelector) {
    $(document).ready(function ($) {
        $(tabsSelector).each(function () {
            const $tabsContainer = $(this);
            const $tabs = $tabsContainer.find(".tab");
            const $tabContents = $tabsContainer.find(".tab-content");

            // Ensure only the first tab is active by default
            $tabs.removeClass("active").first().addClass("active");
            $tabContents.removeClass("active").first().addClass("active");

            $tabs.on("click", function () {
                const tabId = $(this).data("tab");
                const $targetContent = $tabsContainer.find("#" + tabId);

                // Ensure target content exists before toggling
                if ($targetContent.length) {
                    $tabs.removeClass("active");
                    $tabContents.removeClass("active");

                    $(this).addClass("active");
                    $targetContent.addClass("active");
                }
            });
        });
    });
}

export function copyAccountNumber(selector) {
    $(document).ready(function ($) {
        $(document).on("click", selector, function () {
            const accountNumber = $(this).text().trim();
            const tempInput = $("<input>");
            $("body").append(tempInput);
            tempInput.val(accountNumber).select();
            document.execCommand("copy");
            tempInput.remove();

            // Show tooltip
            const $tooltip = $("<span class='copy-tooltip'>Copied!</span>");
            $(this).append($tooltip);
            $tooltip.fadeIn(200).delay(1000).fadeOut(300, function () {
                $(this).remove();
            });
        });
    });
}

export function enableSkipLink(selector = ".skip-link") {
    $(document).ready(function ($) {
        const $skipLink = $(selector);
        const $mainContent = $("#primary");

        if ($skipLink.length && $mainContent.length) {
            $skipLink.on("click", function (event) {
                event.preventDefault();
                $mainContent.attr("tabindex", "-1").focus();
            });
        }
    });
}


/**
 * Universal function to update images and background images dynamically based on screen size.
 * @param {string} selector - The CSS selector for elements (default: '.responsive-image')
 */
export function updateResponsiveImages(selector = '.responsive-image') {
    if (typeof selector !== 'string') {
        console.error('updateResponsiveImages: Selector must be a string.');
        return;
    }

    const elements = document.querySelectorAll(selector);

    if (!elements || elements.length === 0) {
        console.warn(`updateResponsiveImages: No elements found for selector "${selector}"`);
        return;
    }

    Array.from(elements).forEach(el => {
        const mobileSrc = el.getAttribute('data-mobile');
        const desktopSrc = el.getAttribute('data-desktop');
        let newSrc = desktopSrc;

        if (window.innerWidth <= 480 && mobileSrc) {
            newSrc = mobileSrc;
        }

        if (el.tagName === 'IMG') {
            if (el.getAttribute('src') !== newSrc) {
                el.setAttribute('src', newSrc);
            }
        } else {
            const currentBg = el.style.backgroundImage.replace(/url\("|"\)/g, '');
            if (currentBg !== newSrc) {
                el.style.backgroundImage = `url('${newSrc}')`;
            }
        }
    });
}

// Run on initial load
if (typeof window !== 'undefined') {
    window.addEventListener('DOMContentLoaded', () => updateResponsiveImages());
    window.addEventListener('resize', () => updateResponsiveImages());
}

export function offerNavScroll(
    selector1 = "#menu-offer a",
    selector2 = "#priseScroll a",
    headerSelector = "header",
    activeClass = "active"
) {
    $(document).ready(function () {
        const menuLinks = $(`${selector1}, ${selector2}`);
        const headerHeight = $(headerSelector).outerHeight() || 0;

        // Smooth scroll on click
        menuLinks.on("click", function (event) {
            event.preventDefault();
            const target = $(this).attr("href");

            if (target && $(target).length) {
                $("html, body").animate(
                    {
                        scrollTop: $(target).offset().top - headerHeight,
                    },
                    800
                );
            }
        });

        // Highlight menu item from selector1 only
        $(window).on("scroll", function () {
            let scrollPos = $(document).scrollTop() + headerHeight + 10;
            let activeFound = false;

            $(selector1).each(function () {
                const section = $($(this).attr("href"));

                if (section.length) {
                    const sectionTop = section.offset().top;
                    const sectionBottom = sectionTop + section.outerHeight();

                    if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                        $(selector1).removeClass(activeClass);
                        $(this).addClass(activeClass);
                        activeFound = true;
                    }
                }
            });

            if (!activeFound) {
                $(selector1).removeClass(activeClass);
            }
        });
    });
}

export function resizeElements() {
    let $mobileBox = $(".mobile_visible");
    let $desktopBox = $(".desktop_visible");

    if ($(window).width() <= 990) {
        $mobileBox.css("display", "block");
        $desktopBox.css("display", "none");
    } else {
        $mobileBox.css("display", "none");
        $desktopBox.css("display", "block");
    }

}

// Run on page load & window resize
$(document).ready(resizeElements);
$(window).on("resize", resizeElements);


export function handleScrollButtonOffer() {
    $(document).ready(function () {
        const $offerButton = $("#offerButton");
        const $header = $("header");

        $(window).on("scroll", function () {
            if (isInViewport($offerButton)) {
                $offerButton.addClass("active");
                $header.addClass("active").removeClass("out-of-view");
            } else {
                $offerButton.removeClass("active");
                $header.addClass("out-of-view").removeClass("active");
            }

        });

        function isInViewport($element) {
            if ($element.length === 0) return false;
            const rect = $element[0].getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
    });
}

$('#program .download_program a').attr('target', '_blank');


export function initializeFancybox(selector = '[data-fancybox="gallery"]') {
    document.addEventListener("DOMContentLoaded", function () {
        Fancybox.bind(selector, {
            Thumbs: {
                autoStart: true
            },
            Toolbar: {
                display: ['close']
            },
            dragToClose: true,
            animated: true,
            showClass: "fancybox-zoomIn",
            hideClass: "fancybox-zoomOut"
        });
    });
}


export function accordionUniversal(selector) {
    $(document).ready(function () {
        const $accordion = $(selector);
        const $items = $accordion.find('.accordion_item');

        $items.removeClass('open').find('.accordion_content').hide();
        $items.find('.accordion_arrow').css('transform', 'rotate(0deg)');

        $accordion.find('.accordion_header').on('click', function (e) {
            e.preventDefault();

            const $item = $(this).closest('.accordion_item');
            const $content = $item.find('.accordion_content');
            const $arrow = $item.find('.accordion_arrow');

            const isOpen = $item.hasClass('open');

            if (!isOpen) {
                $item.addClass('open');
                $content.slideDown(300);
                $arrow.css('transform', 'rotate(180deg)');
            } else {
                $item.removeClass('open');
                $content.slideUp(300);
                $arrow.css('transform', 'rotate(0deg)');
            }
        });
    });
}

export function initTaxonomyTabs() {
    document.querySelectorAll('.taxonomy-tabs').forEach(tabContainer => {
        const navItems = tabContainer.querySelectorAll('.taxonomy-tabs__nav li');
        const cards = tabContainer.querySelectorAll('.course-card');
        const noResultsMessage = tabContainer.querySelector('.no-taxonomy-results');

        navItems.forEach(item => {
            item.addEventListener('click', () => {
                const selectedTerm = item.dataset.term;
                const isActive = item.classList.contains('active');

                navItems.forEach(i => i.classList.remove('active'));

                let visibleCount = 0;

                if (isActive) {
                    cards.forEach(card => {
                        card.style.display = 'block';
                        visibleCount++;
                    });
                } else {
                    item.classList.add('active');
                    cards.forEach(card => {
                        const match = selectedTerm === card.dataset.term;
                        card.style.display = match ? 'block' : 'none';
                        if (match) visibleCount++;
                    });
                }

                if (noResultsMessage) {
                    noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            });
        });
    });
}

export function initCityTabs() {
    document.querySelectorAll('.city-tabs').forEach(tabContainer => {
        const tabNav = tabContainer.querySelector('.city-tabs__nav');
        const tabLinks = tabNav?.querySelectorAll('a') || [];
        const tabPanes = tabContainer.querySelectorAll('.city-tabs__pane');

        tabLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');

                tabNav.querySelectorAll('li').forEach(li => li.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                this.parentElement.classList.add('active');
                const target = tabContainer.querySelector(href);
                if (target) target.classList.add('active');
            });
        });

        tabPanes.forEach(pane => {
            const accordion = pane.querySelector('.city-tabs__accordion');
            if (!accordion) return;

            accordion.addEventListener('click', () => {
                const isActive = pane.classList.contains('active');
                tabPanes.forEach(p => p.classList.remove('active'));

                if (!isActive) {
                    pane.classList.add('active');
                    pane.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    });

    //  Handle deep-link with tab + term
    const rawHash = window.location.hash;
    if (rawHash.includes('#city-')) {
        const hashParts = rawHash.replace('#', '').split('&');
        const tabId = hashParts.find(part => part.startsWith('city-'));
        const termPart = hashParts.find(part => part.startsWith('term='));

        if (tabId) {
            const tabLink = document.querySelector(`.city-tabs__nav a[href="#${tabId}"]`);
            if (tabLink) {
                tabLink.click();
            }

            if (termPart) {
                const term = termPart.split('=')[1];
                setTimeout(() => {
                    const tabPane = document.querySelector(`#${tabId}`);
                    if (!tabPane) return;

                    const termBtn = tabPane.querySelector(`.taxonomy-tabs__nav li[data-term="${term}"]`);
                    if (termBtn) termBtn.click();
                }, 200);
            }
        }
    }
}


// discountModal.js
export function initializeDiscountModal(modalSelector = "#discountModal") {
    $(document).ready(function ($) {
        const $modal = $(modalSelector);
        const $modalBody = $modal.find(".discount-modal__body");
        let $lastFocused = null;

        // Open modal
        function openModal(contentHtml) {
            $lastFocused = $(document.activeElement);
            $modalBody.html(contentHtml);
            $modal.addClass("is-open").attr("aria-hidden", "false");
            $("body").addClass("no-scroll");
        }

        // Close modal
        function closeModal() {
            $modal.removeClass("is-open").attr("aria-hidden", "true");
            $modalBody.empty();
            $("body").removeClass("no-scroll");
            if ($lastFocused && $lastFocused.length) {
                $lastFocused.focus();
            }
        }

        // Open on arrow click
        $(document).on("click", ".open-discount-modal", function (e) {
            e.preventDefault();
            const $card = $(this).closest(".discount_card");
            const html = $card.find(".js-modal-content").html() || "";
            openModal(html);
        });

        // Close on backdrop / close button
        $(document).on("click", "[data-close-modal]", function (e) {
            e.preventDefault();
            closeModal();
        });

        // Close on ESC
        $(document).on("keydown", function (e) {
            if (e.key === "Escape" && $modal.hasClass("is-open")) {
                closeModal();
            }
        });
    });
}


// tabs-contact-cities

export function initContactCityTabs() {
    jQuery(function ($) {
        $('.contact_city_tabs').each(function () {
            const $wrap  = $(this);
            const $links = $wrap.find('.contact_city_tab a');
            const $items = $wrap.find('.contact_city_tab li');
            const $panes = $wrap.find('.contact_city_tab_content');

            // Guard
            if (!$links.length || !$panes.length) return;

            // Activate helper
            const activate = (hash, pushState = true) => {
                if (!hash) return;
                const $link = $links.filter(`[href="${hash}"]`).first();
                const $pane = $wrap.find(hash).first();
                if (!$link.length || !$pane.length) return;

                // reset
                $items.removeClass('active');
                $links.attr('aria-selected', 'false');
                $panes.removeClass('active');

                // set
                $link.parent().addClass('active');
                $link.attr('aria-selected', 'true');
                $pane.addClass('active');

                // URL + memory
                if (pushState) {
                    try {
                        const url = new URL(window.location);
                        url.hash = hash.replace('#', '');
                        history.replaceState(null, '', url.toString());
                        localStorage.setItem('contactCityTab', hash);
                    } catch (e) { /* no-op */ }
                }
            };

            // Click handler (namespaced, avoids double-binding)
            $links.off('click.cct').on('click.cct', function (e) {
                e.preventDefault();
                activate($(this).attr('href'));
            });

            // Initial state priority: URL hash (if pane exists) -> localStorage -> first active -> first link
            const urlHash  = window.location.hash && $wrap.find(window.location.hash).length ? window.location.hash : null;
            let   saved    = null;
            try { saved = localStorage.getItem('contactCityTab'); } catch (e) {}

            if (urlHash) {
                activate(urlHash, /*pushState*/ false);
            } else if (saved && $wrap.find(saved).length) {
                activate(saved, false);
            } else {
                const $current = $items.filter('.active').find('a').first();
                activate($current.length ? $current.attr('href') : $links.first().attr('href'), false);
            }
        });
    });
}


export function initArchiveAccordion($, toggleSelector = '.archive-year-toggle') {
    if (!$ || typeof $.fn === 'undefined') {
        // jQuery not available
        return;
    }

    // Ensure initial state: all sections collapsed unless aria-expanded="true"
    $(function () {
        $(toggleSelector).each(function () {
            const $btn = $(this);
            const targetId = $btn.attr('aria-controls');
            if (!targetId) return;

            const $target = $('#' + CSS.escape(targetId));
            const expanded = $btn.attr('aria-expanded') === 'true';

            // Use .prop for boolean 'hidden' property
            $target.prop('hidden', !expanded);
        });
    });

    // Event delegation for future elements
    $(document).on('click', toggleSelector, function (e) {
        e.preventDefault();

        const $btn = $(this);
        const targetId = $btn.attr('aria-controls');
        if (!targetId) return;

        const $target = $('#' + CSS.escape(targetId));
        if ($target.length === 0) return;

        const isExpanded = $btn.attr('aria-expanded') === 'true';
        const willExpand = !isExpanded;

        // Toggle current
        $btn.attr('aria-expanded', String(willExpand));
        $target.prop('hidden', !willExpand);

        // Close others
        $(toggleSelector).not($btn).each(function () {
            const $otherBtn = $(this);
            const otherId = $otherBtn.attr('aria-controls');
            if (!otherId) return;

            const $otherTarget = $('#' + CSS.escape(otherId));
            if ($otherTarget.length === 0) return;

            $otherBtn.attr('aria-expanded', 'false');
            $otherTarget.prop('hidden', true);
        });
    });
}



export function initCadreModal() {
    if (window.__cadreModalBound) return;
    window.__cadreModalBound = true;

    const getHeaderHeight = () => {
        const header = document.querySelector('.site-header');
        return header ? Math.round(header.getBoundingClientRect().height) : 0;
    };

    const getViewportTop = () => {
        return window.innerWidth <= 992 ? 0 : getHeaderHeight();
    };

    const setTop = ($modal, topPx) => {
        const modal = $modal[0];
        const overlay = $modal.find('.cadre-modal__overlay')[0];
        const dialog = $modal.find('.cadre-modal__dialog')[0];

        if (modal) {
            modal.style.setProperty('--cadre-modal-top', `${topPx}px`);
        }

        if (overlay) {
            overlay.style.setProperty('--cadre-modal-top', `${topPx}px`);
        }

        if (dialog) {
            dialog.style.setProperty('--cadre-modal-top', `${topPx}px`);
        }
    };

    const openModal = ($modal) => {
        if (!$modal.length) return;

        const $open = $('[data-cadre-modal][aria-hidden="false"]');

        if ($open.length) {
            $open.attr('aria-hidden', 'true');
        }

        setTop($modal, getViewportTop());

        const content = $modal.find('.cadre-modal__content')[0];
        if (content) {
            content.scrollTop = 0;
        }

        $modal.attr('aria-hidden', 'false');

        const closeBtn = $modal.find('.cadre-modal__close')[0];
        if (closeBtn) {
            setTimeout(() => closeBtn.focus(), 30);
        }
    };

    const ANIMATION_DURATION = 300;

    const closeModal = ($modal) => {
        if (!$modal.length) return;
        if ($modal.attr('aria-hidden') === 'true') return;

        $modal.addClass('is-closing');

        window.setTimeout(() => {
            $modal.removeClass('is-closing');
            $modal.attr('aria-hidden', 'true');
        }, ANIMATION_DURATION);
    };

    $(document).on('click', '[data-cadre-open]', function (e) {
        e.preventDefault();

        const target = this.getAttribute('data-target');
        if (!target) return;

        const $modal = $(target);
        if (!$modal.length) return;

        openModal($modal);
    });

    $(document).on('click', '[data-cadre-close]', function (e) {
        e.preventDefault();

        const $modal = $(this).closest('[data-cadre-modal]');
        if ($modal.length) {
            closeModal($modal);
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;

        const $open = $('[data-cadre-modal][aria-hidden="false"]').first();
        if ($open.length) {
            closeModal($open);
        }
    });

    $(window).on('resize', function () {
        const $open = $('[data-cadre-modal][aria-hidden="false"]').first();
        if (!$open.length) return;

        setTop($open, getViewportTop());
    });
}
export function initCf7Redirect() {
    document.addEventListener('wpcf7mailsent', function (event) {

        const form = event.target;

        const redirects = {
            'cf7-open-day-wro': '/dzien-otwarty-wroclaw/dziekujemy-wroclaw/',
            'cf7-open-day-warszawa': '/dzien-otwarty-warszawa/dziekujemy-warszawa/',
        };

        for (const className in redirects) {
            if (form.closest(`.${className}`)) {
                window.location.href = redirects[className];
                return;
            }
        }

    });
}
