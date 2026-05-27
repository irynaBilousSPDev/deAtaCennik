import 'bootstrap';

import General from './_generalScripts';
import initPricesCalculator from './prices-calculator';
import initNewsArchiveFilters from './news-archive-filters';

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

    if (window.jQuery) {
        initPricesCalculator(window.jQuery, window.akademiataPrices || {});
    }
});
