<?php
/**
 * PS Support System Integration für PS Smart Business
 * 
 * Integriert Support-Tickets in das CRM:
 * - Support-Tickets werden automatisch als TODOs im CRM-Planer angezeigt
 * - Tickets können CRM-Agenten zugewiesen werden
 * - Status-Synchronisation zwischen Support und CRM
 * - Verknüpfung von Tickets mit CRM-Kunden
 * 
 * @package PS Smart Business
 * @subpackage Support Integration
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPsCRM_Support_Integration {
	
	/**
	 * Initialize hooks
	 */
	public static function init() {
		// Prüfe ob Support-Plugin aktiv ist
		if ( ! self::is_support_active() ) {
			return;
		}
		
		// Support-Ticket Events
		add_action( 'support_ticket_created', array( __CLASS__, 'on_ticket_created' ), 10, 2 );
		add_action( 'support_ticket_assigned', array( __CLASS__, 'on_ticket_assigned' ), 10, 3 );
		add_action( 'support_ticket_status_changed', array( __CLASS__, 'on_ticket_status_changed' ), 10, 3 );
		add_action( 'support_ticket_updated', array( __CLASS__, 'on_ticket_updated' ), 10, 3 );
		add_action( 'support_ticket_reply_added', array( __CLASS__, 'on_ticket_reply_added' ), 10, 3 );
		
		// CRM Agenda Filter erweitern
		add_filter( 'wpscrm_scheduler_sources', array( __CLASS__, 'add_scheduler_source' ) );
		add_filter( 'wpscrm_scheduler_data', array( __CLASS__, 'add_support_tickets_to_scheduler' ), 10, 2 );
	}
	
	/**
	 * Prüfe ob Support-Plugin aktiv ist
	 */
	public static function is_support_active() {
		return class_exists( 'MU_Support_System' ) && class_exists( 'PSource_Support_Ticket' );
	}
	
	/**
	 * Wird aufgerufen wenn neues Ticket erstellt wird
	 * 
	 * @param int $ticket_id - Support Ticket ID
	 * @param object $ticket - Ticket Objekt
	 */
	public static function on_ticket_created( $ticket_id, $ticket ) {
		// Nur wenn Integration aktiviert ist
		if ( ! self::is_integration_enabled() ) return;

		if ( ! is_object( $ticket ) ) {
			$ticket = function_exists( 'psource_support_get_ticket' ) ? psource_support_get_ticket( $ticket_id ) : false;
		}

		if ( ! is_object( $ticket ) ) {
			return;
		}
		
		// Wenn bereits ein Admin zugewiesen ist, direkt TODO erstellen
		if ( ! empty( $ticket->admin_id ) ) {
			self::create_agenda_todo_from_ticket( $ticket_id, $ticket );
			
			// Push-Benachrichtigung an zugewiesenen Agent
			self::send_ticket_notification( $ticket->admin_id, 'new_assigned', $ticket_id, $ticket );
		}
	}
	
	/**
	 * Wird aufgerufen wenn Ticket einem Agent zugewiesen wird
	 * 
	 * @param int $ticket_id - Support Ticket ID
	 * @param int $agent_id - User ID des zugewiesenen Agenten
	 * @param object $ticket - Ticket Objekt
	 */
	public static function on_ticket_assigned( $ticket_id, $agent_id, $ticket ) {
		if ( ! self::is_integration_enabled() ) return;
		
		// Prüfe ob bereits ein TODO existiert
		$agenda_id = self::get_agenda_id_from_ticket( $ticket_id );
		
		if ( $agenda_id ) {
			// Update existierendes TODO
			self::update_agenda_todo( $agenda_id, $agent_id, $ticket );
		} else {
			// Erstelle neues TODO
			self::create_agenda_todo_from_ticket( $ticket_id, $ticket, $agent_id );
		}
		
		// Push-Benachrichtigung an Agent
		self::send_ticket_notification( $agent_id, 'assigned', $ticket_id, $ticket );
	}
	
	/**
	 * Wird aufgerufen wenn Ticket-Status sich ändert
	 * 
	 * @param int $ticket_id - Support Ticket ID
	 * @param int $old_status - Alter Status
	 * @param int $new_status - Neuer Status
	 */
	public static function on_ticket_status_changed( $ticket_id, $old_status, $new_status ) {
		if ( ! self::is_integration_enabled() ) return;
		
		$agenda_id = self::get_agenda_id_from_ticket( $ticket_id );
		
		if ( ! $agenda_id ) return;
		
		global $wpdb;
		$agenda_table = WPsCRM_TABLE . 'agenda';
		
		// Status-Mapping: Support → CRM
		// Support: 0=neu, 1=offen, 2=in Bearbeitung, 3=wartend, 4=gelöst, 5=geschlossen
		// CRM: 1=offen, 2=erledigt, 3=abgesagt
		$fatto_status = 1; // Standard: offen
		
		if ( in_array( $new_status, array( 4, 5 ) ) ) {
			// Gelöst oder geschlossen → erledigt
			$fatto_status = 2;
		} elseif ( $new_status == 3 ) {
			// Wartend → offen mit niedriger Priorität
			$fatto_status = 1;
		}
		
		$wpdb->update(
			$agenda_table,
			array( 'fatto' => $fatto_status ),
			array( 'id_agenda' => $agenda_id ),
			array( '%d' ),
			array( '%d' )
		);
	}
	
	/**
	 * Wird aufgerufen wenn Ticket aktualisiert wird
	 */
	public static function on_ticket_updated( $ticket_id, $args = array(), $old_ticket = null ) {
		if ( ! self::is_integration_enabled() ) return;

		$ticket = function_exists( 'psource_support_get_ticket' ) ? psource_support_get_ticket( $ticket_id ) : false;
		if ( ! is_object( $ticket ) ) {
			$ticket = $old_ticket;
		}

		if ( ! is_object( $ticket ) ) {
			return;
		}
		
		$agenda_id = self::get_agenda_id_from_ticket( $ticket_id );
		
		if ( ! $agenda_id ) return;
		
		global $wpdb;
		$agenda_table = WPsCRM_TABLE . 'agenda';
		
		$wpdb->update(
			$agenda_table,
			array(
				'oggetto' => 'Support: ' . wp_trim_words( $ticket->title, 10 ),
				'priorita' => self::map_priority( $ticket->ticket_priority )
			),
			array( 'id_agenda' => $agenda_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);
	}
	
	/**
	 * Wird aufgerufen wenn Antwort zu Ticket hinzugefügt wird
	 */
	public static function on_ticket_reply_added( $reply_id, $ticket_id, $reply_data ) {
		if ( ! self::is_integration_enabled() ) return;

		// Setze "visto" zurück damit Agent benachrichtigt wird
		$agenda_id = self::get_agenda_id_from_ticket( $ticket_id );
		
		if ( ! $agenda_id ) return;
		
		global $wpdb;
		$agenda_table = WPsCRM_TABLE . 'agenda';
		
		$wpdb->update(
			$agenda_table,
			array( 'visto' => null ),
			array( 'id_agenda' => $agenda_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		// Push-Benachrichtigung senden
		$ticket = function_exists( 'psource_support_get_ticket' ) ? psource_support_get_ticket( $ticket_id ) : false;
		if ( ! $ticket ) return;
		
		$reply_author_id = isset( $reply_data['author_id'] ) ? $reply_data['author_id'] : get_current_user_id();
		
		// Wenn Agent geantwortet hat: Benachrichtige Kunde
		if ( WPsCRM_Check::isAgent( $reply_author_id ) ) {
			self::send_ticket_notification( $ticket->user_id, 'reply_customer', $ticket_id, $ticket );
		} else {
			// Wenn Kunde geantwortet hat: Benachrichtige Agent
			if ( ! empty( $ticket->admin_id ) ) {
				self::send_ticket_notification( $ticket->admin_id, 'reply_agent', $ticket_id, $ticket );
			}
		}
	}
	
	/**
	 * Erstelle TODO in CRM Agenda aus Support-Ticket
	 * 
	 * @param int $ticket_id - Support Ticket ID
	 * @param object $ticket - Ticket Objekt
	 * @param int $agent_id - Optional: Agent ID (falls nicht in $ticket vorhanden)
	 * @return int|false - Agenda ID oder false bei Fehler
	 */
	private static function create_agenda_todo_from_ticket( $ticket_id, $ticket, $agent_id = null ) {
		global $wpdb;
		$agenda_table = WPsCRM_TABLE . 'agenda';
		
		if ( ! $agent_id ) {
			$agent_id = ! empty( $ticket->admin_id ) ? $ticket->admin_id : 0;
		}
		
		// Versuche Kunde zu finden basierend auf User
		$customer_id = self::find_customer_by_user_id( $ticket->user_id );
		
		// Ticket-Priorität zu CRM-Priorität mappen
		$priority = self::map_priority( $ticket->ticket_priority );
		
		// Erstelle Notiz mit Ticket-Informationen
		$notes = sprintf(
			"Support-Ticket #%d\nKategorie: %s\nStatus: %s\nErstellt: %s\n\nTicket-Link: %s",
			$ticket_id,
			$ticket->get_category_name() ?: __( 'Keine Kategorie', 'cpsmartcrm' ),
			self::get_status_label( $ticket->ticket_status ),
			$ticket->ticket_opened,
			self::get_ticket_admin_url( $ticket_id )
		);
		
		$result = $wpdb->insert(
			$agenda_table,
			array(
				'tipo_agenda' => 1, // TODO
				'fk_utenti_des' => $agent_id,
				'fk_kunde' => $customer_id ?: 0,
				'oggetto' => 'Support: ' . wp_trim_words( $ticket->title, 10 ),
				'annotazioni' => $notes,
				'priorita' => $priority,
				'fatto' => 1, // Offen
				'start_date' => $ticket->ticket_opened,
				'end_date' => date( 'Y-m-d H:i:s', strtotime( $ticket->ticket_opened . ' +7 days' ) ),
				'einstiegsdatum' => current_time( 'mysql' ),
				'visto' => null
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		
		if ( $result ) {
			$agenda_id = $wpdb->insert_id;
			
			// Speichere Verknüpfung in Support Ticket Meta
			self::save_ticket_agenda_mapping( $ticket_id, $agenda_id );
			
			return $agenda_id;
		}
		
		return false;
	}
	
	/**
	 * Update existierendes Agenda-TODO
	 */
	private static function update_agenda_todo( $agenda_id, $agent_id, $ticket ) {
		global $wpdb;
		$agenda_table = WPsCRM_TABLE . 'agenda';
		
		$customer_id = self::find_customer_by_user_id( $ticket->user_id );
		
		$wpdb->update(
			$agenda_table,
			array(
				'fk_utenti_des' => $agent_id,
				'fk_kunde' => $customer_id ?: 0,
				'oggetto' => 'Support: ' . wp_trim_words( $ticket->title, 10 ),
				'priorita' => self::map_priority( $ticket->ticket_priority )
			),
			array( 'id_agenda' => $agenda_id ),
			array( '%d', '%d', '%s', '%d' ),
			array( '%d' )
		);
	}
	
	/**
	 * Speichere Ticket → Agenda Mapping in ticketmeta
	 */
	private static function save_ticket_agenda_mapping( $ticket_id, $agenda_id ) {
		if ( function_exists( 'add_support_ticket_meta' ) ) {
			add_support_ticket_meta( $ticket_id, 'crm_agenda_id', $agenda_id, true );
		}
	}
	
	/**
	 * Hole Agenda ID aus Ticket
	 */
	public static function get_agenda_id_from_ticket( $ticket_id ) {
		if ( function_exists( 'get_support_ticket_meta' ) ) {
			return get_support_ticket_meta( $ticket_id, 'crm_agenda_id', true );
		}
		return false;
	}
	
	/**
	 * Finde CRM-Kunde basierend auf WordPress User ID
	 */
	private static function find_customer_by_user_id( $user_id ) {
		global $wpdb;
		$kunde_table = WPsCRM_TABLE . 'kunde';
		
		$customer_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID_kunde FROM $kunde_table WHERE user_id = %d AND eliminato = 0 LIMIT 1",
			$user_id
		) );
		
		return $customer_id;
	}
	
	/**
	 * Mappe Support-Priorität zu CRM-Priorität
	 */
	private static function map_priority( $support_priority ) {
		// Support: 0=niedrig, 1=normal, 2=hoch, 3=kritisch
		// CRM: 1=niedrig, 2=mittel, 3=hoch, 4=kritisch
		$mapping = array(
			0 => 1,
			1 => 2,
			2 => 3,
			3 => 4
		);
		
		return isset( $mapping[ $support_priority ] ) ? $mapping[ $support_priority ] : 2;
	}
	
	/**
	 * Hole Status-Label
	 */
	private static function get_status_label( $status ) {
		$labels = array(
			0 => __( 'Neu', 'cpsmartcrm' ),
			1 => __( 'Offen', 'cpsmartcrm' ),
			2 => __( 'In Bearbeitung', 'cpsmartcrm' ),
			3 => __( 'Wartend', 'cpsmartcrm' ),
			4 => __( 'Gelöst', 'cpsmartcrm' ),
			5 => __( 'Geschlossen', 'cpsmartcrm' )
		);
		
		return isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Unbekannt', 'cpsmartcrm' );
	}
	
	/**
	 * Generiere Ticket Admin URL
	 */
	private static function get_ticket_admin_url( $ticket_id ) {
		return admin_url( 'admin.php?page=support-tickets&tid=' . $ticket_id );
	}
	
	/**
	 * Prüfe ob Integration aktiviert ist
	 */
	public static function is_integration_enabled() {
		if ( ! self::is_support_active() ) {
			return false;
		}

		if ( ! self::is_crm_ready_on_current_site() ) {
			return false;
		}

		if ( ! is_multisite() ) {
			return true;
		}

		$target_blog_id = self::get_target_sync_blog_id();
		if ( ! $target_blog_id ) {
			return false;
		}

		return get_current_blog_id() === $target_blog_id;
	}

	/**
	 * Prüft, ob CRM auf aktueller Site betriebsbereit ist
	 */
	private static function is_crm_ready_on_current_site() {
		if ( ! defined( 'WPsCRM_TABLE' ) ) {
			return false;
		}

		global $wpdb;
		$agenda_table = WPsCRM_TABLE . 'agenda';
		$agenda_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $agenda_table ) );

		return $agenda_exists === $agenda_table;
	}

	/**
	 * Ziel-Site für CRM-Sync in Multisite bestimmen
	 * Priorität: Support-Override > Support-Front-Site > Main-Site
	 */
	private static function get_target_sync_blog_id() {
		$override_blog_id = 0;
		$support_blog_id = 0;

		if ( function_exists( 'psource_support_get_setting' ) ) {
			$override_blog_id = absint( psource_support_get_setting( 'psource_support_crm_sync_blog_id' ) );
			$support_blog_id = absint( psource_support_get_setting( 'psource_support_blog_id' ) );
		}

		if ( $override_blog_id && get_blog_details( $override_blog_id ) ) {
			return $override_blog_id;
		}

		if ( $support_blog_id && get_blog_details( $support_blog_id ) ) {
			return $support_blog_id;
		}

		if ( function_exists( 'get_main_site_id' ) ) {
			return (int) get_main_site_id();
		}

		return 1;
	}
	
	/**
	 * Füge Support-Tickets als Source im Scheduler hinzu
	 */
	public static function add_scheduler_source( $sources ) {
		$sources['support'] = __( 'Support-Tickets', 'cpsmartcrm' );
		return $sources;
	}
	
	/**
	 * Füge Support-Tickets zu Scheduler-Daten hinzu
	 */
	public static function add_support_tickets_to_scheduler( $data, $args ) {
		if ( ! self::is_integration_enabled() ) return $data;
		
		// Nur wenn Support-Filter aktiv ist
		if ( isset( $args['source'] ) && $args['source'] !== 'support' && $args['source'] !== 'all' ) {
			return $data;
		}
		
		global $wpdb;
		$tickets_table = $wpdb->base_prefix . 'support_tickets';
		$agenda_table = WPsCRM_TABLE . 'agenda';
		
		// Hole alle Tickets die als TODOs im CRM existieren
		$tickets = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, t.ticket_id, t.ticket_status, t.title as ticket_title
			FROM $agenda_table a
			INNER JOIN {$wpdb->base_prefix}support_ticketmeta tm ON tm.meta_value = a.id_agenda
			INNER JOIN $tickets_table t ON t.ticket_id = tm.support_ticket_id
			WHERE tm.meta_key = 'crm_agenda_id'
			AND a.tipo_agenda = 1
			AND a.eliminato = 0
			%s
			ORDER BY a.start_date DESC",
			isset( $args['user_id'] ) ? $wpdb->prepare( " AND a.fk_utenti_des = %d", $args['user_id'] ) : ""
		) );
		
		// Erweitere mit Support-spezifischen Informationen
		foreach ( $tickets as &$ticket ) {
			$ticket->source = 'support';
			$ticket->source_icon = 'dashicons-sos';
			$ticket->source_label = __( 'Support-Ticket', 'cpsmartcrm' );
		}
		
		return array_merge( $data, $tickets );
	}
	
	/**
	 * Sende Push-Benachrichtigung für Ticket-Event
	 * 
	 * @param int $user_id - User ID des Empfängers
	 * @param string $event_type - Art des Events (assigned, reply_customer, reply_agent, status_changed)
	 * @param int $ticket_id - Ticket ID
	 * @param object $ticket - Ticket Object
	 */
	private static function send_ticket_notification( $user_id, $event_type, $ticket_id, $ticket ) {
		// Prüfe ob PWA Manager aktiv ist
		if ( ! class_exists( 'WPsCRM_PWA_Manager' ) ) {
			return;
		}
		
		// Notification Daten basierend auf Event-Type
		$notifications = array(
			'new_assigned' => array(
				'event' => 'assignments',
				'title' => __( 'Neues Support-Ticket', 'cpsmartcrm' ),
				'body' => sprintf( __( 'Ticket #%d wurde Ihnen zugewiesen: %s', 'cpsmartcrm' ), $ticket_id, wp_trim_words( $ticket->title, 10 ) ),
				'icon' => 'dashicons-sos',
			),
			'assigned' => array(
				'event' => 'assignments',
				'title' => __( 'Ticket zugewiesen', 'cpsmartcrm' ),
				'body' => sprintf( __( 'Ticket #%d: %s', 'cpsmartcrm' ), $ticket_id, wp_trim_words( $ticket->title, 10 ) ),
				'icon' => 'dashicons-admin-users',
			),
			'reply_customer' => array(
				'event' => 'replies',
				'title' => __( 'Neue Antwort zu Ihrem Ticket', 'cpsmartcrm' ),
				'body' => sprintf( __( 'Ticket #%d: %s - Ein Agent hat geantwortet', 'cpsmartcrm' ), $ticket_id, wp_trim_words( $ticket->title, 8 ) ),
				'icon' => 'dashicons-email',
			),
			'reply_agent' => array(
				'event' => 'replies',
				'title' => __( 'Neue Kundenantwort', 'cpsmartcrm' ),
				'body' => sprintf( __( 'Ticket #%d: %s', 'cpsmartcrm' ), $ticket_id, wp_trim_words( $ticket->title, 10 ) ),
				'icon' => 'dashicons-email-alt',
			),
			'status_changed' => array(
				'event' => 'status',
				'title' => __( 'Ticket-Status aktualisiert', 'cpsmartcrm' ),
				'body' => sprintf( __( 'Ticket #%d: Status geändert', 'cpsmartcrm' ), $ticket_id ),
				'icon' => 'dashicons-info',
			),
		);
		
		$notification_data = isset( $notifications[ $event_type ] ) ? $notifications[ $event_type ] : $notifications['assigned'];
		
		// Icon URL
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$icon_url = $plugin_url . 'assets/img/icon-192.png';
		
		// App-URL für dieses Ticket
		$ticket_url = add_query_arg( array(
			'app' => '1',
			'view' => 'support',
			'ticket_id' => $ticket_id,
		), home_url( '/' ) );
		
		// Push senden
		WPsCRM_PWA_Manager::send_push_notification( $user_id, array(
			'event' => isset( $notification_data['event'] ) ? $notification_data['event'] : '',
			'title' => $notification_data['title'],
			'body' => $notification_data['body'],
			'icon' => $icon_url,
			'badge' => $icon_url,
			'tag' => 'ticket-' . $ticket_id,
			'requireInteraction' => false,
			'data' => array(
				'url' => $ticket_url,
				'ticket_id' => $ticket_id,
			),
		) );
	}
	
}

// Initialize
add_action( 'plugins_loaded', array( 'WPsCRM_Support_Integration', 'init' ), 20 );
