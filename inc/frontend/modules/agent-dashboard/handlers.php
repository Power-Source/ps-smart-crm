<?php
/**
 * Agent Dashboard - AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get Agent Statistics (AJAX)
 */
function wpscrm_agent_dashboard_get_stats() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    
    // Get current month timetracking
    $timings_table = WPsCRM_TABLE . 'timetracking';
    $current_month_start = date( 'Y-m-01' );
    
    $result = $wpdb->get_row( $wpdb->prepare(
        "SELECT 
            SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))) as total_seconds,
            COUNT(*) as entry_count
        FROM {$timings_table}
        WHERE user_id = %d 
        AND start_time >= %s
        AND start_time < DATE_ADD(%s, INTERVAL 1 MONTH)",
        $user_id,
        $current_month_start,
        $current_month_start
    ) );
    
    $total_seconds = (int) ( $result->total_seconds ?? 0 );
    $total_hours = round( $total_seconds / 3600, 2 );
    
    // Get hourly rate
    $agents_table = WPsCRM_TABLE . 'agents';
    $agent = $wpdb->get_row( $wpdb->prepare(
        "SELECT hourly_rate FROM {$agents_table} WHERE user_id = %d",
        $user_id
    ) );
    
    $hourly_rate = (float) ( $agent->hourly_rate ?? 0 );
    $total_earnings = round( $total_hours * $hourly_rate, 2 );
    
    wp_send_json_success( array(
        'total_hours' => number_format( $total_hours, 2, ',', '.' ),
        'total_earnings' => number_format( $total_earnings, 2, ',', '.' ),
        'entry_count' => (int) $result->entry_count,
    ) );
}

/**
 * Get Active Timetracking (AJAX)
 */
function wpscrm_agent_dashboard_get_active_tracking() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    $timings_table = WPsCRM_TABLE . 'timetracking';
    
    // Find active tracking (no end_time)
    $active = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, start_time, project_id, task_id
        FROM {$timings_table}
        WHERE user_id = %d
        AND end_time IS NULL
        ORDER BY start_time DESC
        LIMIT 1",
        $user_id
    ) );
    
    if ( ! $active ) {
        wp_send_json_success( array() );
    }
    
    wp_send_json_success( array(
        'tracking_id' => $active->id,
        'start_time' => $active->start_time,
        'project_id' => $active->project_id,
        'task_id' => $active->task_id,
    ) );
}

/**
 * Toggle Timetracking (Start/Stop) - AJAX
 */
