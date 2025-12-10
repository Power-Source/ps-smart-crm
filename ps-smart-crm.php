<?php
/*
Plugin Name: PS Smart CRM
Plugin URI: https://cp-psource.github.io/ps-smart-crm/wordpress-crm-invoices-plugin
Description: Fügt WordPress ein leistungsstarkes CRM hinzu. Verwalten Sie Kunden, Rechnungen, TODO, Termine und zukünftige Benachrichtigungen an Agenten, Benutzer und Kunden
Version: 1.0.0
Author:       Karner Oliver
Author URI:   https://nerdservice.eimen.net
Text Domain: cpsmartcrm
Domain Path: /languages
 **************************************************************************
Copyright (C) 2023-2025 PSOURCE


This program is free software: you can redistribute it and/or modify

it under the terms of the GNU General Public License as published by

the Free Software Foundation, either version 3 of the License, or

(at your option) any later version.

This program is distributed in the hope that it will be useful,

but WITHOUT ANY WARRANTY; without even the implied warranty of

MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

GNU General Public License for more details.

You should have received a copy of the GNU General Public License

along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************/
// PS Update Manager - Hinweis wenn nicht installiert
add_action( 'admin_notices', function() {
    // Prüfe ob Update Manager aktiv ist
    if ( ! function_exists( 'ps_register_product' ) && current_user_can( 'install_plugins' ) ) {
        $screen = get_current_screen();
        if ( $screen && in_array( $screen->id, array( 'plugins', 'plugins-network' ) ) ) {
            // Prüfe ob bereits installiert aber inaktiv
            $plugin_file = 'ps-update-manager/ps-update-manager.php';
            $all_plugins = get_plugins();
            $is_installed = isset( $all_plugins[ $plugin_file ] );
            
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>PS Chat:</strong> ';
            
            if ( $is_installed ) {
                // Installiert aber inaktiv - Aktivierungs-Link
                $activate_url = wp_nonce_url(
                    admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
                    'activate-plugin_' . $plugin_file
                );
                echo sprintf(
                    __( 'Aktiviere den <a href="%s">PS Update Manager</a> für automatische Updates von GitHub.', 'psource-chat' ),
                    esc_url( $activate_url )
                );
            } else {
                // Nicht installiert - Download-Link
                echo sprintf(
                    __( 'Installiere den <a href="%s" target="_blank">PS Update Manager</a> für automatische Updates aller PSource Plugins & Themes.', 'psource-chat' ),
                    'https://github.com/Power-Source/ps-update-manager/releases/latest'
                );
            }
            
            echo '</p></div>';
        }
    }
});
/**
 * @@@@@@@@@@@@@@@@@ LOCALIZATION @@@@@@@@@@@
 *
 **/
function WPsmartcrm_l10n(){
    load_plugin_textdomain( 'cpsmartcrm', false, dirname(plugin_basename( __FILE__ )).'/languages/' );
}
add_action('plugins_loaded','WPsmartcrm_l10n');
/**
 * @@@@@@@@@@@@@@@@@ MAIN SETUP @@@@@@@@@@@
 *
 **/
global $wpdb;
define('WPsCRM_TABLE',$wpdb->prefix .'smartcrm_');
define('WPsCRM_PATH',__FILE__);
define('WPsCRM_DIR',dirname(__FILE__ ) );
define('WPsCRM_URL',plugin_dir_url( __FILE__ ) );
$upload_dir = wp_upload_dir();
define('WPsCRM_UPLOADS', $upload_dir['basedir'] . '/CRMdocuments');
define('WPsCRM_IMPORT_FILE',WPsCRM_DIR.'/logs/import.txt');

require_once(__DIR__ . '/inc/setup.php');
require_once(__DIR__ . '/inc/core.php');
require_once(__DIR__ . '/inc/functions.php');
require_once(__DIR__ . '/inc/classes/CRMcustomer.class.php');
require_once(__DIR__ . '/inc/classes/CRMdocument.class.php');
require_once(__DIR__ . '/inc/classes/CRMmail.class.php');
require_once(__DIR__ . '/inc/options.php');



register_activation_hook( __FILE__, 'WPsCRM_crm_install' );
register_activation_hook(__FILE__,'WPsCRM_create_doc_folder');

