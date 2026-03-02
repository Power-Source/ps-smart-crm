<?php
/**
 * CRM Newsletter Integration
 * Integriert Newsletter-Funktionalität in das ps-smart-crm Plugin
 * 
 *  @package ps-smart-crm
 * @since 1.6.5
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPsCRM_Newsletter_Integration {
	
	/**
	 * Prüft ob e-Newsletter Plugin aktiv ist
	 */
	public static function is_enewsletter_active() {
		return function_exists( 'enewsletter_crm_add_subscriber' );
	}
	
	/**
	 * Initialisiert die Integration
	 */
	public static function init() {
		if (!self::is_enewsletter_active()) {
			return;
		}
		
		// Hooks für Kunden-Operationen
		add_action( 'wpscrm_customer_added', array( __CLASS__, 'on_customer_added' ), 10, 2 );
		add_action( 'wpscrm_customer_updated', array( __CLASS__, 'on_customer_updated' ), 10, 2 );
		add_action( 'wpscrm_customer_deleted', array( __CLASS__, 'on_customer_deleted' ), 10, 1 );
		
		// API-Hooks für Newsletter-Events
		add_action( 'enewsletter_crm_subscriber_added', array( __CLASS__, 'on_subscriber_added' ), 10, 3 );
	}
	
	/**
	 * Trigger wenn Kunde hinzugefügt wird
	 * 
	 * @param int $customer_id - CRM Kunden-ID
	 * @param array $customer_data - Kundendaten
	 */
	public static function on_customer_added( $customer_id, $customer_data ) {
		if ( !self::is_enewsletter_active() ) return;
		
		// Nur weiterleiten wenn Email vorhanden
		if ( empty( $customer_data['email'] ) ) return;
		
		// Newsletter-Daten aus Customer Metadata holen
		$newsletter_meta = self::get_customer_newsletter_meta( $customer_id );
		
		// Standard-Gruppen hinzufügen
		$groups = (array) self::get_default_groups();
		
		if ( !empty( $newsletter_meta['groups'] ) ) {
			$groups = array_merge( $groups, (array) $newsletter_meta['groups'] );
		}
		
		// Als Newsletter-Subscriber hinzufügen
		$result = enewsletter_crm_add_subscriber(
			$customer_id,
			array_unique( $groups ),
			array(
				'firstname' => isset( $customer_data['firstname'] ) ? $customer_data['firstname'] : '',
				'lastname' => isset( $customer_data['lastname'] ) ? $customer_data['lastname'] : '',
				'email' => $customer_data['email']
			)
		);
		
		if ( $result['success'] ) {
			// Speichere Newsletter Member ID im Customer
			self::save_customer_newsletter_meta( $customer_id, array(
				'member_id' => $result['member_id'],
				'subscribed' => 1
			) );
			
			// Trigger Willkommens-Serie wenn konfiguriert
			$auto_series = self::get_auto_series_config( 'new_customer' );
			if ( $auto_series && $auto_series['enabled'] ) {
				enewsletter_crm_trigger_series(
					$customer_id,
					$auto_series['series_key'],
					array(
						'firstname' => isset( $customer_data['firstname'] ) ? $customer_data['firstname'] : '',
						'lastname' => isset( $customer_data['lastname'] ) ? $customer_data['lastname'] : '',
						'email' => $customer_data['email']
					)
				);
			}
		}
	}
	
	/**
	 * Trigger wenn Kunde aktualisiert wird
	 * 
	 * @param int $customer_id - CRM Kunden-ID
	 * @param array $customer_data - Kundendaten
	 */
	public static function on_customer_updated( $customer_id, $customer_data ) {
		if ( !self::is_enewsletter_active() ) return;
		
		if ( empty( $customer_data['email'] ) ) return;
		
		// Get existing subscriber
		$subscriber = enewsletter_crm_get_subscriber( $customer_id );
		
		if ( !$subscriber ) {
			// Wenn noch nicht registriert, jetzt hinzufügen
			self::on_customer_added( $customer_id, $customer_data );
			return;
		}
		
		// Newsletter-Meta vom Customer holen und Gruppen aktualisieren
		$newsletter_meta = self::get_customer_newsletter_meta( $customer_id );
		if ( !empty( $newsletter_meta['groups'] ) ) {
			enewsletter_crm_add_subscriber(
				$customer_id,
				array_unique( (array) $newsletter_meta['groups'] ),
				array(
					'firstname' => isset( $customer_data['firstname'] ) ? $customer_data['firstname'] : '',
					'lastname' => isset( $customer_data['lastname'] ) ? $customer_data['lastname'] : '',
					'email' => $customer_data['email']
				)
			);
		}
	}
	
	/**
	 * Trigger wenn Kunde gelöscht wird
	 * 
	 * @param int $customer_id - CRM Kunden-ID
	 */
	public static function on_customer_deleted( $customer_id ) {
		if ( !self::is_enewsletter_active() ) return;
		
		// Aus Newsletter entfernen (komplett)
		enewsletter_crm_remove_subscriber( $customer_id, -1 );
		
		// Metadata löschen
		delete_post_meta( $customer_id, '_newsletter_meta' );
	}
	
	/**
	 * Wird aufgerufen wenn Newsletter-Abonnent hinzugefügt wurde
	 */
	public static function on_subscriber_added( $member_id, $crm_customer_id, $member_data ) {
		// Hook für Custom Integrations
		do_action( 'wpscrm_newsletter_subscriber_added', $member_id, $crm_customer_id, $member_data );
	}
	
	/**
	 * Speichert Newsletter-Meta für Kunden
	 */
	public static function save_customer_newsletter_meta( $customer_id, $meta = array() ) {
		global $wpdb;
		
		if ( empty( $customer_id ) ) return false;
		
		$table = WPsCRM_SETUP_TABLE . 'kunde';
		
		// Existierende Meta laden
		$query = $wpdb->prepare(
			"SELECT custom_fields FROM $table WHERE ID_kunde = %d",
			intval( $customer_id )
		);
		$existing = $wpdb->get_var( $query );
		$fields = !empty( $existing ) ? (array) json_decode( $existing, true ) : array();
		
		// Newsletter-Meta zusammenmergen
		if ( !isset( $fields['_newsletter_meta'] ) ) {
			$fields['_newsletter_meta'] = array();
		}
		$fields['_newsletter_meta'] = array_merge( (array) $fields['_newsletter_meta'], $meta );
		
		// Speichern
		return $wpdb->update(
			$table,
			array( 'custom_fields' => wp_json_encode( $fields ) ),
			array( 'ID_kunde' => intval( $customer_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}
	
	/**
	 * Holt Newsletter-Meta vom Kunden
	 */
	public static function get_customer_newsletter_meta( $customer_id ) {
		global $wpdb;
		
		if ( empty( $customer_id ) ) return array();
		
		$table = WPsCRM_SETUP_TABLE . 'kunde';
		
		$query = $wpdb->prepare(
			"SELECT custom_fields FROM $table WHERE ID_kunde = %d",
			intval( $customer_id )
		);
		$fields = $wpdb->get_var( $query );
		$data = !empty( $fields ) ? (array) json_decode( $fields, true ) : array();
		
		return isset( $data['_newsletter_meta'] ) ? $data['_newsletter_meta'] : array();
	}
	
	/**
	 * Holt Standard-Newsletter-Gruppen
	 */
	public static function get_default_groups() {
		$groups = get_option( 'wpscrm_newsletter_default_groups', array() );
		return is_array( $groups ) ? $groups : array();
	}
	
	/**
	 * Speichert Standard-Newsletter-Gruppen
	 */
	public static function set_default_groups( $group_ids = array() ) {
		if ( !is_array( $group_ids ) ) {
			$group_ids = array( $group_ids );
		}
		return update_option( 'wpscrm_newsletter_default_groups', array_map( 'intval', $group_ids ) );
	}
	
	/**
	 * Holt Auto-Series Konfiguration
	 */
	public static function get_auto_series_config( $trigger_key = 'new_customer' ) {
		$series = get_option( 'wpscrm_newsletter_auto_series_' . $trigger_key, array() );
		return is_array( $series ) ? $series : array();
	}
	
	/**
	 * Speichert Auto-Series Konfiguration
	 */
	public static function set_auto_series_config( $trigger_key, $config = array() ) {
		return update_option( 'wpscrm_newsletter_auto_series_' . $trigger_key, $config );
	}
	
	/**
	 * Holt alle verfügbaren Newsletter-Gruppen
	 */
	public static function get_all_newsletter_groups() {
		if ( !function_exists( 'enewsletter_crm_get_all_groups' ) ) {
			return array();
		}
		return enewsletter_crm_get_all_groups();
	}
	
	/**
	 * Erstellt neue Newsletter-Gruppe
	 */
	public static function create_newsletter_group( $name, $public = 0 ) {
		if ( !function_exists( 'enewsletter_crm_create_group' ) ) {
			return false;
		}
		return enewsletter_crm_create_group( $name, $public );
	}
	
	/**
	 * Sendet Newsletter an CRM-Kunden-Gruppe basierend auf Rollen
	 */
	public static function send_to_crm_role( $newsletter_id, $role_slug, $user_ids = array() ) {
		if ( !function_exists( 'enewsletter_crm_send_to_groups' ) ) {
			return array( 'success' => false, 'message' => 'e-Newsletter nicht verfügbar' );
		}
		
		// Hole Customers basierend auf Agent/Role
		global $wpdb;
		$table = WPsCRM_SETUP_TABLE . 'kunde';
		
		// Wenn spezifische User IDs angegeben, nutze die
		if ( !empty( $user_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
			$query = $wpdb->prepare(
				"SELECT ID_kunde FROM $table WHERE user_id IN ($placeholders)",
				...$user_ids
			);
		} else {
			// Sonst all customers for this role
			$query = "SELECT ID_kunde FROM $table"; // TODO: Add role filtering
		}
		
		$customers = $wpdb->get_results( $query );
		$customer_ids = wp_list_pluck( $customers, 'ID_kunde' );
		
		if ( empty( $customer_ids ) ) {
			return array( 'success' => false, 'message' => 'Keine Kunden gefunden' );
		}
		
		// Create temporary group or get role-specific group
		$group_id = self::get_or_create_role_group( $role_slug );
		
		if ( !$group_id ) {
			return array( 'success' => false, 'message' => 'Konnte keine Gruppe für Rolle erstellen' );
		}
		
		// Send newsletter to group
		return enewsletter_crm_send_to_groups( $newsletter_id, array( 'group_ids' => array( $group_id ) ) );
	}
	
	/**
	 * Holt oder erstellt Gruppen-ID für CRM-Rolle
	 */
	private static function get_or_create_role_group( $role_slug ) {
		if ( !function_exists( 'enewsletter_crm_get_all_groups' ) ) {
			return false;
		}
		
		$groups = enewsletter_crm_get_all_groups();
		
		// Check if role group exists
		foreach ( $groups as $group ) {
			if ( strpos( $group['group_name'], 'CRM-Role: ' . $role_slug ) !== false ) {
				return $group['group_id'];
			}
		}
		
		// Create new group for role
		if ( function_exists( 'enewsletter_crm_create_group' ) ) {
			return enewsletter_crm_create_group( 'CRM-Role: ' . $role_slug, 0 );
		}
		
		return false;
	}
}

// Initialize when plugins are ready
add_action( 'plugins_loaded', array( 'WPsCRM_Newsletter_Integration', 'init' ) );

// Custom hooks for CRM operations
// These should be called in the CRM when relevant operations happen
if ( !function_exists( 'wpscrm_customer_added_hook' ) ) {
	function wpscrm_customer_added_hook( $customer_id, $customer_data ) {
		do_action( 'wpscrm_customer_added', $customer_id, $customer_data );
	}
}

if ( !function_exists( 'wpscrm_customer_updated_hook' ) ) {
	function wpscrm_customer_updated_hook( $customer_id, $customer_data ) {
		do_action( 'wpscrm_customer_updated', $customer_id, $customer_data );
	}
}

if ( !function_exists( 'wpscrm_customer_deleted_hook' ) ) {
	function wpscrm_customer_deleted_hook( $customer_id ) {
		do_action( 'wpscrm_customer_deleted', $customer_id );
	}
}
