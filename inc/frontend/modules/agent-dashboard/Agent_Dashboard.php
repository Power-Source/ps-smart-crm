<?php
/**
 * PS Smart Business - Agent Dashboard Module
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
		
		// Register AJAX actions - Timetracking & Tasks
		add_action( 'wp_ajax_crm_agent_get_stats', 'wpscrm_agent_dashboard_get_stats' );
		add_action( 'wp_ajax_crm_agent_get_active_tracking', 'wpscrm_agent_dashboard_get_active_tracking' );
		add_action( 'wp_ajax_crm_agent_timetracking_toggle', 'wpscrm_agent_dashboard_timetracking_toggle' );
		add_action( 'wp_ajax_crm_toggle_task', 'wpscrm_agent_dashboard_toggle_task' );
		
		// Register AJAX actions - Kundenmanagement
		add_action( 'wp_ajax_crm_get_agent_customers', 'wpscrm_agent_dashboard_get_customers' );
		add_action( 'wp_ajax_crm_get_customer_data', 'wpscrm_agent_dashboard_get_customer_data' );
		add_action( 'wp_ajax_crm_frontend_save_customer', 'wpscrm_agent_dashboard_save_customer' );
		add_action( 'wp_ajax_crm_get_customer_documents', 'wpscrm_agent_dashboard_get_customer_documents' );
		add_action( 'wp_ajax_crm_get_customer_contacts', 'wpscrm_agent_dashboard_get_customer_contacts' );
		add_action( 'wp_ajax_crm_frontend_save_contact', 'wpscrm_agent_dashboard_save_contact' );
		
		// Enqueue scripts with proper localization
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_dashboard_scripts' ) );
	}
	
	/**
	 * Enqueue Dashboard Scripts with Localized Data
	 * Ensures crmAjax is properly defined for AJAX calls
	 */
	public function enqueue_dashboard_scripts() {
		// Get plugin root and URL
		$plugin_root = dirname( dirname( dirname( dirname( $this->base_path ) ) ) );
		$plugin_url = plugins_url( '', $plugin_root . '/ps-smart-crm.php' );
		$base_url = $plugin_url . '/inc/frontend/modules/' . $this->module_id;
		
		// Enqueue stylesheet
		wp_enqueue_style(
			'crm-agent-dashboard-style',
			$base_url . '/assets/style.css',
			array(),
			WPSCRM_VERSION
		);
		
		// Enqueue script
		wp_enqueue_script(
			'crm-agent-dashboard-script',
			$base_url . '/assets/script.js',
			array( 'jquery' ),
			WPSCRM_VERSION,
			true
		);
		
		// Localize script - Make crmAjax available globally with AJAX URL and nonce
		wp_localize_script(
			'crm-agent-dashboard-script',
			'crmAjax',
			array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'crm_customer_management' ),
			)
		);
	}
	
	/**
	 * Render Module
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render( $atts = array() ) {
		// Detect User
		$detector = new WPsCRM_User_Detector();
		$this->set_user_data( $detector->get_data() );
		
		// Load main view
		$output = $this->load_view( 'index', array(
			'user_type' => $detector->get_type(),
			'user_data' => $detector->get_data(),
		) );
		
		return $output;
	}
}
