<?php
/**
 * PS Smart CRM - Agent Dashboard Module
 * 
 * Haupt-Modul für Agent Dashboard
 * Zeigt Zeiterfassung, Aufgaben, Postfach, Profil
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_Agent_Dashboard extends WPsCRM_Module_Base {
	
	protected $module_id = 'agent-dashboard';
	protected $module_name = 'Agent Dashboard';
	protected $version = '1.0.0';
	
	/**
	 * Module Initialization
	 */
	protected function init() {
		// Load AJAX Handlers
		require_once $this->base_path . '/handlers.php';
		
		// Register AJAX actions
		add_action( 'wp_ajax_crm_agent_get_stats', 'wpscrm_agent_dashboard_get_stats' );
		add_action( 'wp_ajax_crm_agent_get_active_tracking', 'wpscrm_agent_dashboard_get_active_tracking' );
		add_action( 'wp_ajax_crm_agent_timetracking_toggle', 'wpscrm_agent_dashboard_timetracking_toggle' );
		add_action( 'wp_ajax_crm_toggle_task', 'wpscrm_agent_dashboard_toggle_task' );
	}
	
	/**
	 * Render Module
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render( $atts = array() ) {
		// Debug: Test ob render() aufgerufen wird
		error_log( 'Agent Dashboard render() called' );
		
		// Detect User
		$detector = new WPsCRM_User_Detector();
		$this->set_user_data( $detector->get_data() );
		
		// Load main view
		$output = $this->load_view( 'index', array(
			'user_type' => $detector->get_type(),
			'user_data' => $detector->get_data(),
		) );
		
		error_log( 'Agent Dashboard output length: ' . strlen( $output ) );
		return $output;
	}
}