function wpscrm_agent_dashboard_timetracking_toggle() {
    check_ajax_referer( 'crm_frontend_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $action_type = sanitize_text_field( $_POST['action_type'] ?? '' );
    
    global $wpdb;
    $timings_table = WPsCRM_TABLE . 'timetracking';
    
    if ( 'start' === $action_type ) {
        
        // Create new tracking
        $result = $wpdb->insert(
            $timings_table,
            array(
                'user_id' => $user_id,
                'project_id' => (int) ( $_POST['project_id'] ?? 0 ),
                'task_id' => (int) ( $_POST['task_id'] ?? 0 ),
                'start_time' => current_time( 'mysql' ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s', '%s' )
        );
        
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Starten' ) );
        }
        
        wp_send_json_success( array(
            'tracking_id' => $wpdb->insert_id,
            'start_time' => current_time( 'mysql' ),
        ) );
        
    } elseif ( 'stop' === $action_type ) {
        
        $tracking_id = (int) ( $_POST['tracking_id'] ?? 0 );
        
        $result = $wpdb->update(
            $timings_table,
            array( 'end_time' => current_time( 'mysql' ) ),
            array( 'id' => $tracking_id, 'user_id' => $user_id ),
            array( '%s' ),
            array( '%d', '%d' )
        );
        
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Stoppen' ) );
        }
        
        wp_send_json_success( array( 'tracking_id' => $tracking_id ) );
        
    } else {
        wp_send_json_error( array( 'message' => 'Ungültige Aktion' ) );
    }
}

/**
 * Toggle Task Status (AJAX)
 */
function wpscrm_agent_dashboard_toggle_task() {
    check_ajax_referer( 'crm_task_toggle', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $task_id = intval( $_POST['task_id'] ?? 0 );
    $fatto = intval( $_POST['fatto'] ?? 1 );
    
    if ( ! $task_id ) {
        wp_send_json_error( array( 'message' => 'Ungültige Aufgabe' ) );
    }
    
    global $wpdb;
    $agenda_table = WPsCRM_TABLE . 'agenda';
    
    // Prüfe ob Task dem User gehört
    $task = $wpdb->get_row( $wpdb->prepare(
        "SELECT id_agenda FROM {$agenda_table} WHERE id_agenda = %d AND fk_utenti_des = %d",
        $task_id,
        $user_id
    ) );
    
    if ( ! $task ) {
        wp_send_json_error( array( 'message' => 'Aufgabe nicht gefunden' ) );
    }
    
    // Update Status
    $result = $wpdb->update(
        $agenda_table,
        array( 'fatto' => $fatto ),
        array( 'id_agenda' => $task_id ),
        array( '%d' ),
        array( '%d' )
    );
    
    if ( $result === false ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Aktualisieren' ) );
    }
    
    wp_send_json_success( array(
        'message' => 'Aufgabe aktualisiert',
        'task_id' => $task_id,
        'fatto' => $fatto
    ) );
}

/**
 * =====================================================
 * KUNDENMANAGEMENT - AJAX HANDLERS
 * =====================================================
 */

/**
 * Get Agent's Customers (AJAX)
 * Smarte Suche:
 * - OHNE Suchbegriff: Nur Agent-Kunden
 * - MIT Suchbegriff: Alle Kunden (Agent-Kunden priorisiert)
 */
function wpscrm_agent_dashboard_get_customers() {
    check_ajax_referer( 'crm_customer_management', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $search = sanitize_text_field( $_POST['search'] ?? '' );
    
    global $wpdb;
    $kunde_table = WPsCRM_TABLE . 'kunde';
    
    // SMARTE LOGIK: Verhalten ändert sich je nach Suchbegriff
    if ( ! empty( $search ) ) {
        // MIT SUCHBEGRIFF: Alle Kunden durchsuchen, aber Agent-Kunden priorisieren
        $search_like = '%' . $wpdb->esc_like( $search ) . '%';
        $where = $wpdb->prepare( 
            "WHERE eliminato = 0 AND (firmenname LIKE %s OR name LIKE %s OR nachname LIKE %s OR email LIKE %s OR telefono1 LIKE %s)",
            $search_like, $search_like, $search_like, $search_like, $search_like
        );
        // ORDER BY: Agent-Kunden zuerst (WHEN agente = 0), dann nach Name
        $order_by = $wpdb->prepare(
            "ORDER BY CASE WHEN agente = %d THEN 0 ELSE 1 END, firmenname ASC, CONCAT(name, ' ', nachname) ASC",
            $user_id
        );
    } else {
        // OHNE SUCHBEGRIFF: Nur Agent-Kunden anzeigen
        $where = $wpdb->prepare( "WHERE agente = %d AND eliminato = 0", $user_id );
        $order_by = "ORDER BY CASE WHEN firmenname != '' THEN firmenname ELSE CONCAT(name, ' ', nachname) END ASC";
    }
    
    $sql = "SELECT 
                ID_kunde,
                firmenname,
                name,
                nachname,
                email,
                telefono1,
                telefono2,
                adresse,
                cap,
                standort,
                provinz,
                agente
            FROM {$kunde_table}
            {$where}
            {$order_by}
            LIMIT 100";
    
    $customers = $wpdb->get_results( $sql, ARRAY_A );
    
    wp_send_json_success( array( 'customers' => $customers ) );
}

/**
 * Get Single Customer Data (AJAX)
 * Lädt vollständige Daten eines Kunden zum Bearbeiten
 */
function wpscrm_agent_dashboard_get_customer_data() {
    check_ajax_referer( 'crm_customer_management', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $customer_id = intval( $_POST['customer_id'] ?? 0 );
    
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Ungültige Kunden-ID' ) );
    }
    
    global $wpdb;
    $kunde_table = WPsCRM_TABLE . 'kunde';
    
    // Prüfe ob Kunde dem Agent gehört
    $customer = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ), ARRAY_A );
    
    if ( ! $customer ) {
        wp_send_json_error( array( 'message' => 'Kunde nicht gefunden oder keinen Zugriff' ) );
    }
    
    // Formatiere Datumsfelder für Frontend (dd.mm.yyyy)
    if ( ! empty( $customer['einstiegsdatum'] ) && $customer['einstiegsdatum'] != '0000-00-00' ) {
        $customer['einstiegsdatum'] = date( 'd.m.Y', strtotime( $customer['einstiegsdatum'] ) );
    } else {
        $customer['einstiegsdatum'] = '';
    }
    
    if ( ! empty( $customer['geburtsdatum'] ) && $customer['geburtsdatum'] != '0000-00-00' ) {
        $customer['geburtsdatum'] = date( 'd.m.Y', strtotime( $customer['geburtsdatum'] ) );
    } else {
        $customer['geburtsdatum'] = '';
    }
    
    wp_send_json_success( $customer );
}

/**
 * Save Customer (AJAX)
 * Speichert neuen Kunden oder aktualisiert bestehenden
 */
function wpscrm_agent_dashboard_save_customer() {
    check_ajax_referer( 'crm_customer_management', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $customer_id = intval( $_POST['ID'] ?? 0 );
    
    // Validierung: Email ist Pflichtfeld
    $email = sanitize_email( $_POST['email'] ?? '' );
    if ( empty( $email ) || ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Gültige Email-Adresse ist erforderlich' ) );
    }
    
    // CustomerData sammeln
    $data = array(
        'firmenname'        => sanitize_text_field( $_POST['firmenname'] ?? '' ),
        'name'              => sanitize_text_field( $_POST['name'] ?? '' ),
        'nachname'          => sanitize_text_field( $_POST['nachname'] ?? '' ),
        'email'             => $email,
        'telefono1'         => sanitize_text_field( $_POST['telefono1'] ?? '' ),
        'telefono2'         => sanitize_text_field( $_POST['telefono2'] ?? '' ),
        'fax'               => sanitize_text_field( $_POST['fax'] ?? '' ),
        'adresse'           => sanitize_text_field( $_POST['adresse'] ?? '' ),
        'cap'               => sanitize_text_field( $_POST['cap'] ?? '' ),
        'standort'          => sanitize_text_field( $_POST['standort'] ?? '' ),
        'provinz'           => sanitize_text_field( $_POST['provinz'] ?? '' ),
        'nation'            => sanitize_text_field( $_POST['nation'] ?? 'DE' ),
        'cod_fis'           => sanitize_text_field( $_POST['cod_fis'] ?? '' ),
        'p_iva'             => sanitize_text_field( $_POST['p_iva'] ?? '' ),
        'sitoweb'           => esc_url_raw( $_POST['sitoweb'] ?? '' ),
        'skype'             => sanitize_text_field( $_POST['skype'] ?? '' ),
        'luogo_nascita'     => sanitize_text_field( $_POST['luogo_nascita'] ?? '' ),
        'tipo_cliente'      => intval( $_POST['tipo_cliente'] ?? 1 ),
        'abrechnungsmodus'  => intval( $_POST['abrechnungsmodus'] ?? 0 ),
        'categoria'         => sanitize_text_field( $_POST['customerCategory'] ?? '' ),
        'interessi'         => sanitize_text_field( $_POST['customerInterests'] ?? '' ),
        'provenienza'       => sanitize_text_field( $_POST['customerComesfrom'] ?? '' ),
        'agente'            => $user_id, // Automatisch auf aktuellen User setzen
        'data_modifica'     => current_time( 'mysql' ),
    );
    
    // Datumsfelder konvertieren (dd.mm.yyyy -> yyyy-mm-dd)
    $einstiegsdatum = sanitize_text_field( $_POST['einstiegsdatum'] ?? '' );
    if ( ! empty( $einstiegsdatum ) ) {
        $parts = explode( '.', $einstiegsdatum );
        if ( count( $parts ) === 3 ) {
            $data['einstiegsdatum'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    
    $geburtsdatum = sanitize_text_field( $_POST['geburtsdatum'] ?? '' );
    if ( ! empty( $geburtsdatum ) ) {
        $parts = explode( '.', $geburtsdatum );
        if ( count( $parts ) === 3 ) {
            $data['geburtsdatum'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    
    global $wpdb;
    $kunde_table = WPsCRM_TABLE . 'kunde';
    
    if ( $customer_id > 0 ) {
        // UPDATE: Prüfe ob Kunde dem Agent gehört
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d",
            $customer_id,
            $user_id
        ) );
        
        if ( ! $existing ) {
            wp_send_json_error( array( 'message' => 'Kunde nicht gefunden oder kein Zugriff' ) );
        }
        
        $result = $wpdb->update(
            $kunde_table,
            $data,
            array( 'ID_kunde' => $customer_id ),
            null,
            array( '%d' )
        );
        
        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Aktualisieren: ' . $wpdb->last_error ) );
        }
        
        wp_send_json_success( array(
            'message' => 'Kunde erfolgreich aktualisiert',
            'customer_id' => $customer_id
        ) );
        
    } else {
        // INSERT: Neuer Kunde
        $data['FK_aziende'] = 1; // Standard
        $data['einstiegsdatum'] = $data['einstiegsdatum'] ?? current_time( 'mysql' );
        
        $result = $wpdb->insert( $kunde_table, $data );
        
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Anlegen: ' . $wpdb->last_error ) );
        }
        
        $new_customer_id = $wpdb->insert_id;
        
        wp_send_json_success( array(
            'message' => 'Kunde erfolgreich angelegt',
            'customer_id' => $new_customer_id
        ) );
    }
}

/**
 * Get Customer Documents (AJAX)
 * Lädt alle Dokumente (Angebote, Rechnungen, Proforma) eines Kunden
 */
function wpscrm_agent_dashboard_get_customer_documents() {
    check_ajax_referer( 'crm_customer_management', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $customer_id = intval( $_POST['customer_id'] ?? 0 );
    
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Ungültige Kunden-ID' ) );
    }
    
    global $wpdb;
    $kunde_table = WPsCRM_TABLE . 'kunde';
    $dokumente_table = WPsCRM_TABLE . 'documenti';
    
    // Prüfe ob Kunde dem Agent gehört
    $customer_check = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ) );
    
    if ( ! $customer_check ) {
        wp_send_json_error( array( 'message' => 'Kein Zugriff auf diesen Kunden' ) );
    }
    
    // Prüfe ob Tabelle existiert
    $table_exists = $wpdb->get_var( 
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$dokumente_table}' LIMIT 1"
    );
    
    if ( ! $table_exists ) {
        wp_send_json_success( array(
            'quotations' => array(),
            'invoices' => array(),
            'proforma' => array()
        ) );
    }
    
    // Lade alle Dokumente des Kunden
    $sql = $wpdb->prepare(
        "SELECT 
            id,
            tipo,
            progressivo,
            einstiegsdatum,
            data,
            oggetto,
            riferimento,
            totale,
            pagato
        FROM {$dokumente_table}
        WHERE fk_kunde = %d
        ORDER BY einstiegsdatum DESC, id DESC",
        $customer_id
    );
    
    $documents = $wpdb->get_results( $sql, ARRAY_A );
    
    // Formatiere Datumsfelder
    foreach ( $documents as &$doc ) {
        if ( ! empty( $doc['einstiegsdatum'] ) && $doc['einstiegsdatum'] != '0000-00-00' ) {
            $doc['einstiegsdatum'] = date( 'd.m.Y', strtotime( $doc['einstiegsdatum'] ) );
        }
        
        if ( ! empty( $doc['data'] ) && $doc['data'] != '0000-00-00' ) {
            $doc['data'] = date( 'd.m.Y', strtotime( $doc['data'] ) );
        }
    }
    
    // Gruppiere nach tipo
    $quotations = array();
    $invoices = array();
    $proforma = array();
    
    foreach ( $documents as $doc ) {
        if ( $doc['tipo'] == 1 ) {
            $quotations[] = $doc;
        } elseif ( $doc['tipo'] == 2 ) {
            $invoices[] = $doc;
        } elseif ( $doc['tipo'] == 3 ) {
            $proforma[] = $doc;
        }
    }
    
    wp_send_json_success( array(
        'quotations' => $quotations,
        'invoices' => $invoices,
        'proforma' => $proforma
    ) );
}

/**
 * Get Customer Contacts (AJAX)
 * Lädt alle Kontaktpersonen eines Kunden
 */
function wpscrm_agent_dashboard_get_customer_contacts() {
    check_ajax_referer( 'crm_customer_management', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $customer_id = intval( $_POST['customer_id'] ?? 0 );
    
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Ungültige Kunden-ID' ) );
    }
    
    global $wpdb;
    $kunde_table = WPsCRM_TABLE . 'kunde';
    $contatti_table = WPsCRM_TABLE . 'contatti';
    
    // Prüfe ob Kunde dem Agent gehört
    $customer_check = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ) );
    
    if ( ! $customer_check ) {
        wp_send_json_error( array( 'message' => 'Kein Zugriff auf diesen Kunden' ) );
    }
    
    // Lade Kontakte
    $contacts = $wpdb->get_results( $wpdb->prepare(
        "SELECT 
            id,
            fk_kunde,
            name,
            nachname,
            email,
            telefono,
            qualifica
        FROM {$contatti_table}
        WHERE fk_kunde = %d
        ORDER BY name ASC, nachname ASC",
        $customer_id
    ), ARRAY_A );
    
    wp_send_json_success( $contacts );
}

