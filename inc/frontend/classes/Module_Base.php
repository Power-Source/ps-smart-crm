<?php
/**
 * PS Smart CRM - Frontend Module Base Class
 * 
 * Basisklasse für alle Frontend-Module
 * Stellt gemeinsame Funktionalität und Struktur bereit
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WPsCRM_Module_Base {
	
	/**
	 * Module Slug/ID
	 * 
	 * @var string
	 */
	protected $module_id = '';
	
	/**
	 * Module Name
	 * 
	 * @var string
	 */
	protected $module_name = '';
	
	/**
	 * Module Version
	 * 
	 * @var string
	 */
	protected $version = '1.0.0';
	
	/**
	 * Basis-Pfad für Module
	 * 
	 * @var string
	 */
	protected $base_path = '';
	
	/**
	 * User-Daten
	 * 
	 * @var array
	 */
	protected $user_data = array();
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setup_base_path();
		$this->init();
	}
	
	/**
	 * Setup Base Path
	 */
	private function setup_base_path() {
		$this->base_path = dirname( dirname( __FILE__ ) ) . '/modules/' . $this->module_id;
	}
	
	/**
	 * Module Initialization Hook
	 * Überschreiben Sie in Child-Klassen
	 */
	protected function init() {
		// Für Child-Klassen zum Überschreiben
	}
	
	/**
	 * Rendert das Modul
	 * 
	 * @param array $atts Shortcode-Attribute
	 * @return string HTML-Output
	 */
	abstract public function render( $atts = array() );
	
	/**
	 * Lädt eine View-Datei
	 * 
	 * @param string $view View-Datei (ohne .php)
	 * @param array $data Variablen für View
	 * @return string HTML-Output
	 */
	protected function load_view( $view, $data = array() ) {
		$view_path = $this->base_path . '/views/' . $view . '.php';
		
		if ( ! file_exists( $view_path ) ) {
			return '<p style="color: red;">Error: View not found: ' . esc_html( $view ) . '</p>';
		}
		
		// Variablen für View verfügbar machen
		extract( $data );
		
		// Output buffering
		ob_start();
		include $view_path;
		return ob_get_clean();
	}
	
	/**
	 * Enqueue Module Assets (CSS/JS)
	 * 
	 * @param string $hook Current admin page (optional)
	 */
	public function enqueue_assets( $hook = '' ) {
		$css_path = $this->base_path . '/assets/style.css';
		$js_path = $this->base_path . '/assets/script.js';
		
		// Basis-Plugin-Pfad
		$plugin_dir = dirname( dirname( dirname( __DIR__ ) ) ); // ps-smart-crm root
		$plugin_url = plugins_url( '', $plugin_dir . '/ps-smart-crm.php' );
		
		// CSS
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'crm-' . $this->module_id . '-style',
				$plugin_url . '/inc/frontend/modules/' . $this->module_id . '/assets/style.css',
				array(),
				WPSCRM_VERSION
			);
		}
		
		// JavaScript
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'crm-' . $this->module_id . '-script',
				$plugin_url . '/inc/frontend/modules/' . $this->module_id . '/assets/script.js',
				array( 'jquery' ),
				WPSCRM_VERSION,
				true
			);
			
			// Lokalisiere JS
			wp_localize_script(
				'crm-' . $this->module_id . '-script',
				'crmFrontend',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'crm_frontend_nonce' ),
				)
			);
		}
	}
	
	/**
	 * Gib den Modul-ID zurück
	 * 
	 * @return string
	 */
	public function get_id() {
		return $this->module_id;
	}
	
	/**
	 * Gib den Modul-Namen zurück
	 * 
	 * @return string
	 */
	public function get_name() {
		return $this->module_name;
	}
	
	/**
	 * Gib den Basis-Pfad zurück
	 * 
	 * @return string
	 */
	public function get_base_path() {
		return $this->base_path;
	}
	
	/**
	 * Set User Data
	 * 
	 * @param array $data
	 */
	public function set_user_data( $data = array() ) {
		$this->user_data = array_merge( $this->user_data, $data );
	}
	
	/**
	 * Get User Data
	 * 
	 * @param string $key Optional key
	 * @return mixed
	 */
	public function get_user_data( $key = null ) {
		if ( null === $key ) {
			return $this->user_data;
		}
		return isset( $this->user_data[ $key ] ) ? $this->user_data[ $key ] : null;
	}
}
