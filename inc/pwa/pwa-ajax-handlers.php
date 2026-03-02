<?php
/**
 * PWA AJAX Handlers
 * 
 * AJAX Endpunkte für PWA App-Mode Features:
 * - Ticket erstellen
 * - Ticket-Antwort hinzufügen
 * 
 * @package PS Smart CRM
 * @subpackage PWA
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPsCRM_PWA_Ajax_Handlers {
	
	/**
	 * Initialize hooks
	 */
	public static function init() {
		// AJAX für eingeloggte User
		add_action( 'wp_ajax_wpscrm_create_ticket', array( __CLASS__, 'ajax_create_ticket' ) );
		add_action( 'wp_ajax_wpscrm_add_ticket_reply', array( __CLASS__, 'ajax_add_ticket_reply' ) );
		add_action( 'wp_ajax_wpscrm_vote_faq', array( __CLASS__, 'ajax_vote_faq' ) );
		
		// FAQ Voting auch für nicht eingeloggte User (optional)
		add_action( 'wp_ajax_nopriv_wpscrm_vote_faq', array( __CLASS__, 'ajax_vote_faq' ) );
	}
	
	/**
	 * AJAX: Ticket erstellen
	 */
	public static function ajax_create_ticket() {
		// Security check
		check_ajax_referer( 'wpscrm_create_ticket', 'nonce' );
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Sie müssen angemeldet sein', 'cpsmartcrm' ) ) );
		}
		
		// Check Support Plugin verfügbar
		if ( ! function_exists( 'psource_support_insert_ticket' ) ) {
			wp_send_json_error( array( 'message' => __( 'Support-System nicht verfügbar', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		
		// Validierung
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$message = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';
		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$priority = isset( $_POST['ticket_priority'] ) ? absint( $_POST['ticket_priority'] ) : 1;
		
		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Betreff ist erforderlich', 'cpsmartcrm' ) ) );
		}
		
		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Nachricht ist erforderlich', 'cpsmartcrm' ) ) );
		}
		
		// Ticket erstellen
		$args = array(
			'title' => $title,
			'message' => $message,
			'user_id' => $user_id,
			'ticket_priority' => $priority,
			'ticket_status' => 0, // Offen
			'cat_id' => $category_id,
		);
		
		$ticket_id = psource_support_insert_ticket( $args );
		
		if ( $ticket_id ) {
			// Success
			wp_send_json_success( array(
				'message' => __( 'Ticket erfolgreich erstellt', 'cpsmartcrm' ),
				'ticket_id' => $ticket_id,
				'redirect_url' => add_query_arg( array( 
					'app' => '1', 
					'view' => 'support', 
					'ticket_id' => $ticket_id 
				), home_url( '/' ) ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Fehler beim Erstellen des Tickets', 'cpsmartcrm' ) ) );
		}
	}
	
	/**
	 * AJAX: Ticket-Antwort hinzufügen
	 */
	public static function ajax_add_ticket_reply() {
		// Security check
		check_ajax_referer( 'wpscrm_ticket_reply', 'nonce' );
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Sie müssen angemeldet sein', 'cpsmartcrm' ) ) );
		}
		
		// Check Support Plugin verfügbar
		if ( ! function_exists( 'psource_support_insert_ticket_reply' ) ) {
			wp_send_json_error( array( 'message' => __( 'Support-System nicht verfügbar', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$reply_body = isset( $_POST['reply_body'] ) ? wp_kses_post( wp_unslash( $_POST['reply_body'] ) ) : '';
		
		if ( ! $ticket_id ) {
			wp_send_json_error( array( 'message' => __( 'Ticket-ID fehlt', 'cpsmartcrm' ) ) );
		}
		
		if ( empty( $reply_body ) ) {
			wp_send_json_error( array( 'message' => __( 'Antwort darf nicht leer sein', 'cpsmartcrm' ) ) );
		}
		
		// Hole Ticket
		$ticket = function_exists( 'psource_support_get_ticket' ) ? psource_support_get_ticket( $ticket_id ) : false;
		
		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => __( 'Ticket nicht gefunden', 'cpsmartcrm' ) ) );
		}
		
		// Berechtigung prüfen (nur Ticket-Ersteller oder Agent)
		$is_ticket_owner = ( $ticket->user_id == $user_id );
		$is_agent = WPsCRM_Check::isAgent( $user_id );
		
		if ( ! $is_ticket_owner && ! $is_agent ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'cpsmartcrm' ) ) );
		}
		
		// Antwort hinzufügen
		$args = array(
			'poster_id' => $user_id,
			'message' => $reply_body,
		);
		
		$reply_id = psource_support_insert_ticket_reply( $ticket_id, $args );
		
		if ( $reply_id ) {
			// Ticket Status update (wenn geschlossen, reaktivieren)
			if ( in_array( $ticket->ticket_status, array( 4, 5 ) ) && function_exists( 'psource_support_open_ticket' ) ) {
				psource_support_open_ticket( $ticket_id );
			}
			
			wp_send_json_success( array(
				'message' => __( 'Antwort hinzugefügt', 'cpsmartcrm' ),
				'reply_id' => $reply_id,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Fehler beim Hinzufügen der Antwort', 'cpsmartcrm' ) ) );
		}
	}
	
	/**
	 * AJAX: FAQ Vote (Hilfreich Ja/Nein)
	 */
	public static function ajax_vote_faq() {
		// Security check
		check_ajax_referer( 'wpscrm_faq_vote', 'nonce' );
		
		// Check Support Plugin verfügbar
		if ( ! function_exists( 'psource_support_get_faq' ) || ! function_exists( 'psource_support_vote_faq' ) ) {
			wp_send_json_error( array( 'message' => __( 'Support-System nicht verfügbar', 'cpsmartcrm' ) ) );
		}
		
		$faq_id = isset( $_POST['faq_id'] ) ? absint( $_POST['faq_id'] ) : 0;
		$vote = isset( $_POST['vote'] ) ? sanitize_key( $_POST['vote'] ) : '';
		
		if ( ! $faq_id || ! in_array( $vote, array( 'yes', 'no' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Anfrage', 'cpsmartcrm' ) ) );
		}
		
		// Hole FAQ vor dem Update
		$faq = psource_support_get_faq( $faq_id );
		
		if ( ! $faq ) {
			wp_send_json_error( array( 'message' => __( 'FAQ nicht gefunden', 'cpsmartcrm' ) ) );
		}
		
		// Update Vote Count (true für yes, false für no)
		$vote_bool = ( $vote === 'yes' );
		$result = psource_support_vote_faq( $faq_id, $vote_bool );
		
		if ( $result ) {
			// Hole aktualisierte Counts
			$updated_faq = psource_support_get_faq( $faq_id );
			
			wp_send_json_success( array(
				'yes_count' => $updated_faq->help_yes,
				'no_count' => $updated_faq->help_no,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Fehler beim Speichern', 'cpsmartcrm' ) ) );
		}
	}
}

// Initialize
WPsCRM_PWA_Ajax_Handlers::init();
