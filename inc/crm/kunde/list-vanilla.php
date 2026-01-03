<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Vanilla.js Customer List
 * 
 * Moderne Kundenliste mit Vanilla.js statt Kendo UI
 */

$delete_nonce = wp_create_nonce( "delete_customer" );
$update_nonce= wp_create_nonce( "update_customer" );
$scheduler_nonce= wp_create_nonce( "update_scheduler" );
$options=get_option('CRM_general_settings');
$clients_options = get_option('CRM_clients_settings');

if(isset($options['customersGridHeight']) && $options['customersGridHeight'] !="")
	$gridHeight=$options['customersGridHeight'];
else
	$gridHeight="600";
?>

<!-- TODO/Termin/Aktivität Dialoge -->
<div id="dialog_todo" style="display:none;" data-from="list" data-fkcliente="">
	<?php include ( WPsCRM_DIR."/inc/crm/kunde/form_todo.php" ) ?>
</div>

<div id="dialog_appuntamento" style="display:none;" data-from="list" data-fkcliente="">
	<?php include (WPsCRM_DIR."/inc/crm/kunde/form_appuntamento.php" ) ?>
</div>

<div id="dialog_attivita" style="display:none;" data-from="list" data-fkcliente="">
	<?php include (WPsCRM_DIR."/inc/crm/kunde/form_attivita.php" ) ?>
</div>

<script type="text/javascript">
<?php 
include (WPsCRM_DIR."/inc/crm/kunde/script_todo.php" );
include (WPsCRM_DIR."/inc/crm/kunde/script_appuntamento.php" );
include (WPsCRM_DIR."/inc/crm/kunde/script_attivita.php" );
?>
</script>
</div>
<?php include (WPsCRM_DIR."/inc/crm/kunde/script_attivita.php" ) ?>

<div id="dialog_mail" style="display:none;" data-from="list" data-fkcliente="">
	<?php include (WPsCRM_DIR."/inc/crm/kunde/form_mail.php" ) ?>
</div>
<?php include (WPsCRM_DIR."/inc/crm/kunde/script_mail.php" ) ?>

<!-- Hauptcontainer -->
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

<!-- Legende -->
<ul class="select-action">
	<li onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=kunde/form.php')?>';return false;" class="_newCustomer bg-success" style="color:#000">
		<i class="glyphicon glyphicon-user"></i>
		<b><?php _e('Neukunde','cpsmartcrm') ?></b>
	</li>
	<span style="float:right;">
		<li class="no-link" style="margin-top:4px">
			<span class="btn btn-info _flat">
				<i class="glyphicon glyphicon-tag"></i>= <?php _e('TODO','cpsmartcrm') ?>
			</span>
			<span class="btn btn_appuntamento_1 _flat">
				<i class="glyphicon glyphicon-pushpin"></i>= <?php _e('TERMIN','cpsmartcrm') ?>
			</span>
			<span class="btn btn-primary _flat">
				<i class="glyphicon glyphicon-option-horizontal"></i>= <?php _e('AKTIVITÄT','cpsmartcrm') ?>
			</span>
		</li>
	</span>
</ul>

<div id="createPdf"></div>
<div id="createDocument"></div>

<?php do_action('WPsCRM_kunde_grid_toolbar') ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('🚀 Vanilla.js Customer Grid wird initialisiert...');
    
    // Grid-Konfiguration
    const gridOptions = {
        height: <?php echo $gridHeight ?>,
        showCategories: <?php echo (isset($clients_options['gridShowCat']) && $clients_options['gridShowCat'] == 1) ? 'true' : 'false' ?>,
        showInterests: <?php echo (isset($clients_options['gridShowInt']) && $clients_options['gridShowInt'] == 1) ? 'true' : 'false' ?>,
        showOrigin: <?php echo (isset($clients_options['gridShowOr']) && $clients_options['gridShowOr'] == 1) ? 'true' : 'false' ?>,
        deleteNonce: '<?php echo $delete_nonce ?>',
        baseUrl: 'admin.php?page=smart-crm'
    };
    
    // Customer Grid erstellen
    const customerGrid = PSCRM.createCustomerGrid('#grid', gridOptions);
    
    console.log('✅ Vanilla.js Customer Grid initialisiert');
    
    // Notification anzeigen
    setTimeout(function() {
        PSCRM.notify('Vanilla.js UI ist aktiv! 🎉', 'info', 5000);
    }, 500);
});
</script>

<style>
    .btn-danger{margin-right:6px!important}
    .k-grid tbody .k-button, .k-ie8 .k-grid tbody button.k-button{min-width:34px}
    
    /* Vanilla UI Badge */
    #tabstrip ul li:first-child::after {
        content: "Vanilla";
        background: #28a745;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        margin-left: 8px;
        font-weight: bold;
    }
</style>
