<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'delete_customer' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {

	$table=WPsCRM_TABLE."clienti";
	$ID=$_GET["ID"];

	//$wpdb->delete( $table, array( 'ID_clienti' => $ID ) );
	if ($ID !=1)
		$wpdb->update(
			$table,
			array(
				'eliminato' => '1'
			),
			array( 'ID_clienti' => $ID ),
			array(
				'%d'	// valore2
			),
			array( '%d' )
		);
}
?>
<script type="text/javascript">
	location.href="<?php echo admin_url('admin.php?page=smart-crm&p=clienti/list.php')?>";
</script>