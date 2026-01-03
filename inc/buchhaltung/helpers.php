<?php
/**
 * Accounting Helpers
 * Provides functions for accounting operations and integrations
 */

if (!defined('ABSPATH')) exit;

/**
 * Get configured tax rates from accounting settings
 */
function WPsCRM_get_tax_rates() {
    $acc_options = get_option('CRM_accounting_settings', array());
    $default_rates = array(
        array('label' => __('Regulär (19%)', 'cpsmartcrm'), 'percentage' => 19, 'account' => '1600'),
        array('label' => __('Ermäßigt (7%)', 'cpsmartcrm'), 'percentage' => 7, 'account' => '1601'),
        array('label' => __('Null (0%)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1602'),
    );
    return !empty($acc_options['tax_rates']) ? $acc_options['tax_rates'] : $default_rates;
}

/**
 * Get tax rate by percentage
 */
function WPsCRM_get_tax_rate_by_percentage($percentage) {
    $rates = WPsCRM_get_tax_rates();
    foreach ($rates as $rate) {
        if ((float)$rate['percentage'] === (float)$percentage) {
            return $rate;
        }
    }
    return null;
}

/**
 * Check if user is Kleinunternehmer (§19 UStG)
 */
function WPsCRM_is_kleinunternehmer() {
    $bus_options = get_option('CRM_business_settings', array());
    return isset($bus_options['crm_kleinunternehmer']) && $bus_options['crm_kleinunternehmer'] == 1;
}

/**
 * Get business VAT ID
 */
function WPsCRM_get_business_vat_id() {
    $bus_options = get_option('CRM_business_settings', array());
    return $bus_options['business_ustid'] ?? '';
}

/**
 * Format currency value
 */
function WPsCRM_format_currency($value, $decimals = 2) {
    $acc_options = get_option('CRM_accounting_settings', array());
    $currency = $acc_options['currency'] ?? 'EUR';
    
    $value = (float)$value;
    $formatted = number_format($value, $decimals, ',', '.');
    
    $symbol = '';
    switch($currency) {
        case 'EUR':
            $symbol = '€';
            break;
        case 'USD':
            $symbol = '$';
            break;
        case 'GBP':
            $symbol = '£';
            break;
    }
    
    return $formatted . ' ' . $symbol;
}

/**
 * Get booking status label
 */
function WPsCRM_get_booking_status_label($pagato, $registrato) {
    if ((int)$registrato === 1) {
        return '<span style="background:#46b450;color:#fff;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Gebucht', 'cpsmartcrm') . '</span>';
    } elseif ((int)$pagato === 1) {
        return '<span style="background:#ffb900;color:#1d2327;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Zu buchen', 'cpsmartcrm') . '</span>';
    } else {
        return '<span style="background:#f0f0f1;color:#1d2327;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Nicht fällig', 'cpsmartcrm') . '</span>';
    }
}

/**
 * Get payment status label
 */
function WPsCRM_get_payment_status_label($pagato) {
    if ((int)$pagato === 1) {
        return '<span style="background:#46b450;color:#fff;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Bezahlt', 'cpsmartcrm') . '</span>';
    } else {
        return '<span style="background:#d63638;color:#fff;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Offen', 'cpsmartcrm') . '</span>';
    }
}

/**
 * Generate accounting summary for dashboard
 * Returns array: total, total_net, total_tax, by_tax_rate[]
 */
function WPsCRM_get_accounting_summary($filter = array()) {
    global $wpdb;
    
    $d_table = WPsCRM_TABLE . 'documenti';
    $invoice_type = 2;
    
    // Build WHERE clause from filter
    $where_parts = array("tipo = %d");
    $where_values = array($invoice_type);
    
    // Default: only paid invoices
    if (!isset($filter['all'])) {
        $where_parts[] = 'pagato = 1';
    }
    
    if (isset($filter['date_from'])) {
        $where_parts[] = "data >= %s";
        $where_values[] = $filter['date_from'];
    }
    
    if (isset($filter['date_to'])) {
        $where_parts[] = "data <= %s";
        $where_values[] = $filter['date_to'];
    }
    
    $where_clause = implode(' AND ', $where_parts);
    
    // Get summary
    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT 
                COUNT(*) as invoice_count,
                COALESCE(SUM(totale_imponibile), 0) as total_net,
                COALESCE(SUM(totale_imposta), 0) as total_tax,
                COALESCE(SUM(totale), 0) as total
             FROM {$d_table} 
             WHERE {$where_clause}",
            ...$where_values
        )
    );
    
    return array(
        'invoice_count' => (int)$result->invoice_count,
        'total_net' => (float)$result->total_net,
        'total_tax' => (float)$result->total_tax,
        'total' => (float)$result->total,
    );
}
