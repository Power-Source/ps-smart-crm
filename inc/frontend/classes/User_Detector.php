<?php
/**
 * PS Smart CRM - User Detector
 * 
 * Zentralisierte User-Typ-Erkennung
 * Bestimmt Benutzerrolle und Berechtigungen
 * 
 * @package PS-Smart-CRM
 * @subpackage Frontend
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_User_Detector {
	
	const TYPE_AGENT = 'agent';
	const TYPE_CUSTOMER = 'customer';
	const TYPE_GUEST = 'guest';
	
	/**
	 * Current User ID
	 * 
	 * @var int
	 */
	private $user_id = 0;
	
	/**
	 * Current User Object
	 * 
	 * @var WP_User|null
	 */
	private $user = null;
	
	/**
	 * Detected User Type
	 * 
	 * @var string
	 */
	private $user_type = '';
	
	/**
	 * User Data Cache
	 * 
	 * @var array
	 */
	private $user_data = array();
	
	/**
	 * Constructor
	 * 
	 * @param int|null $user_id Optional user ID (defaults to current user)
	 */
	public function __construct( $user_id = null ) {
		if ( null === $user_id ) {
			$this->user = wp_get_current_user();
			$this->user_id = $this->user->ID;
		} else {
			$this->user_id = $user_id;
			$this->user = get_user_by( 'ID', $user_id );
		}
		
		$this->detect_user_type();
		$this->load_user_data();
	}
	
	/**
	 * Detect User Type
	 */
	private function detect_user_type() {
		if ( ! is_user_logged_in() ) {
			$this->user_type = self::TYPE_GUEST;
			return;
		}
		
		// Check if agent
		if ( $this->is_agent() ) {
			$this->user_type = self::TYPE_AGENT;
			return;
		}
		
		// Check if customer
		if ( $this->is_customer() ) {
			$this->user_type = self::TYPE_CUSTOMER;
			return;
		}
		
		// Default to guest
		$this->user_type = self::TYPE_GUEST;
	}
	
	/**
	 * Check if User is Agent
	 * 
	 * @return bool
	 */
	public function is_agent() {
		if ( ! $this->user ) {
			return false;
		}
		
		// Agent ist wenn:
		// 1. wpscrm_is_user_agent() Funktion sagt true
		// 2. Oder kann 'manage_options' (Admin)
		
		if ( function_exists( 'wpscrm_is_user_agent' ) ) {
			if ( wpscrm_is_user_agent( $this->user_id ) ) {
				return true;
			}
		}
		
		return $this->user->has_cap( 'manage_options' );
	}
	
	/**
	 * Check if User is Customer
	 * 
	 * @return bool
	 */
	public function is_customer() {
		if ( ! $this->user ) {
			return false;
		}
		
		// Customer ist wenn Email in Kontakte gefunden wird
		if ( function_exists( 'wpscrm_get_customer_by_email' ) ) {
			$customer = wpscrm_get_customer_by_email( $this->user->user_email );
			return ! empty( $customer );
		}
		
		return false;
	}
	
	/**
	 * Load User Data
	 */
	private function load_user_data() {
		if ( ! $this->user ) {
			return;
		}
		
		$this->user_data = array(
			'ID' => $this->user_id,
			'email' => $this->user->user_email,
			'display_name' => $this->user->display_name,
			'first_name' => $this->user->first_name,
			'last_name' => $this->user->last_name,
			'type' => $this->user_type,
		);
		
		// Wenn Agent: Lade Agent-spezifische Daten
		if ( $this->user_type === self::TYPE_AGENT ) {
			$this->load_agent_data();
		}
		
		// Wenn Kunde: Lade Kunden-spezifische Daten
		if ( $this->user_type === self::TYPE_CUSTOMER ) {
			$this->load_customer_data();
		}
	}
	
	/**
	 * Load Agent Data from Database
	 */
	private function load_agent_data() {
		global $wpdb;
		
		$agents_table = WPsCRM_TABLE . 'agents';
		$agent = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $agents_table WHERE user_id = %d",
			$this->user_id
		) );
		
		if ( $agent ) {
			$this->user_data['agent'] = (array) $agent;
			$this->user_data['hourly_rate'] = $agent->hourly_rate ?? 0;
		}
	}
	
	/**
	 * Load Customer Data from Contacts
	 */
	private function load_customer_data() {
		if ( function_exists( 'wpscrm_get_customer_by_email' ) ) {
			$customer = wpscrm_get_customer_by_email( $this->user->user_email );
			if ( $customer ) {
				$this->user_data['customer'] = (array) $customer;
				$this->user_data['customer_id'] = $customer->ID ?? 0;
			}
		}
	}
	
	/**
	 * Get User Type
	 * 
	 * @return string (agent|customer|guest)
	 */
	public function get_type() {
		return $this->user_type;
	}
	
	/**
	 * Get User Data
	 * 
	 * @param string|null $key Optional key
	 * @return mixed
	 */
	public function get_data( $key = null ) {
		if ( null === $key ) {
			return $this->user_data;
		}
		return isset( $this->user_data[ $key ] ) ? $this->user_data[ $key ] : null;
	}
	
	/**
	 * Get User ID
	 * 
	 * @return int
	 */
	public function get_id() {
		return $this->user_id;
	}
	
	/**
	 * Check if User Type Matches
	 * 
	 * @param string|array $type Type or array of types
	 * @return bool
	 */
	public function is_type( $type ) {
		if ( is_array( $type ) ) {
			return in_array( $this->user_type, $type, true );
		}
		return $this->user_type === $type;
	}
}
