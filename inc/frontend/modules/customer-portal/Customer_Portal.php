<?php
/**
 * PS Smart Business - Customer Portal Module
 * 
 * Kundenportal mit Rechnungen, Angeboten, Postfach
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_Customer_Portal extends WPsCRM_Module_Base {
	
	protected $module_id = 'customer-portal';
	protected $module_name = 'Customer Portal';
	protected $version = '1.0.0';
	
	/**
	 * Module Initialization
	 */
	protected function init() {
		// Load AJAX Handlers
		require_once $this->base_path . '/handlers.php';
		
		// Register AJAX actions
		add_action( 'wp_ajax_crm_customer_get_invoices', 'wpscrm_customer_portal_get_invoices' );
		add_action( 'wp_ajax_nopriv_crm_customer_get_invoices', 'wpscrm_customer_portal_get_invoices' );
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
		return $this->load_view( 'index', array(
			'user_type' => $detector->get_type(),
			'user_data' => $detector->get_data(),
		) );
	}
}
