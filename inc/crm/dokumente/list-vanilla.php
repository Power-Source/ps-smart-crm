<?php if ( ! defined( 'ABSPATH' ) ) exit;
	$delete_nonce= wp_create_nonce( "delete_document" );
	$update_nonce= wp_create_nonce( "update_document" );
	do_action('WPsCRM_documents_grid_toolbar');
	$options=get_option('CRM_general_settings');

	if(isset($options['documentsGridHeight']) && $options['documentsGridHeight'] !="")
		$gridHeight=$options['documentsGridHeight'];
	else
		$gridHeight="600";
?>

<ul class="select-action">
	<li class="btn bg-info btn-sm _flat newQuote" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=dokumente/form_quotation.php')?>';return false;">
		<i class="glyphicon glyphicon-send"></i>
		<b><?php _e('NEUES ANGEBOT','cpsmartcrm')?></b>
	</li>
	<li class="btn bg-danger btn-sm _flat btn_todo newInvoice" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=dokumente/form_invoice.php')?>';return false;">
		<i class="glyphicon glyphicon-fire"></i>
		<b><?php _e('NEUE RECHNUNG','cpsmartcrm')?></b>
	</li>
	<?php 
	is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
	if ( in_array( 'wp-smart-crm-accountability/wp-smart-crm-accountability.php', apply_filters( 'active_plugins', $filter) ) ) {
	?>
        <li class="btn bg-danger btn-sm _flat btn_todo" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=dokumente/form_invoice_informal.php')?>';return false;">
		<i class="glyphicon glyphicon-new-window"></i>
		<b><?php _e('NEUE FORMLOSE RECHNUNG','cpsmartcrm')?></b>
	</li>
	<?php } ?>
	<li class="btn  btn-sm _flat" style="background:#ccc;">
		<span class="crmHelp" data-help="section-documents" style="position:relative;top:-3px"></span>
	</li>
	<span style="float:right;">
		<li class="no-link" style="margin-top:4px">
			<?php _e('Registrierte Rechnungen (unterstrichen) können nicht bearbeitet oder gelöscht werden.','cpsmartcrm')?>
			<i class="glyphicon glyphicon-fire"></i>= <?php _e('Rechnung','cpsmartcrm')?>
			<i class="glyphicon glyphicon-send"></i>= <?php _e('Angebot','cpsmartcrm')?>
		</li>
	</span>
</ul>