/**
 * Save Customer Contact (AJAX)
 * Speichert neuen Kontakt oder aktualisiert bestehenden
 */
function wpscrm_agent_dashboard_save_contact() {
    check_ajax_referer( 'crm_customer_management', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Nicht angemeldet' ) );
    }
    
    $user_id = get_current_user_id();
    $customer_id = intval( $_POST['fk_kunde'] ?? 0 );
    $contact_id = intval( $_POST['contact_id'] ?? 0 );
    
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Ungültige Kunden-ID' ) );
    }
    
    // Validierung
    $name = sanitize_text_field( $_POST['contact_name'] ?? '' );
    $nachname = sanitize_text_field( $_POST['contact_nachname'] ?? '' );
    $email = sanitize_email( $_POST['contact_email'] ?? '' );
    
    if ( empty( $name ) || empty( $nachname ) || empty( $email ) ) {
        wp_send_json_error( array( 'message' => 'Name, Nachname und Email sind Pflichtfelder' ) );
    }
    
    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Ungültige Email-Adresse' ) );
    }
    
    global $wpdb;
    $kunde_table = WPsCRM_TABLE . 'kunde';
    $contatti_table = WPsCRM_TABLE . 'contatti';
    
    // Prüfe ob Kunde dem Agent gehört
    $customer_check = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ) );
    
    if ( ! $customer_check ) {
        wp_send_json_error( array( 'message' => 'Kein Zugriff auf diesen Kunden' ) );
    }
    
    $data = array(
        'fk_kunde'   => $customer_id,
        'name'       => $name,
        'nachname'   => $nachname,
        'email'      => $email,
        'telefono'   => sanitize_text_field( $_POST['contact_telefono'] ?? '' ),
        'qualifica'  => sanitize_text_field( $_POST['contact_qualifica'] ?? '' ),
    );
    
    if ( $contact_id > 0 ) {
        // UPDATE
        $result = $wpdb->update(
            $contatti_table,
            $data,
            array( 'id' => $contact_id, 'fk_kunde' => $customer_id ),
            null,
            array( '%d', '%d' )
        );
        
        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Aktualisieren' ) );
        }
        
        wp_send_json_success( array(
            'message' => 'Kontakt aktualisiert',
            'contact_id' => $contact_id
        ) );
        
    } else {
        // INSERT
        $result = $wpdb->insert( $contatti_table, $data );
        
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Fehler beim Anlegen' ) );
        }
        
        wp_send_json_success( array(
            'message' => 'Kontakt angelegt',
            'contact_id' => $wpdb->insert_id
        ) );
    }
}

