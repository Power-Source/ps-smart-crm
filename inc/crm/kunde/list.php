<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Vanilla.js Customer List
 * 
 * Ruft die pluggable Funktion auf
 */

if (function_exists('WPsCRM_display_customers_list')) {
WPsCRM_display_customers_list();
} else {
echo '<p style="color:red;">Error: Pluggable customer list function not found!</p>';
}
