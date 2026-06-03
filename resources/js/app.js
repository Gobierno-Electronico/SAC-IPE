import './bootstrap';
import * as bootstrap from 'bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

window.bootstrap = bootstrap;

const $ = window.jQuery;

if ($ && !$.fn.tooltip) {
    $.fn.tooltip = function (action) {
        return this.each(function () {
            const instance = bootstrap.Tooltip.getOrCreateInstance(this);

            if (typeof action === 'string' && typeof instance[action] === 'function') {
                instance[action]();
            }
        });
    };
}
