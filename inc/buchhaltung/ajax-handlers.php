<?php
/**
 * AJAX Handler für Buchhaltungs-Updates
 * 
 * Verarbeitet Hinzufügen/Löschen von Einnahmen und Ausgaben
 * über AJAX und gibt aktualisierte Daten zurück
 */

if (!defined('ABSPATH')) exit;

error_log('🟢 ajax-handlers.php wurde geladen');
error_log('🔧 Registriere AJAX-Hooks: wpscrm_add_income und wpscrm_add_expense');

/**
 * Konvertiert Dezimaltrennerin (Komma) zu Punkt
 * Funktioniert mit: 90,45 (DE) → 90.45 und 90.45 (EN) → 90.45
 */
function wpscrm_normalize_amount($amount) {
    $amount = trim($amount);
    $amount = str_replace(' ', '', $amount);  // Spaces entfernen
    $amount = str_replace('.', '', $amount);   // Tausender-Punkte entfernen (1.000,45 → 100045)
    $amount = str_replace(',', '.', $amount);  // Komma zu Punkt (100045 → 1000.45 oder 90,45 → 90.45)
    return (float) $amount;
}

/**
 * AJAX: Neue Ausgabe hinzufügen und aktualisierte Tabelle zurückgeben
 */
add_action('wp_ajax_wpscrm_add_expense', function() {
    error_log('🔵 AJAX Handler wpscrm_add_expense wurde aufgerufen');
    error_log('🔍 POST Data: ' . print_r($_POST, true));
    
    // Permissions prüfen
    if (!current_user_can('manage_crm')) {
        error_log('❌ Permission Check failed');
        wp_send_json_error(array('message' => __('Keine Berechtigung', 'cpsmartcrm')));
    }
    
    // Nonce prüfen
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_expense_action')) {
        error_log('❌ Nonce Check failed - nonce: ' . ($_POST['nonce'] ?? 'NOT SET'));
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen', 'cpsmartcrm')));
    }
    
    error_log('✅ Permissions und Nonce OK');
    
    global $wpdb;
    
    // Daten validieren
    $expense_date = sanitize_text_field($_POST['expense_date'] ?? date('Y-m-d'));
    $expense_category = sanitize_text_field($_POST['expense_category'] ?? '');
    $expense_description = sanitize_text_field($_POST['expense_description'] ?? '');
    $expense_amount = wpscrm_normalize_amount($_POST['expense_amount'] ?? 0);
    $expense_tax_rate = wpscrm_normalize_amount($_POST['expense_tax_rate'] ?? 0);
    $period_from = sanitize_text_field($_POST['period_from'] ?? date('Y-m-01'));
    $period_to = sanitize_text_field($_POST['period_to'] ?? date('Y-m-d'));
    
    if (empty($expense_category) || $expense_amount <= 0) {
        wp_send_json_error(array('message' => __('Bitte füllen Sie alle erforderlichen Felder aus', 'cpsmartcrm')));
    }
    
    // Speichern
    $tax = $expense_amount * ($expense_tax_rate / 100);
    $gross = $expense_amount + $tax;
    
    $result = $wpdb->insert(
        WPsCRM_TABLE . 'expenses',
        array(
            'data' => $expense_date,
            'kategoria' => $expense_category,
            'descrizione' => $expense_description,
            'imponibile' => $expense_amount,
            'aliquota' => $expense_tax_rate,
            'imposta' => $tax,
            'totale' => $gross,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        ),
        array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d')
    );
    
    if (!$result) {
        error_log('❌ Expense DB Insert failed: ' . $wpdb->last_error);
        wp_send_json_error(array('message' => __('Fehler beim Speichern', 'cpsmartcrm')));
    }
    
    error_log('✅ Expense gespeichert, ID: ' . $wpdb->insert_id);
    
    // Aktualisierte Daten laden
    error_log('📊 Lade aktualisierte Daten...');
    $data = wpscrm_get_accounting_period_data($period_from, $period_to, get_current_user_id());
    error_log('📊 Expense-Daten geladen');
    
    // Return erfolgreiche Response mit aktualisierten Metrics
    error_log('✅ Sende Expense Success Response');
    wp_send_json_success(array(
        'message' => __('Ausgabe erfolgreich hinzugefügt', 'cpsmartcrm'),
        'data' => array(
            'income_net' => WPsCRM_format_currency($data['income']['total_net']),
            'income_tax' => WPsCRM_format_currency($data['income']['total_tax']),
            'income_gross' => WPsCRM_format_currency($data['income']['total_gross']),
            'income_count' => $data['income']['total_count'],
            'expenses_net' => WPsCRM_format_currency($data['expenses']['net']),
            'expenses_tax' => WPsCRM_format_currency($data['expenses']['tax']),
            'expenses_gross' => WPsCRM_format_currency($data['expenses']['total_gross']),
            'expenses_count' => $data['expenses']['total_count'],
            'profit_net' => WPsCRM_format_currency($data['summary']['profit_net']),
            'profit_gross' => WPsCRM_format_currency($data['summary']['profit_gross']),
            'tax_payment' => WPsCRM_format_currency($data['income']['total_tax'] - $data['expenses']['tax']),
        ),
    ));
});

