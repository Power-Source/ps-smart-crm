/**
 * PS Smart CRM - Dropdown Component
 * 
 * Wrapper für Select2 oder natives Select
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    if (!window.PSCRM) {
        console.error('PSCRM core not loaded!');
        return;
    }

    /**
     * Dropdown Komponente
     */
    PSCRM.Dropdown = function(element, options) {
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        this.options = this._mergeOptions(options);
        this.instance = null;
        this.engine = this._detectEngine();
        
        if (this.element) {
            this.init();
        }
    };

    PSCRM.Dropdown.prototype = {
        /**
         * Standard-Optionen
         */
        _defaults: {
            placeholder: 'Bitte wählen...',
            allowClear: true,
            search: true,
            multiple: false,
            data: null, // Array von {id, text} Objekten
            dataSource: null, // AJAX URL oder Funktion
            valueField: 'id',
            textField: 'text',
            templateResult: null,
            templateSelection: null,
            onChange: null,
            minimumInputLength: 0
        },

        /**
         * Engine erkennen
         */
        _detectEngine: function() {
            if (window.jQuery && jQuery.fn.select2) {
                return 'select2';
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
            if (this.engine === 'select2') {
                this._initSelect2();
            } else {
                this._initNative();
            }
        },

        /**
         * Select2 Initialisierung
         */
        _initSelect2: function() {
            const self = this;
            const $ = jQuery;

            const config = {
                placeholder: this.options.placeholder,
                allowClear: this.options.allowClear,
                width: '100%',
                minimumInputLength: this.options.minimumInputLength
            };

            // Multiple
            if (this.options.multiple) {
                this.element.multiple = true;
            }

            // Daten setzen
            if (this.options.data) {
                config.data = this._normalizeData(this.options.data);
            }

            // AJAX DataSource
            if (this.options.dataSource) {
                if (typeof this.options.dataSource === 'function') {
                    config.ajax = {
                        transport: function(params, success, failure) {
                            self.options.dataSource(params.data, function(data) {
                                success({
                                    results: self._normalizeData(data)
                                });
                            }, failure);
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || data
                            };
                        }
                    };
                } else if (typeof this.options.dataSource === 'string') {
                    config.ajax = {
                        url: this.options.dataSource,
                        dataType: 'json',
                        processResults: function(data) {
                            return {
                                results: self._normalizeData(data.results || data)
                            };
                        }
                    };
                }
            }

            // Templates
            if (this.options.templateResult) {
                config.templateResult = this.options.templateResult;
            }
            if (this.options.templateSelection) {
                config.templateSelection = this.options.templateSelection;
            }

            // Search deaktivieren
            if (!this.options.search) {
                config.minimumResultsForSearch = Infinity;
            }

            // Select2 initialisieren
            this.instance = $(this.element).select2(config);

            // Change Event
            if (this.options.onChange) {
                $(this.element).on('change', function(e) {
                    const value = $(this).val();
                    const selectedData = $(this).select2('data');
                    self.options.onChange.call(self, value, selectedData);
                });
            }
        },

        /**
         * Native Select Initialisierung
         */
        _initNative: function() {
            const self = this;

            // Multiple
            if (this.options.multiple) {
                this.element.multiple = true;
            }

            // Daten setzen
            if (this.options.data) {
                this._populateNativeSelect(this.options.data);
            }

            // Placeholder als erste Option
            if (this.options.placeholder && !this.element.querySelector('option[value=""]')) {
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = this.options.placeholder;
                placeholderOption.disabled = true;
                placeholderOption.selected = true;
                this.element.insertBefore(placeholderOption, this.element.firstChild);
            }

            // Change Event
            if (this.options.onChange) {
                this.element.addEventListener('change', function() {
                    const value = self.getValue();
                    const selectedOptions = Array.from(this.selectedOptions).map(opt => ({
                        id: opt.value,
                        text: opt.textContent
                    }));
                    self.options.onChange.call(self, value, selectedOptions);
                });
            }

            // DataSource laden
            if (this.options.dataSource) {
                this._loadDataSource();
            }
        },

        /**
         * Daten normalisieren
         */
        _normalizeData: function(data) {
            if (!Array.isArray(data)) return [];

            return data.map(item => {
                if (typeof item === 'object') {
                    return {
                        id: item[this.options.valueField],
                        text: item[this.options.textField]
                    };
                }
                return { id: item, text: item };
            });
        },

        /**
         * Native Select füllen
         */
        _populateNativeSelect: function(data) {
            // Bestehende Optionen (außer Placeholder) entfernen
            const placeholder = this.element.querySelector('option[value=""]');
            this.element.innerHTML = '';
            if (placeholder) {
                this.element.appendChild(placeholder);
            }

            const normalizedData = this._normalizeData(data);

            normalizedData.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.text;
                this.element.appendChild(option);
            });
        },

        /**
         * DataSource laden
         */
        _loadDataSource: function() {
            const self = this;

            if (typeof this.options.dataSource === 'function') {
                this.options.dataSource({}, function(data) {
                    self._populateNativeSelect(data);
                });
            } else if (typeof this.options.dataSource === 'string') {
                fetch(this.options.dataSource)
                    .then(response => response.json())
                    .then(data => {
                        self._populateNativeSelect(data.results || data);
                    })
                    .catch(error => {
                        console.error('Error loading data:', error);
                    });
            }
        },

        /**
         * Wert setzen
         */
        setValue: function(value) {
            if (this.engine === 'select2') {
                jQuery(this.element).val(value).trigger('change');
            } else {
                this.element.value = value;
                
                // Change Event triggern
                const event = new Event('change', { bubbles: true });
                this.element.dispatchEvent(event);
            }
        },

        /**
         * Wert holen
         */
        getValue: function() {
            if (this.engine === 'select2') {
                return jQuery(this.element).val();
            } else {
                if (this.options.multiple) {
                    return Array.from(this.element.selectedOptions).map(opt => opt.value);
                }
                return this.element.value;
            }
        },

        /**
         * Daten neu laden
         */
        reload: function() {
            if (this.options.dataSource) {
                if (this.engine === 'select2') {
                    jQuery(this.element).select2('destroy');
                    this._initSelect2();
                } else {
                    this._loadDataSource();
                }
            }
        },

        /**
         * Löschen
         */
        clear: function() {
            this.setValue('');
        },

        /**
         * Destroy
         */
        destroy: function() {
            if (this.instance && this.engine === 'select2') {
                jQuery(this.element).select2('destroy');
            }
        }
    };

    // Dropdown Factory Funktion
    PSCRM.createDropdown = function(element, options) {
        return new PSCRM.Dropdown(element, options);
    };

})(window);
