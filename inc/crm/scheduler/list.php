<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (function_exists('WPsCRM_display_scheduler_list')) {
WPsCRM_display_scheduler_list();
} else {
echo '<p style="color:red;">Error: Pluggable scheduler list function not found!</p>';
}
