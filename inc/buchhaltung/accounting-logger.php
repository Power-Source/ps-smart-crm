<?php
/**
 * Accounting Activity Logger
 * 
 * Protokolliert alle Buchhaltungs-Aktionen für Audit-Zwecke
 * Speichert in Datenbank für schnellen Zugriff
 */

if (!defined('ABSPATH')) exit;

/**
 * Log Buchhaltungs-Aktivität
 * 
 * @param string $action Action type: 'create_income', 'create_expense', 'delete_income', 'delete_expense', etc.
 * @param array $data Zusätzliche Daten zur Aktion
 * @param int $transaction_id Die ID der betroffenen Transaktion
 * @param string $transaction_type 'income' oder 'expense'
 */
function wpscrm_log_accounting_activity($action, $data = array(), $transaction_id = null, $transaction_type = null) {
    global $wpdb;
    
    $table = WPsCRM_TABLE . 'accounting_log';
    
    // Collect metadata
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $username = $user ? $user->display_name : 'System';
    
    // IP Address (anonymized for GDPR)
    $ip_address = '';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
        // Anonymize last octet
        $ip_parts = explode('.', $ip);
        if (count($ip_parts) === 4) {
            $ip_parts[3] = '0';
            $ip_address = implode('.', $ip_parts);
        }
    }
    
    // User Agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    
    // Request URI
    $request_uri = isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 255) : '';
    
    // Prepare log entry
    $log_data = array(
        'action' => $action,
        'user_id' => $user_id,
        'username' => $username,
        'transaction_id' => $transaction_id,
        'transaction_type' => $transaction_type,
        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'request_uri' => $request_uri,
        'created_at' => current_time('mysql'),
    );
    
    // Insert into database
    $result = $wpdb->insert($table, $log_data, array(
        '%s', // action
        '%d', // user_id
        '%s', // username
        '%d', // transaction_id
        '%s', // transaction_type
        '%s', // data
        '%s', // ip_address
        '%s', // user_agent
        '%s', // request_uri
        '%s', // created_at
    ));
    
    return $result !== false;
}

/**
 * Get Accounting Activity Logs
 * 
 * @param array $args Query arguments
 * @return array Log entries
 */
function wpscrm_get_accounting_logs($args = array()) {
    global $wpdb;
    
    $defaults = array(
        'limit' => 100,
        'offset' => 0,
        'action' => null,
        'user_id' => null,
        'transaction_type' => null,
        'date_from' => null,
        'date_to' => null,
        'orderby' => 'created_at',
        'order' => 'DESC',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $table = WPsCRM_TABLE . 'accounting_log';
    
    // Build WHERE clause
    $where = array('1=1');
    $where_values = array();
    
    if ($args['action']) {
        $where[] = 'action = %s';
        $where_values[] = $args['action'];
    }
    
    if ($args['user_id']) {
        $where[] = 'user_id = %d';
        $where_values[] = $args['user_id'];
    }
    
    if ($args['transaction_type']) {
        $where[] = 'transaction_type = %s';
        $where_values[] = $args['transaction_type'];
    }
    
    if ($args['date_from']) {
        $where[] = 'created_at >= %s';
        $where_values[] = $args['date_from'];
    }
    
    if ($args['date_to']) {
        $where[] = 'created_at <= %s';
        $where_values[] = $args['date_to'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    // Build ORDER BY
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    
    // Build query
    $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d";
    $where_values[] = $args['limit'];
    $where_values[] = $args['offset'];
    
    if (!empty($where_values)) {
        $query = $wpdb->prepare($query, $where_values);
    }
    
    return $wpdb->get_results($query);
}

/**
 * Get total count of logs
 */
function wpscrm_get_accounting_logs_count($args = array()) {
    global $wpdb;
    
    $table = WPsCRM_TABLE . 'accounting_log';
    
    // Build WHERE clause (same as above)
    $where = array('1=1');
    $where_values = array();
    
    if (!empty($args['action'])) {
        $where[] = 'action = %s';
        $where_values[] = $args['action'];
    }
    
    if (!empty($args['user_id'])) {
        $where[] = 'user_id = %d';
        $where_values[] = $args['user_id'];
    }
    
    if (!empty($args['transaction_type'])) {
        $where[] = 'transaction_type = %s';
        $where_values[] = $args['transaction_type'];
    }
    
    if (!empty($args['date_from'])) {
        $where[] = 'created_at >= %s';
        $where_values[] = $args['date_from'];
    }
    
    if (!empty($args['date_to'])) {
        $where[] = 'created_at <= %s';
        $where_values[] = $args['date_to'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    $query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
    
    if (!empty($where_values)) {
        $query = $wpdb->prepare($query, $where_values);
    }
    
    return (int) $wpdb->get_var($query);
}

/**
 * Create accounting log table if not exists
 */
function wpscrm_create_accounting_log_table() {
    global $wpdb;
    
    $table = WPsCRM_TABLE . 'accounting_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        action varchar(100) NOT NULL,
        user_id bigint(20) DEFAULT NULL,
        username varchar(255) DEFAULT NULL,
        transaction_id bigint(20) DEFAULT NULL,
        transaction_type varchar(50) DEFAULT NULL,
        data text DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent varchar(255) DEFAULT NULL,
        request_uri varchar(255) DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY action (action),
        KEY user_id (user_id),
        KEY transaction_id (transaction_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create table on plugin activation/load
add_action('plugins_loaded', 'wpscrm_create_accounting_log_table');
