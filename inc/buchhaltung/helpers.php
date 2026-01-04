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
