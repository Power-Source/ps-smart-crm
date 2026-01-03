<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (function_exists('WPsCRM_display_customer_form')) {
WPsCRM_display_customer_form();
} else {
echo '<p style="color:red;">Error: Pluggable customer form function not found!</p>';
}
