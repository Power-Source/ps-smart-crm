<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Vanilla.js Customer List
 * 
 * Moderne Kundenliste mit Vanilla.js statt Kendo UI
 */

function WPsCRM_display_customers_list() {
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

<div id="dialog_mail" style="display:none;" data-from="list" data-fkcliente="">
	<?php include (WPsCRM_DIR."/inc/crm/kunde/form_mail.php" ) ?>
</div>

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
<?php 
	do_action('WPsCRM_kunde_grid_toolbar'); 
?>

<script type="text/javascript">
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Prüfe ob PSCRM vorhanden ist
        if (typeof PSCRM === 'undefined' || typeof PSCRM.createCustomerGrid !== 'function') {
            console.error('PSCRM oder createCustomerGrid nicht geladen!');
            return;
        }
        
        try {
            // Grid-Konfiguration
            const gridOptions = {
                height: <?php echo intval($gridHeight) ?>,
                showCategories: <?php echo (isset($clients_options['gridShowCat']) && $clients_options['gridShowCat'] == 1) ? 'true' : 'false' ?>,
                showInterests: <?php echo (isset($clients_options['gridShowInt']) && $clients_options['gridShowInt'] == 1) ? 'true' : 'false' ?>,
                showOrigin: <?php echo (isset($clients_options['gridShowOr']) && $clients_options['gridShowOr'] == 1) ? 'true' : 'false' ?>,
                deleteNonce: '<?php echo esc_attr($delete_nonce) ?>',
                baseUrl: 'admin.php?page=smart-crm'
            };
            
            // Customer Grid erstellen
            const customerGrid = PSCRM.createCustomerGrid('#grid', gridOptions);
            
            // ===== Include Modal Handler Scripts =====
            <?php 
                ob_start();
                include(WPsCRM_DIR."/inc/crm/kunde/script_todo.php");
                include(WPsCRM_DIR."/inc/crm/kunde/script_appuntamento.php");
                include(WPsCRM_DIR."/inc/crm/kunde/script_attivita.php");
                include(WPsCRM_DIR."/inc/crm/kunde/script_mail.php");
                $allScripts = ob_get_clean();
                echo $allScripts;
            ?>
            
        }
    });
})(jQuery);
</script>
<?php
}
