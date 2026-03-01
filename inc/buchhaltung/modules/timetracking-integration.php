<?php
/**
 * Timetracking ↔ Buchhaltung Integration
 * 
 * Verwaltet die Abrechnung von erfassten Arbeitszeiten
 * - Berechnet noch nicht abgerechnete Stunden
 * - Konvertiert zu Euro basierend auf Rollen/Stundenhonorar
 * - Erstellt Abrechnungsentwürfe
 * - Überführt in Einnahmen-Transaktionen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all unbilled timetracking entries for an agent
 * @param int $agent_id Agent ID aus agents-Tabelle
 * @param string $date_from YYYY-MM-DD
 * @param string $date_to YYYY-MM-DD
 * @return array Timetracking entries
 */
function wpscrm_get_unbilled_timetracking( $agent_id = 0, $date_from = '', $date_to = '' ) {
	global $wpdb;
	
	$tt_table = WPsCRM_TABLE . 'timetracking';
	$billing_table = WPsCRM_TABLE . 'billing_drafts';
	$agents_table = WPsCRM_TABLE . 'agents';
	$roles_table = WPsCRM_TABLE . 'agent_roles';
	
	// Get user_id for this agent
	if ( $agent_id > 0 ) {
		$agent = $wpdb->get_row( $wpdb->prepare(
			"SELECT user_id FROM $agents_table WHERE id = %d",
			$agent_id
		) );
		$user_id = $agent ? $agent->user_id : 0;
		if ( ! $user_id ) {
			return array();
		}
	} else {
		$user_id = 0;
	}
	
	if ( empty( $date_from ) ) {
		$date_from = date( 'Y-m-01' );
	}
	if ( empty( $date_to ) ) {
		$date_to = date( 'Y-m-d' );
	}
	
	$where = "tt.deleted = 0 AND tt.status = 'completed' AND tt.is_billable = 1";
	$where .= " AND tt.start_time >= %s AND tt.start_time <= %s";
	$values = array( $date_from . ' 00:00:00', $date_to . ' 23:59:59' );
	
	if ( $user_id > 0 ) {
		$where .= " AND tt.user_id = %d";
		array_unshift( $values, $user_id );
		$where = str_replace( "tt.deleted", "tt.user_id = %d AND tt.deleted", $where );
	}
	
	// Get entries not yet in a billing draft
	$query = "
		SELECT 
			tt.id,
			tt.user_id,
			tt.project_name,
			tt.task_description,
			tt.start_time,
			tt.end_time,
			tt.duration_minutes,
			tt.total_amount,
			tt.is_billable,
			tt.fk_kunde,
			bd.id as billing_draft_id
		FROM $tt_table tt
		LEFT JOIN $billing_table bd ON tt.id = bd.timetracking_id
		WHERE {$where}
		AND bd.id IS NULL
		ORDER BY tt.start_time DESC
	";
	
	return $wpdb->get_results( $wpdb->prepare( $query, ...$values ) );
}

/**
 * Get agent's hourly rate and billing info
 * @param int $user_id WordPress User ID
 * @return object {hourly_rate, rate_type, billing_mode}
 */
function wpscrm_get_agent_billing_info( $user_id ) {
	if ( function_exists( 'wpscrm_get_user_worktime_settings' ) ) {
		$settings = wpscrm_get_user_worktime_settings( $user_id );
		return (object) array(
			'hourly_rate' => isset( $settings['hourly_rate'] ) ? (float) $settings['hourly_rate'] : 0,
			'rate_type' => isset( $settings['rate_type'] ) ? $settings['rate_type'] : 'net',
			'billing_mode' => isset( $settings['billing_mode'] ) ? $settings['billing_mode'] : 'employee',
		);
	}
	
	return (object) array(
		'hourly_rate' => 0,
		'rate_type' => 'net',
		'billing_mode' => 'employee',
	);
}

/**
 * Calculate total revenue from timetracking entries
 * @param array $timetracking_entries Entries from wpscrm_get_unbilled_timetracking
 * @param object $billing_info From wpscrm_get_agent_billing_info
 * @return float Calculated revenue (net or gross)
 */
function wpscrm_calculate_timetracking_revenue( $timetracking_entries, $billing_info ) {
	$total_minutes = 0;
	
	foreach ( $timetracking_entries as $entry ) {
		$total_minutes += (int) $entry->duration_minutes;
	}
	
	$total_hours = $total_minutes / 60;
	$hourly_rate = (float) $billing_info->hourly_rate;
	
	if ( 'gross' === $billing_info->rate_type ) {
		// Gross is already the final amount
		return $total_hours * $hourly_rate;
	} else {
		// Net → add standard 19% tax for Germany
		$net = $total_hours * $hourly_rate;
		return $net * 1.19;
	}
}

