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
	<li class="btn bg-info btn-sm _flat newQuote" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php')?>';return false;">
		<i class="glyphicon glyphicon-send"></i>
		<b>
			<?php _e('NEUES ANGEBOT','cpsmartcrm')?>
		</b>
	</li>
	<li class="btn bg-danger btn-sm _flat btn_todo newInvoice" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php')?>';return false;">
		<i class="glyphicon glyphicon-fire"></i>
		<b>
			<?php _e('NEUE RECHNUNG','cpsmartcrm')?>
		</b>
	</li>
	<?php 
	is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
if ( in_array( 'wp-smart-crm-accountability/wp-smart-crm-accountability.php', apply_filters( 'active_plugins', $filter) ) ) {
		?>
	<!--<li class="btn bg-danger btn-sm _flat btn_todo" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_credit_note.php')?>';return false;">
		<i class="glyphicon glyphicon-new-window"></i>
		<b>
			<?php _e('NEUE GUTSCHRIFT','cpsmartcrm')?>
		</b>
	</li>-->
        <li class="btn bg-danger btn-sm _flat btn_todo" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php')?>';return false;">
		<i class="glyphicon glyphicon-new-window"></i>
		<b>
			<?php _e('NEUE FORMLOSE RECHNUNG','cpsmartcrm')?>
		</b>
	</li>
		<?php
	}
	?>
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

<div id="documentsTabstrip">
	<ul>
		<li class="k-state-active" id="tab-invoices">
			<i class="glyphicon glyphicon-fire"></i><?php _e('RECHNUNGEN','cpsmartcrm')?>
		</li>
		<li id="tab-quotes">
			<i class="glyphicon glyphicon-send"></i><?php _e('ANGEBOTE','cpsmartcrm')?>
		</li>
		<?php do_action('WPsCRM_add_tabs_to_documents_list'); ?>
	</ul>
	<div>
		<div class="documentGrid" id="grid-2"></div>
		<script>
				var gridheight=<?php echo $gridHeight ?>;
				var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
            	jQuery(document).ready(function ($) {

            	//set datasource 2
					<?php do_action('WPsCRM_documentsDatasource',"2")?>
				//grid output 2
					<?php do_action('WPsCRM_documentsGrid',$delete_nonce,"2") ?>
            	});
		</script>
	</div>
	<div>
		<div class="documentGrid" id="grid-1"></div>
		<script>

            	jQuery(document).ready(function ($) {
            	//set datasource 1
					<?php do_action('WPsCRM_documentsDatasource',"1")?>
				//grid output 1
					<?php do_action('WPsCRM_documentsGrid',$delete_nonce,"1") ?>

            	});
		</script>
	</div>
	<?php do_action('WPsCRM_add_divs_to_documents_list'); ?>
</div>

