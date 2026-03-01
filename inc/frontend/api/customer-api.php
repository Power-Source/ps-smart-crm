<?php
/**
 * Customer Frontend API Handler
 * AJAX Endpoints für Customer Portal
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Get Customer Documents (Rechnungen/Angebote)
 */
add_action('wp_ajax_crm_customer_get_documents', 'wpscrm_ajax_customer_get_documents');

function wpscrm_ajax_customer_get_documents() {
    check_ajax_referer('crm_frontend_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Nicht angemeldet'));
    }
    
    $current_user = wp_get_current_user();
    global $wpdb;
    
    // Get customer ID
    $customer_id = wpscrm_get_customer_by_email($current_user->user_email);
    
    if (!$customer_id) {
        wp_send_json_error(array('message' => 'Kein Kundenkonto'));
    }
    
    $d_table = WPsCRM_TABLE . 'documenti';
    $doc_type = intval($_POST['doc_type'] ?? 2); // 2 = Rechnung (Invoice)
    
    $documents = $wpdb->get_results($wpdb->prepare(
        "SELECT id, oggetto, data, totale, pagato, registrato 
         FROM $d_table 
         WHERE cliente_id = %d AND tipo = %d AND deleted = 0
         ORDER BY data DESC LIMIT 50",
        $customer_id,
        $doc_type
    ));
    
    wp_send_json_success(array(
        'documents' => $documents,
        'count' => count($documents)
    ));
}

/**
 * AJAX: Download Document PDF
 */
add_action('wp_ajax_crm_customer_download_pdf', 'wpscrm_ajax_customer_download_pdf');

function wpscrm_ajax_customer_download_pdf() {
    check_ajax_referer('crm_frontend_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Nicht angemeldet'));
    }
    
    $current_user = wp_get_current_user();
    $document_id = intval($_POST['document_id']);
    
    global $wpdb;
    $d_table = WPsCRM_TABLE . 'documenti';
    
    // Get customer ID
    $customer_id = wpscrm_get_customer_by_email($current_user->user_email);
    
    // Verify document belongs to customer
    $document = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $d_table WHERE id = %d AND cliente_id = %d",
        $document_id,
        $customer_id
    ));
    
    if (!$document) {
        wp_send_json_error(array('message' => 'Dokument nicht gefunden'));
    }
    
    // Generate PDF (using existing PDF generation if available)
    if (function_exists('wpscrm_generate_document_pdf')) {
        $pdf_path = wpscrm_generate_document_pdf($document_id);
        
        wp_send_json_success(array(
            'download_url' => add_query_arg(array(
                'action' => 'crm_customer_pdf_download',
                'doc_id' => $document_id,
                'nonce' => wp_create_nonce('crm_customer_pdf_' . $document_id)
            ), admin_url('admin-ajax.php'))
        ));
    } else {
        wp_send_json_error(array('message' => 'PDF-Export nicht verfügbar'));
    }
}

/**
 * AJAX: Get Customer Invoices Summary
 */
add_action('wp_ajax_crm_customer_get_summary', 'wpscrm_ajax_customer_get_summary');

function wpscrm_ajax_customer_get_summary() {
    check_ajax_referer('crm_frontend_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Nicht angemeldet'));
    }
    
    $current_user = wp_get_current_user();
    global $wpdb;
    
    $customer_id = wpscrm_get_customer_by_email($current_user->user_email);
    
    if (!$customer_id) {
        wp_send_json_error(array('message' => 'Kein Kundenkonto'));
    }
    
    $d_table = WPsCRM_TABLE . 'documenti';
    
    // Get summary stats
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) as total_invoices,
            SUM(CASE WHEN pagato = 0 THEN 1 ELSE 0 END) as open_invoices,
            SUM(CASE WHEN pagato = 1 THEN 1 ELSE 0 END) as paid_invoices,
            SUM(CASE WHEN pagato = 0 THEN totale ELSE 0 END) as outstanding_amount,
            SUM(totale) as total_invoiced
         FROM $d_table 
         WHERE cliente_id = %d AND tipo = 2 AND deleted = 0",
        $customer_id
    ));
    
    wp_send_json_success(array(
        'total_invoices' => intval($stats->total_invoices ?? 0),
        'open_invoices' => intval($stats->open_invoices ?? 0),
        'paid_invoices' => intval($stats->paid_invoices ?? 0),
        'outstanding_amount' => floatval($stats->outstanding_amount ?? 0),
        'total_invoiced' => floatval($stats->total_invoiced ?? 0)
    ));
}