/**
 * AJAX Handler: Create Todo
 */
function wpscrm_agent_dashboard_create_todo() {
    check_ajax_referer( 'crm_task_management', 'nonce' );
    
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Nicht authentifiziert' ) );
    }
    
    global $wpdb;
    $agenda_table = WPsCRM_TABLE . 'agenda';
    
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
    $end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
    $priority = intval( $_POST['priority'] ?? 2 );
    $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );
    
    if ( empty( $subject ) || empty( $start_date ) ) {
        wp_send_json_error( array( 'message' => 'Betreff und Startdatum erforderlich' ) );
    }
    
    // Validate dates
    if ( ! strtotime( $start_date ) || ! strtotime( $end_date ) ) {
        wp_send_json_error( array( 'message' => 'Ungültige Datumsangabe' ) );
    }
    
    $importante = ( $priority === 3 ) ? 'Si' : 'No';
    $urgente = ( $priority === 3 ) ? 'Si' : 'No';
    
    $data = array(
        'tipo_agenda'      => 1,  // 1 = Todo
        'oggetto'          => $subject,
        'data_agenda'      => date( 'Y-m-d', strtotime( $start_date ) ),
        'start_date'       => date( 'Y-m-d', strtotime( $start_date ) ),
        'end_date'         => date( 'Y-m-d', strtotime( $end_date ) ),
        'annotazioni'      => $notes,
        'priorita'         => $priority,
        'importante'       => $importante,
        'urgente'          => $urgente,
        'fatto'            => 1,  // 1 = Todo, 2 = Completed
        'fk_utenti_des'    => $user_id,
        'fk_utenti_ins'    => $user_id,
        'data_inserimento' => current_time( 'mysql' ),
    );
    
    $result = $wpdb->insert( $agenda_table, $data );
    
    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Erstellen des Todos' ) );
    }
    
    wp_send_json_success( array(
        'message' => 'Todo erstellt',
        'task_id' => $wpdb->insert_id
    ) );
}
add_action( 'wp_ajax_crm_create_todo', 'wpscrm_agent_dashboard_create_todo' );

