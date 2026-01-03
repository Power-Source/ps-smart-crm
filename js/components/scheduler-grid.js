/**
 * PS Smart CRM - Scheduler Grid Component
 * 
 * Planer/Aktivitäten Grid Implementation
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    if (!window.PSCRM) {
        console.error('PSCRM core not loaded!');
        return;
    }

    /**
     * Scheduler Grid Component
     */
    PSCRM.SchedulerGrid = function(element, options) {
        this.element = element;
        this.options = options || {};
        this.grid = null;
        this.dateFilters = {};
        this.init();
    };

    PSCRM.SchedulerGrid.prototype = {
        init: function() {
            const self = this;

            // Grid Konfiguration
            const gridOptions = {
                engine: 'datatable',
                height: 500,
                pageSize: 50,
                pageSizes: [20, 50, 100],
                sortable: true,
                filterable: true,
                groupable: false,
                columns: this._getColumns(),
                dataSource: function(params, callback) {
                    self._loadData(params, callback);
                },
                onDataBound: function() {
                    self._onDataBound();
                }
            };

            // Grid erstellen
            this.grid = PSCRM.createGrid(this.element, gridOptions);

            // Filter-Controls initialisieren
            this._initFilterControls();
        },

        /**
         * Spalten-Definition
         */
        _getColumns: function() {
            return [
                {
                    field: 'id_agenda',
                    title: 'ID',
                    hidden: true
                },
                {
                    field: 'fk_utenti_ins',
                    title: 'Ins',
                    hidden: true
                },
                {
                    field: 'tipo_agenda',
                    title: 'Typ',
                    width: '150px'
                },
                {
                    field: 'cliente',
                    title: 'Kunde'
                },
                {
                    field: 'oggetto',
                    title: 'Objekt'
                },
                {
                    field: 'data_scadenza',
                    title: 'Ablauf',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            return PSCRM.utils.formatDate(new Date(data), PSCRM.config.dateTimeFormat);
                        }
                        return data;
                    }
                },
                {
                    field: 'destinatari',
                    title: 'Empfänger'
                },
                {
                    field: 'status',
                    title: 'Status',
                    width: '100px',
                    filterable: false,
                    render: function(data, type, row) {
                        if (type === 'display') {
                            let icon = '';
                            if (row.fatto == 1) {
                                icon = '<i class="glyphicon glyphicon-ok" style="color:green;font-size:1.3em" title="Erledigt"></i>';
                            } else if (row.fatto == 2) {
                                icon = '<i class="glyphicon glyphicon-remove" style="color:red;font-size:1.3em" title="Abgebrochen"></i>';
                            } else {
                                icon = '<i class="glyphicon glyphicon-bookmark" style="color:black;font-size:1.3em" title="Noch zu erledigen"></i>';
                            }
                            return icon;
                        }
                        return data;
                    }
                },
                {
                    field: null, // actions column is purely rendered
                    title: 'Aktionen',
                    width: '200px',
                    sortable: false,
                    filterable: false,
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return `
                                <button class="btn btn-inverse btn-sm _flat btn-view" data-id="${row.id_agenda}" title="Öffnen">
                                    <i class="glyphicon glyphicon-eye-open"></i> Öffnen
                                </button>
                                <button class="btn btn-danger btn-sm _flat btn-delete" data-id="${row.id_agenda}" data-user="${row.fk_utenti_ins}" title="Löschen">
                                    <i class="glyphicon glyphicon-trash"></i> Löschen
                                </button>
                            `;
                        }
                        return '';
                    }
                },
                {
                    field: 'esito',
                    hidden: true
                },
                {
                    field: 'class',
                    hidden: true
                }
            ];
        },

        /**
         * Filter Controls
         */
        _initFilterControls: function() {
            const self = this;

            // Hilfsfunktion: DatePicker sicher anlegen oder Stub zurückgeben
            const safeDatePicker = function(selector) {
                if (PSCRM.createDatePicker && typeof PSCRM.createDatePicker === 'function') {
                    try {
                        return PSCRM.createDatePicker(selector, {
                            format: (PSCRM.config && PSCRM.config.dateTimeFormat) ? PSCRM.config.dateTimeFormat : 'YYYY-MM-DD HH:mm',
                            enableTime: true
                        });
                    } catch (e) {
                        console.warn('DatePicker init failed for', selector, e);
                    }
                }
                // Fallback Stub, damit getValue/clear nicht crashen
                return {
                    getValue: function() { return null; },
                    clear: function() {},
                    destroy: function() {}
                };
            };

            // DatePicker für Von-Bis
            if (document.getElementById('dateFrom')) {
                this.dateFilters.from = safeDatePicker('#dateFrom');
            }

            if (document.getElementById('dateTo')) {
                this.dateFilters.to = safeDatePicker('#dateTo');
            }

            // Filter Button
            if (document.getElementById('dateRange')) {
                document.getElementById('dateRange').addEventListener('click', function() {
                    self._applyDateFilter();
                });
            }

            // Reset Button
            if (document.getElementById('btn_reset')) {
                document.getElementById('btn_reset').addEventListener('click', function() {
                    self._resetFilters();
                });
            }
        },

        /**
         * Daten Filter anwenden
         */
        _applyDateFilter: function() {
            // Implementierung für DataTables Filter
            // Dies würde die Grid-API verwenden um zu filtern
            if (this.grid && this.grid.instance) {
                // Custom Filter-Logik hier
                this.reload();
            }
        },

        /**
         * Filter zurücksetzen
         */
        _resetFilters: function() {
            if (this.dateFilters.from) {
                this.dateFilters.from.clear();
            }
            if (this.dateFilters.to) {
                this.dateFilters.to.clear();
            }
            this.reload();
        },

        /**
         * Daten laden via AJAX
         */
        _loadData: function(params, callback) {
            const filterParams = {
                action: 'WPsCRM_get_scheduler',
                self_client: 1
            };

            // tipo_agenda hinzufügen wenn in options vorhanden
            if (this.options.tipo_agenda) {
                filterParams.type = this.options.tipo_agenda;
            }

            // Datum-Filter hinzufügen wenn gesetzt
            if (this.dateFilters.from) {
                const fromDates = this.dateFilters.from.getValue();
                if (fromDates && fromDates[0]) {
                    filterParams.dateFrom = fromDates[0].toISOString();
                }
            }

            if (this.dateFilters.to) {
                const toDates = this.dateFilters.to.getValue();
                if (toDates && toDates[0]) {
                    filterParams.dateTo = toDates[0].toISOString();
                }
            }

            PSCRM.utils.ajax({
                data: filterParams,
                success: function(result) {
                    callback({
                        data: result.scheduler || [],
                        total: result.scheduler ? result.scheduler.length : 0
                    });
                },
                error: function(error) {
                    console.error('Error loading scheduler data:', error);
                    PSCRM.notify('Fehler beim Laden der Aktivitäten', 'error');
                    callback({ data: [], total: 0 });
                }
            });
        },

        /**
         * Nach Daten-Laden
         */
        _onDataBound: function() {
            const self = this;
            
            // Event Delegation für Buttons
            if (jQuery) {
                jQuery(this.element).off('click', '.btn-view, .btn-delete');
                
                jQuery(this.element).on('click', '.btn-view', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    self._handleView(id);
                });

                jQuery(this.element).on('click', '.btn-delete', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    const userId = jQuery(this).data('user');
                    self._handleDelete(id, userId);
                });
            }
        },

        /**
         * Action Handlers
         */
        _handleView: function(id) {
            const self = this;

            // Response ist HTML, kein JSON -> jQuery.ajax mit dataType 'html'
            if (window.jQuery && typeof window.jQuery.ajax === 'function') {
                window.jQuery.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'WPsCRM_view_activity_modal',
                        id: id
                    },
                    dataType: 'html',
                    success: function(result) {
                        if (window.jQuery('#dialog-view').length) {
                            window.jQuery('#dialog-view').show().html(result);
                        } else if (PSCRM.createModal) {
                            const modal = PSCRM.createModal({
                                title: 'Aktivität',
                                content: result,
                                width: '800px'
                            });
                            modal.open();
                        } else {
                            console.log(result);
                        }
                    },
                    error: function(xhr, status, error) {
                        PSCRM.notify('Fehler beim Laden der Aktivität', 'error');
                    }
                });
                return;
            }

            // Fallback ohne jQuery: fetch als Text
            fetch(window.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({ action: 'WPsCRM_view_activity_modal', id: id })
            })
            .then(r => {
                if (!r.ok) throw new Error('Network error');
                return r.text();
            })
            .then(result => {
                if (window.jQuery && window.jQuery('#dialog-view').length) {
                    window.jQuery('#dialog-view').show().html(result);
                } else if (PSCRM.createModal) {
                    const modal = PSCRM.createModal({
                        title: 'Aktivität',
                        content: result,
                        width: '800px'
                    });
                    modal.open();
                } else {
                    console.log(result);
                }
            })
            .catch(() => {
                PSCRM.notify('Fehler beim Laden der Aktivität', 'error');
            });
        },

        _handleDelete: function(id, userId) {
            const self = this;
            const currentUser = this.options.currentUser || 0;
            const isAdmin = this.options.isAdmin || false;
            const deletionPrivileges = this.options.deletionPrivileges || false;

            // Berechtigungsprüfung
            if (!isAdmin && deletionPrivileges && currentUser != userId) {
                PSCRM.alert('Du hast nicht die Berechtigung, dieses Ereignis zu löschen.', 'Keine Berechtigung');
                return;
            }

            PSCRM.confirm('Löschen bestätigen?', 'Aktivität löschen', function(confirmed) {
                if (confirmed) {
                    const baseUrl = self.options.baseUrl || 'admin.php?page=smart-crm';
                    const security = self.options.deleteNonce || '';
                    window.location.href = `${baseUrl}&p=scheduler/delete.php&ID=${id}&ref=scheduler&security=${security}`;
                }
            });
        },

        /**
         * Neu laden
         */
        reload: function() {
            if (this.grid) {
                this.grid.reload();
            }
        },

        /**
         * Destroy
         */
        destroy: function() {
            if (this.grid) {
                this.grid.destroy();
            }
            
            // DatePicker destroyen
            if (this.dateFilters.from) {
                this.dateFilters.from.destroy();
            }
            if (this.dateFilters.to) {
                this.dateFilters.to.destroy();
            }
        }
    };

    // Factory Funktion
    PSCRM.createSchedulerGrid = function(element, options) {
        return new PSCRM.SchedulerGrid(element, options);
    };

})(window);
