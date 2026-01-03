<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Prüfe ob Vanilla UI aktiviert ist
$options = get_option('CRM_general_settings');
$use_vanilla = isset($options['enable_vanilla_ui']) && $options['enable_vanilla_ui'] == 1;

// Lade entsprechende Version
if ($use_vanilla) {
    include ( WPsCRM_DIR."/inc/crm/kunde/list-vanilla.php" );
    return;
}

// Standard Kendo UI Version
$delete_nonce = wp_create_nonce( "delete_customer" );
$update_nonce= wp_create_nonce( "update_customer" );
$scheduler_nonce= wp_create_nonce( "update_scheduler" );

if(isset($options['customersGridHeight']) && $options['customersGridHeight'] !="")
	$gridHeight=$options['customersGridHeight'];
else
	$gridHeight="600";

?>
<script>
	var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
	var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
<?php do_action('WPsCRM_customer_datasource')?>
<?php do_action('WPsCRM_databound_customerGrid') ?>
</script>
<div id="dialog_todo" style="display:none;" data-from="list" data-fkcliente="">
	<?php
	include ( WPsCRM_DIR."/inc/crm/kunde/form_todo.php" )
    ?>
</div>
<?php
include (WPsCRM_DIR."/inc/crm/kunde/script_todo.php" )
?>
<div id="dialog_appuntamento" style="display:none;" data-from="list" data-fkcliente="">
	<?php
	include (WPsCRM_DIR."/inc/crm/kunde/form_appuntamento.php" )
    ?>
</div>
<?php
include (WPsCRM_DIR."/inc/crm/kunde/script_appuntamento.php" )
?>
<div id="dialog_attivita" style="display:none;" data-from="list" data-fkcliente="">
	<?php
	include (WPsCRM_DIR."/inc/crm/kunde/form_attivita.php" )
    ?>
</div>
<?php
include (WPsCRM_DIR."/inc/crm/kunde/script_attivita.php" )
?>
<div id="dialog_mail" style="display:none;" data-from="list" data-fkcliente="">
	<?php
	include (WPsCRM_DIR."/inc/crm/kunde/form_mail.php" )
    ?>
</div>
<?php
include (WPsCRM_DIR."/inc/crm/kunde/script_mail.php" )
?>
<script type="text/javascript" id="mainGrid">
	var gridheight=<?php echo $gridHeight ?>;
        jQuery(document).ready(function ($) {
		//grid output
			<?php do_action('WPsCRM_customerGrid',$delete_nonce) ?>
        });
</script>
<div id="tabstrip">
	<ul>
		<li class="k-state-active">
			<i class="glyphicon glyphicon-user"></i><?php _e('KUNDEN','cpsmartcrm')?>
		</li>
		<?php do_action('WPsCRM_add_tabs_to_customers_list'); ?>
	</ul>
	<div>
		<div class="customerGrid" id="grid"></div>

	</div>
	<?php do_action('WPsCRM_add_divs_to_customers_list'); ?>
</div>

<?php do_action('WPsCRM_customersLegend');?>

<!--<div id="grid" class="datagrid"></div>-->
<div id="createPdf"></div>
<div id="createDocument"></div>
<?php do_action('WPsCRM_kunde_grid_toolbar') ?>
<style>
    /*.btn-danger{margin-right:6px!important}*/
    .k-grid tbody .k-button, .k-ie8 .k-grid tbody button.k-button{min-width:34px}
</style>
