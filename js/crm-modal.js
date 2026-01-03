/**
 * PS Smart CRM - Modal Component
 * 
 * Leichtgewichtiges Modal-System (Vanilla JS)
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    if (!window.PSCRM) {
        console.error('PSCRM core not loaded!');
        return;
    }

    /**
     * Modal Komponente
     */
    PSCRM.Modal = function(options) {
        this.options = this._mergeOptions(options);
        this.element = null;
        this.isOpen = false;
        this.init();
    };

    PSCRM.Modal.prototype = {
        /**
         * Standard-Optionen
         */
        _defaults: {
            title: '',
            content: '',
            width: '600px',
            height: 'auto',
            modal: true, // true = mit Overlay
            closeButton: true,
            buttons: [], // { text: 'OK', className: 'btn-primary', onClick: function() {} }
            onOpen: null,
            onClose: null,
            destroyOnClose: true,
            customClass: ''
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
            this._createModal();
            this._attachEvents();
        },

        /**
         * Modal erstellen
         */
        _createModal: function() {
            // Overlay
            if (this.options.modal) {
                this.overlay = document.createElement('div');
                this.overlay.className = 'pscrm-modal-overlay';
                this.overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 9998;
                    display: none;
                `;
            }

            // Modal Container
            this.element = document.createElement('div');
            this.element.className = 'pscrm-modal ' + this.options.customClass;
            this.element.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: ${this.options.width};
                height: ${this.options.height};
                background: white;
                border-radius: 4px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                z-index: 9999;
                display: none;
                overflow: hidden;
            `;

            // Header
            const header = document.createElement('div');
            header.className = 'pscrm-modal-header';
            header.style.cssText = `
                padding: 15px 20px;
                border-bottom: 1px solid #e0e0e0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            `;

            const title = document.createElement('h3');
            title.className = 'pscrm-modal-title';
            title.textContent = this.options.title;
            title.style.cssText = 'margin: 0; font-size: 18px;';
            header.appendChild(title);

            if (this.options.closeButton) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'pscrm-modal-close';
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = `
                    background: none;
                    border: none;
                    font-size: 28px;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    line-height: 1;
                    color: #666;
                `;
                closeBtn.addEventListener('click', () => this.close());
                header.appendChild(closeBtn);
                this.closeBtn = closeBtn;
            }

            // Content
            const content = document.createElement('div');
            content.className = 'pscrm-modal-content';
            content.style.cssText = `
                padding: 20px;
                overflow-y: auto;
                max-height: calc(80vh - 140px);
            `;

            if (typeof this.options.content === 'string') {
                content.innerHTML = this.options.content;
            } else if (this.options.content instanceof HTMLElement) {
                content.appendChild(this.options.content);
            }

            this.contentElement = content;

            // Footer mit Buttons
            let footer = null;
            if (this.options.buttons && this.options.buttons.length > 0) {
                footer = document.createElement('div');
                footer.className = 'pscrm-modal-footer';
                footer.style.cssText = `
                    padding: 15px 20px;
                    border-top: 1px solid #e0e0e0;
                    text-align: right;
                `;

                this.options.buttons.forEach(btnConfig => {
                    const btn = document.createElement('button');
                    btn.className = 'pscrm-modal-btn ' + (btnConfig.className || 'btn');
                    btn.textContent = btnConfig.text || 'OK';
                    btn.style.cssText = `
                        margin-left: 10px;
                        padding: 8px 16px;
                        border: 1px solid #ddd;
                        background: #f5f5f5;
                        cursor: pointer;
                        border-radius: 3px;
                    `;

                    // Primary Button Styling
                    if (btnConfig.className && btnConfig.className.includes('primary')) {
                        btn.style.background = '#007bff';
                        btn.style.color = 'white';
                        btn.style.borderColor = '#007bff';
                    }

                    btn.addEventListener('click', () => {
                        if (btnConfig.onClick) {
                            const result = btnConfig.onClick.call(this);
                            if (result !== false) {
                                this.close();
                            }
                        } else {
                            this.close();
                        }
                    });

                    footer.appendChild(btn);
                });

                this.footerElement = footer;
            }

            // Zusammenbauen
            this.element.appendChild(header);
            this.element.appendChild(content);
            if (footer) {
                this.element.appendChild(footer);
            }

            // Zum DOM hinzufügen
            document.body.appendChild(this.element);
            if (this.overlay) {
                document.body.appendChild(this.overlay);
            }
        },

        /**
         * Events
         */
        _attachEvents: function() {
            const self = this;

            // ESC zum Schließen
            this._escHandler = function(e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.close();
                }
            };

            // Overlay Click
            if (this.overlay) {
                this.overlay.addEventListener('click', function() {
                    self.close();
                });
            }
        },

        /**
         * Öffnen
         */
        open: function() {
            if (this.isOpen) return;

            if (this.overlay) {
                this.overlay.style.display = 'block';
            }
            this.element.style.display = 'block';

            // Animation
            setTimeout(() => {
                if (this.overlay) {
                    this.overlay.style.opacity = '1';
                }
                this.element.style.opacity = '1';
            }, 10);

            this.isOpen = true;

            document.addEventListener('keydown', this._escHandler);

            if (this.options.onOpen) {
                this.options.onOpen.call(this);
            }

            // Body Scroll sperren
            document.body.style.overflow = 'hidden';
        },

        /**
         * Schließen
         */
        close: function() {
            if (!this.isOpen) return;

            if (this.overlay) {
                this.overlay.style.opacity = '0';
            }
            this.element.style.opacity = '0';

            setTimeout(() => {
                if (this.overlay) {
                    this.overlay.style.display = 'none';
                }
                this.element.style.display = 'none';

                if (this.options.destroyOnClose) {
                    this.destroy();
                }
            }, 300);

            this.isOpen = false;

            document.removeEventListener('keydown', this._escHandler);

            if (this.options.onClose) {
                this.options.onClose.call(this);
            }

            // Body Scroll entsperren
            document.body.style.overflow = '';
        },

        /**
         * Titel setzen
         */
        setTitle: function(title) {
            const titleEl = this.element.querySelector('.pscrm-modal-title');
            if (titleEl) {
                titleEl.textContent = title;
            }
        },

        /**
         * Content setzen
         */
        setContent: function(content) {
            if (typeof content === 'string') {
                this.contentElement.innerHTML = content;
            } else if (content instanceof HTMLElement) {
                this.contentElement.innerHTML = '';
                this.contentElement.appendChild(content);
            }
        },

        /**
         * Content Element holen
         */
        getContent: function() {
            return this.contentElement;
        },

        /**
         * Zentrieren
         */
        center: function() {
            // Already centered via CSS transform
            return this;
        },

        /**
         * Destroy
         */
        destroy: function() {
            if (this.element) {
                this.element.remove();
            }
            if (this.overlay) {
                this.overlay.remove();
            }
            
            document.removeEventListener('keydown', this._escHandler);
            document.body.style.overflow = '';
        }
    };

    // Modal Factory Funktionen
    PSCRM.createModal = function(options) {
        return new PSCRM.Modal(options);
    };

    PSCRM.alert = function(message, title) {
        const modal = new PSCRM.Modal({
            title: title || 'Hinweis',
            content: message,
            width: '400px',
            buttons: [
                { text: 'OK', className: 'btn-primary' }
            ]
        });
        modal.open();
        return modal;
    };

    PSCRM.confirm = function(message, title, callback) {
        const modal = new PSCRM.Modal({
            title: title || 'Bestätigung',
            content: message,
            width: '400px',
            buttons: [
                { 
                    text: 'Abbrechen', 
                    className: 'btn-secondary',
                    onClick: function() {
                        if (callback) callback(false);
                    }
                },
                { 
                    text: 'OK', 
                    className: 'btn-primary',
                    onClick: function() {
                        if (callback) callback(true);
                    }
                }
            ]
        });
        modal.open();
        return modal;
    };

})(window);
