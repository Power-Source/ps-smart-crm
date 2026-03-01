<?php
/**
 * AJAX Handler für Buchhaltungs-Updates
 * 
 * Verarbeitet Hinzufügen/Löschen von Einnahmen und Ausgaben
 * über AJAX und gibt aktualisierte Daten zurück
 */

if (!defined('ABSPATH')) exit;

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
    // Permissions prüfen
    if (!current_user_can('manage_crm')) {
        wp_send_json_error(array('message' => __('Keine Berechtigung', 'cpsmartcrm')));
    }
    
    // Nonce prüfen
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_expense_action')) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen', 'cpsmartcrm')));
    }
    
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
        wp_send_json_error(array('message' => __('Fehler beim Speichern', 'cpsmartcrm')));
    }
    
    $expense_id = $wpdb->insert_id;
    
    // Log accounting activity
    wpscrm_log_accounting_activity('create_expense', array(
        'kategorie' => $expense_category,
        'beschreibung' => $expense_description,
        'betrag' => $expense_amount,
        'steuersatz' => $expense_tax_rate,
        'datum' => $expense_date,
    ), $expense_id, 'expense');
    
    // Aktualisierte Daten laden
    $data = wpscrm_get_accounting_period_data($period_from, $period_to, get_current_user_id());
    
    // Return erfolgreiche Response mit aktualisierten Metrics
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
    // Permissions prüfen
    if (!current_user_can('manage_crm')) {
        wp_send_json_error(array('message' => __('Keine Berechtigung', 'cpsmartcrm')));
    }
    
    // Nonce prüfen
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_income_action')) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen', 'cpsmartcrm')));
    }
    
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
        wp_send_json_error(array('message' => __('Fehler beim Speichern', 'cpsmartcrm')));
    }
    
    $income_id = $wpdb->insert_id;
    
    // Log accounting activity
    wpscrm_log_accounting_activity('create_income', array(
        'kategorie' => $income_category,
        'beschreibung' => $income_description,
        'betrag' => $income_amount,
        'steuersatz' => $income_tax_rate,
        'datum' => $income_date,
    ), $income_id, 'income');
    
    // Aktualisierte Daten laden
    $data = wpscrm_get_accounting_period_data($period_from, $period_to, get_current_user_id());
    
    // Einnahmequellen nach Kategorie laden
    $income_by_source = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            kategoria as source,
            COUNT(*) as count,
            COALESCE(SUM(totale), 0) as total,
            COALESCE(SUM(imponibile), 0) as net
         FROM " . WPsCRM_TABLE . "incomes
         WHERE data BETWEEN %s AND %s
         GROUP BY kategoria
         ORDER BY total DESC",
        $period_from,
        $period_to
    ), ARRAY_A);
    
    $sources_breakdown = array();
    foreach ($income_by_source as $source) {
        if (!empty($source['source'])) {
            $sources_breakdown[$source['source']] = array(
                'count' => (int)$source['count'],
                'net' => WPsCRM_format_currency((float)$source['net']),
                'total' => WPsCRM_format_currency((float)$source['total']),
            );
        }
    }
    
    // Return erfolgreiche Response mit aktualisierten Metrics
    wp_send_json_success(array(
        'message' => __('Einnahme erfolgreich hinzugefügt', 'cpsmartcrm'),
        'sources' => $sources_breakdown,
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
 * AJAX: Transaktion stornieren/löschen
 */
add_action('wp_ajax_wpscrm_delete_transaction', function() {
    // Nonce prüfen
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_transaction_action')) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen', 'cpsmartcrm')));
    }
    
    global $wpdb;
    
    $type = sanitize_text_field($_POST['type'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    
    if (empty($type) || $id <= 0) {
        wp_send_json_error(array('message' => __('Ungültige Parameter', 'cpsmartcrm')));
    }
    
    // Determine table and check permissions
    $table = null;
    $created_by_col = 'created_by';
    
    if ($type === 'manual_income') {
        $table = WPsCRM_TABLE . 'incomes';
    } elseif ($type === 'expense') {
        $table = WPsCRM_TABLE . 'expenses';
    } else {
        wp_send_json_error(array('message' => __('Ungültiger Transaktionstyp', 'cpsmartcrm')));
    }
    
    // Check if transaction exists and get created_by
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
    ));
    
    if (!$transaction) {
        wp_send_json_error(array('message' => __('Transaktion nicht gefunden', 'cpsmartcrm')));
    }
    
    // Check permissions: Admin can delete all, users can only delete their own
    $can_delete = current_user_can('manage_options');
    if (!$can_delete && isset($transaction->created_by)) {
        $can_delete = ($transaction->created_by == get_current_user_id());
    }
    
    if (!$can_delete) {
        wp_send_json_error(array('message' => __('Keine Berechtigung zum Löschen', 'cpsmartcrm')));
    }
    
    // Delete transaction
    $result = $wpdb->delete($table, array('id' => $id), array('%d'));
    
    if ($result === false) {
        wp_send_json_error(array('message' => __('Fehler beim Löschen', 'cpsmartcrm')));
    }
    
    // Log accounting activity
    wpscrm_log_accounting_activity('delete_transaction', array(
        'typ' => $type,
        'kategorie' => isset($transaction->kategoria) ? $transaction->kategoria : '',
        'betrag' => isset($transaction->totale) ? $transaction->totale : 0,
        'datum' => isset($transaction->data) ? $transaction->data : '',
    ), $id, ($type === 'manual_income' ? 'income' : 'expense'));
    
    wp_send_json_success(array(
        'message' => __('Transaktion erfolgreich storniert', 'cpsmartcrm')
    ));
});
?>
