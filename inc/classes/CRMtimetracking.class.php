<?php
/**
 * PS Smart CRM - Zeiterfassung System
 * 
 * Time-Tracking für Agents mit:
 * - Start/Stop/Pause Timer
 * - Kunden/Projekt-Zuordnung
 * - Stundensatz & Abrechnung
 * - Reports & Export
 * 
 * @package PS-Smart-CRM
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPsCRM_Timetracking {
	
	private static $instance = null;
	private $table_name;
	
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		global $wpdb;
		$this->table_name = WPsCRM_TABLE . 'timetracking';
		
		// AJAX Actions
		add_action( 'wp_ajax_wpscrm_start_timer', array( $this, 'ajax_start_timer' ) );
		add_action( 'wp_ajax_wpscrm_stop_timer', array( $this, 'ajax_stop_timer' ) );
		add_action( 'wp_ajax_wpscrm_pause_timer', array( $this, 'ajax_pause_timer' ) );
		add_action( 'wp_ajax_wpscrm_get_active_timer', array( $this, 'ajax_get_active_timer' ) );
		add_action( 'wp_ajax_wpscrm_update_timer_entry', array( $this, 'ajax_update_entry' ) );
		add_action( 'wp_ajax_wpscrm_delete_timer_entry', array( $this, 'ajax_delete_entry' ) );
		
		// Shortcode
		add_shortcode( 'crm_timer', array( $this, 'render_timer_widget' ) );
	}
	
	/**
	 * Startet neuen Timer
	 */
	public function start_timer( $user_id, $args = array() ) {
		global $wpdb;
		
		// Prüfe ob bereits ein laufender Timer existiert
		$existing = $this->get_active_timer( $user_id );
		if ( $existing ) {
			return new WP_Error( 'timer_running', __( 'Es läuft bereits ein Timer!', 'cpsmartcrm' ) );
		}
		
		$defaults = array(
			'fk_kunde' => null,
			'fk_agenda' => null,
			'project_name' => '',
			'task_description' => '',
			'hourly_rate' => 0,
			'is_billable' => 1,
			'location' => '',
			'tags' => '',
		);
		
		$data = wp_parse_args( $args, $defaults );
		
		$insert_data = array(
			'user_id' => $user_id,
			'fk_kunde' => $data['fk_kunde'],
			'fk_agenda' => $data['fk_agenda'],
			'project_name' => sanitize_text_field( $data['project_name'] ),
			'task_description' => sanitize_textarea_field( $data['task_description'] ),
			'start_time' => current_time( 'mysql' ),
			'hourly_rate' => floatval( $data['hourly_rate'] ),
			'is_billable' => intval( $data['is_billable'] ),
			'status' => 'running',
			'location' => sanitize_text_field( $data['location'] ),
			'tags' => sanitize_text_field( $data['tags'] ),
		);
		
		$result = $wpdb->insert( $this->table_name, $insert_data );
		
		if ( $result ) {
			return $wpdb->insert_id;
		}
		
		return new WP_Error( 'db_error', __( 'Timer konnte nicht gestartet werden.', 'cpsmartcrm' ) );
	}
	
	/**
	 * Stoppt laufenden Timer
	 */
	public function stop_timer( $user_id, $entry_id = null ) {
		global $wpdb;
		
		if ( ! $entry_id ) {
			$entry = $this->get_active_timer( $user_id );
			if ( ! $entry ) {
				return new WP_Error( 'no_timer', __( 'Kein aktiver Timer gefunden.', 'cpsmartcrm' ) );
			}
			$entry_id = $entry->id;
		}
		
		// Hole Entry
		$entry = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d AND user_id = %d",
			$entry_id,
			$user_id
		) );
		
		if ( ! $entry ) {
			return new WP_Error( 'not_found', __( 'Timer-Eintrag nicht gefunden.', 'cpsmartcrm' ) );
		}
		
		$now = current_time( 'timestamp' );
		$start = strtotime( $entry->start_time );
		$duration_minutes = round( ( $now - $start ) / 60 ) - intval( $entry->break_minutes );
		
		$total_amount = 0;
		if ( $entry->is_billable && $entry->hourly_rate > 0 ) {
			$hours = $duration_minutes / 60;
			$total_amount = $hours * $entry->hourly_rate;
		}
		
		$result = $wpdb->update(
			$this->table_name,
			array(
				'end_time' => current_time( 'mysql' ),
				'duration_minutes' => $duration_minutes,
				'total_amount' => $total_amount,
				'status' => 'completed',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $entry_id ),
			array( '%s', '%d', '%f', '%s', '%s' ),
			array( '%d' )
		);
		
		return $result !== false ? $entry_id : new WP_Error( 'update_failed', __( 'Timer konnte nicht gestoppt werden.', 'cpsmartcrm' ) );
	}
	
	/**
	 * Pausiert/Fortsetzt Timer (Toggle)
	 */
	public function pause_timer( $user_id, $entry_id, $break_minutes = 0 ) {
		global $wpdb;
		
		$entry = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d AND user_id = %d",
			$entry_id,
			$user_id
		) );
		
		if ( ! $entry ) {
			return new WP_Error( 'not_found', __( 'Timer-Eintrag nicht gefunden.', 'cpsmartcrm' ) );
		}
		
		$new_status = ( $entry->status === 'running' ) ? 'paused' : 'running';
		
		$update_data = array(
			'status' => $new_status,
			'updated_at' => current_time( 'mysql' ),
		);
		
		if ( $break_minutes > 0 ) {
			$update_data['break_minutes'] = intval( $entry->break_minutes ) + intval( $break_minutes );
		}
		
		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $entry_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		
		return $result !== false ? $new_status : new WP_Error( 'update_failed', __( 'Timer-Status konnte nicht geändert werden.', 'cpsmartcrm' ) );
	}
	
	/**
	 * Holt aktiven Timer für User
	 */
	public function get_active_timer( $user_id ) {
		global $wpdb;
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE user_id = %d AND status IN ('running', 'paused') ORDER BY start_time DESC LIMIT 1",
			$user_id
		) );
	}
	
	/**
	 * Holt Timer-Einträge für User
	 */
	public function get_user_entries( $user_id, $args = array() ) {
		global $wpdb;
		
		$defaults = array(
			'status' => 'all',
			'date_from' => null,
			'date_to' => null,
			'customer_id' => null,
			'is_billable' => null,
			'limit' => 50,
			'offset' => 0,
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$where = array( "user_id = %d", "deleted = 0" );
		$values = array( $user_id );
		
		if ( 'all' !== $args['status'] ) {
			$where[] = "status = %s";
			$values[] = $args['status'];
		}
		
		if ( $args['date_from'] ) {
			$where[] = "start_time >= %s";
			$values[] = $args['date_from'] . ' 00:00:00';
		}
		
		if ( $args['date_to'] ) {
			$where[] = "start_time <= %s";
			$values[] = $args['date_to'] . ' 23:59:59';
		}
		
		if ( $args['customer_id'] ) {
			$where[] = "fk_kunde = %d";
			$values[] = $args['customer_id'];
		}
		
		if ( null !== $args['is_billable'] ) {
			$where[] = "is_billable = %d";
			$values[] = $args['is_billable'];
		}
		
		$query = "SELECT * FROM {$this->table_name} WHERE " . implode( ' AND ', $where );
		$query .= " ORDER BY start_time DESC LIMIT %d OFFSET %d";
		$values[] = $args['limit'];
		$values[] = $args['offset'];
		
		return $wpdb->get_results( $wpdb->prepare( $query, $values ) );
	}
	
	/**
	 * AJAX: Start Timer
	 */
	public function ajax_start_timer() {
		check_ajax_referer( 'wpscrm_timer_nonce', 'nonce' );
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		
		$args = array(
			'fk_kunde' => isset( $_POST['customer_id'] ) ? intval( $_POST['customer_id'] ) : null,
			'project_name' => isset( $_POST['project_name'] ) ? sanitize_text_field( $_POST['project_name'] ) : '',
			'task_description' => isset( $_POST['task_description'] ) ? sanitize_textarea_field( $_POST['task_description'] ) : '',
			'hourly_rate' => isset( $_POST['hourly_rate'] ) ? floatval( $_POST['hourly_rate'] ) : 0,
			'is_billable' => isset( $_POST['is_billable'] ) ? 1 : 0,
		);
		
		$result = $this->start_timer( $user_id, $args );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array(
			'entry_id' => $result,
			'message' => __( 'Timer gestartet!', 'cpsmartcrm' ),
		) );
	}
	
	/**
	 * AJAX: Stop Timer
	 */
	public function ajax_stop_timer() {
		check_ajax_referer( 'wpscrm_timer_nonce', 'nonce' );
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		$entry_id = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : null;
		
		$result = $this->stop_timer( $user_id, $entry_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array(
			'entry_id' => $result,
			'message' => __( 'Timer gestoppt!', 'cpsmartcrm' ),
		) );
	}
	
	/**
	 * AJAX: Pause Timer
	 */
	public function ajax_pause_timer() {
		check_ajax_referer( 'wpscrm_timer_nonce', 'nonce' );
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		$entry_id = intval( $_POST['entry_id'] );
		
		$result = $this->pause_timer( $user_id, $entry_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array(
			'status' => $result,
			'message' => ( $result === 'paused' ) ? __( 'Timer pausiert', 'cpsmartcrm' ) : __( 'Timer fortgesetzt', 'cpsmartcrm' ),
		) );
	}
	
	/**
	 * AJAX: Get Active Timer
	 */
	public function ajax_get_active_timer() {
		check_ajax_referer( 'wpscrm_timer_nonce', 'nonce' );
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		$entry = $this->get_active_timer( $user_id );
		
		if ( ! $entry ) {
			wp_send_json_success( array( 'active' => false ) );
		}
		
		// Berechne Elapsed Time
		$start = strtotime( $entry->start_time );
		$now = current_time( 'timestamp' );
		$elapsed_seconds = $now - $start - ( $entry->break_minutes * 60 );
		
		wp_send_json_success( array(
			'active' => true,
			'entry' => $entry,
			'elapsed_seconds' => max( 0, $elapsed_seconds ),
		) );
	}
	
	/**
	 * AJAX: Update Entry
	 */
	public function ajax_update_entry() {
		check_ajax_referer( 'wpscrm_timer_nonce', 'nonce' );
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'cpsmartcrm' ) ) );
		}
		
		// Implementierung für manuelle Anpassungen
		wp_send_json_success( array( 'message' => __( 'Eintrag aktualisiert.', 'cpsmartcrm' ) ) );
	}
	
	/**
	 * AJAX: Delete Entry
	 */
	public function ajax_delete_entry() {
		check_ajax_referer( 'wpscrm_timer_nonce', 'nonce' );
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'cpsmartcrm' ) ) );
		}
		
		$entry_id = intval( $_POST['entry_id'] );
		$user_id = get_current_user_id();
		
		global $wpdb;
		$result = $wpdb->update(
			$this->table_name,
			array( 'deleted' => 1 ),
			array( 'id' => $entry_id, 'user_id' => $user_id ),
			array( '%d' ),
			array( '%d', '%d' )
		);
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Eintrag gelöscht.', 'cpsmartcrm' ) ) );
		}
		
		wp_send_json_error( array( 'message' => __( 'Löschen fehlgeschlagen.', 'cpsmartcrm' ) ) );
	}
	
	/**
	 * Shortcode: Timer Widget
	 */
	public function render_timer_widget( $atts ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_crm' ) ) {
			return '';
		}
		
		$atts = shortcode_atts( array(
			'show_history' => 'yes',
		), $atts );
		
		wp_enqueue_script( 'wpscrm-timer-widget', WPsCRM_URL . 'assets/js/timer-widget.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'wpscrm-timer-widget', 'wpsCRMTimer', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpscrm_timer_nonce' ),
			'strings' => array(
				'start' => __( 'Start', 'cpsmartcrm' ),
				'stop' => __( 'Stop', 'cpsmartcrm' ),
				'pause' => __( 'Pause', 'cpsmartcrm' ),
				'resume' => __( 'Fortsetzen', 'cpsmartcrm' ),
			),
		) );
		
		ob_start();
		include WPsCRM_DIR . '/inc/templates/timer-widget.php';
		return ob_get_clean();
	}
}

/**
 * Helper: Get Timetracking Instance
 */
function wpscrm_timetracking() {
	return WPsCRM_Timetracking::get_instance();
}

// Init
WPsCRM_Timetracking::get_instance();
