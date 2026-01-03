<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (function_exists('WPsCRM_display_documents_list')) {
WPsCRM_display_documents_list();
} else {
echo '<p style="color:red;">Error: Pluggable documents list function not found!</p>';
}
