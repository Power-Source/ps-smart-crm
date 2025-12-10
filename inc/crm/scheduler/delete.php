<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'delete_activity' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {
	$table=WPsCRM_TABLE."agenda";
	$ID=$_GET["ID"];
	$ref=$_GET['ref'];
	$ref=="dashboard" ? $location="" : $location="&p=scheduler/list.php";
	$wpdb->update(
		$table,
		array(
			'eliminato' => '1'
		),
		array( 'id_agenda' => $ID ),
		array(
			'%d'	// valore2
		),
		array( '%d' )
	);
}
?>
<script type="text/javascript">
	location.href="<?php echo admin_url('admin.php?page=smart-crm'.$location)?>";
</script>