function WPsCRM_create_doc_folder(){
	$save_to_path = WPsCRM_UPLOADS;
  if (!file_exists($save_to_path)){
    wp_mkdir_p($save_to_path);
		if(!file_exists($save_to_path.DIRECTORY_SEPARATOR.'.htaccess')){
			$content = 'Options -Indexes' . "\n";
			file_put_contents($save_to_path.DIRECTORY_SEPARATOR.'.htaccess', $content);
		}
	}
	if(!file_exists($save_to_path.DIRECTORY_SEPARATOR.'.htaccess')){
		$content = 'Options -Indexes' . "\n";
		file_put_contents($save_to_path.DIRECTORY_SEPARATOR.'.htaccess', $content);
	}
}

/**
 * @@@@@@@@@@@@@@@@@ LOAD SCRIPTS @@@@@@@@@@@
 *
 **/
function WPsCRM_add_smartcrm_scripts(){
    $options = get_option( 'CRM_general_settings' );
    if(isset($options['grid_style']) && $options['grid_style'] =="")
        $style='light';
    else{
		if ( isset( $options['grid_style'] ) )
			$style=$options['grid_style'];
		else
			$style="light";
    }
    wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css', array(), '1.13.8' );
    wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', array('jquery'), '1.13.8', true );
wp_enqueue_style( 'datatables-bootstrap-css', 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', array('datatables-css'), '1.13.8' );
wp_enqueue_script( 'datatables-bootstrap-js', 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', array('datatables-js', 'bootstrap'), '1.13.8', true );
    wp_enqueue_style( 'k-commoncss',plugin_dir_url( __FILE__ ).'css/kendo.common.min.css');
    wp_enqueue_style( 'k-common1',plugin_dir_url( __FILE__ ).'css/kendo.custom.min.css',array(),'4.2.2');
    wp_enqueue_style( 'bootstrap',plugin_dir_url( __FILE__ ).'inc/bootstrap/css/bootstrap-'.$style.'.min.css');
    wp_enqueue_style( 'extend',plugin_dir_url( __FILE__ ).'css/extend-'.$style.'.css');
    wp_enqueue_style( 'smartcrm',plugin_dir_url( __FILE__ ).'css/smartcrm.css');
    wp_enqueue_script( 'kendoc', plugin_dir_url( __FILE__ ).'js/kendo.custom.min.js',array('jquery'),"2.2",false );
    wp_enqueue_script( 'mainjs', plugin_dir_url( __FILE__ ).'js/adminScript.min.js',array('jquery'),"1.1",true );
    wp_enqueue_script( 'signature',  plugin_dir_url( __FILE__ ).'js/signature.js',array('jquery'),"1.3",false );
    wp_enqueue_script( 'culture',  plugin_dir_url( __FILE__ ).'js/cultures/kendo.culture.'.WPsCRM_CULTURE.'.min.js',array(), "1.4",false );
    wp_enqueue_script( 'noty',  plugin_dir_url( __FILE__ ).'js/noty-2.3.8/js/noty/packaged/jquery.noty.packaged.min.js', array('jquery'),"1.4",false );
    wp_enqueue_script( 'pako',  plugin_dir_url( __FILE__ ).'js/pako/pako.min.js', array('jquery'),"1.4",false );
    wp_enqueue_script('underscore',plugin_dir_url( __FILE__ ).'js/underscore.js',array('jquery'),false);
    wp_enqueue_media();
    wp_enqueue_script( 'autonumeric', 'https://cdn.jsdelivr.net/npm/autonumeric@4.10.5/dist/autoNumeric.min.js', array('jquery'), null, true );
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));
    wp_enqueue_script('parsley', 'https://cdn.jsdelivr.net/npm/parsleyjs@2.9.2/dist/parsley.min.js', array('jquery'));
    wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    wp_enqueue_script('jquery-ui-tooltip');
}
if ( isset( $_GET['page'] ) &&  ( $_GET['page'] == 'smart-crm'  || $_GET['page'] == 'smartcrm_custom-fields' || $_GET['page'] == 'smartcrm_subscription-rules' || $_GET['page'] == 'smartcrm_settings') )
	add_action('admin_enqueue_scripts','WPsCRM_add_smartcrm_scripts',99);

