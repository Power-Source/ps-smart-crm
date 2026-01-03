<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	$update_nonce= wp_create_nonce( "update_document" );
?>
<script type="text/javascript">
jQuery(document).ready(function ($) {

	var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