/**
 * AJAX Handler: Create Appointment
 */
function wpscrm_agent_dashboard_create_appointment() {
    check_ajax_referer( 'crm_task_management', 'nonce' );
    
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Nicht authentifiziert' ) );
    }
    
    global $wpdb;
    $agenda_table = WPsCRM_TABLE . 'agenda';
    
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $date = sanitize_text_field( $_POST['date'] ?? '' );
    $time = sanitize_text_field( $_POST['time'] ?? '' );
    $priority = intval( $_POST['priority'] ?? 2 );
    $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );
    
    if ( empty( $subject ) || empty( $date ) || empty( $time ) ) {
        wp_send_json_error( array( 'message' => 'Betreff, Datum und Uhrzeit erforderlich' ) );
    }
    
    // Validate date and time
    if ( ! strtotime( $date ) || ! strtotime( $time ) ) {
        wp_send_json_error( array( 'message' => 'Ungültige Datumsangabe oder Uhrzeit' ) );
    }
    
    $importante = ( $priority === 3 ) ? 'Si' : 'No';
    $urgente = ( $priority === 3 ) ? 'Si' : 'No';
    
    $data = array(
        'tipo_agenda'      => 2,  // 2 = Appointment
        'oggetto'          => $subject,
        'data_agenda'      => date( 'Y-m-d', strtotime( $date ) ),
        'start_date'       => date( 'Y-m-d', strtotime( $date ) ),
        'end_date'         => date( 'Y-m-d', strtotime( $date ) ),
        'ora_agenda'       => date( 'H:i:s', strtotime( $time ) ),
        'annotazioni'      => $notes,
        'priorita'         => $priority,
        'importante'       => $importante,
        'urgente'          => $urgente,
        'fatto'            => 1,  // 1 = Scheduled, 2 = Completed
        'fk_utenti_des'    => $user_id,
        'fk_utenti_ins'    => $user_id,
        'data_inserimento' => current_time( 'mysql' ),
    );
    
    $result = $wpdb->insert( $agenda_table, $data );
    
    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Erstellen des Termins' ) );
    }
    
    wp_send_json_success( array(
        'message' => 'Termin erstellt',
        'task_id' => $wpdb->insert_id
    ) );
}
add_action( 'wp_ajax_crm_create_appointment', 'wpscrm_agent_dashboard_create_appointment' );

/**
 * Parse and validate line items from POST
 * Compatible with CRM document row logic (qty * price - discount + VAT)
 */
