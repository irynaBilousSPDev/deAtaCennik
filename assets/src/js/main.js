import 'bootstrap';

import General from './_generalScripts';
import initPricesCalculator from './prices-calculator';

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

    if (window.jQuery) {
        initPricesCalculator(window.jQuery, window.akademiataPrices || {});
    }
});
