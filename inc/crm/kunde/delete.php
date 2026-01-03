<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'delete_customer' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {

	$table = WPsCRM_get_customer_table();
	$pk = WPsCRM_get_customer_pk($table);
	$ID=$_GET["ID"];

	//$wpdb->delete( $table, array( $pk => $ID ) );
	if ($ID !=1)
		$wpdb->update(
			$table,
			array(
				'eliminato' => '1'
			),
			array( $pk => $ID ),
			array(
				'%d'	// valore2
			),
			array( '%d' )
		);
}
?>
<script type="text/javascript">
	location.href="<?php echo admin_url('admin.php?page=smart-crm&p=kunde/list.php')?>";
</script>