function wpscrm_agent_dashboard_parse_line_items() {
    $raw_items = wp_unslash( $_POST['line_items'] ?? '' );
    $discount_mode = sanitize_text_field( $_POST['discount_mode'] ?? 'percent' );
    if ( ! in_array( $discount_mode, array( 'percent', 'fixed' ), true ) ) {
        $discount_mode = 'percent';
    }

    if ( empty( $raw_items ) ) {
        return new WP_Error( 'missing_lines', 'Bitte mindestens eine Positionszeile hinzufügen.' );
    }

    $items = json_decode( $raw_items, true );
    if ( ! is_array( $items ) ) {
        return new WP_Error( 'invalid_lines', 'Ungültige Positionsdaten.' );
    }

    $validated_items = array();
    $total_net = 0.0;
    $total_tax = 0.0;
    $total_gross = 0.0;

    foreach ( $items as $item ) {
        $line_type = intval( $item['line_type'] ?? 2 );
        if ( ! in_array( $line_type, array( 2, 3, 4 ), true ) ) {
            $line_type = 2;
        }

        $description = sanitize_textarea_field( $item['description'] ?? '' );
        $quantity = floatval( $item['quantity'] ?? 0 );
        $unit_price = floatval( $item['unit_price'] ?? 0 );
        $discount_value = floatval( $item['discount_value'] ?? 0 );
        $vat_percent = floatval( $item['vat_percent'] ?? 0 );

        if ( $description === '' ) {
            continue;
        }

        $line_net = 0;
        $line_tax = 0;
        $line_gross = 0;

        if ( 3 !== $line_type ) {
            if ( $quantity <= 0 || $unit_price < 0 ) {
                continue;
            }

            if ( $discount_value < 0 ) {
                $discount_value = 0;
            }
            if ( $vat_percent < 0 ) {
                $vat_percent = 0;
            }

            $line_base = $quantity * $unit_price;
            if ( 'fixed' === $discount_mode ) {
                $discount_calc = min( $line_base, $discount_value );
            } else {
                $discount_calc = $line_base * $discount_value / 100;
            }

            $line_net = max( 0, $line_base - $discount_calc );
            $line_tax = ( 4 === $line_type ) ? 0 : ( $line_net * ( $vat_percent / 100 ) );
            $line_gross = $line_net + $line_tax;

            if ( 4 === $line_type ) {
                $line_net = -$line_net;
                $line_tax = 0;
                $line_gross = -$line_gross;
                $vat_percent = 0;
            }
        } else {
            $quantity = 0;
            $unit_price = 0;
            $discount_value = 0;
            $vat_percent = 0;
        }

        $validated_items[] = array(
            'line_type' => $line_type,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'discount_value' => $discount_value,
            'vat_percent' => $vat_percent,
            'line_net' => $line_net,
            'line_tax' => $line_tax,
            'line_gross' => $line_gross,
        );

        $total_net += $line_net;
        $total_tax += $line_tax;
        $total_gross += $line_gross;
    }

    if ( empty( $validated_items ) ) {
        return new WP_Error( 'empty_lines', 'Bitte mindestens eine gültige Positionszeile hinzufügen.' );
    }

    return array(
        'items' => $validated_items,
        'discount_mode' => $discount_mode,
        'totals' => array(
            'net' => $total_net,
            'tax' => $total_tax,
            'gross' => $total_gross,
        ),
    );
}

/**
 * Save document rows in dokumente_dettaglio
 */
function wpscrm_agent_dashboard_save_document_rows( $document_id, $line_items ) {
    global $wpdb;
    $detail_table = WPsCRM_TABLE . 'dokumente_dettaglio';

    foreach ( $line_items as $index => $line_item ) {
        $wpdb->insert(
            $detail_table,
            array(
                'fk_dokumente' => $document_id,
                'fk_artikel' => 0,
                'qta' => $line_item['quantity'],
                'n_riga' => $index + 1,
                'sconto' => $line_item['discount_value'],
                'iva' => (int) round( $line_item['vat_percent'] ),
                'prezzo' => $line_item['unit_price'],
                'totale' => $line_item['line_gross'],
                'tipo' => $line_item['line_type'],
                'codice' => '',
                'descrizione' => $line_item['description'],
                'eliminato' => 0,
                'fk_subscriptionrules' => 0,
            ),
            array( '%d', '%d', '%f', '%d', '%f', '%d', '%f', '%f', '%d', '%s', '%s', '%d', '%d' )
        );
    }
}

/**
 * AJAX Handler: Create Quotation (Angebot)
 */
