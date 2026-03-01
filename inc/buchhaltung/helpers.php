<?php
/**
 * Accounting Helpers
 * Provides functions for accounting operations and integrations
 */

if (!defined('ABSPATH')) exit;

/**
 * Get configured tax rates from accounting settings
 * Auto-adjusts for Kleinunternehmer if applicable
 */
function WPsCRM_get_tax_rates() {
    $acc_options = get_option('CRM_accounting_settings', array());
    $bus_options = get_option('CRM_business_settings', array());
    $business_type = isset($bus_options['business_type']) ? $bus_options['business_type'] : 'standard';
    
    $default_rates = array(
        array('label' => __('Regulär (19%)', 'cpsmartcrm'), 'percentage' => 19, 'account' => '1776'),
        array('label' => __('Ermäßigt (7%)', 'cpsmartcrm'), 'percentage' => 7, 'account' => '1771'),
        array('label' => __('Null (0%)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1000'),
        array('label' => __('Reverse Charge §13b', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1407'),
        array('label' => __('Innergemeinschaftl. (IGE)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1404'),
    );
    
    $configured_rates = !empty($acc_options['tax_rates']) ? $acc_options['tax_rates'] : $default_rates;
    
    // Für Kleinunternehmer: Alle Sätze auf 0% setzen (informativ)
    if ($business_type === 'kleinunternehmer') {
        $configured_rates = array(
            array('label' => __('Keine USt (§19 Kleinunternehmer)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1000'),
        );
    }
    
    return $configured_rates;
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

/**
 * ==========================================
 * BELEGE / DOKUMENTE VERWALTUNG
 * ==========================================
 */

/**
 * Belegnummer automatisch generieren
 */
function WPsCRM_generate_belegnummer($beleg_typ = 'Beleg') {
    global $wpdb;
    $b_table = WPsCRM_TABLE . 'belege';
    
    $year = date('Y');
    $month = date('m');
    
    // Zähle Belege für diesen Monat
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$b_table} WHERE YEAR(beleg_datum) = %d AND MONTH(beleg_datum) = %d AND deleted = 0",
        $year, $month
    ));
    
    $nummer = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    return 'BEL-' . $year . $month . '-' . $nummer;
}

/**
 * Beleg hochladen
 * 
 * @param array $file $_FILES['beleg_datei']
 * @param array $args Argumente (beleg_typ, beleg_datum, kategorie, beschreibung, etc.)
 * @return array|WP_Error
 */
function WPsCRM_upload_beleg($file, $args = array()) {
    // Berechtigungen prüfen
    if (!current_user_can('manage_crm')) {
        return new WP_Error('unauthorized', __('Sie haben keine Berechtigung für diese Aktion', 'cpsmartcrm'));
    }
    if (function_exists('wpscrm_user_can') && !current_user_can('manage_options') && !wpscrm_user_can(get_current_user_id(), 'can_edit_documents')) {
        return new WP_Error('forbidden', __('Keine Berechtigung zum Hochladen von Belegen', 'cpsmartcrm'));
    }
    
    // Datei validieren
    if (empty($file) || $file['size'] === 0) {
        return new WP_Error('no_file', __('Keine Datei ausgewählt', 'cpsmartcrm'));
    }
    
    // Max. Dateigröße: 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        return new WP_Error('file_too_large', __('Datei zu groß (max. 10MB)', 'cpsmartcrm'));
    }
    
    // Erlaubte Dateitypen
    $allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'zip');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return new WP_Error('invalid_type', __('Dateityp nicht erlaubt', 'cpsmartcrm'));
    }
    
    // Zielverzeichnis
    $upload_dir = wp_upload_dir();
    $belege_dir = $upload_dir['basedir'] . '/CRMdocuments/belege';
    
    if (!file_exists($belege_dir)) {
        wp_mkdir_p($belege_dir);
    }
    
    // Eindeutigen Dateinamen generieren
    $year_month = date('Ym');
    $uniqid = wp_generate_password(8, false);
    $new_filename = $year_month . '_' . $uniqid . '.' . $file_ext;
    $file_path = $belege_dir . '/' . $new_filename;
    
    // Datei verschieben
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return new WP_Error('upload_failed', __('Dateiupload fehlgeschlagen', 'cpsmartcrm'));
    }
    
    // Beleg in Datenbank speichern
    global $wpdb;
    $b_table = WPsCRM_TABLE . 'belege';
    
    $defaults = array(
        'beleg_typ'    => __('Beleg', 'cpsmartcrm'),
        'beleg_datum'  => date('Y-m-d'),
        'kategorie'    => 'Sonstiges',
        'beschreibung' => '',
        'notizen'      => '',
        'tags'         => '',
        'fk_documenti' => null,
        'fk_incomes'   => null,
        'fk_expenses'  => null,
        'fk_kunde'     => null,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $belegnummer = WPsCRM_generate_belegnummer($args['beleg_typ']);
    
    $insert_data = array(
        'belegnummer'   => $belegnummer,
        'beleg_typ'     => $args['beleg_typ'],
        'beleg_datum'   => $args['beleg_datum'],
        'filename'      => $new_filename,
        'file_path'     => $file_path,
        'file_mime'     => $file['type'],
        'file_size'     => $file['size'],
        'fk_documenti'  => $args['fk_documenti'],
        'fk_incomes'    => $args['fk_incomes'],
        'fk_expenses'   => $args['fk_expenses'],
        'fk_kunde'      => $args['fk_kunde'],
        'beschreibung'  => $args['beschreibung'],
        'kategorie'     => $args['kategorie'],
        'notizen'       => $args['notizen'],
        'tags'          => $args['tags'],
        'upload_datum'  => date('Y-m-d H:i:s'),
        'uploaded_by'   => get_current_user_id(),
        'deleted'       => 0,
    );
    
    $inserted = $wpdb->insert($b_table, $insert_data);
    
    if (!$inserted) {
        // Datei löschen wenn DB Insert fehlschlägt
        unlink($file_path);
        return new WP_Error('db_insert_failed', __('Fehler beim Speichern in der Datenbank', 'cpsmartcrm'));
    }
    
    return array(
        'success'    => true,
        'beleg_id'   => $wpdb->insert_id,
        'belegnummer' => $belegnummer,
        'message'    => __('Beleg erfolgreich hochgeladen', 'cpsmartcrm')
    );
}

