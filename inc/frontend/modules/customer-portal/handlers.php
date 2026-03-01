<?php
/**
 * Customer Portal - AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get Customer Invoices (AJAX)
 */
function wpscrm_customer_portal_get_invoices() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_email = wp_get_current_user()->user_email;
    
    // Get customer from contacts
    if ( ! function_exists( 'wpscrm_get_customer_by_email' ) ) {
        wp_send_json_error( array( 'message' => 'Kunden-System nicht verfügbar' ) );
    }
    
    $customer = wpscrm_get_customer_by_email( $user_email );
    if ( ! $customer ) {
        wp_send_json_error( array( 'message' => 'Kunden-Profil nicht gefunden' ) );
    }
    
    global $wpdb;
    
    // Get invoices from transactions/invoices table
    $invoices_table = WPsCRM_TABLE . 'invoices';
    
    // Check if table exists
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$invoices_table'" ) !== $invoices_table ) {
        wp_send_json_success( array(
            'html' => '<p style="color: #999; text-align: center; padding: 40px;">Keine Rechnungen vorhanden.</p>'
        ) );
    }
    
    $invoices = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $invoices_table 
         WHERE customer_id = %d OR contact_id = %d 
         ORDER BY created_at DESC 
         LIMIT 20",
        $customer->ID,
        $customer->ID
    ) );
    
    if ( empty( $invoices ) ) {
        wp_send_json_success( array(
            'html' => '<p style="color: #999; text-align: center; padding: 40px;">Keine Rechnungen vorhanden.</p>'
        ) );
    }
    
    // Build HTML
    $html = '<table style="width: 100%; border-collapse: collapse;">';
    $html .= '<thead style="background: #f5f5f5;">';
    $html .= '<tr>';
    $html .= '<th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Rechnung</th>';
    $html .= '<th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Datum</th>';
    $html .= '<th style="padding: 12px; text-align: right; border-bottom: 1px solid #ddd;">Betrag</th>';
    $html .= '<th style="padding: 12px; text-align: center; border-bottom: 1px solid #ddd;">Status</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ( $invoices as $invoice ) {
        $status_badge = 'pending';
        $status_label = 'Ausstehend';
        $status_color = '#ff9800';
        
        if ( isset( $invoice->status ) ) {
            if ( 'paid' === $invoice->status ) {
                $status_badge = 'paid';
                $status_label = 'Bezahlt';
                $status_color = '#4caf50';
            }
        }
        
        $amount = isset( $invoice->total ) ? $invoice->total : ( $invoice->amount ?? 0 );
        
        $html .= '<tr style="border-bottom: 1px solid #eee;">';
        $html .= '<td style="padding: 12px;">#' . esc_html( $invoice->id ?? $invoice->ID ) . '</td>';
        $html .= '<td style="padding: 12px;">' . ( isset( $invoice->created_at ) ? date_i18n( 'd.m.Y', strtotime( $invoice->created_at ) ) : '—' ) . '</td>';
        $html .= '<td style="padding: 12px; text-align: right;">€' . number_format( $amount, 2, ',', '.' ) . '</td>';
        $html .= '<td style="padding: 12px; text-align: center;">';
        $html .= '<span style="background: ' . $status_color . '; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">';
        $html .= esc_html( $status_label );
        $html .= '</span>';
        $html .= '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    wp_send_json_success( array( 'html' => $html ) );
}
