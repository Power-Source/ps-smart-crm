<?php
/**
 * PS Smart CRM - Frontend Manager
 * 
 * Orchestriert alle Frontend-Module
 * Verwaltet Shortcodes, Assets und Rendering
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_Frontend_Manager {
	
	private static $instance = null;
	private $modules = array();
	
	/**
	 * Singleton Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		$this->load_modules();
		$this->register_hooks();
	}
	
	/**
	 * Load All Modules
	 */
	private function load_modules() {
		// Agent Dashboard
		if ( class_exists( 'WPsCRM_Agent_Dashboard' ) ) {
			$this->modules['agent-dashboard'] = new WPsCRM_Agent_Dashboard();
		}
		
		// Customer Portal
		if ( class_exists( 'WPsCRM_Customer_Portal' ) ) {
			$this->modules['customer-portal'] = new WPsCRM_Customer_Portal();
		}
		
		// Guest View
		if ( class_exists( 'WPsCRM_Guest_View' ) ) {
			$this->modules['guest-view'] = new WPsCRM_Guest_View();
		}
	}
	
	/**
	 * Register Hooks
	 */
	private function register_hooks() {
		// Enqueue Assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		
		// Register Shortcodes
		add_action( 'init', array( $this, 'register_shortcodes' ), 20 );
		
		// AJAX
		add_action( 'wp_ajax_crm_create_frontend_page', array( $this, 'ajax_create_frontend_page' ) );
		add_action( 'wp_ajax_nopriv_crm_create_frontend_page', array( $this, 'ajax_create_frontend_page' ) );
	}
	
	/**
	 * Register Shortcodes
	 */
	public function register_shortcodes() {
		foreach ( $this->modules as $module ) {
			$shortcode = 'crm_' . $module->get_id();
			add_shortcode( $shortcode, array( $module, 'render' ) );
		}
	}
	
	/**
	 * Enqueue Frontend Assets
	 */
	public function enqueue_frontend_assets() {
		foreach ( $this->modules as $module ) {
			$module->enqueue_assets();
		}
	}
	
	/**
	 * Get Module by ID
	 * 
	 * @param string $module_id
	 * @return WPsCRM_Module_Base|null
	 */
	public function get_module( $module_id ) {
		return isset( $this->modules[ $module_id ] ) ? $this->modules[ $module_id ] : null;
	}
	
	/**
	 * Get All Modules
	 * 
	 * @return array
	 */
	public function get_modules() {
		return $this->modules;
	}
	
	/**
	 * AJAX: Create Frontend Page
	 */
	public function ajax_create_frontend_page() {
		check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Keine Berechtigung' ) );
		}
		
		$page_type = sanitize_text_field( $_POST['page_type'] ?? '' );
		$page_title = sanitize_text_field( $_POST['page_title'] ?? '' );
		
		if ( empty( $page_type ) || empty( $page_title ) ) {
			wp_send_json_error( array( 'message' => 'Fehlende Daten' ) );
		}
		
		// Bestimme Shortcode basierend auf Seiten-Typ
		$shortcode = '';
		switch ( $page_type ) {
			case 'agent-dashboard':
				$shortcode = '[crm_agent_dashboard]';
				break;
			case 'customer-portal':
				$shortcode = '[crm_customer_portal]';
				break;
			default:
				wp_send_json_error( array( 'message' => 'Unbekannter Seityp' ) );
		}
		
		// Erstelle WordPress-Seite
		$page_id = wp_insert_post( array(
			'post_type' => 'page',
			'post_title' => $page_title,
			'post_content' => $shortcode,
			'post_status' => 'publish',
		) );
		
		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( array( 'message' => $page_id->get_error_message() ) );
		}
		
		wp_send_json_success( array(
			'page_id' => $page_id,
			'edit_url' => admin_url( 'post.php?post=' . $page_id . '&action=edit' ),
			'view_url' => get_permalink( $page_id ),
		) );
	}
}

// Initialisiere Manager
add_action( 'plugins_loaded', function() {
	WPsCRM_Frontend_Manager::get_instance();
}, 15 );