/**
 * AJAX: Neue Einnahme hinzufügen und aktualisierte Tabelle zurückgeben
 */
add_action('wp_ajax_wpscrm_add_income', function() {
    error_log('🔵 AJAX Handler wpscrm_add_income wurde aufgerufen');
    error_log('🔍 POST Data: ' . print_r($_POST, true));
    
    // Permissions prüfen
    if (!current_user_can('manage_crm')) {
        error_log('❌ Permission Check failed');
        wp_send_json_error(array('message' => __('Keine Berechtigung', 'cpsmartcrm')));
    }
    
    // Nonce prüfen
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_income_action')) {
        error_log('❌ Nonce Check failed - nonce: ' . ($_POST['nonce'] ?? 'NOT SET'));
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen', 'cpsmartcrm')));
    }
    
    error_log('✅ Permissions und Nonce OK');
    
    global $wpdb;
    
    // Daten validieren
    $income_date = sanitize_text_field($_POST['income_date'] ?? date('Y-m-d'));
    $income_category = sanitize_text_field($_POST['income_category'] ?? '');
    $income_description = sanitize_text_field($_POST['income_description'] ?? '');
    $income_amount = wpscrm_normalize_amount($_POST['income_amount'] ?? 0);
    $income_tax_rate = wpscrm_normalize_amount($_POST['income_tax_rate'] ?? 0);
    $period_from = sanitize_text_field($_POST['period_from'] ?? date('Y-m-01'));
    $period_to = sanitize_text_field($_POST['period_to'] ?? date('Y-m-d'));
    
    if (empty($income_category) || $income_amount <= 0) {
        wp_send_json_error(array('message' => __('Bitte füllen Sie alle erforderlichen Felder aus', 'cpsmartcrm')));
    }
    
    // Speichern
    $tax = $income_amount * ($income_tax_rate / 100);
    $gross = $income_amount + $tax;
    
    $result = $wpdb->insert(
        WPsCRM_TABLE . 'incomes',
        array(
            'data' => $income_date,
            'kategoria' => $income_category,
            'descrizione' => $income_description,
            'imponibile' => $income_amount,
            'aliquota' => $income_tax_rate,
            'imposta' => $tax,
            'totale' => $gross,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        ),
        array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d')
    );
    
    if (!$result) {
        error_log('❌ Income DB Insert failed: ' . $wpdb->last_error);
        wp_send_json_error(array('message' => __('Fehler beim Speichern', 'cpsmartcrm')));
    }
    
    error_log('✅ Income gespeichert, ID: ' . $wpdb->insert_id);
    
    // Aktualisierte Daten laden
    error_log('📊 Lade aktualisierte Daten...');
    $data = wpscrm_get_accounting_period_data($period_from, $period_to, get_current_user_id());
    error_log('📊 Income-Daten geladen');
    
    // Return erfolgreiche Response mit aktualisierten Metrics
    error_log('✅ Sende Income Success Response');
    wp_send_json_success(array(
        'message' => __('Einnahme erfolgreich hinzugefügt', 'cpsmartcrm'),
        'data' => array(
            'income_net' => WPsCRM_format_currency($data['income']['total_net']),
            'income_tax' => WPsCRM_format_currency($data['income']['total_tax']),
            'income_gross' => WPsCRM_format_currency($data['income']['total_gross']),
            'income_count' => $data['income']['total_count'],
            'expenses_net' => WPsCRM_format_currency($data['expenses']['net']),
            'expenses_tax' => WPsCRM_format_currency($data['expenses']['tax']),
            'expenses_gross' => WPsCRM_format_currency($data['expenses']['total_gross']),
            'expenses_count' => $data['expenses']['total_count'],
            'profit_net' => WPsCRM_format_currency($data['summary']['profit_net']),
            'profit_gross' => WPsCRM_format_currency($data['summary']['profit_gross']),
            'tax_payment' => WPsCRM_format_currency($data['income']['total_tax'] - $data['expenses']['tax']),
        ),
    ));
});
?>
