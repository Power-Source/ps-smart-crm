<?php
/**
 * Agent Dashboard - AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get Agent Statistics (AJAX)
 */
function wpscrm_agent_dashboard_get_stats() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    
    // Get current month timetracking
    $timings_table = WPsCRM_TABLE . 'timetracking';
    $current_month_start = date( 'Y-m-01' );
    
    $result = $wpdb->get_row( $wpdb->prepare(
        "SELECT 
            SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))) as total_seconds,
            COUNT(*) as entry_count
        FROM {$timings_table}
        WHERE user_id = %d 
        AND start_time >= %s
        AND start_time < DATE_ADD(%s, INTERVAL 1 MONTH)",
        $user_id,
        $current_month_start,
        $current_month_start
    ) );
    
    $total_seconds = (int) ( $result->total_seconds ?? 0 );
    $total_hours = round( $total_seconds / 3600, 2 );
    
    // Get hourly rate
    $agents_table = WPsCRM_TABLE . 'agents';
    $agent = $wpdb->get_row( $wpdb->prepare(
        "SELECT hourly_rate FROM {$agents_table} WHERE user_id = %d",
        $user_id
    ) );
    
    $hourly_rate = (float) ( $agent->hourly_rate ?? 0 );
    $total_earnings = round( $total_hours * $hourly_rate, 2 );
    
    wp_send_json_success( array(
        'total_hours' => number_format( $total_hours, 2, ',', '.' ),
        'total_earnings' => number_format( $total_earnings, 2, ',', '.' ),
        'entry_count' => (int) $result->entry_count,
    ) );
}

/**
 * Get Active Timetracking (AJAX)
 */
function wpscrm_agent_dashboard_get_active_tracking() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    $timings_table = WPsCRM_TABLE . 'timetracking';
    
    // Find active tracking (no end_time)
    $active = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, start_time, project_id, task_id
        FROM {$timings_table}
        WHERE user_id = %d
        AND end_time IS NULL
        ORDER BY start_time DESC
        LIMIT 1",
        $user_id
    ) );
    
    if ( ! $active ) {
        wp_send_json_success( array() );
    }
    
    wp_send_json_success( array(
        'tracking_id' => $active->id,
        'start_time' => $active->start_time,
        'project_id' => $active->project_id,
        'task_id' => $active->task_id,
    ) );
}

/**
 * Toggle Timetracking (Start/Stop) - AJAX
 */
function wpscrm_agent_dashboard_timetracking_toggle() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $action_type = sanitize_text_field( $_POST['action_type'] ?? '' );
    
    global $wpdb;
    $timings_table = WPsCRM_TABLE . 'timetracking';
    
    if ( 'start' === $action_type ) {
        
        // Create new tracking
        $result = $wpdb->insert(
            $timings_table,
            array(
                'user_id' => $user_id,
                'project_id' => (int) ( $_POST['project_id'] ?? 0 ),
                'task_id' => (int) ( $_POST['task_id'] ?? 0 ),
                'start_time' => current_time( 'mysql' ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s', '%s' )
        );
        
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Starten' ) );
        }
        
        wp_send_json_success( array(
            'tracking_id' => $wpdb->insert_id,
            'start_time' => current_time( 'mysql' ),
        ) );
        
    } elseif ( 'stop' === $action_type ) {
        
        $tracking_id = (int) ( $_POST['tracking_id'] ?? 0 );
        
        $result = $wpdb->update(
            $timings_table,
            array( 'end_time' => current_time( 'mysql' ) ),
            array( 'id' => $tracking_id, 'user_id' => $user_id ),
            array( '%s' ),
            array( '%d', '%d' )
        );
        
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Stoppen' ) );
        }
        
        wp_send_json_success( array( 'tracking_id' => $tracking_id ) );
        
    } else {
        wp_send_json_error( array( 'message' => 'Ungültige Aktion' ) );
    }
}

/**
 * Toggle Task Status (AJAX)
 */
function wpscrm_agent_dashboard_toggle_task() {
    check_ajax_referer( 'crm_task_toggle', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $task_id = intval( $_POST['task_id'] ?? 0 );
    $fatto = intval( $_POST['fatto'] ?? 1 );
    
    if ( ! $task_id ) {
        wp_send_json_error( array( 'message' => 'Ungültige Aufgabe' ) );
    }
    
    global $wpdb;
    $agenda_table = WPsCRM_TABLE . 'agenda';
    
    // Prüfe ob Task dem User gehört
    $task = $wpdb->get_row( $wpdb->prepare(
        "SELECT id_agenda FROM {$agenda_table} WHERE id_agenda = %d AND fk_utenti_des = %d",
        $task_id,
        $user_id
    ) );
    
    if ( ! $task ) {
        wp_send_json_error( array( 'message' => 'Aufgabe nicht gefunden' ) );
    }
    
    // Update Status
    $result = $wpdb->update(
        $agenda_table,
        array( 'fatto' => $fatto ),
        array( 'id_agenda' => $task_id ),
        array( '%d' ),
        array( '%d' )
    );
    
    if ( $result === false ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Aktualisieren' ) );
    }
    
    wp_send_json_success( array(
        'message' => 'Aufgabe aktualisiert',
        'task_id' => $task_id,
        'fatto' => $fatto
    ) );
}
