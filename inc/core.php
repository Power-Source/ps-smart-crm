<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function WPsCRM_smartcrm(){
    global $wpdb;

include(plugin_dir_path( __FILE__ ))."crm/main.php";
}

