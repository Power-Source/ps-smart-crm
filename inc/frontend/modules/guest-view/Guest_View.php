<?php
/**
 * PS Smart CRM - Guest View Module
 * 
 * Einfache Gast-Ansicht (nicht angemeldet)
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_Guest_View extends WPsCRM_Module_Base {
	
	protected $module_id = 'guest-view';
	protected $module_name = 'Guest View';
	protected $version = '1.0.0';
	
	/**
	 * Render Module
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render( $atts = array() ) {
		return $this->load_view( 'index' );
	}
}