/**
 * Beleg löschen (Soft Delete)
 */
function WPsCRM_delete_beleg($beleg_id) {
    global $wpdb;
    $b_table = WPsCRM_TABLE . 'belege';
    
    // Berechtigungen
    if (!current_user_can('manage_crm')) {
        return new WP_Error('unauthorized', __('Keine Berechtigung', 'cpsmartcrm'));
    }

    if (function_exists('wpscrm_user_can') && !current_user_can('manage_options') && !wpscrm_user_can(get_current_user_id(), 'can_edit_documents')) {
        return new WP_Error('forbidden', __('Keine Berechtigung', 'cpsmartcrm'));
    }

    if (function_exists('wpscrm_user_can') && !current_user_can('manage_options') && !wpscrm_user_can(get_current_user_id(), 'can_view_all_documents')) {
        $owner_id = (int)$wpdb->get_var($wpdb->prepare("SELECT uploaded_by FROM {$b_table} WHERE id = %d", $beleg_id));
        if ($owner_id !== (int)get_current_user_id()) {
            return new WP_Error('forbidden_own', __('Sie dürfen nur eigene Belege löschen', 'cpsmartcrm'));
        }
    }
    
    $updated = $wpdb->update(
        $b_table,
        array('deleted' => 1),
        array('id' => $beleg_id),
        array('%d'),
        array('%d')
    );
    
    return $updated !== false;
}

/**
 * Beleg von Transaktion abrufen
 */
function WPsCRM_get_beleg_by_transaction($type, $transaction_id) {
    global $wpdb;
    $b_table = WPsCRM_TABLE . 'belege';
    
    $column_map = array(
        'invoice'   => 'fk_documenti',
        'income'    => 'fk_incomes',
        'expense'   => 'fk_expenses',
    );
    
    if (!isset($column_map[$type])) {
        return array();
    }
    
    $column = $column_map[$type];
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$b_table} WHERE {$column} = %d AND deleted = 0 ORDER BY beleg_datum DESC",
        $transaction_id
    ));
}

/**
 * Alle Belege einer Kategorie abrufen
 */
function WPsCRM_get_belege_by_category($kategorie, $limit = 0) {
    global $wpdb;
    $b_table = WPsCRM_TABLE . 'belege';
    
    $sql = $wpdb->prepare(
        "SELECT * FROM {$b_table} WHERE kategorie = %s AND deleted = 0 ORDER BY beleg_datum DESC",
        $kategorie
    );
    
    if ($limit > 0) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    return $wpdb->get_results($sql);
}

/**
 * Belege statistik
 */
function WPsCRM_get_belege_statistics($date_from = null, $date_to = null) {
    global $wpdb;
    $b_table = WPsCRM_TABLE . 'belege';
    
    $where = "deleted = 0";
    $where_args = array();
    
    if ($date_from) {
        $where .= " AND beleg_datum >= %s";
        $where_args[] = $date_from;
    }
    
    if ($date_to) {
        $where .= " AND beleg_datum <= %s";
        $where_args[] = $date_to;
    }
    
    $sql = "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT kategorie) as categories,
                SUM(file_size) as total_size,
                kategorie,
                COUNT(*) as count
            FROM {$b_table} 
            WHERE {$where}
            GROUP BY kategorie
            ORDER BY count DESC";
    
    if (!empty($where_args)) {
        $sql = $wpdb->prepare($sql, ...$where_args);
    }
    
    return $wpdb->get_results($sql);
}

