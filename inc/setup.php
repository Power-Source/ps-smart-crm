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

	$sql[] = "CREATE TABLE `".WPsCRM_SETUP_TABLE."clienti` (
  `ID_clienti` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) NOT NULL DEFAULT '0',
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `ragione_sociale` varchar(250) NOT NULL DEFAULT '',
  `indirizzo` varchar(200) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  `localita` varchar(55) NOT NULL DEFAULT '',
  `provincia` varchar(100) DEFAULT NULL,
  `nazione` varchar(100) DEFAULT NULL,
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
  `data_inserimento` date DEFAULT NULL,
  `data_modifica` date DEFAULT NULL,
  `eliminato` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `aggiornato` enum('No','Si') NOT NULL DEFAULT 'No',
  `provenienza` varchar(100) NOT NULL DEFAULT '',
  `luogo_nascita` varchar(200) NOT NULL,
  `data_nascita` date DEFAULT NULL,
  `stripe_ID` varchar(32) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `tipo_cliente` tinyint(3) unsigned NOT NULL,
  `agente` int(10) unsigned NULL,
  `interessi` varchar(100) NOT NULL DEFAULT '',
  `fatturabile` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `custom_fields` text,
  `custom_tax` text,
  `uploads` text,
  PRIMARY KEY (`ID_clienti`),
  KEY `FK_categorie_clienti` (`categoria`),
  KEY `FK_aziende` (`FK_aziende`)
) ENGINE=MyISAM  ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."agenda` (
 `id_agenda` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fk_aziende` int(10) unsigned NOT NULL DEFAULT '0',
  `fk_utenti_ins` int(10) unsigned NOT NULL DEFAULT '0',
  `oggetto` varchar(255) DEFAULT NULL,
  `fk_utenti_des` int(10) unsigned NOT NULL DEFAULT '0',
  `fk_clienti` int(10) unsigned DEFAULT NULL,
  `fk_contatti` int(10) unsigned NOT NULL DEFAULT '0',
  `data_agenda` date DEFAULT NULL,
  `ora_agenda` time DEFAULT NULL,
  `annotazioni` text NOT NULL,
  `data_inserimento` datetime NOT NULL,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `esito` text,
  `priorita` tinyint(3) unsigned NOT NULL,
  `importante` enum('No','Si') NOT NULL DEFAULT 'No',
  `urgente` enum('No','Si') NOT NULL DEFAULT 'No',
  `fatto` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1=da fare, 2= fatto, 3=cancellato',
  `tipo_agenda` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1=todo, 2= appuntamento, 3=notifica scadenza pagamento fattura, 4=acquisto,5 notifica scadenza servizio',
  `fk_documenti` int(10) unsigned NOT NULL,
  `fk_documenti_dettaglio` int(10) unsigned NOT NULL,
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
  `fk_clienti` int(10) unsigned NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `qualifica` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ".$charset_collate." AUTO_INCREMENT=1";

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."documenti` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` tinyint(3) unsigned NOT NULL COMMENT '1=preventivo, 2=fattura, 3=proforma',
  `data` date NOT NULL,
  `data_inserimento` date NOT NULL,
  `data_timestamp` int(15) NOT NULL,
  `data_scadenza_timestamp` int(15) NOT NULL,
  `oggetto` varchar(100) NOT NULL,
  `riferimento` varchar(100) NOT NULL,
  `fk_clienti` int(10) unsigned NOT NULL,
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

	$sql[]="CREATE TABLE `".WPsCRM_SETUP_TABLE."documenti_dettaglio` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fk_documenti` int(10) unsigned NOT NULL,
  `fk_articoli` int(10) unsigned NOT NULL,
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
  `fk_documenti` int(10) unsigned NOT NULL,
  `fk_documenti_dettaglio` int(10) unsigned NOT NULL,
  `e_unsent` VARCHAR( 255 ) NOT NULL,
  `fk_clienti` int(10) unsigned NOT NULL,
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

    //print_r ($sql);//exit;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$nomefile="error_setup_".date("YmdHi").".txt";
	$myFile = WPsCRM_DIR."/logs/".$nomefile;
	$msg="";
	foreach($sql as $q)
    {
		dbDelta( $q );
    };

	if ( $msg =="")
		update_option( 'WPsCRM_db_version', $WPsCRM_db_version );

}

function WPsCRM_upgrade_taxonomies()
{
	global $wpdb;
    $table=WPsCRM_TABLE."clienti";
	$sql="select ID_clienti, categoria, provenienza from $table";
    foreach( $wpdb->get_results( $sql ) as $record)
	{
		$cat=$record->categoria;
		$pro=$record->provenienza;
		$id_cli=$record->ID_clienti;
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
					'ID_clienti'=>$id_cli
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
					'ID_clienti'=>$id_cli
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
}
add_action( 'plugins_loaded', 'WPsCRM_update_db_check',13 );


add_action( 'plugins_loaded', 'WPsCRM_create_clienti',11 );
function WPsCRM_create_clienti() {

    register_post_type( 'clienti',
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
	register_taxonomy( 'WPsCRM_customersInt', array('clienti'), $args );

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
	register_taxonomy( 'WPsCRM_customersCat', array('clienti'), $args );

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
	register_taxonomy( 'WPsCRM_customersProv', array('clienti'), $args );
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

    add_menu_page( 'CP SMART CRM', 'PS Smart CRM', 'manage_crm', 'smart-crm', 'WPsCRM_smartcrm', 'dashicons-analytics', 71 );
    add_submenu_page('SMART CRM', 'PS Smart CRM', 'manage_crm', 'smart-crm', 'WPsCRM_smartcrm', 'dashicons-analytics', 71);
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
                'admin.php?page=smart-crm&p=clienti/list.php',
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
                'admin.php?page=smart-crm&p=documenti/list.php',
                ''
                );
    }
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
