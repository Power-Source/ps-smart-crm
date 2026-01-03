/**
 * PS Smart CRM - Core JavaScript Module
 * 
 * Zentrale Initialisierung und Utility-Funktionen
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    // Suprimir jQuery Migrate warnings non-critical (deferred.pipe deprecated from Parsley.js)
    const originalWarn = console.warn;
    console.warn = function(...args) {
        if (args[0] && args[0].includes && args[0].includes('JQMIGRATE: deferred.pipe()')) {
            return; // Suprimir warning de deferred.pipe do Parsley.js
        }
        originalWarn.apply(console, args);
    };

    /**
     * Hauptobjekt für PS Smart CRM
     */
    window.PSCRM = window.PSCRM || {
        version: '1.0.0',
        config: {},
        components: {},
        utils: {},
        initialized: false
    };

    /**
     * Initialisierung
     */
    PSCRM.init = function(config) {
        if (this.initialized) {
            console.warn('PSCRM already initialized');
            return;
        }

        // Konfiguration übernehmen
        this.config = Object.assign({
            locale: 'de-DE',
            dateFormat: 'DD-MM-YYYY',
            dateTimeFormat: 'DD-MM-YYYY HH:mm',
            currency: 'EUR',
            currencySymbol: '€',
            ajaxUrl: window.ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: '',
            debug: false
        }, config || {});

        this.initialized = true;

        if (this.config.debug) {
        }

        // Events triggern
        this.trigger('init', this.config);
    };

    /**
     * Event System
     */
    PSCRM._events = {};

    PSCRM.on = function(event, callback) {
        if (!this._events[event]) {
            this._events[event] = [];
        }
        this._events[event].push(callback);
    };

    PSCRM.off = function(event, callback) {
        if (!this._events[event]) return;
        
        if (!callback) {
            delete this._events[event];
            return;
        }

        this._events[event] = this._events[event].filter(cb => cb !== callback);
    };

    PSCRM.trigger = function(event, data) {
        if (!this._events[event]) return;
        
        this._events[event].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error('Error in event handler:', error);
            }
        });
    };

    /**
     * Utility Funktionen
     */
    PSCRM.utils = {
        /**
         * Formatiert ein Datum
         */
        formatDate: function(date, format) {
            format = format || PSCRM.config.dateFormat;
            
            if (!date) return '';
            if (typeof date === 'string') {
                date = new Date(date);
            }

            if (!(date instanceof Date) || isNaN(date)) {
                return '';
            }

            const pad = (num) => String(num).padStart(2, '0');
            
            const map = {
                'DD': pad(date.getDate()),
                'MM': pad(date.getMonth() + 1),
                'YYYY': date.getFullYear(),
                'YY': String(date.getFullYear()).slice(-2),
                'HH': pad(date.getHours()),
                'mm': pad(date.getMinutes()),
                'ss': pad(date.getSeconds())
            };

            return format.replace(/DD|MM|YYYY|YY|HH|mm|ss/g, matched => map[matched]);
        },

        /**
         * Parst ein Datum-String
         */
        parseDate: function(dateString, format) {
            format = format || PSCRM.config.dateFormat;
            
            if (!dateString) return null;
            
            // ISO Format direkt parsen
            if (dateString.match(/^\d{4}-\d{2}-\d{2}/)) {
                return new Date(dateString);
            }

            // Custom Format parsen
            const parts = {};
            const formatParts = format.match(/DD|MM|YYYY|HH|mm|ss/g);
            const separators = format.split(/DD|MM|YYYY|HH|mm|ss/).filter(s => s);
            
            let regex = format;
            formatParts.forEach(part => {
                regex = regex.replace(part, '(\\d+)');
            });
            
            const matches = dateString.match(new RegExp(regex));
            if (!matches) return null;

            formatParts.forEach((part, index) => {
                parts[part] = parseInt(matches[index + 1], 10);
            });

            return new Date(
                parts.YYYY || parts.YY + 2000,
                (parts.MM || 1) - 1,
                parts.DD || 1,
                parts.HH || 0,
                parts.mm || 0,
                parts.ss || 0
            );
        },

        /**
         * Formatiert Währungsbeträge
         */
        formatCurrency: function(amount, symbol) {
            symbol = symbol || PSCRM.config.currencySymbol;
            
            if (amount === null || amount === undefined) return '';
            
            const num = parseFloat(amount);
            if (isNaN(num)) return '';

            return symbol + ' ' + num.toLocaleString(PSCRM.config.locale, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        /**
         * Debounce Funktion
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * AJAX Helper
         */
        ajax: function(options) {
            const defaults = {
                url: PSCRM.config.ajaxUrl,
                method: 'POST',
                data: {},
                success: function() {},
                error: function() {},
                complete: function() {}
            };

            options = Object.assign(defaults, options);

            // Nonce hinzufügen wenn verfügbar
            if (PSCRM.config.nonce && !options.data.nonce) {
                options.data.nonce = PSCRM.config.nonce;
            }

            // FormData oder URLSearchParams für POST
            let body = null;
            if (options.method === 'POST') {
                const formData = new FormData();
                for (let key in options.data) {
                    formData.append(key, options.data[key]);
                }
                body = formData;
            }

            fetch(options.url, {
                method: options.method,
                body: body,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                options.success(data);
            })
            .catch(error => {
                options.error(error);
                console.error('AJAX Error:', error);
            })
            .finally(() => {
                options.complete();
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Query String Parameter holen
         */
        getUrlParameter: function(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name);
        },

        /**
         * Element show/hide
         */
        show: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) element.style.display = '';
        },

        hide: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) element.style.display = 'none';
        },

        /**
         * Element Klassen Toggle
         */
        toggleClass: function(element, className) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) element.classList.toggle(className);
        }
    };

    /**
     * Notification System
     */
    PSCRM.notify = function(message, type, duration) {
        type = type || 'info'; // info, success, warning, error
        duration = duration || 3000;

        // Prüfen ob noty verfügbar ist
        if (window.noty) {
            noty({
                text: message,
                type: type,
                timeout: duration,
                layout: 'topRight'
            });
        } else {
            // Fallback: Console oder natives Alert
            
            // Einfaches natives Notification-Element erstellen
            const notification = document.createElement('div');
            notification.className = `pscrm-notification pscrm-notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'error' ? '#f44336' : type === 'success' ? '#4caf50' : type === 'warning' ? '#ff9800' : '#2196f3'};
                color: white;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 10000;
                max-width: 300px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transition = 'opacity 0.3s';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    };

    // Auto-Init bei DOM Ready wenn Config existiert
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (window.PSCRMConfig) {
                PSCRM.init(window.PSCRMConfig);
            }
        });
    } else {
        if (window.PSCRMConfig) {
            PSCRM.init(window.PSCRMConfig);
        }
    }

})(window);
