<?php
if ( ! defined( 'ABSPATH' ) ) exit;
//global $WPsCRM_db_version;
$WPsCRM_db_version = '1.6.4';
function WPsCRM_crm_install() {
	global $wpdb;
	global $table_prefix;
	define ('WPsCRM_SETUP_TABLE',$table_prefix.'smartcrm_');
	global $WPsCRM_db_version;

	$charset_collate = $wpdb->get_charset_collate();

	$sql[] = "CREATE TABLE `".WPsCRM_SETUP_TABLE."kunde` (
  `ID_kunde` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL,
  `nachname` varchar(100) NOT NULL,
  `firmenname` varchar(250) NOT NULL DEFAULT '',
  `adresse` varchar(200) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  `standort` varchar(55) NOT NULL DEFAULT '',
  `provinz` varchar(100) DEFAULT NULL,
  `nation` varchar(100) DEFAULT NULL,
  `telefono1` varchar(50) DEFAULT NULL,
  `telefono2` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL DEFAULT '',
  `sitoweb` varchar(100) NOT NULL,
  `skype` varchar(100) NOT NULL,
  `p_iva` varchar(30) DEFAULT NULL,
  `cod_fis` varchar(30) DEFAULT NULL,
  `annotazioni` text,
  `FK_aziende` int(10) unsigned NOT NULL DEFAULT '0',
  `einstiegsdatum` date DEFAULT NULL,
  `data_modifica` date DEFAULT NULL,
  `eliminato` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `aggiornato` enum('No','Si') NOT NULL DEFAULT 'No',
  `provenienza` varchar(100) NOT NULL DEFAULT '',
  `luogo_nascita` varchar(200) NOT NULL,
  `geburtsdatum` date DEFAULT NULL,
  `stripe_ID` varchar(32) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `tipo_cliente` tinyint(3) unsigned NOT NULL,
  `agente` int(10) unsigned NULL,
  `interessi` varchar(100) NOT NULL DEFAULT '',
  `abrechnungsmodus` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `custom_fields` text,
  `custom_tax` text,
  `uploads` text,
  PRIMARY KEY (`ID_kunde`),
  KEY `FK_categorie_kunde` (`categoria`),
  KEY `FK_aziende` (`FK_aziende`)
) ENGINE=MyISAM  ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."agenda` (
 `id_agenda` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fk_aziende` int(10) unsigned NOT NULL DEFAULT '0',
  `fk_utenti_ins` int(10) unsigned NOT NULL DEFAULT '0',
  `oggetto` varchar(255) DEFAULT NULL,
  `fk_utenti_des` int(10) unsigned NOT NULL DEFAULT '0',
  `fk_kunde` int(10) unsigned DEFAULT NULL,
  `fk_contatti` int(10) unsigned NOT NULL DEFAULT '0',
  `data_agenda` date DEFAULT NULL,
  `ora_agenda` time DEFAULT NULL,
  `annotazioni` text NOT NULL,
  `einstiegsdatum` datetime NOT NULL,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `esito` text,
  `priorita` tinyint(3) unsigned NOT NULL,
  `importante` enum('No','Si') NOT NULL DEFAULT 'No',
  `urgente` enum('No','Si') NOT NULL DEFAULT 'No',
  `fatto` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1=da fare, 2= fatto, 3=cancellato',
  `tipo_agenda` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1=todo, 2= appuntamento, 3=notifica scadenza pagamento fattura, 4=acquisto,5 notifica scadenza servizio',
  `fk_dokumente` int(10) unsigned NOT NULL,
  `fk_dokumente_dettaglio` int(10) unsigned NOT NULL,
  `fk_subscriptionrules` int(10) unsigned NOT NULL,
  `eliminato` tinyint(3) unsigned NOT NULL,
  `visto` varchar(50)  DEFAULT NULL,
  `timezone_offset` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_agenda`),
  KEY `FK_aziende` (`fk_aziende`),
  KEY `FK_utenti_ins` (`fk_utenti_ins`),
  KEY `FK_utenti_des` (`fk_utenti_des`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."contatti` (
   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fk_kunde` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `nachname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `qualifica` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."documenti` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` tinyint(3) unsigned NOT NULL COMMENT '1=preventivo, 2=fattura, 3=proforma',
  `data` date NOT NULL,
  `einstiegsdatum` date NOT NULL,
  `data_timestamp` int(15) NOT NULL,
  `data_scadenza_timestamp` int(15) NOT NULL,
  `oggetto` varchar(100) NOT NULL,
  `riferimento` varchar(100) NOT NULL,
  `fk_kunde` int(10) unsigned NOT NULL,
  `fk_utenti_ins` int(10) unsigned NOT NULL,
  `fk_utenti_age` int(10) unsigned NOT NULL,
  `progressivo` int(11) NOT NULL,
  `totale_imponibile` float(9,2) unsigned NOT NULL,
  `totale_imposta` float(9,2) unsigned NOT NULL,
  `totale` float(9,2) unsigned NOT NULL,
  `tot_cassa_inps` float(9,2) unsigned NOT NULL,
  `ritenuta_acconto` float(9,2) unsigned NOT NULL,
  `totale_netto` float(9,2) unsigned NOT NULL,
  `valore_preventivo` float(9,2) unsigned NOT NULL,
  `sezionale_iva` char(1) NOT NULL,
  `movimenta_magazzino` char(1) NOT NULL,
  `testo_libero` text NOT NULL,
  `modalita_pagamento` varchar(250) NOT NULL,
  `annotazioni` text NOT NULL,
  `commento` text NOT NULL,
  `giorni_pagamento` tinyint(3) unsigned DEFAULT NULL,
  `data_scadenza` date NOT NULL,
  `pagato` tinyint(3) unsigned NOT NULL,
  `registrato` tinyint(3) unsigned NOT NULL,
  `approvato` tinyint(3) unsigned NOT NULL,
  `filename` varchar(100) NOT NULL,
  `perc_realizzo` varchar(10) DEFAULT NULL,
  `notifica_pagamento` tinyint(3) unsigned NOT NULL,
  `fk_woo_order` int(10) unsigned NOT NULL,
  `origine_proforma` tinyint(3) UNSIGNED NOT NULL DEFAULT  '0',
  `tipo_sconto` tinyint(3) UNSIGNED NOT NULL DEFAULT  '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."dokumente_dettaglio` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fk_dokumente` int(10) unsigned NOT NULL,
  `fk_artikel` int(10) unsigned NOT NULL,
  `qta` float(5,2) unsigned NOT NULL,
  `n_riga` int(10) unsigned NOT NULL,
  `sconto` float(9,2) unsigned NOT NULL,
  `iva` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `prezzo` float(9,2) unsigned NOT NULL,
  `totale` float(9,2) unsigned NOT NULL,
  `tipo` tinyint(3) unsigned NOT NULL COMMENT '1=prodotto, 2=articolo manuale, 3=descrizione, 4=rimborso',
  `codice` varchar(30) NOT NULL,
  `descrizione` text NOT NULL,
  `eliminato` tinyint(3) unsigned NOT NULL ,
  `fk_subscriptionrules` int(10) unsigned NOT NULL ,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."emails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `e_from` varchar(100) NOT NULL,
  `e_to` varchar(100) NOT NULL,
  `e_subject` varchar(100) NOT NULL,
  `e_body` text NOT NULL,
  `e_sent` tinyint(3) unsigned NOT NULL,
  `e_date` datetime NOT NULL,
  `fk_agenda` int(10) unsigned NOT NULL,
  `fk_dokumente` int(10) unsigned NOT NULL,
  `fk_dokumente_dettaglio` int(10) unsigned NOT NULL,
  `e_unsent` VARCHAR( 255 ) NOT NULL,
  `fk_kunde` int(10) unsigned NOT NULL,
  `attachments` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_label` varchar(50) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `field_alt` varchar(50) NOT NULL,
  `required` tinyint(3) unsigned NOT NULL,
  `multiple` tinyint(1) NOT NULL,
  `sorting` tinyint(3) unsigned NOT NULL,
  `position` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `show_grid` TINYINT UNSIGNED NOT NULL DEFAULT  '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."subscriptionrules` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `length` tinyint(2) NOT NULL,
  `steps` text NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT '1',
  `s_specific` tinyint(3) unsigned NOT NULL,
  `s_type` tinyint(3) unsigned NOT NULL COMMENT '1=todo, 2=appuntamento',
  `s_email` tinyint(4) unsigned NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."email_templates` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `lingua` varchar(10) NOT NULL DEFAULT 'it',
  `oggetto` varchar(255) NOT NULL,
  `corpo` text NOT NULL,
  `contesto` varchar(55) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1 ;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `kategoria` varchar(100) NOT NULL,
  `descrizione` text NOT NULL,
  `imponibile` float(9,2) unsigned NOT NULL,
  `aliquota` float(4,2) unsigned NOT NULL DEFAULT '19',
  `imposta` float(9,2) unsigned NOT NULL,
  `totale` float(9,2) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `kategoria` (`kategoria`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."incomes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `kategoria` varchar(100) NOT NULL,
  `descrizione` text NOT NULL,
  `imponibile` float(9,2) unsigned NOT NULL,
  `aliquota` float(4,2) unsigned NOT NULL DEFAULT '19',
  `imposta` float(9,2) unsigned NOT NULL,
  `totale` float(9,2) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `kategoria` (`kategoria`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."belege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `belegnummer` varchar(50) NOT NULL,
  `beleg_typ` varchar(50) NOT NULL COMMENT 'Rechnung, Quittung, Beleg, Zahlungsnachweis, etc.',
  `beleg_datum` date NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_mime` varchar(50) NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `fk_documenti` int(10) unsigned DEFAULT NULL COMMENT 'Verkn\u00fcpfung zu Rechnung',
  `fk_incomes` int(10) unsigned DEFAULT NULL COMMENT 'Verkn\u00fcpfung zu manuellem Einkommen',
  `fk_expenses` int(10) unsigned DEFAULT NULL COMMENT 'Verkn\u00fcpfung zu Ausgabe',
  `fk_kunde` int(10) unsigned DEFAULT NULL COMMENT 'Verkn\u00fcpfung zu Kunde',
  `beschreibung` text,
  `kategorie` varchar(100) NOT NULL COMMENT 'Betriebskosten, Material, Dienste, etc.',
  `notizen` text,
  `tags` varchar(255),
  `upload_datum` datetime NOT NULL,
  `uploaded_by` int(10) unsigned NOT NULL COMMENT 'Benutzer-ID',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `belegnummer` (`belegnummer`),
  KEY `beleg_datum` (`beleg_datum`),
  KEY `fk_documenti` (`fk_documenti`),
  KEY `fk_incomes` (`fk_incomes`),
  KEY `fk_expenses` (`fk_expenses`),
  KEY `fk_kunde` (`fk_kunde`),
  KEY `kategorie` (`kategorie`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."agent_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_slug` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL DEFAULT '',
  `icon` varchar(50) NOT NULL DEFAULT 'dashicons-businessman',
  `show_in_contact` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Im Frontend als Kontakt anzeigen',
  `is_system_role` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Systemrolle (nicht löschbar)',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `capabilities` text COMMENT 'JSON: zusätzliche Rechte',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_slug` (`role_slug`),
  KEY `show_in_contact` (`show_in_contact`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'WordPress User-ID',
  `role_id` int(10) unsigned NOT NULL COMMENT 'FK zu agent_roles',
  `status` enum('active','inactive','archived') NOT NULL DEFAULT 'active' COMMENT 'Agent-Status im CRM',
  `joined_at` datetime NOT NULL COMMENT 'Beitrittsdatum',
  `updated_at` datetime NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_agent_role` FOREIGN KEY (`role_id`) REFERENCES `".WPsCRM_SETUP_TABLE."agent_roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."timetracking` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Agent User-ID',
  `fk_kunde` int(10) unsigned DEFAULT NULL COMMENT 'Verknüpfung zu Kunde',
  `fk_agenda` int(10) unsigned DEFAULT NULL COMMENT 'Verknüpfung zu Termin',
  `project_name` varchar(200) NOT NULL DEFAULT '',
  `task_description` text,
  `start_time` datetime NOT NULL,
  `end_time` datetime NULL,
  `duration_minutes` int(10) unsigned NOT NULL DEFAULT '0',
  `hourly_rate` float(9,2) unsigned NOT NULL DEFAULT '0.00',
  `total_amount` float(9,2) unsigned NOT NULL DEFAULT '0.00',
  `is_billable` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `is_billed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `fk_documenti` int(10) unsigned DEFAULT NULL COMMENT 'Verknüpfte Rechnung',
  `status` varchar(20) NOT NULL DEFAULT 'running' COMMENT 'running, paused, completed',
  `break_minutes` int(10) unsigned NOT NULL DEFAULT '0',
  `location` varchar(100) NOT NULL DEFAULT '',
  `tags` varchar(255),
  `notes` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fk_kunde` (`fk_kunde`),
  KEY `fk_agenda` (`fk_agenda`),
  KEY `start_time` (`start_time`),
  KEY `status` (`status`),
  KEY `is_billable` (`is_billable`),
  KEY `is_billed` (`is_billed`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."billing_drafts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(10) unsigned NOT NULL COMMENT 'FK zu agents',
  `user_id` int(10) unsigned NOT NULL COMMENT 'WordPress User-ID',
  `status` enum('draft','billed','cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Entwurf, Gebucht, Gelöscht',
  `total_minutes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Summe der erfassten Minuten',
  `hourly_rate` float(9,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'Stundensatz zum Zeitpunkt der Erstellung',
  `rate_type` varchar(20) NOT NULL DEFAULT 'net' COMMENT 'net oder gross',
  `amount_net` float(9,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'Nettobetrag',
  `amount_gross` float(9,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'Bruttobetrag (mit MwSt)',
  `description` text COMMENT 'Beschreibung/Notizen',
  `income_entry_id` int(10) unsigned DEFAULT NULL COMMENT 'FK zur incomes-Tabelle nach Buchung',
  `document_id` int(10) unsigned DEFAULT NULL COMMENT 'FK zu Rechnung/Dokument',
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL COMMENT 'Erstellt von User-ID',
  `billed_at` datetime DEFAULT NULL,
  `billed_by` int(10) unsigned DEFAULT NULL COMMENT 'Gebucht von User-ID',
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB ".$charset_collate." AUTO_INCREMENT=1;";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."billing_drafts_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `billing_draft_id` int(10) unsigned NOT NULL COMMENT 'FK zu billing_drafts',
  `timetracking_id` int(10) unsigned NOT NULL COMMENT 'FK zu timetracking',
  PRIMARY KEY (`id`),
  KEY `billing_draft_id` (`billing_draft_id`),
  KEY `timetracking_id` (`timetracking_id`)
) ENGINE=InnoDB ".$charset_collate." AUTO_INCREMENT=1;";

    //print_r ($sql);//exit;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$namefile="error_setup_".date("YmdHi").".txt";
	$myFile = WPsCRM_DIR."/logs/".$namefile;
	$msg="";
	foreach($sql as $q)
    {
		dbDelta( $q );
    };

	if ( $msg =="")
		update_option( 'WPsCRM_db_version', $WPsCRM_db_version );
	
	// Initialize CRM capabilities for admin
	WPsCRM_init_capabilities();
	
	// Install default agent roles
	WPsCRM_install_default_agent_roles();

  // Standard: Seitenadmin automatisch als Chef setzen (wenn noch keine Rolle vorhanden)
  WPsCRM_assign_site_admin_as_chef();

}

/**
 * Installiert Standard-Agent-Rollen beim ersten Setup
 */
function WPsCRM_install_default_agent_roles() {
	global $wpdb;
	$table = WPsCRM_TABLE . 'agent_roles';
	
	// Prüfen ob bereits Rollen existieren
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
	if ( $count > 0 ) {
		return; // Bereits installiert
	}
	
	$now = current_time( 'mysql' );
	$default_roles = array(
		array(
			'role_slug' => 'chef',
			'role_name' => 'Chef',
			'display_name' => __( 'Geschäftsführung', 'cpsmartcrm' ),
			'department' => __( 'Management', 'cpsmartcrm' ),
			'icon' => 'dashicons-businessman',
			'show_in_contact' => 1,
			'is_system_role' => 1,
			'sort_order' => 10,
		),
		array(
			'role_slug' => 'buero',
			'role_name' => 'Büro',
			'display_name' => __( 'Büro / Verwaltung', 'cpsmartcrm' ),
			'department' => __( 'Administration', 'cpsmartcrm' ),
			'icon' => 'dashicons-building',
			'show_in_contact' => 1,
			'is_system_role' => 1,
			'sort_order' => 20,
		),
		array(
			'role_slug' => 'vertrieb',
			'role_name' => 'Vertrieb',
			'display_name' => __( 'Vertrieb / Sales', 'cpsmartcrm' ),
			'department' => __( 'Vertrieb', 'cpsmartcrm' ),
			'icon' => 'dashicons-groups',
			'show_in_contact' => 1,
			'is_system_role' => 0,
			'sort_order' => 30,
		),
		array(
			'role_slug' => 'lager',
			'role_name' => 'Lager & Einkauf',
			'display_name' => __( 'Lager und Einkauf', 'cpsmartcrm' ),
			'department' => __( 'Logistik', 'cpsmartcrm' ),
			'icon' => 'dashicons-store',
			'show_in_contact' => 1,
			'is_system_role' => 0,
			'sort_order' => 40,
		),
		array(
			'role_slug' => 'support',
			'role_name' => 'Support',
			'display_name' => __( 'Kundensupport', 'cpsmartcrm' ),
			'department' => __( 'Service', 'cpsmartcrm' ),
			'icon' => 'dashicons-sos',
			'show_in_contact' => 1,
			'is_system_role' => 0,
			'sort_order' => 50,
		),
	);
	
	foreach ( $default_roles as $role ) {
		$role['created_at'] = $now;
		$wpdb->insert( $table, $role );
	}
}

/**
 * Setzt den jeweiligen Seitenadmin (admin_email) als Chef, falls noch keine CRM-Rolle vorhanden ist.
 * Multisite-schonend: nimmt den Admin der aktuellen Site und überschreibt niemals bestehende Rollen.
 */
function WPsCRM_assign_site_admin_as_chef() {
  global $wpdb;

  $table = WPsCRM_TABLE . 'agent_roles';
  $chef_exists = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE role_slug = %s",
    'chef'
  ) );

  if ( ! $chef_exists ) {
    return;
  }

  $admin_email = get_option( 'admin_email' );
  if ( empty( $admin_email ) ) {
    return;
  }

  $site_admin_user = get_user_by( 'email', $admin_email );
  if ( ! ( $site_admin_user instanceof WP_User ) ) {
    return;
  }

  if ( ! user_can( $site_admin_user->ID, 'manage_options' ) ) {
    return;
  }

  $current_role = get_user_meta( $site_admin_user->ID, '_crm_agent_role', true );
  if ( ! empty( $current_role ) ) {
    return;
  }

  if ( function_exists( 'wpscrm_apply_role_permissions' ) ) {
    wpscrm_apply_role_permissions( $site_admin_user->ID, 'chef' );
  } else {
    update_user_meta( $site_admin_user->ID, '_crm_agent_role', 'chef' );
  }
}

/**
 * Stellt sicher, dass es nur genau einen Chef gibt.
 * Bei Mehrfachzuweisung bleibt bevorzugt der Seitenadmin (admin_email), sonst der erste gefundene Chef.
 */
function WPsCRM_enforce_single_chef_assignment() {
  if ( ! function_exists( 'wpscrm_remove_role_permissions' ) ) {
    return;
  }

  $chef_user_ids = get_users( array(
    'fields' => 'ID',
    'meta_key' => '_crm_agent_role',
    'meta_value' => 'chef',
  ) );

  if ( count( $chef_user_ids ) <= 1 ) {
    return;
  }

  $keep_user_id = 0;
  $admin_email = get_option( 'admin_email' );
  if ( ! empty( $admin_email ) ) {
    $admin_user = get_user_by( 'email', $admin_email );
    if ( $admin_user instanceof WP_User && in_array( (int) $admin_user->ID, array_map( 'intval', $chef_user_ids ), true ) ) {
      $keep_user_id = (int) $admin_user->ID;
    }
  }

  if ( ! $keep_user_id ) {
    $keep_user_id = (int) reset( $chef_user_ids );
  }

  foreach ( $chef_user_ids as $chef_user_id ) {
    $chef_user_id = (int) $chef_user_id;
    if ( $chef_user_id === $keep_user_id ) {
      continue;
    }
    wpscrm_remove_role_permissions( $chef_user_id, 'chef' );
  }
}

function WPsCRM_upgrade_taxonomies()
{
	global $wpdb;
    $table=WPsCRM_TABLE."kunde";
	$sql="select ID_kunde, categoria, provenienza from $table";
    foreach( $wpdb->get_results( $sql ) as $record)
	{
		$cat=$record->categoria;
		$pro=$record->provenienza;
		$id_cli=$record->ID_kunde;
		if ($cat!="0" && $cat!="")
		{
			$categorybyname = get_term_by('name', $cat, 'WPsCRM_customersCat');
			if ($categorybyname!=false)
			{
				$cat_id=$categorybyname->term_id;
			}
			else
			{
				$categorybyid = get_term_by('id', (int)$cat, 'WPsCRM_customersCat');
				if ($categorybyid!=false)
				{
					$cat_id=$categorybyid->term_id;
				}
				else
				{
					$ret=wp_insert_term($cat, 'WPsCRM_customersCat');
					$cat_id=$ret["term_id"];
				}
			}
			$wpdb->update(
				$table,
				array(
					'categoria'=>$cat_id
				),
				array(
					'ID_kunde'=>$id_cli
				),
				array(
				'%s'
				)
			);
		}
		if ($pro!="0" && $pro!="")
		{
			$originbyname = get_term_by('name', $pro, 'WPsCRM_customersProv');
			if ($originbyname!=false)
			{
				$pro_id=$originbyname->term_id;
			}
			else
			{
				$originbyid = get_term_by('id', (int)$pro, 'WPsCRM_customersProv');
				if ($originbyid!=false)
				{
					$pro_id=$originbyid->term_id;
				}
				else
				{
					$ret=wp_insert_term($pro, 'WPsCRM_customersProv');
					$pro_id=$ret["term_id"];
				}
			}
			$wpdb->update(
				$table,
				array(
					'provenienza'=>$pro_id
				),
				array(
					'ID_kunde'=>$id_cli
				),
				array(
				'%s'
				)
			);
			//echo $wpdb->last_query;
		}
	}
	update_option ("WPsCRM_upgrade_taxonomies", 1);
}


function WPsCRM_update_db_check() {
    global $WPsCRM_db_version;
    if ( get_option( 'WPsCRM_db_version' ) != $WPsCRM_db_version ) {
        WPsCRM_crm_install();
    }
    if ( get_option( 'WPsCRM_upgrade_taxonomies' ) == false ) {
        WPsCRM_upgrade_taxonomies();
    }
  WPsCRM_migrate_accounting_created_by();
	WPsCRM_assign_site_admin_as_chef();
  WPsCRM_enforce_single_chef_assignment();
}
add_action( 'plugins_loaded', 'WPsCRM_update_db_check',13 );

/**
 * Einmalige Migration: created_by in incomes/expenses hinzufügen und backfillen.
 */
function WPsCRM_migrate_accounting_created_by() {
  if ( get_option( 'WPsCRM_migration_created_by_done' ) ) {
    return;
  }

  global $wpdb;
  $expenses_table = WPsCRM_TABLE . 'expenses';
  $incomes_table  = WPsCRM_TABLE . 'incomes';
  $belege_table   = WPsCRM_TABLE . 'belege';

  $expenses_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$expenses_table}'" );
  $incomes_exists  = $wpdb->get_var( "SHOW TABLES LIKE '{$incomes_table}'" );

  if ( $expenses_exists ) {
    $has_col = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$expenses_table} LIKE %s", 'created_by' ) );
    if ( ! $has_col ) {
      $wpdb->query( "ALTER TABLE {$expenses_table} ADD COLUMN `created_by` int(10) unsigned NOT NULL DEFAULT '0' AFTER `created_at`" );
    }
    $has_idx = $wpdb->get_var( "SHOW INDEX FROM {$expenses_table} WHERE Key_name = 'created_by'" );
    if ( ! $has_idx ) {
      $wpdb->query( "ALTER TABLE {$expenses_table} ADD KEY `created_by` (`created_by`)" );
    }
  }

  if ( $incomes_exists ) {
    $has_col = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$incomes_table} LIKE %s", 'created_by' ) );
    if ( ! $has_col ) {
      $wpdb->query( "ALTER TABLE {$incomes_table} ADD COLUMN `created_by` int(10) unsigned NOT NULL DEFAULT '0' AFTER `created_at`" );
    }
    $has_idx = $wpdb->get_var( "SHOW INDEX FROM {$incomes_table} WHERE Key_name = 'created_by'" );
    if ( ! $has_idx ) {
      $wpdb->query( "ALTER TABLE {$incomes_table} ADD KEY `created_by` (`created_by`)" );
    }
  }

  // Backfill: Wenn Belege mit Transaktionen verknüpft sind, Owner übernehmen.
  $belege_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$belege_table}'" );
  if ( $belege_exists && $expenses_exists ) {
    $wpdb->query( "UPDATE {$expenses_table} e INNER JOIN {$belege_table} b ON b.fk_expenses = e.id SET e.created_by = b.uploaded_by WHERE e.created_by = 0 AND b.deleted = 0" );
  }
  if ( $belege_exists && $incomes_exists ) {
    $wpdb->query( "UPDATE {$incomes_table} i INNER JOIN {$belege_table} b ON b.fk_incomes = i.id SET i.created_by = b.uploaded_by WHERE i.created_by = 0 AND b.deleted = 0" );
  }

  update_option( 'WPsCRM_migration_created_by_done', 1 );
}


add_action( 'plugins_loaded', 'WPsCRM_create_kunde',11 );
function WPsCRM_create_kunde() {

    register_post_type( 'kunde',
        array(
            'labels' => array(
                'name' => __( 'Clienti','commonFunctions' ),
                'singular_name' => __( 'Cliente' ),
                'edit_item'         => __( 'Modifica cliente' ),
                'add_new_item'      => __( 'Aggiungi cliente' ),
                'new_item_name'     => __( 'Nuovo cliente' ),
            ),
        'public' => false,
        'has_archive' => false,
        'rewrite' => false,
        'supports'=>array('thumbnail','author','editor','title'),
        'show_ui' => false,
        'publicly_queryable'=>true,
        'capability_type' => 'post'
        )
    );
}
add_action( 'init', 'WPsCRM_customers_tax' ,12);
function WPsCRM_customers_tax() {

    $labels=array(
    'name'              => _x( 'Interessen', 'taxonomy general name', 'cpsmartcrm' ),
    'singular_name'     => _x( 'Interesse', 'taxonomy singular name', 'cpsmartcrm' ),
    'search_items'      => __( 'Suche Interesse','cpsmartcrm'),
    'all_items'         => __( 'Alle Interessen','cpsmartcrm'),
    'edit_item'         => __( 'Interesse bearbeiten','cpsmartcrm'),
    'update_item'       => __( 'Interesse aktualisieren','cpsmartcrm'),
    'add_new_item'      => __( 'Interesse hinzufügen','cpsmartcrm'),
    'new_item_name'     => __( 'Neues Interesse','cpsmartcrm'),
    'menu_name'         => __( 'Interessen','cpsmartcrm'),
    );
    $args = array(
    'hierarchical'      => true,
    'labels'            => $labels,
    'show_ui'           => true,
    'show_admin_column' => false,
    'query_var'         => 'WPsCRM_customersInt',
    'rewrite'           => false,
	);
	register_taxonomy( 'WPsCRM_customersInt', array('kunde'), $args );

	$labels=array(
   'name'              => _x( 'Kategorien', 'taxonomy general name' ),
   'singular_name'     => _x( 'Kategorie', 'taxonomy singular name' ),
   'search_items'      => __( 'Suche Kategorie','cpsmartcrm'),
   'all_items'         => __( 'Alle Kategorien','cpsmartcrm'),
   'edit_item'         => __( 'Kategorie bearbeiten','cpsmartcrm'),
   'update_item'       => __( 'Kategorie aktualisieren','cpsmartcrm'),
   'add_new_item'      => __( 'Kategorie hinzufügen','cpsmartcrm'),
   'new_item_name'     => __( 'Neue Kategorie','cpsmartcrm'),
   'menu_name'         => __( 'Kategorien','cpsmartcrm'),
   );
    $args = array(
    'hierarchical'      => true,
    'labels'            => $labels,
    'show_ui'           => true,
    'show_admin_column' => false,
    'query_var'         => 'WPsCRM_customersCat',
    'rewrite'           => false
	);
	register_taxonomy( 'WPsCRM_customersCat', array('kunde'), $args );

  $labels = array(
    'name'              => _x( 'Quellen', 'taxonomy general name' ),
    'singular_name'     => _x( 'Quelle', 'taxonomy singular name' ),
    'search_items'      => __( 'Quelle suchen','cpsmartcrm'),
    'all_items'         => __( 'Alle Quellen','cpsmartcrm'),
    'edit_item'         => __( 'Quelle bearbeiten','cpsmartcrm'),
    'update_item'       => __( 'Quelle aktualisieren','cpsmartcrm'),
    'add_new_item'      => __( 'Neue Quelle hinzufügen','cpsmartcrm'),
    'new_item_name'     => __( 'Neue Quelle','cpsmartcrm'),
    'menu_name'         => __( 'Quellen','cpsmartcrm'),
 );
    $args = array(
    'hierarchical'      => true,
    'labels'            => $labels,
    'show_ui'           => true,
    'show_admin_column' => false,
    'query_var'         => 'WPsCRM_customersProv',
    'rewrite'           => false
	);
	register_taxonomy( 'WPsCRM_customersProv', array('kunde'), $args );
}


/**
** adds menu pages in main wp menu
 **/
add_action('admin_menu', 'smart_crm_menu');
function smart_crm_menu(){
    is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
    if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
        $agent_obj=new AGsCRM_agent();
        $privileges=$agent_obj->getAllPrivileges();
    }
    else
        $privileges=null;

    add_menu_page( 'CP SMART CRM', 'PS Smart Business', 'manage_crm', 'smart-crm', 'WPsCRM_smartcrm', 'dashicons-analytics', 71 );
    add_submenu_page('SMART CRM', 'PS Smart Business', 'manage_crm', 'smart-crm', 'WPsCRM_smartcrm', 'dashicons-analytics', 71);
	add_submenu_page(
			'smart-crm',
			__('CP SMART CRM BENACHRICHTIGUNGSREGELN', 'cpsmartcrm'),
			__('Benachrichtigungen', 'cpsmartcrm'),
			'manage_options',
			'smartcrm_subscription-rules',
			'smartcrm_subscription_rules'
			);
    if($privileges ==null || $privileges['customer'] >0 ){
        add_submenu_page(
                'smart-crm',
                __('CP SMART CRM Kunden', 'cpsmartcrm'),
                __('Kunden', 'cpsmartcrm'),
                'manage_crm',
                'admin.php?page=smart-crm&p=kunde/list.php',
                ''
                );
    }
    if($privileges ==null || $privileges['agenda'] >0 ){
        add_submenu_page(
                'smart-crm',
                __('CP SMART CRM Planer', 'cpsmartcrm'),
                __('Planer', 'cpsmartcrm'),
                'manage_crm',
                'admin.php?page=smart-crm&p=scheduler/list.php',
                ''
                );
    }
    if($privileges ==null || $privileges['quote'] >0 || $privileges['invoice'] >0 ){
        add_submenu_page(
                'smart-crm',
                __('CP SMART CRM Dokumente', 'cpsmartcrm'),
                __('Dokumente', 'cpsmartcrm'),
                'manage_crm',
                'admin.php?page=smart-crm&p=dokumente/list.php',
                ''
                );
    }

    // Buchhaltung (neu) - modulares Tab-System
    add_submenu_page(
        'smart-crm',
        __('CP SMART CRM Buchhaltung', 'cpsmartcrm'),
        __('Buchhaltung', 'cpsmartcrm'),
        'manage_crm',
        'admin.php?page=smart-crm&p=buchhaltung/index.php',
        ''
        );

    // Agenten-Verwaltung
    add_submenu_page(
        'smart-crm',
        __('CP SMART CRM Agenten', 'cpsmartcrm'),
        __('Agenten', 'cpsmartcrm'),
        'manage_options',
        'admin.php?page=smart-crm&p=agents/list.php',
        ''
        );

    // Zeiterfassung Übersicht
    add_submenu_page(
        'smart-crm',
        __('CP SMART CRM Zeiterfassung', 'cpsmartcrm'),
        __('Zeiterfassung', 'cpsmartcrm'),
        'manage_crm',
        'admin.php?page=smart-crm&p=timetracking/list.php',
        ''
        );
}
//$options=get_option('CRM_general_settings');
//if(isset($options['services']) && $options['services']==1)
//    add_action( 'admin_menu', 'add_CRM_services_menu' );
//function add_CRM_services_menu() {
//    add_submenu_page(
//            'smart-crm',
//            'SERVICES',
//            __('Services'),
//            'manage_options',
//            'edit.php?post_type=services',
//            ''
//            );
//}
add_action('admin_head', 'WPsCRM_JSVar');
function WPsCRM_JSVar() {
	if ( isset( $_GET['page'] ) &&  ( $_GET['page'] == 'smart-crm') )
	{
		$options=get_option('CRM_ColumnsWidth');
		$grids=array();
		$index=0;
		if ($options)
			foreach($options as $key=>$grid){
				$grids[$index]=array('grid'=>$key,'columns'=>$grid);
				$index ++;
			}
		echo '
    <script type="text/javascript">
        var columnsWidth='.json_encode($grids,JSON_UNESCAPED_SLASHES ).';
    </script>';
	}
}

/**
 * Initializes CRM capabilities for administrator role
 */
function WPsCRM_init_capabilities() {
	$admin_role = get_role( 'administrator' );
	
	if ( ! $admin_role ) {
		return;
	}
	
	// Add CRM capability to admin
	if ( ! $admin_role->has_cap( 'manage_crm' ) ) {
		$admin_role->add_cap( 'manage_crm' );
	}
}

/**
 * Initialize standard agent roles if not present
 */
function WPsCRM_init_standard_agent_roles() {
	global $wpdb;
	
	$table = WPsCRM_TABLE . 'agent_roles';
	
	// Standard roles to seed
	$standard_roles = array(
		array(
			'role_slug' => 'chef',
			'role_name' => __( 'Chef', 'cpsmartcrm' ),
			'display_name' => __( 'Chef', 'cpsmartcrm' ),
			'department' => __( 'Management', 'cpsmartcrm' ),
			'icon' => 'dashicons-businessman',
			'show_in_contact' => 1,
			'is_system_role' => 1,
			'sort_order' => 10,
		),
		array(
			'role_slug' => 'vertrieb',
			'role_name' => __( 'Vertrieb', 'cpsmartcrm' ),
			'display_name' => __( 'Vertrieb', 'cpsmartcrm' ),
			'department' => __( 'Sales', 'cpsmartcrm' ),
			'icon' => 'dashicons-megaphone',
			'show_in_contact' => 1,
			'is_system_role' => 0,
			'sort_order' => 20,
		),
		array(
			'role_slug' => 'support',
			'role_name' => __( 'Support', 'cpsmartcrm' ),
			'display_name' => __( 'Support', 'cpsmartcrm' ),
			'department' => __( 'Customer Service', 'cpsmartcrm' ),
			'icon' => 'dashicons-email',
			'show_in_contact' => 1,
			'is_system_role' => 0,
			'sort_order' => 30,
		),
		array(
			'role_slug' => 'buchhaltung',
			'role_name' => __( 'Buchhaltung', 'cpsmartcrm' ),
			'display_name' => __( 'Buchhaltung', 'cpsmartcrm' ),
			'department' => __( 'Accounting', 'cpsmartcrm' ),
			'icon' => 'dashicons-clipboard',
			'show_in_contact' => 0,
			'is_system_role' => 0,
			'sort_order' => 40,
		),
		array(
			'role_slug' => 'projektleiter',
			'role_name' => __( 'Projektleiter', 'cpsmartcrm' ),
			'display_name' => __( 'Projektleiter', 'cpsmartcrm' ),
			'department' => __( 'Projects', 'cpsmartcrm' ),
			'icon' => 'dashicons-admin-users',
			'show_in_contact' => 1,
			'is_system_role' => 0,
			'sort_order' => 50,
		),
	);
	
	foreach ( $standard_roles as $role_data ) {
		// Check if role already exists
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE role_slug = %s",
			$role_data['role_slug']
		) );
		
		if ( ! $exists ) {
			$capabilities = array(
				'wp_role' => '',
				'can_view_accounting' => 'buchhaltung' === $role_data['role_slug'] ? 1 : 0,
				'can_edit_accounting' => 'chef' === $role_data['role_slug'] ? 1 : 0,
				'can_view_all_accounting' => 'chef' === $role_data['role_slug'] ? 1 : 0,
				'can_view_documents' => 1,
				'can_edit_documents' => 'chef' === $role_data['role_slug'] || 'projektleiter' === $role_data['role_slug'] ? 1 : 0,
				'can_view_all_documents' => 1,
				'can_view_customers' => 1,
				'can_edit_customers' => 'vertrieb' === $role_data['role_slug'] || 'chef' === $role_data['role_slug'] ? 1 : 0,
			);
			
			$wpdb->insert(
				$table,
				array(
					'role_slug' => $role_data['role_slug'],
					'role_name' => $role_data['role_name'],
					'display_name' => $role_data['display_name'],
					'department' => $role_data['department'],
					'icon' => $role_data['icon'],
					'show_in_contact' => $role_data['show_in_contact'],
					'is_system_role' => $role_data['is_system_role'],
					'sort_order' => $role_data['sort_order'],
					'capabilities' => wp_json_encode( $capabilities ),
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
			);
		}
	}
}

// Hook to initialize on activation
add_action( 'admin_init', 'WPsCRM_init_standard_agent_roles' );