/**
 * Get summary of unbilled timetracking by agent
 * @param string $date_from YYYY-MM-DD
 * @param string $date_to YYYY-MM-DD
 * @return array Agent summary data
 */
function wpscrm_get_timetracking_summary( $date_from = '', $date_to = '' ) {
	global $wpdb;
	
	if ( empty( $date_from ) ) {
		$date_from = date( 'Y-m-01' );
	}
	if ( empty( $date_to ) ) {
		$date_to = date( 'Y-m-d' );
	}
	
	$tt_table = WPsCRM_TABLE . 'timetracking';
	$billing_table = WPsCRM_TABLE . 'billing_drafts';
	$agents_table = WPsCRM_TABLE . 'agents';
	
	$query = $wpdb->prepare(
		"
		SELECT 
			a.id as agent_id,
			a.user_id,
			u.display_name,
			COUNT(DISTINCT tt.id) as entry_count,
			COALESCE(SUM(tt.duration_minutes), 0) as total_minutes,
			bd.id as has_draft
		FROM $agents_table a
		INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
		LEFT JOIN $tt_table tt ON a.user_id = tt.user_id 
			AND tt.deleted = 0 
			AND tt.status = 'completed' 
			AND tt.is_billable = 1
			AND tt.start_time >= %s
			AND tt.start_time <= %s
		LEFT JOIN $billing_table bd ON a.id = bd.agent_id 
			AND bd.status = 'draft'
		WHERE a.status = 'active'
		GROUP BY a.id, u.display_name
		HAVING entry_count > 0 OR has_draft IS NOT NULL
		ORDER BY u.display_name ASC
		",
		$date_from . ' 00:00:00',
		$date_to . ' 23:59:59'
	);
	
	return $wpdb->get_results( $query );
}

/**
 * Create a billing draft (Entwurf) from timetracking entries
 * @param int $agent_id Agent ID
 * @param array $timetracking_ids Array of timetracking IDs to include
 * @param string $description Optional description
 * @return int|WP_Error Draft ID or error
 */
function wpscrm_create_billing_draft( $agent_id, $timetracking_ids = array(), $description = '' ) {
	global $wpdb;
	
	// Get agent and user info
	$agents_table = WPsCRM_TABLE . 'agents';
	$agent = $wpdb->get_row( $wpdb->prepare(
		"SELECT user_id FROM $agents_table WHERE id = %d AND status = 'active'",
		$agent_id
	) );
	
	if ( ! $agent ) {
		return new WP_Error( 'invalid_agent', __( 'Agent nicht gefunden.', 'cpsmartcrm' ) );
	}
	
	// If no specific IDs provided, get all unbilled for this agent
	if ( empty( $timetracking_ids ) ) {
		$unbilled = wpscrm_get_unbilled_timetracking( $agent_id );
		$timetracking_ids = wp_list_pluck( $unbilled, 'id' );
	}
	
	if ( empty( $timetracking_ids ) ) {
		return new WP_Error( 'no_entries', __( 'Keine unbezahlten Einträge gefunden.', 'cpsmartcrm' ) );
	}
	
	// Calculate total
	$tt_table = WPsCRM_TABLE . 'timetracking';
	$tt_data = $wpdb->get_results(
		"SELECT SUM(duration_minutes) as total_minutes FROM $tt_table WHERE id IN (" 
		. implode( ',', array_map( 'absint', $timetracking_ids ) ) . ")"
	);
	
	$total_minutes = isset( $tt_data[0] ) ? (int) $tt_data[0]->total_minutes : 0;
	
	// Get billing info
	$billing_info = wpscrm_get_agent_billing_info( $agent->user_id );
	
	// Calculate amount
	$total_hours = $total_minutes / 60;
	$amount_net = $total_hours * $billing_info->hourly_rate;
	$amount_gross = 'gross' === $billing_info->rate_type ? $amount_net : $amount_net * 1.19;
	
	// Create draft record
	$billing_table = WPsCRM_TABLE . 'billing_drafts';
	$result = $wpdb->insert(
		$billing_table,
		array(
			'agent_id' => (int) $agent_id,
			'user_id' => (int) $agent->user_id,
			'status' => 'draft',
			'total_minutes' => $total_minutes,
			'hourly_rate' => (float) $billing_info->hourly_rate,
			'rate_type' => $billing_info->rate_type,
			'amount_net' => $amount_net,
			'amount_gross' => $amount_gross,
			'description' => sanitize_text_field( $description ),
			'created_at' => current_time( 'mysql' ),
			'created_by' => get_current_user_id(),
		),
		array( '%d', '%d', '%s', '%d', '%f', '%s', '%f', '%f', '%s', '%s', '%d' )
	);
	
	if ( ! $result ) {
		return new WP_Error( 'insert_failed', __( 'Fehler beim Erstellen des Entwurfs.', 'cpsmartcrm' ) );
	}
	
	$draft_id = $wpdb->insert_id;
	
	// Link timetracking entries to draft
	foreach ( $timetracking_ids as $tt_id ) {
		$wpdb->insert(
			$billing_table . '_items',
			array(
				'billing_draft_id' => $draft_id,
				'timetracking_id' => absint( $tt_id ),
			),
			array( '%d', '%d' )
		);
	}
	
	return $draft_id;
}

