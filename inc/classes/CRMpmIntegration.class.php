<?php
/**
 * PS Smart CRM - Private Messaging Integration
 * 
 * Eigenständige PM-Integration für interne Agent-Kommunikation
 * unabhängig vom Terminmanager Plugin
 * 
 * @package PS-Smart-CRM
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_PM_Integration {
	
	private static $instance = null;
	
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
		// Hooks nur registrieren wenn PM aktiv ist
		if ( $this->is_pm_active() ) {
			add_action( 'init', array( $this, 'init_hooks' ), 20 );
		}
	}
	
	/**
	 * Init Hooks
	 */
	public function init_hooks() {
		// PM Message-Context für CRM erweitern
		add_filter( 'mm_suggest_users_args', array( $this, 'filter_pm_suggest_users' ) );
		add_filter( 'mm_before_send_message', array( $this, 'add_crm_context_to_message' ) );
	}
	
	/**
	 * Prüft ob Private-Messaging Plugin aktiv ist
	 */
	public function is_pm_active() {
		// Prüfe ob MMessaging Klasse/Plugin existiert
		if ( ! class_exists( 'MMessaging' ) ) {
			return false;
		}
		
		// Prüfe ob message_inbox Shortcode registriert ist
		global $shortcode_tags;
		return isset( $shortcode_tags['message_inbox'] );
	}
	
	/**
	 * Gibt URL zur PM-Inbox zurück
	 * 
	 * @param string $box inbox|sent|archive|setting
	 * @return string|false
	 */
	public function get_pm_inbox_url( $box = 'inbox' ) {
		if ( ! $this->is_pm_active() ) {
			return false;
		}

		// Suche Seite mit [message_inbox] Shortcode
		$pages = get_posts( array(
			'post_type' => 'page',
			'post_status' => 'publish',
			's' => '[message_inbox]',
			'posts_per_page' => 1,
		) );

		if ( empty( $pages ) ) {
			return false;
		}

		$url = get_permalink( $pages[0]->ID );
		if ( 'inbox' !== $box ) {
			$url = add_query_arg( 'box', $box, $url );
		}

		return $url;
	}
	
	/**
	 * Erstellt PM Contact-Button HTML für einen User
	 * 
	 * @param int $user_id Ziel User-ID
	 * @param string $text Button-Text
	 * @param string $subject Nachrichten-Betreff
	 * @param string $class CSS-Klassen
	 * @return string HTML oder leerer String
	 */
	public function render_pm_contact_button( $user_id, $text = '', $subject = '', $class = 'button' ) {
		if ( ! $this->is_pm_active() || ! $user_id ) {
			return '';
		}

		if ( empty( $text ) ) {
			$text = __( 'Nachricht senden', 'cpsmartcrm' );
		}

		return mm_display_contact_button( $user_id, $class, $text, $subject, false );
	}
	
	/**
	 * Holt alle Agents mit Agent-Rolle und deren Metadaten
	 * 
	 * @param bool $include_owner Chef/Owner einschließen
	 * @return array
	 */
	public function get_crm_agents_with_roles( $include_owner = true ) {
		$args = array(
			'role__in' => array( 'administrator' ),
			'meta_query' => array(
				array(
					'key' => 'wp_capabilities',
					'value' => 'manage_crm',
					'compare' => 'LIKE',
				),
			),
		);
		
		if ( ! $include_owner ) {
			$args['meta_query'][] = array(
				'key' => 'wp_capabilities',
				'value' => 'manage_options',
				'compare' => 'NOT LIKE',
			);
		}
		
		$users = get_users( $args );
		$agents = array();
		
		foreach ( $users as $user ) {
			$agent_role_slug = get_user_meta( $user->ID, '_crm_agent_role', true );
			$agent_role = $this->get_agent_role_by_slug( $agent_role_slug );
			
			$agents[] = array(
				'ID' => $user->ID,
				'display_name' => $user->display_name,
				'user_login' => $user->user_login,
				'email' => $user->user_email,
				'role_slug' => $agent_role_slug,
				'role_name' => $agent_role ? $agent_role->role_name : __( 'Nicht zugewiesen', 'cpsmartcrm' ),
				'display_name_role' => $agent_role ? $agent_role->display_name : '',
				'department' => $agent_role ? $agent_role->department : '',
				'icon' => $agent_role ? $agent_role->icon : 'dashicons-businessman',
				'is_owner' => user_can( $user->ID, 'manage_options' ),
			);
		}
		
		return $agents;
	}
	
	/**
	 * Holt Agent-Rolle nach Slug
	 */
	public function get_agent_role_by_slug( $slug ) {
		if ( empty( $slug ) ) {
			return null;
		}
		
		global $wpdb;
		$table = WPsCRM_TABLE . 'agent_roles';
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE role_slug = %s",
			$slug
		) );
	}
	
	/**
	 * Holt alle verfügbaren Agent-Rollen für Kontakt-Buttons
	 */
	public function get_contact_roles() {
		global $wpdb;
		$table = WPsCRM_TABLE . 'agent_roles';
		
		return $wpdb->get_results(
			"SELECT * FROM $table WHERE show_in_contact = 1 ORDER BY sort_order ASC"
		);
	}
	
	/**
	 * Holt Agents nach Rolle
	 */
	public function get_agents_by_role( $role_slug ) {
		$all_agents = $this->get_crm_agents_with_roles();
		
		return array_filter( $all_agents, function( $agent ) use ( $role_slug ) {
			return $agent['role_slug'] === $role_slug;
		} );
	}
	
	/**
	 * Erweitert PM User-Suggestion um CRM-Agents
	 */
	public function filter_pm_suggest_users( $args ) {
		// Agent-Rollen in Display-Namen einbeziehen
		$current_user = wp_get_current_user();
		
		// Nur für CRM-Agents relevant
		if ( ! user_can( $current_user, 'manage_crm' ) ) {
			return $args;
		}
		
		// Füge CRM-Agents zur Suggestion hinzu
		return $args;
	}
	
	/**
	 * Fügt CRM-Kontext zu PM-Nachrichten hinzu
	 */
	public function add_crm_context_to_message( $model ) {
		$current_user = wp_get_current_user();
		
		// Nur für CRM-Agents
		if ( ! user_can( $current_user, 'manage_crm' ) ) {
			return $model;
		}
		
		// CRM-Context in Subject einfügen wenn noch nicht vorhanden
		if ( ! empty( $model->subject ) && false === strpos( $model->subject, '[CRM]' ) ) {
			$agent_role_slug = get_user_meta( $current_user->ID, '_crm_agent_role', true );
			$agent_role = $this->get_agent_role_by_slug( $agent_role_slug );
			
			if ( $agent_role ) {
				$model->subject = sprintf(
					'[CRM: %s] %s',
					$agent_role->role_name,
					$model->subject
				);
			}
		}
		
		return $model;
	}
	
	/**
	 * Prüft ob User ein CRM-A]gent ist (manage_crm ohne manage_options)
	 */
	public function is_crm_agent( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		return user_can( $user_id, 'manage_crm' ) && ! user_can( $user_id, 'manage_options' );
	}
	
	/**
	 * Prüft ob User CRM-Owner ist (manage_options)
	 */
	public function is_crm_owner( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		return user_can( $user_id, 'manage_options' );
	}
	
	/**
	 * Holt zugewiesene Kunden eines Agents
	 */
	public function get_agent_customers( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		global $wpdb;
		$table = WPsCRM_TABLE . 'kunde';
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table WHERE agente = %d AND eliminato = 0 ORDER BY name ASC",
			$user_id
		) );
	}
}

/**
 * Helper Function: Get PM Integration Instance
 */
function wpscrm_pm() {
	return WPsCRM_PM_Integration::get_instance();
}
