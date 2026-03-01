<?php
/**
 * Agent Dashboard - Kunden-Management Card
 * 
 * Features:
 * - Kundensuche
 * - Liste der eigenen Kunden
 * - Dokumente (Angebote, Rechnungen) je Kunde
 * - Neuen Kunden anlegen
 * - Kontakte verwalten
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();
?>

<!-- Customers Card -->
<div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #4caf50; grid-column: span 2;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #4caf50; display: flex; align-items: center;">
            <span style="font-size: 24px; margin-right: 10px;">👥</span>
            <span>Meine Kunden</span>
        </h3>
        <button id="crm-add-customer-btn" class="button button-primary" style="background: #4caf50; border-color: #4caf50;">
            ➕ Neuer Kunde
        </button>
    </div>

    <!-- Search Bar -->
    <div style="margin-bottom: 20px;">
        <input type="text" id="crm-customer-search" placeholder="🔍 Kunde suchen (Name, Firma, Email)..." style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>

    <!-- Tabs -->
    <div style="display: flex; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #eee;">
        <button class="crm-customer-tab active" data-tab="list" style="padding: 8px 16px; border: none; background: none; cursor: pointer; border-bottom: 3px solid #4caf50; color: #4caf50; font-weight: 600;">
            📋 Kundenliste
        </button>
        <button class="crm-customer-tab" data-tab="add" style="padding: 8px 16px; border: none; background: none; cursor: pointer; color: #666; font-weight: 600; display: none;">
            ➕ Neu anlegen
        </button>
        <button class="crm-customer-tab" data-tab="details" style="padding: 8px 16px; border: none; background: none; cursor: pointer; color: #666; font-weight: 600; display: none;">
            👤 Kundendetails
        </button>
    </div>

    <!-- Tab Content: Liste -->
    <div class="crm-customer-content active" data-content="list">
        <div id="crm-customers-list" style="max-height: 400px; overflow-y: auto;">
            <p style="text-align: center; color: #999; padding: 20px;">Laden...</p>
        </div>
    </div>

    <!-- Tab Content: Neu anlegen -->
    <div class="crm-customer-content" data-content="add" style="display: none;">
        <form id="crm-add-customer-form" style="display: grid; gap: 15px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Firmenname</label>
                    <input type="text" name="firmenname" placeholder="z.B. Müller GmbH" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Email *</label>
                    <input type="email" name="email" required placeholder="kontakt@firma.de" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Vorname</label>
                    <input type="text" name="name" placeholder="Max" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Nachname</label>
                    <input type="text" name="nachname" placeholder="Mustermann" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Adresse</label>
                    <input type="text" name="adresse" placeholder="Musterstraße 123" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">PLZ</label>
                    <input type="text" name="cap" placeholder="12345" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Stadt</label>
                    <input type="text" name="standort" placeholder="Berlin" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Telefon</label>
                    <input type="text" name="telefono1" placeholder="+49 123 456789" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div>
                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Notizen</label>
                <textarea name="annotazioni" rows="3" placeholder="Zusätzliche Informationen..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="button crm-cancel-add" style="background: #e0e0e0; border: none;">Abbrechen</button>
                <button type="submit" class="button button-primary" style="background: #4caf50; border-color: #4caf50;">✅ Kunde anlegen</button>
            </div>
        </form>
        
        <div id="crm-add-customer-message" style="margin-top: 12px; padding: 10px; border-radius: 4px; display: none;"></div>
    </div>

    <!-- Tab Content: Details -->
    <div class="crm-customer-content" data-content="details" style="display: none;">
        <div id="crm-customer-details">
            <!-- Wird per AJAX geladen -->
        </div>
    </div>

</div>

<style>
.crm-customer-tab.active {
    border-bottom-color: #4caf50 !important;
    color: #4caf50 !important;
}

.crm-customer-item {
    padding: 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
}

.crm-customer-item:hover {
    background: #f5f5f5;
}

.crm-doc-item {
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
}

.crm-doc-item:hover {
    border-color: #4caf50;
    background: #f1f8e9;
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentCustomerId = null;
    
    // Tab Switching
    $('.crm-customer-tab').on('click', function() {
        const tab = $(this).data('tab');
        $('.crm-customer-tab').removeClass('active').css({'border-bottom-color': 'transparent', 'color': '#666'});
        $(this).addClass('active');
        $('.crm-customer-content').hide();
        $('.crm-customer-content[data-content="' + tab + '"]').show();
    });
    
    // Load Customers
    function loadCustomers(search = '') {
        $.post(ajaxurl, {
            action: 'crm_get_customers',
            search: search,
            nonce: '<?php echo wp_create_nonce( 'crm_customers' ); ?>'
        }, function(response) {
            if (response.success) {
                renderCustomerList(response.data.customers);
            }
        });
    }
    
    // Render Customer List
    function renderCustomerList(customers) {
        const $list = $('#crm-customers-list');
        
        if (!customers || customers.length === 0) {
            $list.html('<div style="padding: 40px; text-align: center; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">📭</div><p>Keine Kunden gefunden</p></div>');
            return;
        }
        
        let html = '';
        customers.forEach(function(customer) {
            const displayName = customer.firmenname || (customer.name + ' ' + customer.nachname);
            const docCount = customer.doc_count || 0;
            
            html += '<div class="crm-customer-item" data-customer-id="' + customer.ID_kunde + '">';
            html += '  <div style="display: flex; justify-content: space-between; align-items: center;">';
            html += '    <div style="flex: 1;">';
            html += '      <div style="font-weight: 600; color: #333; margin-bottom: 4px;">' + displayName + '</div>';
            html += '      <div style="font-size: 12px; color: #666;">';
            if (customer.email) html += '📧 ' + customer.email + ' ';
            if (customer.telefono1) html += '📞 ' + customer.telefono1;
            html += '      </div>';
            html += '    </div>';
            html += '    <div style="display: flex; align-items: center; gap: 10px;">';
            html += '      <span style="background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">' + docCount + ' 📄</span>';
            html += '      <span style="color: #4caf50; font-size: 20px;">→</span>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
        });
        
        $list.html(html);
    }
    
    // Customer Search
    let searchTimeout;
    $('#crm-customer-search').on('input', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        searchTimeout = setTimeout(() => loadCustomers(search), 300);
    });
    
    // Click auf Kunde
    $(document).on('click', '.crm-customer-item', function() {
        currentCustomerId = $(this).data('customer-id');
        loadCustomerDetails(currentCustomerId);
    });
    
    // Load Customer Details mit Dokumenten
    function loadCustomerDetails(customerId) {
        $('.crm-customer-tab[data-tab="details"]').show().click();
        $('#crm-customer-details').html('<p style="text-align: center; padding: 20px;">Laden...</p>');
        
        $.post(ajaxurl, {
            action: 'crm_get_customer_details',
            customer_id: customerId,
            nonce: '<?php echo wp_create_nonce( 'crm_customers' ); ?>'
        }, function(response) {
            if (response.success) {
                renderCustomerDetails(response.data);
            }
        });
    }
    
    // Render Customer Details
    function renderCustomerDetails(data) {
        const customer = data.customer;
        const documents = data.documents || [];
        const contacts = data.contacts || [];
        
        const displayName = customer.firmenname || (customer.name + ' ' + customer.nachname);
        
        let html = '<div style="padding-bottom: 20px; border-bottom: 2px solid #eee; margin-bottom: 20px;">';
        html += '  <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">';
        html += '    <div>';
        html += '      <h4 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">' + displayName + '</h4>';
        if (customer.email) html += '<div style="color: #666; font-size: 13px; margin-bottom: 4px;">📧 ' + customer.email + '</div>';
        if (customer.telefono1) html += '<div style="color: #666; font-size: 13px; margin-bottom: 4px;">📞 ' + customer.telefono1 + '</div>';
        if (customer.adresse) html += '<div style="color: #666; font-size: 13px;">📍 ' + customer.adresse + ', ' + (customer.cap || '') + ' ' + (customer.standort || '') + '</div>';
        html += '    </div>';
        html += '    <button class="crm-back-to-list button" style="background: #e0e0e0; border: none;">← Zurück</button>';
        html += '  </div>';
        html += '</div>';
        
        // Dokumente
        html += '<div style="margin-bottom: 25px;">';
        html += '  <h5 style="margin: 0 0 12px 0; color: #666; font-size: 14px; font-weight: 600;">📄 Dokumente (' + documents.length + ')</h5>';
        
        if (documents.length > 0) {
            html += '<div style="display: grid; gap: 10px;">';
            documents.forEach(function(doc) {
                const typeLabel = doc.tipo == 1 ? 'Angebot' : (doc.tipo == 2 ? 'Rechnung' : 'Proforma');
                const typeColor = doc.tipo == 1 ? '#ff9800' : (doc.tipo == 2 ? '#4caf50' : '#2196F3');
                const date = doc.datum || '—';
                const amount = doc.totale ? '€' + parseFloat(doc.totale).toFixed(2) : '—';
                
                html += '<div class="crm-doc-item">';
                html += '  <div style="flex: 1;">';
                html += '    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">';
                html += '      <span style="background: ' + typeColor + '; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">' + typeLabel + '</span>';
                html += '      <span style="font-weight: 600; color: #333;">' + (doc.numero || 'N/A') + '</span>';
                html += '    </div>';
                html += '    <div style="font-size: 12px; color: #666;">📅 ' + date + '</div>';
                html += '  </div>';
                html += '  <div style="text-align: right;">';
                html += '    <div style="font-weight: 700; color: #333; font-size: 16px;">' + amount + '</div>';
                html += '    <a href="' + (doc.pdf_url || '#') + '" target="_blank" style="font-size: 11px; color: #2196F3; text-decoration: none;">PDF öffnen →</a>';
                html += '  </div>';
                html += '</div>';
            });
            html += '</div>';
        } else {
            html += '<p style="color: #999; font-size: 13px; padding: 15px; background: #f5f5f5; border-radius: 4px; text-align: center;">Keine Dokumente vorhanden</p>';
        }
        html += '</div>';
        
        // Kontakte
        if (contacts.length > 0) {
            html += '<div>';
            html += '  <h5 style="margin: 0 0 12px 0; color: #666; font-size: 14px; font-weight: 600;">📞 Kontakte (' + contacts.length + ')</h5>';
            html += '<div style="display: grid; gap: 8px;">';
            contacts.forEach(function(contact) {
                html += '<div style="padding: 10px; background: #f9f9f9; border-radius: 4px; border-left: 3px solid #4caf50;">';
                html += '  <div style="font-weight: 600; color: #333; margin-bottom: 4px;">' + contact.name + ' ' + contact.nachname + '</div>';
                html += '  <div style="font-size: 12px; color: #666;">';
                if (contact.email) html += '📧 ' + contact.email + ' ';
                if (contact.telefono) html += '📞 ' + contact.telefono + ' ';
                if (contact.qualifica) html += '<span style="background: #e0e0e0; padding: 2px 6px; border-radius: 3px; margin-left: 6px;">' + contact.qualifica + '</span>';
                html += '  </div>';
                html += '</div>';
            });
            html += '</div>';
            html += '</div>';
        }
        
        $('#crm-customer-details').html(html);
    }
    
    // Back to List
    $(document).on('click', '.crm-back-to-list', function() {
        $('.crm-customer-tab[data-tab="list"]').click();
        $('.crm-customer-tab[data-tab="details"]').hide();
    });
    
    // Add Customer Button
    $('#crm-add-customer-btn').on('click', function() {
        $('.crm-customer-tab[data-tab="add"]').show().click();
    });
    
    // Cancel Add
    $('.crm-cancel-add').on('click', function() {
        $('#crm-add-customer-form')[0].reset();
        $('.crm-customer-tab[data-tab="list"]').click();
        $('.crm-customer-tab[data-tab="add"]').hide();
    });
    
    // Submit Add Customer Form
    $('#crm-add-customer-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize() + '&action=crm_add_customer&nonce=<?php echo wp_create_nonce( 'crm_add_customer' ); ?>';
        
        $.post(ajaxurl, formData, function(response) {
            const $msg = $('#crm-add-customer-message');
            if (response.success) {
                $msg.css({'background': '#d4edda', 'color': '#155724', 'border': '1px solid #c3e6cb'})
                    .text('✅ ' + response.data.message)
                    .show();
                
                $('#crm-add-customer-form')[0].reset();
                
                setTimeout(() => {
                    $('.crm-customer-tab[data-tab="list"]').click();
                    $('.crm-customer-tab[data-tab="add"]').hide();
                    loadCustomers();
                    $msg.hide();
                }, 2000);
            } else {
                $msg.css({'background': '#f8d7da', 'color': '#721c24', 'border': '1px solid #f5c6cb'})
                    .text('❌ ' + response.data.message)
                    .show();
            }
        });
    });
    
    // Initial Load
    loadCustomers();
});
</script>