/**
 * Convert billing draft to actual income entry
 * @param int $draft_id Billing draft ID
 * @param int $document_id Optional document ID to link
 * @return int|WP_Error Income ID or error
 */
function wpscrm_convert_draft_to_income( $draft_id, $document_id = 0 ) {
	global $wpdb;
	
	$billing_table = WPsCRM_TABLE . 'billing_drafts';
	$incomes_table = WPsCRM_TABLE . 'incomes';
	
	// Get draft
	$draft = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $billing_table WHERE id = %d AND status = 'draft'",
		$draft_id
	) );
	
	if ( ! $draft ) {
		return new WP_Error( 'invalid_draft', __( 'Entwurf nicht gefunden oder bereits aktualisiert.', 'cpsmartcrm' ) );
	}
	
	// Create income entry
	$income_result = $wpdb->insert(
		$incomes_table,
		array(
			'data' => current_time( 'Y-m-d' ),
			'kategoria' => __( 'Arbeitszeiten', 'cpsmartcrm' ),
			'beschreibung' => sprintf(
				__( 'Abrechnung: %s Stunden à €%.2f', 'cpsmartcrm' ),
				round( $draft->total_minutes / 60, 2 ),
				$draft->hourly_rate
			),
			'imponibile' => $draft->amount_net,
			'aliquota' => 19, // Standard German VAT
			'imposta' => $draft->amount_gross - $draft->amount_net,
			'totale' => $draft->amount_gross,
			'created_at' => current_time( 'mysql' ),
			'created_by' => get_current_user_id(),
		),
		array( '%s', '%s', '%s', '%f', '%d', '%f', '%f', '%s', '%d' )
	);
	
	if ( ! $income_result ) {
		return new WP_Error( 'income_create_failed', __( 'Fehler beim Buchen.', 'cpsmartcrm' ) );
	}
	
	$income_id = $wpdb->insert_id;
	
	// Update draft status
	$wpdb->update(
		$billing_table,
		array(
			'status' => 'billed',
			'income_entry_id' => $income_id,
			'document_id' => $document_id ? (int) $document_id : null,
			'billed_at' => current_time( 'mysql' ),
			'billed_by' => get_current_user_id(),
		),
		array( 'id' => $draft_id ),
		array( '%s', '%d', '%d', '%s', '%d' ),
		array( '%d' )
	);
	
	return $income_id;
}

/**
 * Get all billing drafts
 * @param string $status 'draft', 'billed', or ''
 * @return array Billing drafts
 */
function wpscrm_get_billing_drafts( $status = '' ) {
	global $wpdb;
	
	$billing_table = WPsCRM_TABLE . 'billing_drafts';
	$agents_table = WPsCRM_TABLE . 'agents';
	
	$where = "bd.status IS NOT NULL";
	if ( ! empty( $status ) ) {
		$where = $wpdb->prepare( "bd.status = %s", $status );
	}
	
	$query = "
		SELECT 
			bd.*,
			u.display_name,
			a.role_id
		FROM $billing_table bd
		LEFT JOIN $agents_table a ON bd.agent_id = a.id
		LEFT JOIN {$wpdb->users} u ON bd.user_id = u.ID
		WHERE {$where}
		ORDER BY bd.created_at DESC
	";
	
	return $wpdb->get_results( $query );
}
