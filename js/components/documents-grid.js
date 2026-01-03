/**
 * PS Smart CRM - Documents Grid Component
 * Vanilla.js Implementation für Dokumenten-Listen (Rechnungen & Angebote)
 */

(function(window) {
    'use strict';

    if (typeof PSCRM === 'undefined') {
        console.error('PSCRM core not loaded! Load crm-core.js first.');
        return;
    }

    /**
     * Erstellt ein Dokumenten-Grid mit Tabs für Rechnungen und Angebote
     * @param {string} containerId - ID des Container-Elements
     * @param {Object} options - Konfigurationsoptionen
     */
    PSCRM.createDocumentsGrid = function(containerId, options) {
        const defaults = {
            type: 'invoice', // 'invoice' oder 'quote'
            height: 600,
            ajaxAction: 'WPsCRM_documents_datasource',
            deleteAction: 'WPsCRM_delete_document',
            security: '',
            locale: 'de-DE',
            dateFormat: 'dd-MM-yyyy',
            currencySymbol: '€',
            texts: {
                invoice: 'Rechnung',
                quote: 'Angebot',
                edit: 'Bearbeiten',
                delete: 'Löschen',
                print: 'Drucken',
                confirmDelete: 'Dokument wirklich löschen?',
                filterByDate: 'Nach Datum filtern',
                filterByAgent: 'Nach Agent filtern',
                from: 'Von',
                to: 'Bis',
                filter: 'Filter',
                resetFilters: 'Filter zurücksetzen',
                total: 'Total'
            }
        };

        const config = Object.assign({}, defaults, options);
        const container = document.getElementById(containerId);
        
        if (!container) {
            console.error('Container not found:', containerId);
            return;
        }

        // Filter-Toolbar erstellen
        const toolbar = _createFilterToolbar(containerId, config);
        container.insertBefore(toolbar, container.firstChild);

        // Grid initialisieren
        const grid = new PSCRM.Grid(containerId, {
            height: config.height,
            columns: _getColumns(config),
            dataSource: {
                url: PSCRMConfig.ajaxUrl,
                method: 'POST',
                data: function() {
                    return {
                        action: config.ajaxAction,
                        type: config.type,
                        security: config.security,
                        dateFrom: document.getElementById('dateFrom_' + containerId)?.value || '',
                        dateTo: document.getElementById('dateTo_' + containerId)?.value || '',
                        agent: document.getElementById('selectAgent_' + containerId)?.value || ''
                    };
                },
                processResults: function(response) {
                    if (response.success && response.data) {
                        return response.data;
                    }
                    return [];
                }
            },
            pageSize: 50,
            searching: true,
            ordering: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/de-DE.json'
            },
            footerCallback: function(row, data, start, end, display) {
                const api = this.api();
                
                // Berechne Total
                const total = api.column(3).data().reduce(function(a, b) {
                    const val = parseFloat(b) || 0;
                    return a + val;
                }, 0);
                
                // Footer anzeigen
                $(api.column(3).footer()).html(
                    '<strong>' + config.texts.total + ': ' + 
                    PSCRM.utils.formatCurrency(total, config.currencySymbol) + '</strong>'
                );
            }
        });

        // Event Handlers
        _initEventHandlers(containerId, grid, config);

        return grid;
    };

    /**
     * Erstellt Filter-Toolbar mit Datepickern und Agent-Dropdown
     */
    function _createFilterToolbar(gridId, config) {
        const toolbar = document.createElement('div');
        toolbar.className = 'pscrm-filter-toolbar';
        toolbar.innerHTML = `
            <div class="filter-row">
                <label>${config.texts.filterByDate}:</label>
                <label>${config.texts.from}:</label>
                <input type="text" id="dateFrom_${gridId}" class="pscrm-datepicker" style="width: 200px" />
                
                <label>${config.texts.to}:</label>
                <input type="text" id="dateTo_${gridId}" class="pscrm-datepicker" style="width: 200px" />
                
                <button type="button" id="btnFilter_${gridId}" class="button-primary _flat">
                    ${config.texts.filter}
                </button>
                
                <button type="button" id="btnReset_${gridId}" class="button-secondary _flat">
                    ${config.texts.resetFilters}
                </button>
                
                <label style="margin-left: 20px">${config.texts.filterByAgent}:</label>
                <select id="selectAgent_${gridId}" style="width: 200px"></select>
            </div>
        `;

        return toolbar;
    }

    /**
     * Grid-Spalten definieren
     */
    function _getColumns(config) {
        const baseUrl = window.location.pathname + window.location.search.split('&p=')[0];
        const docType = config.type === 'invoice' ? 'invoice' : 'quotation';
        
        return [
            {
                title: '#',
                data: 'numero',
                width: '80px',
                render: function(data, type, row) {
                    const icon = row.tipo === 'Rechnung' 
                        ? '<i class="glyphicon glyphicon-fire"></i>' 
                        : '<i class="glyphicon glyphicon-send"></i>';
                    
                    const style = row.registered === '1' ? 'text-decoration: underline;' : '';
                    
                    return `<span style="${style}">${icon} ${data}</span>`;
                }
            },
            {
                title: 'Datum',
                data: 'data',
                width: '120px',
                render: function(data) {
                    return PSCRM.utils.formatDate(data);
                }
            },
            {
                title: 'Kontakt',
                data: 'firmenname',
                render: function(data, type, row) {
                    return `<a href="${baseUrl}&p=kunde/form.php&ID=${row.fk_kunde}">${data}</a>`;
                }
            },
            {
                title: 'Summe',
                data: 'totale',
                width: '120px',
                className: 'dt-right',
                render: function(data) {
                    return PSCRM.utils.formatCurrency(data, config.currencySymbol);
                }
            },
            {
                title: 'Verfallsdatum',
                data: 'scadenza',
                width: '120px',
                render: function(data) {
                    return data ? PSCRM.utils.formatDate(data) : '';
                }
            },
            {
                title: 'Bezahlt',
                data: 'stato',
                width: '80px',
                className: 'dt-center',
                render: function(data) {
                    return data === '1' 
                        ? '<i class="glyphicon glyphicon-ok text-success"></i>'
                        : '';
                }
            },
            {
                title: 'Aktionen',
                data: null,
                orderable: false,
                width: '200px',
                className: 'dt-center',
                render: function(data, type, row) {
                    const canEdit = row.registered !== '1';
                    const editBtn = canEdit 
                        ? `<button class="btn btn-sm btn-primary btn-edit" data-id="${row.ID_dokumente}">
                            <i class="glyphicon glyphicon-pencil"></i> ${config.texts.edit}
                           </button>`
                        : '';
                    
                    const deleteBtn = canEdit
                        ? `<button class="btn btn-sm btn-danger btn-delete" data-id="${row.ID_dokumente}">
                            <i class="glyphicon glyphicon-trash"></i> ${config.texts.delete}
                           </button>`
                        : '';
                    
                    const printBtn = `<button class="btn btn-sm btn-info btn-print" data-id="${row.ID_dokumente}">
                        <i class="glyphicon glyphicon-print"></i> ${config.texts.print}
                    </button>`;
                    
                    return `${printBtn} ${editBtn} ${deleteBtn}`;
                }
            }
        ];
    }

    /**
     * Event-Handler initialisieren
     */
    function _initEventHandlers(gridId, grid, config) {
        const container = document.getElementById(gridId);
        const docType = config.type === 'invoice' ? 'invoice' : 'quotation';
        
        // Datepicker initialisieren
        new PSCRM.DatePicker('dateFrom_' + gridId, {
            dateFormat: config.dateFormat
        });
        
        new PSCRM.DatePicker('dateTo_' + gridId, {
            dateFormat: config.dateFormat
        });
        
        // Agent Dropdown initialisieren
        new PSCRM.Dropdown('selectAgent_' + gridId, {
            placeholder: 'Alle Agenten',
            allowClear: true,
            dataSource: {
                url: PSCRMConfig.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'WPsCRM_get_agents'
                },
                processResults: function(response) {
                    if (response.success && response.data) {
                        return response.data.map(function(agent) {
                            return {
                                id: agent.ID,
                                text: agent.display_name
                            };
                        });
                    }
                    return [];
                }
            }
        });
        
        // Filter Button
        const btnFilter = document.getElementById('btnFilter_' + gridId);
        if (btnFilter) {
            btnFilter.addEventListener('click', function() {
                grid.reload();
            });
        }
        
        // Reset Button
        const btnReset = document.getElementById('btnReset_' + gridId);
        if (btnReset) {
            btnReset.addEventListener('click', function() {
                document.getElementById('dateFrom_' + gridId).value = '';
                document.getElementById('dateTo_' + gridId).value = '';
                const agentSelect = document.getElementById('selectAgent_' + gridId);
                if (agentSelect && agentSelect.selectize) {
                    agentSelect.selectize.clear();
                }
                grid.reload();
            });
        }
        
        // Row Click Events
        container.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;
            
            const id = target.dataset.id;
            const baseUrl = window.location.pathname + window.location.search.split('&p=')[0];
            
            if (target.classList.contains('btn-edit')) {
                _handleEdit(id, docType, baseUrl);
            } else if (target.classList.contains('btn-delete')) {
                _handleDelete(id, grid, config);
            } else if (target.classList.contains('btn-print')) {
                _handlePrint(id, docType, baseUrl);
            }
        });
    }

    /**
     * Dokument bearbeiten
     */
    function _handleEdit(id, docType, baseUrl) {
        window.location.href = `${baseUrl}&p=dokumente/form_${docType}.php&ID=${id}`;
    }

    /**
     * Dokument löschen
     */
    function _handleDelete(id, grid, config) {
        if (!confirm(config.texts.confirmDelete)) {
            return;
        }
        
        PSCRM.utils.ajax({
            url: PSCRMConfig.ajaxUrl,
            method: 'POST',
            data: {
                action: config.deleteAction,
                ID: id,
                security: config.security
            },
            success: function(response) {
                if (response.success) {
                    PSCRM.notify('Dokument gelöscht', 'success');
                    grid.reload();
                } else {
                    PSCRM.notify('Fehler beim Löschen: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                PSCRM.notify('Fehler beim Löschen', 'error');
            }
        });
    }

    /**
     * Dokument drucken
     */
    function _handlePrint(id, docType, baseUrl) {
        const printUrl = `${baseUrl}&p=dokumente/document_print.php&ID=${id}`;
        window.open(printUrl, '_blank');
    }

})(window);