function wpscrm_agent_dashboard_create_quotation() {
    check_ajax_referer( 'crm_customer_management', 'nonce' );
    
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Nicht authentifiziert' ) );
    }
    
    global $wpdb;
    $dokumente_table = WPsCRM_TABLE . 'documenti';
    $kunde_table = WPsCRM_TABLE . 'kunde';
    
    $customer_id = intval( $_POST['customer_id'] ?? 0 );
    $date = sanitize_text_field( $_POST['date'] ?? '' );
    $due_date = sanitize_text_field( $_POST['due_date'] ?? '' );
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );
    
    if ( ! $customer_id || empty( $date ) || empty( $due_date ) || empty( $subject ) ) {
        wp_send_json_error( array( 'message' => 'Erforderliche Felder fehlen' ) );
    }
    
    // Verify customer ownership
    $customer_check = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ) );
    
    if ( ! $customer_check ) {
        wp_send_json_error( array( 'message' => 'Kein Zugriff auf diesen Kunden' ) );
    }
    
    // Validate dates
    if ( ! strtotime( $date ) || ! strtotime( $due_date ) ) {
        wp_send_json_error( array( 'message' => 'Ungültige Datumsangabe' ) );
    }

    $lines_payload = wpscrm_agent_dashboard_parse_line_items();
    if ( is_wp_error( $lines_payload ) ) {
        wp_send_json_error( array( 'message' => $lines_payload->get_error_message() ) );
    }
    $line_items = $lines_payload['items'];
    $discount_mode = $lines_payload['discount_mode'];
    $total_net = $lines_payload['totals']['net'];
    $total_tax = $lines_payload['totals']['tax'];
    $total_gross = $lines_payload['totals']['gross'];
    
    $document_date = date( 'Y-m-d', strtotime( $date ) );
    $due_date_sql = date( 'Y-m-d', strtotime( $due_date ) );
    $data_timestamp = strtotime( $document_date );
    $due_timestamp = strtotime( $due_date_sql );

    $next_progressivo = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COALESCE(MAX(progressivo), 0) + 1 FROM {$dokumente_table} WHERE tipo = %d AND YEAR(data) = %d",
        1,
        (int) date( 'Y', $data_timestamp )
    ) );

    $data = array(
        'tipo'                  => 1,
        'data'                  => $document_date,
        'einstiegsdatum'        => current_time( 'Y-m-d' ),
        'data_timestamp'        => $data_timestamp,
        'data_scadenza_timestamp' => $due_timestamp,
        'oggetto'               => $subject,
        'riferimento'           => '',
        'fk_kunde'              => $customer_id,
        'fk_utenti_ins'         => $user_id,
        'fk_utenti_age'         => $user_id,
        'progressivo'           => max( 1, $next_progressivo ),
        'totale_imponibile'     => $total_net,
        'totale_imposta'        => $total_tax,
        'totale'                => $total_gross,
        'tot_cassa_inps'        => 0,
        'ritenuta_acconto'      => 0,
        'totale_netto'          => $total_gross,
        'valore_preventivo'     => $total_net,
        'sezionale_iva'         => '',
        'movimenta_magazzino'   => '',
        'testo_libero'          => '',
        'modalita_pagamento'    => '',
        'annotazioni'           => $notes,
        'commento'              => '',
        'giorni_pagamento'      => 0,
        'data_scadenza'         => $due_date_sql,
        'pagato'                => 0,
        'registrato'            => 0,
        'approvato'             => 0,
        'filename'              => '',
        'perc_realizzo'         => '',
        'notifica_pagamento'    => 0,
        'fk_woo_order'          => 0,
        'origine_proforma'      => 0,
        'tipo_sconto'           => ( 'fixed' === $discount_mode ) ? 1 : 0,
    );
    
    $result = $wpdb->insert( $dokumente_table, $data );
    
    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Erstellen des Angebots' ) );
    }
    
    $document_id = $wpdb->insert_id;
    wpscrm_agent_dashboard_save_document_rows( $document_id, $line_items );

    wp_send_json_success( array(
        'message' => 'Angebot erstellt',
        'document_id' => $document_id
    ) );
}
add_action( 'wp_ajax_crm_create_quotation', 'wpscrm_agent_dashboard_create_quotation' );

/**
 * AJAX Handler: Create Invoice (Rechnung)
 */
function wpscrm_agent_dashboard_create_invoice() {
    check_ajax_referer( 'crm_customer_management', 'nonce' );
    
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Nicht authentifiziert' ) );
    }
    
    global $wpdb;
    $dokumente_table = WPsCRM_TABLE . 'documenti';
    $kunde_table = WPsCRM_TABLE . 'kunde';
    
    $customer_id = intval( $_POST['customer_id'] ?? 0 );
    $date = sanitize_text_field( $_POST['date'] ?? '' );
    $due_date = sanitize_text_field( $_POST['due_date'] ?? '' );
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $payment_method = sanitize_text_field( $_POST['payment_method'] ?? '' );
    $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );
    
    if ( ! $customer_id || empty( $date ) || empty( $due_date ) || empty( $subject ) ) {
        wp_send_json_error( array( 'message' => 'Erforderliche Felder fehlen' ) );
    }
    
    // Verify customer ownership
    $customer_check = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ) );
    
    if ( ! $customer_check ) {
        wp_send_json_error( array( 'message' => 'Kein Zugriff auf diesen Kunden' ) );
    }
    
    // Validate dates
    if ( ! strtotime( $date ) || ! strtotime( $due_date ) ) {
        wp_send_json_error( array( 'message' => 'Ungültige Datumsangabe' ) );
    }
    
    $lines_payload = wpscrm_agent_dashboard_parse_line_items();
    if ( is_wp_error( $lines_payload ) ) {
        wp_send_json_error( array( 'message' => $lines_payload->get_error_message() ) );
    }
    $line_items = $lines_payload['items'];
    $discount_mode = $lines_payload['discount_mode'];
    $total_net = $lines_payload['totals']['net'];
    $total_tax = $lines_payload['totals']['tax'];
    $total_gross = $lines_payload['totals']['gross'];
    
    $document_date = date( 'Y-m-d', strtotime( $date ) );
    $due_date_sql = date( 'Y-m-d', strtotime( $due_date ) );
    $data_timestamp = strtotime( $document_date );
    $due_timestamp = strtotime( $due_date_sql );

    $next_progressivo = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COALESCE(MAX(progressivo), 0) + 1 FROM {$dokumente_table} WHERE tipo = %d AND YEAR(data) = %d",
        2,
        (int) date( 'Y', $data_timestamp )
    ) );

    $data = array(
        'tipo'                  => 2,
        'data'                  => $document_date,
        'einstiegsdatum'        => current_time( 'Y-m-d' ),
        'data_timestamp'        => $data_timestamp,
        'data_scadenza_timestamp' => $due_timestamp,
        'oggetto'               => $subject,
        'riferimento'           => '',
        'fk_kunde'              => $customer_id,
        'fk_utenti_ins'         => $user_id,
        'fk_utenti_age'         => $user_id,
        'progressivo'           => max( 1, $next_progressivo ),
        'totale_imponibile'     => $total_net,
        'totale_imposta'        => $total_tax,
        'totale'                => $total_gross,
        'tot_cassa_inps'        => 0,
        'ritenuta_acconto'      => 0,
        'totale_netto'          => $total_gross,
        'valore_preventivo'     => 0,
        'sezionale_iva'         => '',
        'movimenta_magazzino'   => '',
        'testo_libero'          => '',
        'modalita_pagamento'    => $payment_method,
        'annotazioni'           => $notes,
        'commento'              => '',
        'giorni_pagamento'      => 0,
        'data_scadenza'         => $due_date_sql,
        'pagato'                => 0,
        'registrato'            => 0,
        'approvato'             => 0,
        'filename'              => '',
        'perc_realizzo'         => '',
        'notifica_pagamento'    => 0,
        'fk_woo_order'          => 0,
        'origine_proforma'      => 0,
        'tipo_sconto'           => ( 'fixed' === $discount_mode ) ? 1 : 0,
    );
    
    $result = $wpdb->insert( $dokumente_table, $data );
    
    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Erstellen der Rechnung' ) );
    }
    
    $document_id = $wpdb->insert_id;
    wpscrm_agent_dashboard_save_document_rows( $document_id, $line_items );

    wp_send_json_success( array(
        'message' => 'Rechnung erstellt',
        'document_id' => $document_id
    ) );
}
add_action( 'wp_ajax_crm_create_invoice', 'wpscrm_agent_dashboard_create_invoice' );

