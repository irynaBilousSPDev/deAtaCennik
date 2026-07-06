import $ from 'jquery';

// Group imports by functionality
import * as Sliders from './__sliders';
import {initYouTubeSlider} from './__YouTubeSlider';
import * as CustomFunctions from './__customFunctions';
import {initCityTabs, initContactTabs, initLanguageFilters, initLanguageTabs} from "./__customFunctions";
import initPgMbaThemeFilter from './pg-mba-theme-filter';
import { initOfferMobileToolbar } from './offer-mobile-toolbar';
import { initOfferViewToggle } from './offer-view-toggle';
import { initOfferFavorites, deactivateFavoritesFilter, isFavoritesFilterActive, applyOfferCardFilters } from './offer-favorites';


class General {
    constructor() {
        console.log('General initialized!');
        this.youTubeSlider();
        this.initializeYouTubeVideos();
        this.bindEvents();
        CustomFunctions.enableSkipLink();
    }

    youTubeSlider() {
        document.querySelectorAll(".youtube-slider").forEach(slider => {
            initYouTubeSlider(slider);
        });
    }

    initializeYouTubeVideos() {
        document.querySelectorAll("[data-youtube-id]").forEach(container => {
            const videoId = container.getAttribute("data-youtube-id");
            if (videoId) {
                // Lazy load addVideo from videoUtils.js
                import('./__videoUtils')
                    .then(module => module.addVideo(container, videoId))
                    .catch(err => console.error("Failed to load addVideo", err));
            }
        });
    }

    bindEvents() {
        document.querySelectorAll('.hero-slider').forEach((slider) => {
            Sliders.initHeroSlider(slider);
        });

        CustomFunctions.initializeAccordion('.accordion_container');
        CustomFunctions.accordionUniversal('.accordion_universal');
        CustomFunctions.initializeTabsContainer('.tabs_container');
        CustomFunctions.copyAccountNumber('.copy_account_number');
        CustomFunctions.updateHeaderLink('#sourceLink', '.registration_link');

        CustomFunctions.initializeCounterWrapper('.counter_wrapper');
        Sliders.initializePartnerLogosSlider('.partner_logos_slider');
        Sliders.initializeNewWhyStudySlider('.new_study_slider');
        Sliders.initializeDiscountsSlider('.discounts');
        CustomFunctions.initializeFancybox();
        CustomFunctions.initCityTabs();
        initPgMbaThemeFilter();
        CustomFunctions.initPodcastSocialCounter();
        CustomFunctions.initTaxonomyTabs();
        CustomFunctions.initializeDiscountModal();
        CustomFunctions.initCadreModal();
        CustomFunctions.initContactCityTabs();
        CustomFunctions. initCf7Redirect();
        CustomFunctions.initArchiveAccordion($);


        CustomFunctions.offerNavScroll(); // Default: Uses "#menu-offer a"
        CustomFunctions.handleScrollButtonOffer();

        $(document).ready(function () {
            CustomFunctions.initMegaMenu('.megaMenuToggle', '#megaMenu');
        });

        CustomFunctions.filterAccordion('.filter_accordion_header');
        CustomFunctions.initMobileFilterToggle();
        initOfferMobileToolbar();
        initOfferViewToggle();
        initOfferFavorites();

        // Initialize for all instances of .image_content_slider
        document.querySelectorAll('.image_content_slider').forEach(slider => {
            Sliders.initializeImageContentSlider(slider);
        });
        

        // Run immediately on script execution
        CustomFunctions.updateResponsiveImages('.responsive-image');

        function watchResponsiveImages() {
            const update = () => CustomFunctions.updateResponsiveImages('.responsive-image');

            update(); // Initial call
            document.addEventListener('DOMContentLoaded', update);
            window.addEventListener('load', update);
            window.addEventListener('resize', update);
        }
        
    }
}

export default General;


