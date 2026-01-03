/**
 * jQuery UI Shim - Ersetzt jQuery UI Aufrufe durch Vanilla.js/Flatpickr
 * 
 * Dieses Shim ermöglicht es, Legacy-Code mit jQuery UI Aufrufen
 * ohne Änderungen zu verwenden, indem sie automatisch auf
 * moderne Vanilla.js-Lösungen gemappt werden.
 */
(function($) {
    'use strict';
    
    // Datetimepicker Shim (nutzt Flatpickr)
    $.fn.datetimepicker = function(options) {
        return this.each(function() {
            const $el = $(this);
            const flatpickrOptions = {
                enableTime: true,
                dateFormat: 'd-m-Y H:i',
                time_24hr: true,
                locale: {
                    firstDayOfWeek: 1
                }
            };
            
            // Wenn Flatpickr verfügbar ist, verwenden
            if (typeof flatpickr !== 'undefined') {
                flatpickr(this, flatpickrOptions);
            } else {
                console.warn('Flatpickr nicht verfügbar für datetimepicker auf', this);
            }
        });
    };
    
    // Datepicker Shim (nutzt Flatpickr)
    $.fn.datepicker = function(options) {
        return this.each(function() {
            const $el = $(this);
            const flatpickrOptions = {
                dateFormat: 'd-m-Y',
                locale: {
                    firstDayOfWeek: 1
                }
            };
            
            if (typeof flatpickr !== 'undefined') {
                flatpickr(this, flatpickrOptions);
            } else {
                console.warn('Flatpickr nicht verfügbar für datepicker auf', this);
            }
        });
    };
    
    // Timepicker Shim (nutzt Flatpickr nur für Zeit)
    $.fn.timepicker = function(options) {
        return this.each(function() {
            const flatpickrOptions = {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true
            };
            
            if (typeof flatpickr !== 'undefined') {
                flatpickr(this, flatpickrOptions);
            } else {
                console.warn('Flatpickr nicht verfügbar für timepicker auf', this);
            }
        });
    };
    
    // Tooltip Shim (nutzt native CSS tooltips via title attribute)
    $.fn.tooltip = function(options) {
        // jQuery UI Tooltip wird nicht mehr benötigt
        // Title-Attribute funktionieren nativ im Browser
        return this;
    };
    
    // jQuery UI Shim geladen
    
})(jQuery);
