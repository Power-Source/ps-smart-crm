/**
 * PS Smart CRM - DatePicker Component
 * 
 * Wrapper für Flatpickr (oder Fallback auf jQuery UI)
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    if (!window.PSCRM) {
        console.error('PSCRM core not loaded!');
        return;
    }

    /**
     * DatePicker Komponente
     */
    PSCRM.DatePicker = function(element, options) {
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        this.options = this._mergeOptions(options);
        this.instance = null;
        this.engine = this._detectEngine();
        
        if (this.element) {
            this.init();
        }
    };

    PSCRM.DatePicker.prototype = {
        /**
         * Standard-Optionen
         */
        _defaults: {
            format: 'DD-MM-YYYY',
            enableTime: false,
            timeFormat: 'HH:mm',
            locale: 'de',
            minDate: null,
            maxDate: null,
            defaultDate: null,
            onChange: null,
            onOpen: null,
            onClose: null,
            mode: 'single', // single, multiple, range
            inline: false,
            allowInput: true
        },

        /**
         * Engine erkennen
         */
        _detectEngine: function() {
            if (window.flatpickr) {
                return 'flatpickr';
            } else if (window.jQuery && jQuery.fn.datepicker) {
                return 'jquery-ui';
            }
            return 'native';
        },

        /**
         * Optionen zusammenführen
         */
        _mergeOptions: function(options) {
            return Object.assign({}, this._defaults, options || {});
        },

        /**
         * Initialisierung
         */
        init: function() {
            if (this.engine === 'flatpickr') {
                this._initFlatpickr();
            } else if (this.engine === 'jquery-ui') {
                this._initJQueryUI();
            } else {
                this._initNative();
            }
        },

        /**
         * Flatpickr Initialisierung
         */
        _initFlatpickr: function() {
            const self = this;
            
            // Format für Flatpickr konvertieren
            const dateFormat = this._convertFormatForFlatpickr(this.options.format);
            
            const config = {
                dateFormat: dateFormat,
                enableTime: this.options.enableTime,
                time_24hr: true,
                locale: this._getFlatpickrLocale(),
                minDate: this.options.minDate,
                maxDate: this.options.maxDate,
                defaultDate: this.options.defaultDate,
                mode: this.options.mode,
                inline: this.options.inline,
                allowInput: this.options.allowInput,
                onChange: function(selectedDates, dateStr, instance) {
                    if (self.options.onChange) {
                        self.options.onChange.call(self, selectedDates, dateStr);
                    }
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    if (self.options.onOpen) {
                        self.options.onOpen.call(self);
                    }
                },
                onClose: function(selectedDates, dateStr, instance) {
                    if (self.options.onClose) {
                        self.options.onClose.call(self);
                    }
                }
            };

            this.instance = flatpickr(this.element, config);
        },

        /**
         * jQuery UI Datepicker Initialisierung
         */
        _initJQueryUI: function() {
            const self = this;
            const $ = jQuery;
            
            const dateFormat = this._convertFormatForJQueryUI(this.options.format);
            
            const config = {
                dateFormat: dateFormat,
                minDate: this.options.minDate,
                maxDate: this.options.maxDate,
                defaultDate: this.options.defaultDate,
                onSelect: function(dateText, inst) {
                    if (self.options.onChange) {
                        const date = $(this).datepicker('getDate');
                        self.options.onChange.call(self, [date], dateText);
                    }
                },
                beforeShow: function() {
                    if (self.options.onOpen) {
                        self.options.onOpen.call(self);
                    }
                },
                onClose: function() {
                    if (self.options.onClose) {
                        self.options.onClose.call(self);
                    }
                }
            };

            // Sprache setzen
            if ($.datepicker.regional[this.options.locale]) {
                $.datepicker.setDefaults($.datepicker.regional[this.options.locale]);
            }

            this.instance = $(this.element).datepicker(config);
        },

        /**
         * Native HTML5 Date Input
         */
        _initNative: function() {
            const self = this;
            
            this.element.type = this.options.enableTime ? 'datetime-local' : 'date';
            
            if (this.options.minDate) {
                this.element.min = this._formatForNative(this.options.minDate);
            }
            if (this.options.maxDate) {
                this.element.max = this._formatForNative(this.options.maxDate);
            }
            if (this.options.defaultDate) {
                this.element.value = this._formatForNative(this.options.defaultDate);
            }

            this.element.addEventListener('change', function() {
                if (self.options.onChange) {
                    const date = new Date(this.value);
                    self.options.onChange.call(self, [date], this.value);
                }
            });
        },

        /**
         * Format-Konvertierungen
         */
        _convertFormatForFlatpickr: function(format) {
            return format
                .replace('DD', 'd')
                .replace('MM', 'm')
                .replace('YYYY', 'Y')
                .replace('HH', 'H')
                .replace('mm', 'i');
        },

        _convertFormatForJQueryUI: function(format) {
            return format
                .replace('DD', 'dd')
                .replace('MM', 'mm')
                .replace('YYYY', 'yy')
                .toLowerCase();
        },

        _formatForNative: function(date) {
            if (typeof date === 'string') {
                date = new Date(date);
            }
            
            if (this.options.enableTime) {
                return date.toISOString().slice(0, 16);
            } else {
                return date.toISOString().slice(0, 10);
            }
        },

        /**
         * Flatpickr Locale
         */
        _getFlatpickrLocale: function() {
            if (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.de) {
                return window.flatpickr.l10ns.de;
            }
            
            // Fallback deutsche Übersetzung
            return {
                weekdays: {
                    shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
                    longhand: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"]
                },
                months: {
                    shorthand: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
                    longhand: ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"]
                },
                firstDayOfWeek: 1,
                weekAbbreviation: "KW",
                rangeSeparator: " bis ",
                scrollTitle: "Zum Ändern scrollen",
                toggleTitle: "Zum Umschalten klicken"
            };
        },

        /**
         * Wert setzen
         */
        setValue: function(value) {
            if (this.engine === 'flatpickr') {
                this.instance.setDate(value);
            } else if (this.engine === 'jquery-ui') {
                jQuery(this.element).datepicker('setDate', value);
            } else {
                this.element.value = this._formatForNative(value);
            }
        },

        /**
         * Wert holen
         */
        getValue: function() {
            if (this.engine === 'flatpickr') {
                return this.instance.selectedDates;
            } else if (this.engine === 'jquery-ui') {
                return [jQuery(this.element).datepicker('getDate')];
            } else {
                return [new Date(this.element.value)];
            }
        },

        /**
         * Löschen
         */
        clear: function() {
            if (this.engine === 'flatpickr') {
                if (this.instance && this.instance.config) {
                    this.instance.clear();
                } else if (this.element) {
                    this.element.value = '';
                }
            } else if (this.engine === 'jquery-ui') {
                jQuery(this.element).datepicker('setDate', null);
            } else if (this.element) {
                this.element.value = '';
            }
        },

        /**
         * Destroy
         */
        destroy: function() {
            if (this.instance) {
                if (this.engine === 'flatpickr') {
                    this.instance.destroy();
                } else if (this.engine === 'jquery-ui') {
                    jQuery(this.element).datepicker('destroy');
                }
            }
        }
    };

    // DatePicker Factory Funktion
    PSCRM.createDatePicker = function(element, options) {
        return new PSCRM.DatePicker(element, options);
    };

})(window);
