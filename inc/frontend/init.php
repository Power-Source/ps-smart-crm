<?php
/**
 * PS Smart CRM - Frontend System Initialization (Modular)
 * 
 * Modulares Frontend System mit:
 * - Agent Dashboard (Zeiterfassung, Aufgaben, Postfach)
 * - Customer Portal (Rechnungen, Angebote, Postfach)
 * - Guest View (Login Screen)
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize Modular Frontend System
 */
function wpscrm_init_frontend_system() {
	// Load base classes
	require_once __DIR__ . '/classes/Module_Base.php';
	require_once __DIR__ . '/classes/User_Detector.php';
	require_once __DIR__ . '/classes/Frontend_Manager.php';
	require_once __DIR__ . '/classes/Frontend_Settings.php';
	
	// Load module classes
	require_once __DIR__ . '/modules/agent-dashboard/Agent_Dashboard.php';
	require_once __DIR__ . '/modules/customer-portal/Customer_Portal.php';
	require_once __DIR__ . '/modules/guest-view/Guest_View.php';
	
	// Initialize Frontend Manager (registers shortcodes and hooks)
	WPsCRM_Frontend_Manager::get_instance();
}

/**
 * Hook on plugins_loaded
 */
add_action( 'plugins_loaded', 'wpscrm_init_frontend_system', 5 ); // Früher laden (Priority 5 statt 10)

/**
 * Helper: Get customer by email
 * 
 * @param string $email Customer email
 * @return object|null Customer object or null
 */
function wpscrm_get_customer_by_email( $email ) {
	global $wpdb;
	$customer = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM " . WPsCRM_TABLE . "contacts WHERE email = %s AND deleted = 0 LIMIT 1",
		$email
	) );
	return ! empty( $customer ) ? $customer : null;
}

/**
 * Check if user is agent
 * 
 * @param int|null $user_id Optional user ID (defaults to current user)
 * @return bool
 */
if ( ! function_exists( 'wpscrm_is_user_agent' ) ) {
	function wpscrm_is_user_agent( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		global $wpdb;
		$agents_table = WPsCRM_TABLE . 'agents';
		
		// Check if agents table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
			DB_NAME,
			$agents_table
		) );
		
		if ( ! $table_exists ) {
			return false;
		}
		
		$agent = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM " . $agents_table . " WHERE user_id = %d",
			$user_id
		) );
		
		return ! empty( $agent );
	}
}

/**
 * Get Frontend Manager Instance
 * 
 * Hilfsfunktion um auf den Frontend Manager zuzugreifen
 * 
 * @return WPsCRM_Frontend_Manager
 */
function wpscrm_get_frontend_manager() {
	return WPsCRM_Frontend_Manager::get_instance();
}

/**
 * Load Frontend API Handlers (für Kompatibilität)
 */
if ( file_exists( __DIR__ . '/api/agent-api.php' ) ) {
	require_once __DIR__ . '/api/agent-api.php';
}
if ( file_exists( __DIR__ . '/api/customer-api.php' ) ) {
	require_once __DIR__ . '/api/customer-api.php';
}