/**
 * AJAX Handler: Create Proforma
 */
function wpscrm_agent_dashboard_create_proforma() {
    check_ajax_referer( 'crm_customer_management', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( array( 'message' => 'Nicht authentifiziert' ) );
    }

    global $wpdb;
    $dokumente_table = WPsCRM_TABLE . 'documenti';
    $kunde_table = WPsCRM_TABLE . 'kunde';

    $customer_id = intval( $_POST['customer_id'] ?? 0 );
    $date = sanitize_text_field( $_POST['date'] ?? '' );
    $due_date = sanitize_text_field( $_POST['due_date'] ?? '' );
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $amount = floatval( $_POST['amount'] ?? 0 );
    $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );

    if ( ! $customer_id || empty( $date ) || empty( $due_date ) || empty( $subject ) ) {
        wp_send_json_error( array( 'message' => 'Erforderliche Felder fehlen' ) );
    }

    $customer_check = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID_kunde FROM {$kunde_table} WHERE ID_kunde = %d AND agente = %d AND eliminato = 0",
        $customer_id,
        $user_id
    ) );

    if ( ! $customer_check ) {
        wp_send_json_error( array( 'message' => 'Kein Zugriff auf diesen Kunden' ) );
    }

    if ( ! strtotime( $date ) || ! strtotime( $due_date ) ) {
        wp_send_json_error( array( 'message' => 'Ungültige Datumsangabe' ) );
    }

    $document_date = date( 'Y-m-d', strtotime( $date ) );
    $due_date_sql = date( 'Y-m-d', strtotime( $due_date ) );
    $data_timestamp = strtotime( $document_date );
    $due_timestamp = strtotime( $due_date_sql );

    $next_progressivo = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COALESCE(MAX(progressivo), 0) + 1 FROM {$dokumente_table} WHERE tipo = %d AND YEAR(data) = %d",
        3,
        (int) date( 'Y', $data_timestamp )
    ) );

    $data = array(
        'tipo'                    => 3,
        'data'                    => $document_date,
        'einstiegsdatum'          => current_time( 'Y-m-d' ),
        'data_timestamp'          => $data_timestamp,
        'data_scadenza_timestamp' => $due_timestamp,
        'oggetto'                 => $subject,
        'riferimento'             => '',
        'fk_kunde'                => $customer_id,
        'fk_utenti_ins'           => $user_id,
        'fk_utenti_age'           => $user_id,
        'progressivo'             => max( 1, $next_progressivo ),
        'totale_imponibile'       => $amount,
        'totale_imposta'          => 0,
        'totale'                  => $amount,
        'tot_cassa_inps'          => 0,
        'ritenuta_acconto'        => 0,
        'totale_netto'            => $amount,
        'valore_preventivo'       => 0,
        'sezionale_iva'           => '',
        'movimenta_magazzino'     => '',
        'testo_libero'            => '',
        'modalita_pagamento'      => '',
        'annotazioni'             => $notes,
        'commento'                => '',
        'giorni_pagamento'        => 0,
        'data_scadenza'           => $due_date_sql,
        'pagato'                  => 0,
        'registrato'              => 0,
        'approvato'               => 0,
        'filename'                => '',
        'perc_realizzo'           => '',
        'notifica_pagamento'      => 0,
        'fk_woo_order'            => 0,
        'origine_proforma'        => 1,
        'tipo_sconto'             => 0,
    );

    $result = $wpdb->insert( $dokumente_table, $data );

    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Fehler beim Erstellen der Proforma' ) );
    }

    wp_send_json_success( array(
        'message' => 'Proforma erstellt',
        'document_id' => $wpdb->insert_id
    ) );
}
add_action( 'wp_ajax_crm_create_proforma', 'wpscrm_agent_dashboard_create_proforma' );

