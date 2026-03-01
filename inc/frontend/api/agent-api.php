<?php
/**
 * Agent Frontend API Handler
 * AJAX Endpoints für Agent Dashboard
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Zeiterfassung starten/stoppen
 */
add_action('wp_ajax_crm_agent_timetracking_toggle', 'wpscrm_ajax_timetracking_toggle');

function wpscrm_ajax_timetracking_toggle() {
    check_ajax_referer('crm_frontend_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Nicht angemeldet'));
    }
    
    $current_user = wp_get_current_user();
    $action = sanitize_text_field($_POST['action_type'] ?? 'start');
    
    global $wpdb;
    $tt_table = WPsCRM_TABLE . 'timetracking';
    
    if ($action === 'start') {
        // Neue Zeiterfassung starten
        $wpdb->insert($tt_table, array(
            'user_id' => $current_user->ID,
            'project_id' => intval($_POST['project_id'] ?? 0),
            'task_id' => intval($_POST['task_id'] ?? 0),
            'start_time' => current_time('mysql'),
            'status' => 'running'
        ), array('%d', '%d', '%d', '%s', '%s'));
        
        wp_send_json_success(array(
            'message' => 'Zeiterfassung gestartet',
            'tracking_id' => $wpdb->insert_id,
            'start_time' => current_time('mysql')
        ));
    } 
    elseif ($action === 'stop') {
        // Zeiterfassung stoppen
        $tracking_id = intval($_POST['tracking_id']);
        
        $wpdb->update($tt_table, 
            array('end_time' => current_time('mysql'), 'status' => 'completed'),
            array('id' => $tracking_id),
            array('%s', '%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => 'Zeiterfassung beendet'
        ));
    }
    
    wp_send_json_error(array('message' => 'Unbekannte Aktion'));
}

/**
 * AJAX: Get Agent Stats (Stunden, Verdienst für aktuellen Monat)
 */
add_action('wp_ajax_crm_agent_get_stats', 'wpscrm_ajax_agent_get_stats');

function wpscrm_ajax_agent_get_stats() {
    check_ajax_referer('crm_frontend_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Nicht angemeldet'));
    }
    
    $current_user = wp_get_current_user();
    global $wpdb;
    
    $month_start = date('Y-m-01 00:00:00');
    $month_end = date('Y-m-t 23:59:59');
    
    $tt_table = WPsCRM_TABLE . 'timetracking';
    $agents_table = WPsCRM_TABLE . 'agents';
    
    // Get total hours this month
    $total_minutes = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)), 0) 
         FROM $tt_table 
         WHERE user_id = %d AND date(start_time) BETWEEN %s AND %s",
        $current_user->ID,
        $month_start,
        $month_end
    ));
    
    $total_hours = round($total_minutes / 60, 2);
    
    // Get agent billing info
    $agent = $wpdb->get_row($wpdb->prepare(
        "SELECT hourly_rate, rate_type FROM $agents_table WHERE user_id = %d",
        $current_user->ID
    ));
    
    $hourly_rate = $agent->hourly_rate ?? 0;
    $total_earnings = $total_hours * $hourly_rate;
    
    wp_send_json_success(array(
        'total_minutes' => $total_minutes,
        'total_hours' => $total_hours,
        'hourly_rate' => $hourly_rate,
        'total_earnings' => $total_earnings,
        'current_month' => date_i18n('F Y')
    ));
}

/**
 * AJAX: Get Active Timetracking
 */
add_action('wp_ajax_crm_agent_get_active_tracking', 'wpscrm_ajax_get_active_tracking');

function wpscrm_ajax_get_active_tracking() {
    check_ajax_referer('crm_frontend_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Nicht angemeldet'));
    }
    
    $current_user = wp_get_current_user();
    global $wpdb;
    
    $tt_table = WPsCRM_TABLE . 'timetracking';
    
    $active = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tt_table 
         WHERE user_id = %d AND status = 'running' 
         ORDER BY start_time DESC LIMIT 1",
        $current_user->ID
    ));
    
    if ($active) {
        wp_send_json_success(array(
            'tracking_id' => $active->id,
            'start_time' => $active->start_time
        ));
    } else {
        wp_send_json_success(array('tracking_id' => null));
    }
}
