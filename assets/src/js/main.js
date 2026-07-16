import 'bootstrap';

import General from './_generalScripts';
import initPricesCalculator from './prices-calculator';
import initNewsArchiveFilters from './news-archive-filters';
import initLpOUczelni from './lp-o-uczelni';
import initOfferStartTimer from './offer-start-timer';

const App = {
    init() {
        function initGeneral() {
            return new General();
        }
        initGeneral();
    },
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
    initNewsArchiveFilters();
    initLpOUczelni();
    initOfferStartTimer();

    if (window.jQuery) {
        initPricesCalculator(window.jQuery, window.akademiataPrices || {});
    }
});