<script type="text/javascript">
	jQuery(document).ready(function ($) {
		var grid,filter,ds,gridID;
		var dateFrom_1=$("#dateFrom_1").kendoDatePicker({
			 format: $format
		}).data('kendoDatePicker');
		var dateTo_1=$("#dateTo_1").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateFrom_2=$("#dateFrom_2").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateTo_2=$("#dateTo_2").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateFrom_3 = $("#dateFrom_3").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateTo_3 = $("#dateTo_3").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');

		$(".dateRange").on("click", function (e) {
			gridID=$(this).data('grid')
			grid = $('#grid-'+ gridID ).data('kendoGrid');
			ds  = grid.dataSource;
			if(gridID ==1)
				filter = [
					{ field: "datao", operator: "gte", value: (dateFrom_1).value() },
					{ field: "datao", operator: "lte", value: (dateTo_1).value() },

				];
			else if (gridID == 2)
				filter = [
					{ field: "datao", operator: "gte", value: (dateFrom_2).value() },
					{ field: "datao", operator: "lte", value: (dateTo_2).value() },

				];

			if ($('#selectAgent_' + gridID).data('kendoDropDownList').value() != "") {
				filter.push(
					{ field: "agente", operator: "eq", value: $('#selectAgent_' + gridID).data('kendoDropDownList').value() }
					);
			if ($('#search_all_' + gridID).val() != "") {
				filter.push(
					{
					logic: 'or',
					filters: [
						{ field: 'progressivo', operator: 'eq', value: $('#search_all_' + gridID).val() },
						{ field: 'cliente', operator: 'contains', value: $('#search_all_' + gridID).val() }
					]
					})
				}
			}
			ds.filter([
				{
					logic: "and",
					filters: filter
				},
			]);
		})
		$(".btn_reset").on("click", function (e) {
			var ds = $("#grid-" + $(this).data('grid')).data("kendoGrid").dataSource;
			ds.filter([]);
			dateFrom_1.value('');
			dateTo_1.value('');
			dateFrom_2.value('');
			dateTo_2.value('');
			$("#selectAgent_1").data("kendoDropDownList").value('');
			$("#selectAgent_2").data("kendoDropDownList").value('');
			$('#search_all_1').val('');
			$('#search_all_2').val('');
		})
		var _users = new kendo.data.DataSource({

			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_CRM_users',
							'role': 'CRM_agent',
							'include_admin': true
						},
						success: function (result) {
                           if($("#selectAgent_1").length)
							   $("#selectAgent_1").data("kendoDropDownList").dataSource.data(result);
                           if($("#selectAgent_2").length)
							   $("#selectAgent_2").data("kendoDropDownList").dataSource.data(result);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		$('#selectAgent_1').select2({
			optionLabel: "<?php _e('Wähle Agent aus','cpsmartcrm')?>...",
			dataTextField: "display_name",
			dataValueField: "ID",
			dataSource: _users,
			change: function (e) {
				console.log(this.value())
				var grid = $('#grid-1').data('kendoGrid');
				var ds = grid.dataSource;

				var filter = [
					{ field: "agente", operator: "eq", value: this.value() },

				];
				if( dateFrom_1.value() !="" && dateTo_1.value() !=null ){
					filter.push(
						{ field: "datao", operator: "gte", value: dateFrom_1.value() },
						{ field: "datao", operator: "lte", value: dateTo_1.value() }
						);
				}
				if ($('#search_all_' + gridID).length) {
					if ($('#search_all_' + gridID).val() != "") {
						filter.push(
						{
							logic: 'or',
							filters: [
								{ field: 'progressivo', operator: 'eq', value: $('#search_all_' + gridID).val() },
								{ field: 'cliente', operator: 'contains', value: $('#search_all_' + gridID).val() }
							]
						})
					}
				}
				ds.filter([
					{
						logic: "and",
						filters: filter
					},
				]);
			}
		});
		$('#selectAgent_2').select2({
			optionLabel: "<?php _e('Wähle Agent aus','cpsmartcrm')?>...",
			dataTextField: "display_name",
			dataValueField: "ID",
			dataSource: _users,
			change: function (e) {
				console.log(this.value())
				var grid = $('#grid-2').data('kendoGrid');
				var ds = grid.dataSource;

				var filter = [
					{ field: "agente", operator: "eq", value: this.value() },
				];
				if (dateFrom_2.value() != "" && dateTo_2.value() != null) {
					filter.push(
						{ field: "datao", operator: "gte", value: dateFrom_2.value() },
						{ field: "datao", operator: "lte", value: dateTo_2.value() }
						);
				}
				if ($('#search_all_' + gridID).length) {
					if ($('#search_all_' + gridID).val() != "") {
						filter.push(
						{
							logic: 'or',
							filters: [
								{ field: 'progressivo', operator: 'eq', value: $('#search_all_' + gridID).val() },
								{ field: 'cliente', operator: 'contains', value: $('#search_all_' + gridID).val() }
							]
						})
					}
				}
				ds.filter([
					{
						logic: "and",
						filters: filter
					},
				]);
			}
		});
        $('.documentGrid').on('click', '.noEdit', function (e) {
          	showMouseLoader();
            noty({
                text: "<?php _e('Du hast keine Berechtigung zum Bearbeiten dieses Datensatzes','cpsmartcrm')?>",
                layout: 'topRight',
                type: 'error',
                template: '<div class="noty_message"><span class="noty_text"></span></div>',
                timeout: 1500
                });
           });

		$('.documentGrid').on('click', '.togglePaid', function (e) {
			if ($(this).hasClass('glyphicon-fire'))
				return;
			showMouseLoader();

			var $this = $(this);
			var tr = $(e.target).closest("tr"); // get the current table row (tr)
			var el=$(e.target).closest(".documentGrid")
			var rowID = tr.data('uid');
			grid = el.data("kendoGrid");
			row = el.find("tbody>tr[data-uid="+ rowID +"]");

			grid.select(row);
			var ID = $(tr).find('td:eq(0)').html();
			console.log(ID);

			$.ajax({
				url: ajaxurl,
				data: {
					action: 'WPsCRM_set_payment_status',
					value: $(this).data('nopaid'),
					security:'<?php echo $update_nonce?>',
					ID:ID
				},
				success: function (result) {
					console.log(result);
    grid.dataSource.read();
					
					hideMouseLoader();
				},
				error: function (errorThrown) {
					console.log(errorThrown);
				}
			})
		})
    <?php if (isset($_GET["tab"])) {?>
      setTimeout(function(){
        $("#documentsTabstrip").data("kendoTabStrip").select(<?php echo $_GET["tab"]?>)
      },500)
  
    <?php }?>
	});

</script>
<ul class="select-action">
	<li class="btn bg-info btn-sm _flat newQuote" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php')?>';return false;">
		<i class="glyphicon glyphicon-send"></i>
		<b>
			<?php _e('NEUES ANGEBOT','cpsmartcrm')?>
		</b>
	</li>
	<li class="btn bg-danger btn-sm _flat btn_todo newInvoice" onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php')?>';return false;">
		<i class="glyphicon glyphicon-fire"></i>
		<b>
			<?php _e('NEUE RECHNUNG','cpsmartcrm')?>
		</b>
	</li>
	<li class="btn  btn-sm _flat" style="background:#ccc;">
		<span class="crmHelp" data-help="section-documents" style="position:relative;top:-3px"></span>
	</li>
	<span style="float:right;">
		<li class="no-link" style="margin-top:4px">
			<?php _e('Registrierte Rechnungen (unterstrichen) können nicht bearbeitet oder gelöscht werden.','cpsmartcrm')?>
			<i class="glyphicon glyphicon-fire"></i>= <?php _e('Invoice','cpsmartcrm')?>
			<i class="glyphicon glyphicon-send"></i>= <?php _e('Angebot','cpsmartcrm')?>
		</li>
	</span>
</ul>

<div id="dialog_mail" style="display:none;" data-from="list" data-fkcliente="">
	<?php
	include (WPsCRM_DIR."/inc/crm/clienti/form_mail.php" )
    ?>
</div>
<?php
include (WPsCRM_DIR."/inc/crm/clienti/script_mail.php" )
?>
<style>
    .k-grid-toolbar {
        padding: 0 24px;
    }
</style>
<script>
	function filterMenu(e) {
		if (e.field == "datao") {
			var beginOperator = e.container.find("[data-role=dropdownlist]:eq(0)").data("kendoDropDownList");
			beginOperator.value("gte");
			beginOperator.trigger("change");

			var endOperator = e.container.find("[data-role=dropdownlist]:eq(2)").data("kendoDropDownList");
			endOperator.value("lte");
			endOperator.trigger("change");
			debugger;
			e.container.find(".k-dropdown").hide()
		}
	}

</script>
<style>
    .noEdit {
    color:darkgrey!important
    }
</style>