function WPsCRM_define_cultures(){

    $local=get_locale() ;

    $european = array ( 'it_IT', 'fr_FR', 'es_ES', 'de_DE');
    $culture = str_replace( "_" , "-", $local );
    if(! defined('WPsCRM_CULTURE') ){
        define("WPsCRM_CULTURE", $culture );

        if (in_array( $local , $european )) {
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','EUR');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','&euro;');
        } elseif($local=="en_GB"){
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','GBP');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','&pound;');
        }
        elseif($local=="de_CH" || $local=="de_CH_informal"){
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','CHF');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','Fr.');
        }
        elseif($local=="el" || $local=='el_GR' ){
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','EUR');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','&euro;');
        }elseif($local=="pt_BR" ){
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','BRL');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','R$');
        }elseif($local=="as_IN" || $local=="in" || $local=="hi_IN"){
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','INR');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','&\\\#8377;');
        }elseif($local=="zh_HANS" || $local=="zh" || $local=="zh_HANT" || $local=="zh_CN"){
            define('WPsCRM_DATEFORMAT','yyyy-MM-dd');
            define('WPsCRM_DATETIMEFORMAT','yyyy-MM-dd HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','CNY');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','¥');
        }elseif($local=="ya" || $local=="ya_JP"  ){
            define('WPsCRM_DATEFORMAT','dd-MM-yyyy');
            define('WPsCRM_DATETIMEFORMAT','dd-MM-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','JPY ');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','¥');
        }

        else {
            //if no available cultures load en-US format
            define('WPsCRM_DATEFORMAT','MM-dd-yyyy');
            define('WPsCRM_DATETIMEFORMAT','MM-dd-yyyy HH:mm');
            define('WPsCRM_DEFAULT_CURRENCY','USD');
            define('WPsCRM_DEFAULT_CURRENCY_SYMBOL','$');
        }
        define ('WPsCRM_TIMEFORMAT','HH:mm');
    }

}
add_action('admin_init','WPsCRM_define_cultures');
add_action('plugins_loaded','WPsCRM_define_cultures');
register_activation_hook( __FILE__, 'WPsCRM_define_cultures' );
/**
 *
 * Register roles and set capabilities for CRM user and customers
 *
 **/
function WPsCRM_add_CRM_role(){
	$author = get_role( 'author' );
	$capabilities = $author->capabilities;
    $CRM_client = add_role(
    'CRM_client',
    __( 'CRM Client' ),
    $capabilities
    );
    $CRM_agent=add_role(
    'CRM_agent',
    __( 'CRM Agent' ),
    $capabilities
    );

	$admin=get_role('administrator');
	$admin->add_cap('manage_crm');

	$role = get_role( 'CRM_client' );
	$role->add_cap( 'read' );
	$role->add_cap( 'read_private_pages' );

	$role1 = get_role( 'CRM_agent' );
	$role1->add_cap( 'read' );
	$role1->add_cap( 'read_private_pages' );
	$role1->add_cap('manage_crm');

}
register_activation_hook( __FILE__, 'WPsCRM_add_CRM_role' );

/**
 *
 * Set default values for some options
 *
 **/
function WPsCRM_set_defaults(){
	$defaults_documents = array(
			  'default_vat'   => 19,
			  'crm_currency'=>WPsCRM_DEFAULT_CURRENCY
			  );
	$defaults_general=array(
		'administrator_all'=>1,
		'deletion_privileges',0
		);
	add_option('CRM_documents_settings',$defaults_documents);
	add_option('CRM_general_settings',$defaults_general);
	if( get_option('CRM_ColumnsWidth') ==null)
		add_option('CRM_ColumnsWidth',array());
}
register_activation_hook( __FILE__, 'WPsCRM_set_defaults' );

/**
 *
 * Hide Admin bar for CRM clients
 *
 **/
function WPsCRM_hide_admin_bar(){
    $current_user = wp_get_current_user();
	if ( current_user_can('manage_crm') && isset( $_GET['layout'] ) &&  $_GET['layout'] =='modal' ){
?>
<style>
    #wpadminbar, .term-slug-wrap, .term-parent-wrap, .term-description-wrap {
        display: none !important;
    }

    .tablenav.bottom {
        visibility: hidden;
    }

    #wpbody {
        padding-top: 0 !important;
    }

    .update-nag, .notice-error, .notice-success, .notice-warning, .notice-info, .updated, .settings-error {
        display: none !important
    }
