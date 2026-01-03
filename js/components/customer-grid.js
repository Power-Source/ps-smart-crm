/**
 * PS Smart CRM - Customer Grid Component
 * 
 * Kundenliste Grid Implementation
 * Version: 1.0.0
 */

(function(window) {
    'use strict';

    if (!window.PSCRM) {
        console.error('PSCRM core not loaded!');
        return;
    }

    /**
     * Customer Grid Component
     */
    PSCRM.CustomerGrid = function(element, options) {
        this.element = element;
        this.options = options || {};
        this.grid = null;
        this.init();
    };

    PSCRM.CustomerGrid.prototype = {
        init: function() {
            const self = this;

            // Grid Konfiguration
            const gridOptions = {
                engine: 'datatable',
                height: this.options.height || 600,
                pageSize: 50,
                pageSizes: [20, 50, 100],
                sortable: true,
                filterable: true,
                columns: this._getColumns(),
                dataSource: function(params, callback) {
                    self._loadData(params, callback);
                },
                onDataBound: function() {
                    self._onDataBound();
                },
                onRowClick: function(data, row) {
                    if (self.options.onRowClick) {
                        self.options.onRowClick(data, row);
                    }
                }
            };

            // Grid erstellen
            this.grid = PSCRM.createGrid(this.element, gridOptions);
        },

        /**
         * Spalten-Definition
         */
        _getColumns: function() {
            const columns = [
                {
                    field: 'ID_kunde',
                    title: 'ID',
                    width: '50px',
                    hidden: true
                },
                {
                    field: 'firmenname',
                    title: 'Firmenname',
                    width: '150px'
                },
                {
                    field: 'adresse',
                    title: 'Adresse',
                    width: '150px'
                },
                {
                    field: 'cod_fis',
                    title: 'Steuernummer',
                    width: '100px'
                },
                {
                    field: 'p_iva',
                    title: 'Ust.-ID',
                    width: '100px'
                },
                {
                    field: 'telefono1',
                    title: 'Telefon',
                    width: '80px'
                },
                {
                    field: 'email',
                    title: 'Email',
                    width: '100px'
                }
            ];

            // Optionale Spalten basierend auf Einstellungen
            if (this.options.showCategories) {
                columns.push({
                    field: 'categoria',
                    title: 'Kategorien',
                    width: '100px'
                });
            }

            if (this.options.showInterests) {
                columns.push({
                    field: 'interessi',
                    title: 'Interessen',
                    width: '100px'
                });
            }

            if (this.options.showOrigin) {
                columns.push({
                    field: 'provenienza',
                    title: 'Quelle',
                    width: '100px'
                });
            }

            // Command Spalte
            columns.push({
                field: null, // dataTables uses full row; avoids missing 'actions' property warning
                title: 'Aktionen',
                width: '300px',
                sortable: false,
                filterable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `
                            <button class="btn btn-sm _flat btn-edit" data-id="${row.ID_kunde}" title="Bearbeiten">
                                <i class="glyphicon glyphicon-pencil"></i> Bearbeiten
                            </button>
                            <button class="btn btn-sm btn-danger _flat btn-delete" data-id="${row.ID_kunde}" title="Löschen">
                                <i class="glyphicon glyphicon-trash"></i> Löschen
                            </button>
                            <button class="btn btn-sm btn-info _flat btn-todo" data-id="${row.ID_kunde}" data-name="${PSCRM.utils.escapeHtml(row.firmenname)}" title="TODO">
                                <i class="glyphicon glyphicon-tag"></i>
                            </button>
                            <button class="btn btn-sm btn_appuntamento_1 _flat btn-appointment" data-id="${row.ID_kunde}" data-name="${PSCRM.utils.escapeHtml(row.firmenname)}" title="Termin">
                                <i class="glyphicon glyphicon-pushpin"></i>
                            </button>
                            <button class="btn btn-sm btn-primary _flat btn-activity" data-id="${row.ID_kunde}" data-name="${PSCRM.utils.escapeHtml(row.firmenname)}" title="Aktivität">
                                <i class="glyphicon glyphicon-option-horizontal"></i>
                            </button>
                        `;
                    }
                    return data;
                }
            });

            return columns;
        },

        /**
         * Daten laden via AJAX
         */
        _loadData: function(params, callback) {
            PSCRM.utils.ajax({
                data: {
                    action: 'WPsCRM_get_clients2'
                },
                success: function(result) {
                    callback({
                        data: result.clients || [],
                        total: result.clients ? result.clients.length : 0
                    });
                },
                error: function(error) {
                    console.error('Error loading customers:', error);
                    PSCRM.notify('Fehler beim Laden der Kundendaten', 'error');
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
                jQuery(this.element).off('click', '.btn-edit, .btn-delete, .btn-todo, .btn-appointment, .btn-activity');
                
                jQuery(this.element).on('click', '.btn-edit', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    self._handleEdit(id);
                });

                jQuery(this.element).on('click', '.btn-delete', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    self._handleDelete(id);
                });

                jQuery(this.element).on('click', '.btn-todo', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    const name = jQuery(this).data('name');
                    self._handleTodo(id, name);
                });

                jQuery(this).on('click', '.btn-appointment', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    const name = jQuery(this).data('name');
                    self._handleAppointment(id, name);
                });

                jQuery(this.element).on('click', '.btn-activity', function(e) {
                    e.preventDefault();
                    const id = jQuery(this).data('id');
                    const name = jQuery(this).data('name');
                    self._handleActivity(id, name);
                });
            }
        },

        /**
         * Action Handlers
         */
        _handleEdit: function(id) {
            const baseUrl = this.options.baseUrl || 'admin.php?page=smart-crm';
            window.location.href = `${baseUrl}&p=kunde/form.php&ID=${id}`;
        },

        _handleDelete: function(id) {
            const self = this;
            
            PSCRM.confirm('Löschen bestätigen?', 'Kunde löschen', function(confirmed) {
                if (confirmed) {
                    const baseUrl = self.options.baseUrl || 'admin.php?page=smart-crm';
                    const security = self.options.deleteNonce || '';
                    window.location.href = `${baseUrl}&p=kunde/delete.php&ID=${id}&security=${security}`;
                }
            });
        },

        _handleTodo: function(id, name) {
            // Modal für TODO öffnen
            if (window.jQuery && jQuery('#dialog_todo').length) {
                jQuery('#dialog_todo').attr('data-fkcliente', id);
                if (jQuery('.name_cliente').length) {
                    jQuery('.name_cliente').html(name);
                }
                
                // Öffne Modal
                if (jQuery('#dialog_todo').length) {
                    jQuery('#dialog_todo').show();
                }
            }
        },

        _handleAppointment: function(id, name) {
            // Modal für Termin öffnen
            if (window.jQuery && jQuery('#dialog_appuntamento').length) {
                jQuery('#dialog_appuntamento').attr('data-fkcliente', id);
                if (jQuery('.name_cliente').length) {
                    jQuery('.name_cliente').html(name);
                }
                jQuery('#dialog_appuntamento').show();
            }
        },

        _handleActivity: function(id, name) {
            // Modal für Aktivität öffnen
            if (window.jQuery && jQuery('#dialog_attivita').length) {
                jQuery('#dialog_attivita').attr('data-fkcliente', id);
                if (jQuery('.name_cliente').length) {
                    jQuery('.name_cliente').html(name);
                }
                jQuery('#dialog_attivita').show();
            }
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
        }
    };

    // Factory Funktion
    PSCRM.createCustomerGrid = function(element, options) {
        return new PSCRM.CustomerGrid(element, options);
    };

})(window);
