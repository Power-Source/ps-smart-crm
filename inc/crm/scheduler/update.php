<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'update_activity' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {
	$fatto=$_POST["fatto"]==1?"Si":"No";
	$esito=addslashes($_POST["esito"]);
	$ID=$_GET["ID"];
	$ref=$_GET['ref'];
	$ref=="dashboard" ? $location="" : $location="&p=scheduler/list.php";
	$a_table=WPsCRM_TABLE."agenda";

	$result=$wpdb->query( 
			$wpdb->prepare(
				"UPDATE $a_table SET fatto=%s, esito=%s WHERE id_agenda=%d",
				$fatto, $esito ,$ID
				) 
			);
}
?> 
<script type="text/javascript">
//	location.href="?page=smart-crm&p=scheduler/form.php&ID=<?php echo $ID_ret?>";
	location.href="<?php echo admin_url( 'admin.php?page=smart-crm'.$location)?>";
</script>