</style>
<?php
	}
    return false;
}
add_action('admin_footer','WPsCRM_hide_admin_bar');
/**
 *
 * Localization of Kendo controls
 *
 **/
function WPsCRM_add_culture(){
?>
<script>
        if (pagenow.search('smart-crm') != -1 || pagenow.search('smartcrm') != -1){
        	kendo.culture("<?php echo WPsCRM_CULTURE?>");
			var localCulture="<?php echo WPsCRM_CULTURE?>";
			var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
			var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
		}
</script>
<?php
}
add_action( 'admin_head', 'WPsCRM_add_culture' );

/**
 *
 * Optionally redirect to CRM dashboard on login
 *
 **/
function WPsCRM_redirect_to_CRM($redirectTo, $request, $user) {
    if(!is_admin())
        return $redirectTo;
    $options=get_option('CRM_general_settings');

    if($options['smartcrm_redirect-'.$user->ID] ==1 && ! defined('DOING_AJAX')){

        return(admin_url( ).'admin.php?page=smart-crm' );
    }
    return $redirectTo;
}
add_filter('login_redirect', 'WPsCRM_redirect_to_CRM',10,3);

/**
 *
 * REDIRECT TO REQUIRED SETTINGS PAGE ON ACTIVATION
 *
 **/
function WPsCRM_notify_CRM_SETTINGS(){
?>
<div class="notice notice-error">
    <p>
        <?php _e( 'Achtung: Es sind einige Grundeinstellungen erforderlich','cpsmartcrm');?>
        <a href="<?php echo admin_url("admin.php?page=smartcrm_settings&tab=CRM_business_settings" )?>">
            <?php _e('auf dieser Seite','cpsmartcrm');?>
        </a>
        <?php _e('um PS Smart CRM zu verwenden ','cpsmartcrm')?>!
    </p>
</div>
<?php
}
function WPsCRM_redirect_to_CRM_SETTINGS() {

	$options=get_option('CRM_business_settings');
	if(!isset($options['CRM_required_settings']) || $options['CRM_required_settings'] !=1 && ! defined('DOING_AJAX') ){
		add_filter('admin_notices', 'WPsCRM_notify_CRM_SETTINGS');
		if( isset($_GET['page']) ){
			if(strstr($_GET['page'],"smart-crm") || strstr($_GET['page'],"smartcrm") && ($_GET['tab'] !="CRM_business_settings") )
			{
				wp_redirect(admin_url( ).'admin.php?page=smartcrm_settings&tab=CRM_business_settings&noty=settings_required') ;
			}
		}
	}
	return;
}
add_filter('admin_init','WPsCRM_redirect_to_CRM_SETTINGS',1);

/**
 *
 * Optionally minimize WP main menu to use CRM fullpage
 *
 **/
function WPsCRM_fullpage(){
    $user   = wp_get_current_user();
    $options=get_option('CRM_general_settings');
    if(isset($options['minimize_WP_menu-'.$user->ID]) && $options['minimize_WP_menu-'.$user->ID] ==1 ){
?>
<script>
    jQuery(document).ready(function ($) {
        if (!$('body').hasClass('folded') && pagenow.search('smart-crm') != -1)
            setTimeout(function () {
                $('body').addClass('folded');
                //$('#collapse-button').trigger('click');
            }, 10)
        }
    )
</script>
<?php
    }
}
add_action( 'admin_head', 'WPsCRM_fullpage',11 );

add_action('admin_menu', 'WPsCRM_documentation_link',99);

function WPsCRM_documentation_link(){
	if(class_exists('sitepress')){
		if(ICL_LANGUAGE_CODE =="de")
			$link='https://cp-psource.github.io/ps-smart-crm/dokumentation.html';
		else
			$link='https://cp-psource.github.io/ps-smart-crm/dokumentation.html';
		}
		else{
			$link='https://cp-psource.github.io/ps-smart-crm/dokumentation.html';
		}
	add_submenu_page(
	'smart-crm',
	__('Dokumentation', 'cpsmartcrm'),
	__('Dokumentation', 'cpsmartcrm'),
	'manage_crm',
	$link,
	''
	);
}
