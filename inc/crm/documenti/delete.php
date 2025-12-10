<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'delete_document' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {
	$d_table=WPsCRM_TABLE."documenti";
	$dd_table=WPsCRM_TABLE."documenti_dettaglio";
	$ID=$_GET["ID"];
  
	$wpdb->delete( $d_table, array( 'id' => $ID ) );
	$wpdb->delete( $dd_table, array( 'fk_documenti' => $ID ) );
  if (isset($_GET["fromGrid"]) && $_GET["fromGrid"]==1){
    $tab=1;
  }
  elseif(isset($_GET["fromGrid"]) && $_GET["fromGrid"]==2){
    $tab=0;
  }
}
?>
<script type="text/javascript">
	location.href="<?php echo admin_url('admin.php?page=smart-crm&p=documenti/list.php&tab='.$tab)?>";
</script>