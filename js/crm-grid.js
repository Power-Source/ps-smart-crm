/**
 * PS Smart CRM - Grid Component
 * 
 * Universeller Grid-Wrapper für DataTables/Tabulator
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    if (!window.PSCRM) {
        console.error('PSCRM core not loaded!');
        return;
    }

    /**
     * Grid Komponente
     */
    PSCRM.Grid = function(element, options) {
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        this.options = this._mergeOptions(options);
        this.data = [];
        this.instance = null;
        this.engine = this.options.engine || 'datatable'; // datatable oder tabulator
        
        if (this.element) {
            this.init();
        }
    };

    PSCRM.Grid.prototype = {
        /**
         * Standard-Optionen
         */
        _defaults: {
            engine: 'datatable', // 'datatable' oder 'tabulator'
            height: 600,
            pageSize: 50,
            pageSizes: [20, 50, 100],
            sortable: true,
            filterable: true,
            groupable: false,
            columns: [],
            dataSource: null,
            locale: 'de-DE',
            responsive: true,
            noRecordsText: 'Keine Einträge vorhanden',
            loadingText: 'Lädt...',
            onDataBound: null,
            onRowClick: null,
            onInit: null
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
            if (this.engine === 'datatable' && window.jQuery && jQuery.fn.DataTable) {
                this._initDataTable();
            } else if (this.engine === 'tabulator' && window.Tabulator) {
                this._initTabulator();
            } else {
                console.error('Grid engine not available:', this.engine);
                return;
            }

            if (this.options.onInit) {
                this.options.onInit.call(this);
            }
        },

        /**
         * DataTables Initialisierung
         */
        _initDataTable: function() {
            const self = this;
            const $ = jQuery;

            // Tabelle erstellen falls nicht vorhanden
            if (this.element.tagName !== 'TABLE') {
                const table = document.createElement('table');
                table.className = 'pscrm-grid display';
                table.style.width = '100%';
                this.element.appendChild(table);
                this.tableElement = table;
            } else {
                this.tableElement = this.element;
            }

            // DataTables Konfiguration
            const dtConfig = {
                pageLength: this.options.pageSize,
                lengthMenu: this.options.pageSizes,
                searching: this.options.filterable,
                ordering: this.options.sortable,
                responsive: this.options.responsive,
                language: this._getDataTablesLanguage(),
                columns: this._mapColumnsForDataTables(),
                data: [],
                drawCallback: function(settings) {
                    if (self.options.onDataBound) {
                        self.options.onDataBound.call(self);
                    }
                }
            };

            // AJAX DataSource
            if (this.options.dataSource) {
                if (typeof this.options.dataSource === 'function') {
                    dtConfig.ajax = function(data, callback, settings) {
                        // Defensive Parameter-Prüfung
                        const params = {
                            page: (data && data.start !== undefined) ? Math.floor(data.start / (data.length || 10)) + 1 : 1,
                            pageSize: (data && data.length) ? data.length : 10,
                            search: (data && data.search && data.search.value) ? data.search.value : '',
                            sortField: (data && data.order && data.order.length) ? data.columns[data.order[0].column].data : null,
                            sortDir: (data && data.order && data.order.length) ? data.order[0].dir : null
                        };
                        
                        self.options.dataSource(params, function(result) {
                            callback({
                                draw: data ? data.draw : 1,
                                recordsTotal: result.total || (result.data ? result.data.length : 0),
                                recordsFiltered: result.filtered || (result.data ? result.data.length : 0),
                                data: result.data || []
                            });
                        });
                    };
                } else if (typeof this.options.dataSource === 'string') {
                    dtConfig.ajax = this.options.dataSource;
                }
            }

            // DataTable initialisieren
            this.instance = $(this.tableElement).DataTable(dtConfig);

            // Row Click Event
            if (this.options.onRowClick) {
                $(this.tableElement).on('click', 'tbody tr', function() {
                    const data = self.instance.row(this).data();
                    self.options.onRowClick.call(self, data, this);
                });
            }
        },

        /**
         * Tabulator Initialisierung
         */
        _initTabulator: function() {
            const self = this;

            const tabulatorConfig = {
                height: this.options.height,
                layout: this.options.responsive ? 'fitColumns' : 'fitData',
                pagination: 'local',
                paginationSize: this.options.pageSize,
                paginationSizeSelector: this.options.pageSizes,
                columns: this._mapColumnsForTabulator(),
                locale: this.options.locale,
                langs: this._getTabulatorLanguage(),
                placeholder: this.options.noRecordsText,
                dataLoaded: function(data) {
                    if (self.options.onDataBound) {
                        self.options.onDataBound.call(self, data);
                    }
                },
                rowClick: function(e, row) {
                    if (self.options.onRowClick) {
                        self.options.onRowClick.call(self, row.getData(), row.getElement());
                    }
                }
            };

            // DataSource konfigurieren
            if (this.options.dataSource) {
                if (typeof this.options.dataSource === 'function') {
                    tabulatorConfig.ajaxRequestFunc = function(url, config, params) {
                        return new Promise(function(resolve, reject) {
                            self.options.dataSource(params, function(result) {
                                resolve(result.data || result);
                            }, reject);
                        });
                    };
                } else if (typeof this.options.dataSource === 'string') {
                    tabulatorConfig.ajaxURL = this.options.dataSource;
                }
            }

            this.instance = new Tabulator(this.element, tabulatorConfig);
        },

        /**
         * Spalten für DataTables mappen
         */
        _mapColumnsForDataTables: function() {
            return this.options.columns.map(col => {
                const dtCol = {
                    data: col.field,
                    title: col.title,
                    visible: !col.hidden,
                    orderable: col.sortable !== false,
                    searchable: col.filterable !== false,
                    width: col.width
                };

                // Template/Render Funktion
                if (col.template) {
                    dtCol.render = function(data, type, row) {
                        if (type === 'display') {
                            return typeof col.template === 'function' 
                                ? col.template(row) 
                                : col.template.replace(/\{(\w+)\}/g, (m, key) => row[key]);
                        }
                        return data;
                    };
                } else if (col.render) {
                    dtCol.render = col.render;
                }

                // Command Spalte
                if (col.command) {
                    dtCol.render = function(data, type, row) {
                        if (type === 'display') {
                            return col.command.map(cmd => {
                                const btnClass = cmd.className || 'btn btn-sm';
                                const title = cmd.name || '';
                                return `<button class="${btnClass}" data-action="${cmd.action || ''}" title="${title}">${cmd.icon || title}</button>`;
                            }).join(' ');
                        }
                        return data;
                    };

                    dtCol.orderable = false;
                    dtCol.searchable = false;
                }

                return dtCol;
            });
        },

        /**
         * Spalten für Tabulator mappen
         */
        _mapColumnsForTabulator: function() {
            return this.options.columns.map(col => {
                const tabCol = {
                    field: col.field,
                    title: col.title,
                    visible: !col.hidden,
                    sorter: col.sortable !== false ? 'string' : false,
                    width: col.width
                };

                // Formatter (Template)
                if (col.template) {
                    tabCol.formatter = function(cell) {
                        const row = cell.getRow().getData();
                        return typeof col.template === 'function' 
                            ? col.template(row) 
                            : col.template.replace(/\{(\w+)\}/g, (m, key) => row[key]);
                    };
                } else if (col.formatter) {
                    tabCol.formatter = col.formatter;
                }

                // Command Spalte
                if (col.command) {
                    tabCol.formatter = function(cell) {
                        const row = cell.getRow().getData();
                        return col.command.map(cmd => {
                            const btnClass = cmd.className || 'btn btn-sm';
                            const title = cmd.name || '';
                            return `<button class="${btnClass}" data-action="${cmd.action || ''}" title="${title}">${cmd.icon || title}</button>`;
                        }).join(' ');
                    };

                    tabCol.headerSort = false;
                }

                return tabCol;
            });
        },

        /**
         * DataTables Spracheinstellungen
         */
        _getDataTablesLanguage: function() {
            return {
                "sEmptyTable": "Keine Daten in der Tabelle vorhanden",
                "sInfo": "_START_ bis _END_ von _TOTAL_ Einträgen",
                "sInfoEmpty": "Keine Einträge vorhanden",
                "sInfoFiltered": "(gefiltert von _MAX_ Einträgen)",
                "sInfoPostFix": "",
                "sInfoThousands": ".",
                "sLengthMenu": "_MENU_ Einträge anzeigen",
                "sLoadingRecords": "Lädt...",
                "sProcessing": "Bitte warten...",
                "sSearch": "Suchen:",
                "sZeroRecords": "Keine Einträge vorhanden",
                "oPaginate": {
                    "sFirst": "Erste",
                    "sPrevious": "Zurück",
                    "sNext": "Weiter",
                    "sLast": "Letzte"
                },
                "oAria": {
                    "sSortAscending": ": aktivieren, um Spalte aufsteigend zu sortieren",
                    "sSortDescending": ": aktivieren, um Spalte absteigend zu sortieren"
                }
            };
        },

        /**
         * Tabulator Spracheinstellungen
         */
        _getTabulatorLanguage: function() {
            return {
                "de-DE": {
                    "pagination": {
                        "first": "Erste",
                        "first_title": "Erste Seite",
                        "last": "Letzte",
                        "last_title": "Letzte Seite",
                        "prev": "Zurück",
                        "prev_title": "Vorherige Seite",
                        "next": "Weiter",
                        "next_title": "Nächste Seite"
                    },
                    "data": {
                        "loading": "Lädt...",
                        "error": "Fehler"
                    }
                }
            };
        },

        /**
         * Daten setzen
         */
        setData: function(data) {
            if (this.engine === 'datatable') {
                this.instance.clear();
                this.instance.rows.add(data);
                this.instance.draw();
            } else if (this.engine === 'tabulator') {
                this.instance.setData(data);
            }
        },

        /**
         * Daten neu laden
         */
        reload: function() {
            if (this.engine === 'datatable') {
                this.instance.ajax.reload();
            } else if (this.engine === 'tabulator') {
                this.instance.setData();
            }
        },

        /**
         * Destroy
         */
        destroy: function() {
            if (this.instance) {
                if (this.engine === 'datatable') {
                    this.instance.destroy();
                } else if (this.engine === 'tabulator') {
                    this.instance.destroy();
                }
            }
        }
    };

    // Grid Factory Funktion
    PSCRM.createGrid = function(element, options) {
        return new PSCRM.Grid(element, options);
    };

    /**
     * Customer-spezifische Grid Initialisierung
     */
    PSCRM.createCustomerGrid = function(element, options) {
        // Standard Kundenlistenkonfiguration - OHNE initiale Daten
        const customerGridOptions = {
            engine: 'datatable',
            height: options.height || 600,
            columns: [
                { field: 'id', title: 'ID', width: '80px' },
                { field: 'nome', title: 'Name' },
                { field: 'email', title: 'Email' },
                { field: 'telefono', title: 'Telefon' },
                { field: 'kategorien', title: 'Kategorien', hidden: !options.showCategories },
                { field: 'interessi', title: 'Interessen', hidden: !options.showInterests },
                { field: 'provenienza', title: 'Herkunft', hidden: !options.showOrigin },
                {
                    field: 'azioni',
                    title: 'Aktionen',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-primary" data-action="edit" data-id="${row.id}">Bearbeiten</button>
                                <button class="btn btn-sm btn-danger" data-action="delete" data-id="${row.id}" data-nonce="${options.deleteNonce}">Löschen</button>`;
                    }
                }
            ],
            onDataBound: function() {
            },
            onRowClick: function(data, row) {
            }
        };

        // Merge mit zusätzlichen Optionen (ohne dataSource)
        $.extend(true, customerGridOptions, options);
        
        // Entferne dataSource falls vorhanden
        delete customerGridOptions.dataSource;

        // Grid erstellen
        const grid = PSCRM.createGrid(element, customerGridOptions);
        
        // Load data NACH Grid-Initialisierung wenn AJAX vorhanden
        if (window.ajaxurl && grid && grid.instance) {
            setTimeout(function() {
                $.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: { action: 'WPsCRM_load_customers_grid' },
                    dataType: 'json',
                    success: function(result) {
                        if (result && result.data && grid.instance && grid.setData) {
                            grid.setData(result.data);
                        }
                    },
                    error: function() {
                    }
                });
            }, 100);
        }
        
        return grid;
    };

    /**
     * Documents-spezifische Grid Initialisierung
     */
    PSCRM.createDocumentsGrid = function(element, options) {
        const docGridOptions = {
            engine: 'datatable',
            height: options.height || 600,
            columns: [
                { field: 'id', title: 'ID', width: '80px' },
                { field: 'numero', title: 'Nummer' },
                { field: 'data', title: 'Datum' },
                { field: 'importo', title: 'Betrag' },
                { field: 'stato', title: 'Status' },
                {
                    field: 'azioni',
                    title: 'Aktionen',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-primary" data-action="view" data-id="${row.id}">Ansehen</button>`;
                    }
                }
            ],
            onDataBound: function() {
            }
        };

        $.extend(true, docGridOptions, options);
        
        // Entferne dataSource falls vorhanden - Grid soll ohne AJAX initialisieren
        delete docGridOptions.dataSource;
        
        const grid = PSCRM.createGrid(element, docGridOptions);
        
        // Load data NACH Grid-Initialisierung wenn AJAX vorhanden
        if (window.ajaxurl && options.type && grid && grid.instance) {
            setTimeout(function() {
                $.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: { action: 'WPsCRM_load_documents_grid', type: options.type },
                    dataType: 'json',
                    success: function(result) {
                        if (result && result.data && grid.setData) {
                            grid.setData(result.data);
                        }
                    },
                    error: function() {
                    }
                });
            }, 100);
        }
        
        return grid;
    };

    /**
     * Scheduler-spezifische Grid Initialisierung
     */
    PSCRM.createSchedulerGrid = function(element, options) {
        const schedulerGridOptions = {
            engine: 'datatable',
            height: options.height || 600,
            columns: [
                { field: 'id', title: 'ID', width: '80px' },
                { field: 'titolo', title: 'Titel' },
                { field: 'data', title: 'Datum' },
                { field: 'utente', title: 'Benutzer' },
                { field: 'tipo', title: 'Typ' },
                {
                    field: 'azioni',
                    title: 'Aktionen',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-primary" data-action="edit" data-id="${row.id}">Bearbeiten</button>`;
                    }
                }
            ],
            onDataBound: function() {
            }
        };

        $.extend(true, schedulerGridOptions, options);
        
        // Entferne dataSource falls vorhanden - Grid soll ohne AJAX initialisieren
        delete schedulerGridOptions.dataSource;
        
        const grid = PSCRM.createGrid(element, schedulerGridOptions);
        
        // Load data NACH Grid-Initialisierung wenn AJAX vorhanden
        if (window.ajaxurl && grid && grid.instance) {
            setTimeout(function() {
                $.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: { action: 'WPsCRM_load_scheduler_grid' },
                    dataType: 'json',
                    success: function(result) {
                        if (result && result.data && grid.setData) {
                            grid.setData(result.data);
                        }
                    },
                    error: function() {
                    }
                });
            }, 100);
        }
        
        return grid;
    };

})(window);
