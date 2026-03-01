<?php
/**
 * Customer Portal Frontend Module
 * Rechnungen, Angebote, Postfach
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
global $wpdb;

// Get customer data
$customer_id = wpscrm_get_customer_by_email($current_user->user_email);
$contacts_table = WPsCRM_TABLE . 'contacts';

$customer = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $contacts_table WHERE id = %d AND deleted = 0",
    $customer_id
));
?>

<div class="crm-customer-portal" style="padding: 20px; background: #f5f5f5; min-height: 100vh;">
    
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 style="margin: 0; color: #333;">🏢 Kundenzone</h1>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                <?php _e('Rechnungen, Angebote und Dokumente', 'cpsmartcrm'); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo wp_logout_url(); ?>" class="button button-secondary">🚪 Logout</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #2196F3; text-align: center;">
            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">RECHNUNGEN</div>
            <div id="crm-total-invoices" style="font-size: 32px; font-weight: bold; color: #2196F3;">0</div>
            <div style="font-size: 12px; color: #999; margin-top: 8px;">insgesamt</div>
        </div>
        
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #ff9800; text-align: center;">
            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">OFFENE RECHNUNGEN</div>
            <div id="crm-open-invoices" style="font-size: 32px; font-weight: bold; color: #ff9800;">0</div>
            <div style="font-size: 12px; color: #999; margin-top: 8px;">ausstehend</div>
        </div>
        
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #4caf50; text-align: center;">
            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">FÄLLIG</div>
            <div id="crm-outstanding-amount" style="font-size: 32px; font-weight: bold; color: #4caf50;">€0,00</div>
            <div style="font-size: 12px; color: #999; margin-top: 8px;">Gesamtsumme</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="crm-tabs-container" style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
        
        <!-- Tab Navigation -->
        <div style="display: flex; border-bottom: 2px solid #eee;">
            <button class="crm-tab-button active" data-tab="invoices" style="flex: 1; padding: 15px; background: none; border: none; cursor: pointer; font-weight: 500; color: #333; border-bottom: 3px solid #2196F3; margin-bottom: -2px;">
                📄 Rechnungen
            </button>
            <button class="crm-tab-button" data-tab="quotations" style="flex: 1; padding: 15px; background: none; border: none; cursor: pointer; font-weight: 500; color: #999; border-bottom: 3px solid transparent;">
                📋 Angebote
            </button>
            <button class="crm-tab-button" data-tab="inbox" style="flex: 1; padding: 15px; background: none; border: none; cursor: pointer; font-weight: 500; color: #999; border-bottom: 3px solid transparent;">
                📬 Postfach
            </button>
        </div>

        <!-- Tab Content -->
        <div style="padding: 20px;">
            
            <!-- Invoices Tab -->
            <div id="crm-tab-invoices" class="crm-tab-content">
                <div id="crm-invoices-list" style="min-height: 300px;">
                    <p style="text-align: center; color: #999; padding: 40px;">Laden...</p>
                </div>
            </div>

            <!-- Quotations Tab -->
            <div id="crm-tab-quotations" class="crm-tab-content" style="display: none;">
                <div id="crm-quotations-list" style="min-height: 300px;">
                    <p style="text-align: center; color: #999; padding: 40px;">Laden...</p>
                </div>
            </div>

            <!-- Inbox Tab -->
            <div id="crm-tab-inbox" class="crm-tab-content" style="display: none;">
                <div id="crm-customer-inbox" style="min-height: 300px;">
                    <p style="text-align: center; color: #999; padding: 40px;">Laden...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Section -->
    <div style="margin-top: 30px; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h3 style="margin: 0 0 20px 0; color: #333;">👤 Mein Konto</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Name</label>
                <p style="margin: 0; color: #333; font-weight: 500;">
                    <?php echo esc_html($customer->nome ?? '—'); ?>
                </p>
            </div>
            
            <div>
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">E-Mail</label>
                <p style="margin: 0; color: #333;">
                    <?php echo esc_html($customer->email ?? '—'); ?>
                </p>
            </div>
            
            <div>
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Telefon</label>
                <p style="margin: 0; color: #333;">
                    <?php echo esc_html($customer->telefono ?? '—'); ?>
                </p>
            </div>
            
            <div>
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Stadt</label>
                <p style="margin: 0; color: #333;">
                    <?php echo esc_html($customer->citta ?? '—'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.crm-customer-portal {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.crm-card {
    transition: box-shadow 0.3s ease;
}

.crm-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.crm-tab-button.active {
    color: #2196F3;
}

.crm-tab-button:hover {
    color: #2196F3;
}

.crm-document-item {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 4px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
}

.crm-document-item:hover {
    background: #f9f9f9;
    border-color: #ddd;
}

.crm-document-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.crm-status-open {
    background: #fff3cd;
    color: #ff9800;
}

.crm-status-paid {
    background: #d4edda;
    color: #4caf50;
}
</style>

<script>
jQuery(document).ready(function($) {
    const crmFrontend = window.crmFrontend || {};
    
    // Load customer summary
    loadCustomerSummary();
    
    // Load invoices
    loadCustomerDocuments(2); // 2 = Invoices
    
    // Tab switching
    $('.crm-tab-button').on('click', function(e) {
        e.preventDefault();
        const tabName = $(this).data('tab');
        
        // Update active tab button
        $('.crm-tab-button').removeClass('active').removeAttr('style');
        $(this).addClass('active').css({
            'color': '#2196F3',
            'border-bottom': '3px solid #2196F3',
            'margin-bottom': '-2px'
        });
        $('.crm-tab-button:not(.active)').css({
            'color': '#999',
            'border-bottom': '3px solid transparent'
        });
        
        // Hide all tabs
        $('.crm-tab-content').hide();
        $('#crm-tab-' + tabName).show();
        
        // Load content based on tab
        if (tabName === 'invoices') {
            loadCustomerDocuments(2);
        } else if (tabName === 'quotations') {
            loadCustomerDocuments(1);
        }
    });
    
    /**
     * Load Customer Summary Stats
     */
    function loadCustomerSummary() {
        $.ajax({
            type: 'POST',
            url: crmFrontend.ajaxurl,
            data: {
                action: 'crm_customer_get_summary',
                nonce: crmFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#crm-total-invoices').text(data.total_invoices);
                    $('#crm-open-invoices').text(data.open_invoices);
                    $('#crm-outstanding-amount').text('€' + parseFloat(data.outstanding_amount).toLocaleString('de-DE', {minimumFractionDigits: 2}));
                }
            }
        });
    }
    
    /**
     * Load Customer Documents
     */
    function loadCustomerDocuments(docType) {
        const listElement = docType === 2 ? '#crm-invoices-list' : '#crm-quotations-list';
        
        $.ajax({
            type: 'POST',
            url: crmFrontend.ajaxurl,
            data: {
                action: 'crm_customer_get_documents',
                doc_type: docType,
                nonce: crmFrontend.nonce
            },
            success: function(response) {
                if (response.success && response.data.documents.length > 0) {
                    let html = '';
                    
                    $.each(response.data.documents, function(idx, doc) {
                        const date = new Date(doc.data);
                        const statusClass = doc.pagato ? 'crm-status-paid' : 'crm-status-open';
                        const statusText = doc.pagato ? '✓ Bezahlt' : '⏳ Offen';
                        
                        html += `
                            <div class="crm-document-item">
                                <div>
                                    <div style="font-weight: 500; color: #333;">
                                        ${escapeHtml(doc.oggetto)}
                                    </div>
                                    <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                        ${date.toLocaleDateString('de-DE')} • €${parseFloat(doc.totale).toLocaleString('de-DE', {minimumFractionDigits: 2})}
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span class="crm-document-status ${statusClass}">
                                        ${statusText}
                                    </span>
                                    <button class="button button-small crm-btn-download-pdf" data-doc-id="${doc.id}">
                                        📥 PDF
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    $(listElement).html(html);
                    
                    // Download button handler
                    $('.crm-btn-download-pdf').on('click', function() {
                        const docId = $(this).data('doc-id');
                        downloadDocument(docId);
                    });
                } else {
                    $(listElement).html('<p style="text-align: center; color: #999; padding: 40px;">Keine Dokumente vorhanden</p>');
                }
            }
        });
    }
    
    /**
     * Download Document
     */
    function downloadDocument(docId) {
        $.ajax({
            type: 'POST',
            url: crmFrontend.ajaxurl,
            data: {
                action: 'crm_customer_download_pdf',
                document_id: docId,
                nonce: crmFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.download_url;
                } else {
                    alert('Fehler beim Download: ' + response.data.message);
                }
            }
        });
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
</script>