<!-- Vanilla.js Tabs für Dokumente -->
<div class="pscrm-tabs">
	<ul class="pscrm-tab-nav">
		<li class="active" data-tab="invoices">
			<i class="glyphicon glyphicon-fire"></i>
			<?php _e('RECHNUNGEN','cpsmartcrm')?>
		</li>
		<li data-tab="quotes">
			<i class="glyphicon glyphicon-send"></i>
			<?php _e('ANGEBOTE','cpsmartcrm')?>
		</li>
		<?php do_action('WPsCRM_add_tabs_to_documents_list'); ?>
	</ul>
	
	<!-- Tab Content: Rechnungen -->
	<div id="tab-invoices" class="pscrm-tab-content active">
		<div id="grid-invoices" class="pscrm-grid-container"></div>
	</div>
	
	<!-- Tab Content: Angebote -->
	<div id="tab-quotes" class="pscrm-tab-content">
		<div id="grid-quotes" class="pscrm-grid-container"></div>
	</div>
	
	<?php do_action('WPsCRM_add_divs_to_documents_list'); ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	
	// Tab-Funktionalität
	$('.pscrm-tab-nav li').on('click', function() {
		const tab = $(this).data('tab');
		
		// Tab Navigation aktivieren
		$('.pscrm-tab-nav li').removeClass('active');
		$(this).addClass('active');
		
		// Tab Content anzeigen
		$('.pscrm-tab-content').removeClass('active');
		$('#tab-' + tab).addClass('active');
	});
	
	// Rechnungen Grid
	const invoicesGrid = PSCRM.createDocumentsGrid('grid-invoices', {
		type: 'invoice',
		height: <?php echo $gridHeight ?>,
		security: '<?php echo $delete_nonce ?>',
		dateFormat: '<?php echo WPsCRM_DATEFORMAT ?>',
		currencySymbol: '<?php echo html_entity_decode(WPsCRM_DEFAULT_CURRENCY_SYMBOL) ?>',
		texts: {
			invoice: '<?php _e('Rechnung', 'cpsmartcrm') ?>',
			quote: '<?php _e('Angebot', 'cpsmartcrm') ?>',
			edit: '<?php _e('Bearbeiten', 'cpsmartcrm') ?>',
			delete: '<?php _e('Löschen', 'cpsmartcrm') ?>',
			print: '<?php _e('Drucken', 'cpsmartcrm') ?>',
			confirmDelete: '<?php _e('Dokument wirklich löschen?', 'cpsmartcrm') ?>',
			filterByDate: '<?php _e('Nach Datum filtern', 'cpsmartcrm') ?>',
			filterByAgent: '<?php _e('Nach Agent filtern', 'cpsmartcrm') ?>',
			from: '<?php _e('Von', 'cpsmartcrm') ?>',
			to: '<?php _e('Bis', 'cpsmartcrm') ?>',
			filter: '<?php _e('Filter', 'cpsmartcrm') ?>',
			resetFilters: '<?php _e('Filter zurücksetzen', 'cpsmartcrm') ?>',
			total: '<?php _e('Total', 'cpsmartcrm') ?>'
		}
	});
	
	// Angebote Grid (Lazy Loading - erst wenn Tab aktiviert wird)
	let quotesGridLoaded = false;
	$('.pscrm-tab-nav li[data-tab="quotes"]').on('click', function() {
		if (!quotesGridLoaded) {
			const quotesGrid = PSCRM.createDocumentsGrid('grid-quotes', {
				type: 'quote',
				height: <?php echo $gridHeight ?>,
				security: '<?php echo $delete_nonce ?>',
				dateFormat: '<?php echo WPsCRM_DATEFORMAT ?>',
				currencySymbol: '<?php echo html_entity_decode(WPsCRM_DEFAULT_CURRENCY_SYMBOL) ?>',
				texts: {
					invoice: '<?php _e('Rechnung', 'cpsmartcrm') ?>',
					quote: '<?php _e('Angebot', 'cpsmartcrm') ?>',
					edit: '<?php _e('Bearbeiten', 'cpsmartcrm') ?>',
					delete: '<?php _e('Löschen', 'cpsmartcrm') ?>',
					print: '<?php _e('Drucken', 'cpsmartcrm') ?>',
					confirmDelete: '<?php _e('Dokument wirklich löschen?', 'cpsmartcrm') ?>',
					filterByDate: '<?php _e('Nach Datum filtern', 'cpsmartcrm') ?>',
					filterByAgent: '<?php _e('Nach Agent filtern', 'cpsmartcrm') ?>',
					from: '<?php _e('Von', 'cpsmartcrm') ?>',
					to: '<?php _e('Bis', 'cpsmartcrm') ?>',
					filter: '<?php _e('Filter', 'cpsmartcrm') ?>',
					resetFilters: '<?php _e('Filter zurücksetzen', 'cpsmartcrm') ?>',
					total: '<?php _e('Total', 'cpsmartcrm') ?>'
				}
			});
			quotesGridLoaded = true;
		}
	});
});
</script>

<style>
.pscrm-tabs {
	margin-top: 20px;
}

.pscrm-tab-nav {
	list-style: none;
	margin: 0;
	padding: 0;
	border-bottom: 2px solid #ddd;
	display: flex;
}

.pscrm-tab-nav li {
	padding: 12px 24px;
	cursor: pointer;
	background: #f5f5f5;
	border: 1px solid #ddd;
	border-bottom: none;
	margin-right: 5px;
	transition: background 0.3s;
}

.pscrm-tab-nav li:hover {
	background: #e9e9e9;
}

.pscrm-tab-nav li.active {
	background: #fff;
	border-bottom: 2px solid #fff;
	margin-bottom: -2px;
	font-weight: bold;
}

.pscrm-tab-content {
	display: none;
	padding: 20px 0;
}

.pscrm-tab-content.active {
	display: block;
}

.pscrm-filter-toolbar {
	background: #f9f9f9;
	padding: 15px;
	margin-bottom: 15px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.pscrm-filter-toolbar .filter-row {
	display: flex;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
}

.pscrm-filter-toolbar label {
	margin: 0;
	font-weight: 600;
}

.pscrm-filter-toolbar input,
.pscrm-filter-toolbar select {
	padding: 6px 12px;
	border: 1px solid #ddd;
	border-radius: 3px;
}
</style>