/**
 * Get last 12 months tax data (Steuerlast calculations)
 * Returns: income_ust, expense_vst, month_label for each month
 */
function wpscrm_get_last_12_months_tax_data($user_id = null) {
    global $wpdb;
    $tax_data = array();
    $business_type = isset($GLOBALS['CRM_business_settings']['business_type']) ? $GLOBALS['CRM_business_settings']['business_type'] : 'standard';
    
    // For Kleinunternehmer, return zeros (no tax)
    if ($business_type === 'kleinunternehmer') {
        for ($i = 11; $i >= 0; $i--) {
            $month = strtotime("-$i months");
            $tax_data[] = array(
                'month_label' => date_i18n('M Y', $month),
                'month_key'   => date('Y-m', $month),
                'income_ust'  => 0,
                'expense_vst' => 0,
            );
        }
        return $tax_data;
    }
    
    // For standard/freiberufler: Calculate actual tax
    for ($i = 11; $i >= 0; $i--) {
        $month = strtotime("-$i months");
        $month_start = date('Y-m-01 00:00:00', $month);
        $month_end = date('Y-m-t 23:59:59', $month);
        
        // Get income USt (Umsatzsteuer)
        $income_ust = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(tax), 0) FROM " . WPsCRM_TABLE . "incomes 
             WHERE data_created BETWEEN %s AND %s AND deleted = 0 AND tax_rate > 0" 
             . ($user_id ? " AND user_id = %d" : ""),
            $month_start,
            $month_end,
            ...$user_id ? array($user_id) : array()
        ));
        
        // Get expense VSt (Vorsteuer)
        $expense_vst = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(tax), 0) FROM " . WPsCRM_TABLE . "expenses 
             WHERE data_created BETWEEN %s AND %s AND deleted = 0" 
             . ($user_id ? " AND user_id = %d" : ""),
            $month_start,
            $month_end,
            ...$user_id ? array($user_id) : array()
        ));
        
        $tax_data[] = array(
            'month_label' => date_i18n('M Y', $month),
            'month_key'   => date('Y-m', $month),
            'income_ust'  => (float)$income_ust,
            'expense_vst' => (float)$expense_vst,
        );
    }
    
    return $tax_data;
}

/**
 * Generate tax trend chart configuration for Chart.js
 */
function wpscrm_generate_tax_trend_chart_config($tax_months, $business_type = 'standard') {
    $labels = array();
    $income_ust_data = array();
    $expense_vst_data = array();
    $zahllast_data = array();
    $background_colors = array();
    
    foreach ($tax_months as $month) {
        $labels[] = $month['month_key'];
        $income_ust_data[] = $month['income_ust'];
        $expense_vst_data[] = $month['expense_vst'];
        
        $zahllast = $month['income_ust'] - $month['expense_vst'];
        $zahllast_data[] = $zahllast;
        $background_colors[] = $zahllast > 0 ? '#f44336' : '#4caf50';
    }
    
    return array(
        'type' => 'line',
        'data' => array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Einnahmen-USt', 'cpsmartcrm'),
                    'data' => $income_ust_data,
                    'borderColor' => '#46b450',
                    'backgroundColor' => 'rgba(70, 180, 80, 0.05)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => '#46b450',
                ),
                array(
                    'label' => __('Ausgaben-VSt', 'cpsmartcrm'),
                    'data' => $expense_vst_data,
                    'borderColor' => '#ff6b00',
                    'backgroundColor' => 'rgba(255, 107, 0, 0.05)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => '#ff6b00',
                ),
                array(
                    'label' => __('Zahllast (Netto)', 'cpsmartcrm'),
                    'data' => $zahllast_data,
                    'borderColor' => '#2196F3',
                    'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 5,
                    'pointBackgroundColor' => '#2196F3',
                    'yAxisID' => 'y1',
                ),
            ),
        ),
        'options' => array(
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => array('mode' => 'index', 'intersect' => false),
            'plugins' => array(
                'legend' => array('display' => true, 'position' => 'top'),
                'title' => array('display' => false),
                'tooltip' => array('enabled' => true),
            ),
            'scales' => array(
                'y' => array(
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'min' => 0,
                    'title' => array('display' => true, 'text' => __('EUR', 'cpsmartcrm')),
                ),
                'y1' => array(
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => array('display' => true, 'text' => __('Zahllast EUR', 'cpsmartcrm')),
                    'grid' => array('drawOnChartArea' => false),
                ),
            ),
        ),
    );
}
