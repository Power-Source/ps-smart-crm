<?php
/**
 * PS Smart CRM - Frontend Settings Management
 * 
 * Verwaltet Frontend-Settings für Seitenauswahl
 * und Konfiguration
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_Frontend_Settings {
	
	private static $instance = null;
	
	const OPTION_KEY = 'crm_frontend_settings';
	
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
		// Settings registrieren
		$this->register_settings();
	}
	
	/**
	 * Register Settings
	 */
	private function register_settings() {
		register_setting(
			'crm-frontend-settings',
			self::OPTION_KEY,
			array(
				'type' => 'object',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest' => false,
			)
		);
	}
	
	/**
	 * Sanitize Settings
	 */
	public function sanitize_settings( $value ) {
		if ( ! is_array( $value ) ) {
			$value = array();
		}
		
		return array(
			'agent_dashboard_page' => isset( $value['agent_dashboard_page'] ) ? (int) $value['agent_dashboard_page'] : 0,
			'customer_portal_page' => isset( $value['customer_portal_page'] ) ? (int) $value['customer_portal_page'] : 0,
		);
	}
	
	/**
	 * Get Settings
	 */
	public static function get_option( $key = null ) {
		$options = get_option( self::OPTION_KEY, array() );
		
		if ( null === $key ) {
			return $options;
		}
		
		return isset( $options[ $key ] ) ? $options[ $key ] : null;
	}
	
	/**
	 * Update Settings
	 */
	public static function update_option( $key, $value ) {
		$options = get_option( self::OPTION_KEY, array() );
		$options[ $key ] = $value;
		update_option( self::OPTION_KEY, $options );
	}
	
	/**
	 * Get Page URL by Type
	 * 
	 * @param string $type 'agent-dashboard' or 'customer-portal'
	 * @return string|false
	 */
	public static function get_page_url( $type ) {
		$page_id = null;
		
		switch ( $type ) {
			case 'agent-dashboard':
				$page_id = self::get_option( 'agent_dashboard_page' );
				break;
			case 'customer-portal':
				$page_id = self::get_option( 'customer_portal_page' );
				break;
		}
		
		if ( empty( $page_id ) ) {
			return false;
		}
		
		return get_permalink( $page_id );
	}
	
	/**
	 * AJAX: Create Frontend Page
	 */
	public function ajax_create_page() {
		check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Keine Berechtigung' ) );
		}
		
		$page_type = sanitize_text_field( $_POST['page_type'] ?? '' );
		$page_title = sanitize_text_field( $_POST['page_title'] ?? '' );
		
		if ( empty( $page_type ) || empty( $page_title ) ) {
			wp_send_json_error( array( 'message' => 'Fehlende Daten' ) );
		}
		
		// Bestimme Shortcode basierend auf Typ
		$shortcode = '';
		$option_key = '';
		
		switch ( $page_type ) {
			case 'agent-dashboard':
				$shortcode = '[crm_agent_dashboard]';
				$option_key = 'agent_dashboard_page';
				break;
			case 'customer-portal':
				$shortcode = '[crm_customer_portal]';
				$option_key = 'customer_portal_page';
				break;
			default:
				wp_send_json_error( array( 'message' => 'Unbekannter Seitentyp' ) );
		}
		
		// Erstelle Seite
		$page_id = wp_insert_post( array(
			'post_type' => 'page',
			'post_title' => $page_title,
			'post_content' => $shortcode,
			'post_status' => 'publish',
		) );
		
		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( array( 'message' => $page_id->get_error_message() ) );
		}
		
		// Speichere in Settings
		self::update_option( $option_key, $page_id );
		
		wp_send_json_success( array(
			'page_id' => $page_id,
			'edit_url' => admin_url( 'post.php?post=' . $page_id . '&action=edit' ),
			'view_url' => get_permalink( $page_id ),
		) );
	}
}

// Initialize on plugins_loaded
add_action( 'plugins_loaded', function() {
	WPsCRM_Frontend_Settings::get_instance();
}, 15 );
