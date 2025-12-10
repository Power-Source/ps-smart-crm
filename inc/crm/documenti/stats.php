<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$stats_nonce=wp_create_nonce( "CRM_stats" );
do_action('WPsCRM_stats_grid_toolbar');
?>
<div id="statsTabstrip">
	<ul>
		<li class="k-state-active">
			<i class="glyphicon glyphicon-signal"></i> <?php _e('RECHNUNGSSTATISTIK','cpsmartcrm')?>
		</li>
        <li>
            <i class="glyphicon glyphicon-signal"></i> <?php _e('ANGEBOTE STATISTIK','cpsmartcrm')?>
        </li>
		<?php do_action('WPsCRM_add_tabs_to_stats_list'); ?>
	</ul>
	<div>
		<div class="statsGrid" id="grid-2"></div>
		<script>
				var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
            	jQuery(document).ready(function ($) {
            	//set datasource 2
				<?php do_action('WPsCRM_statsDatasource',"2")?>
				//grid output 2
				<?php do_action('WPsCRM_statsGrid',$stats_nonce,"2") ?>
            	});
		</script>
	</div>
    <div>
        <div class="statsGrid" id="grid-1"></div>
        <script>
				var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
            	jQuery(document).ready(function ($) {
            	//set datasource 1
				<?php do_action('WPsCRM_statsDatasource',"1")?>
				//grid output 1
				<?php do_action('WPsCRM_statsGrid',$stats_nonce,"1") ?>
            	});
        </script>
    </div>
	<?php do_action('WPsCRM_add_divs_to_stats_list'); ?>
</div>
<script>
	jQuery(document).ready(function ($) {
		var grid, filter, ds, gridID;
		var dateFrom_1 = $("#dateFrom_1").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateTo_1 = $("#dateTo_1").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateFrom_2 = $("#dateFrom_2").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');
		var dateTo_2 = $("#dateTo_2").kendoDatePicker({
			format: $format
		}).data('kendoDatePicker');

		$(".dateRange").on("click", function (e) {
			gridID = $(this).data('grid')
			grid = $('#grid-' + gridID).data('kendoGrid');
			ds = grid.dataSource;
			if (gridID == 1)
				filter = [
					{ field: "datao", operator: "gte", value: (dateFrom_1).value() },
					{ field: "datao", operator: "lte", value: (dateTo_1).value() },

				];
			else if (gridID == 2)
				filter = [
					{ field: "datao", operator: "gte", value: (dateFrom_2).value() },
					{ field: "datao", operator: "lte", value: (dateTo_2).value() },

				];
			if ( $('#selectAgent_' + gridID).data('kendoDropDownList').value() != "") {
				filter.push(
						{ field: "agente", operator: "eq", value: $('#selectAgent_' + gridID).data('kendoDropDownList').value() }
					);

			}
			ds.filter(filter);
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
		})
		var _users = new kendo.data.DataSource({

			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							action: 'WPsCRM_get_CRM_users',
							role: 'CRM_agent',
							include_admin: true
						},
						success: function (result) {
							//console.log(result);
							$("#selectAgent_1").data("kendoDropDownList").dataSource.data(result);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		$('#selectAgent_1').select2({
			//placeholder: "Select User...",
			optionLabel: "<?php _e('Wählen...','cpsmartcrm') ?>",
			dataTextField: "display_name",
			dataValueField: "ID",
			dataSource: _users,
			change: function (e) {
				var grid = $('#grid-1').data('kendoGrid');
				var ds = grid.dataSource;

				var filter = [
					{ field: "agente", operator: "eq", value: this.value() },

				];
				console.log(dateFrom_1.value(), dateTo_1.value());
				if (dateFrom_1.value() != "" && dateTo_1.value() != null) {
					filter.push(
						{ field: "datao", operator: "gte", value: dateFrom_1.value() },
						{ field: "datao", operator: "lte", value: dateTo_1.value() }
						);

				}
				ds.filter(filter);
			}
		});
		$('#selectAgent_2').select2({
			//placeholder: "Select User...",
			optionLabel: "<?php _e('Wählen...','cpsmartcrm') ?>",
			//valuePrimitive: true,
			dataTextField: "display_name",
			dataValueField: "ID",
			// autoBind: true,
			dataSource: _users,
			change: function (e) {
				console.log(this.value())
				var grid = $('#grid-2').data('kendoGrid');
				var ds = grid.dataSource;

				var filter = [
					{ field: "agente", operator: "eq", value: this.value() },
				];
				console.log(dateFrom_2.value(), dateTo_2.value());
				if (dateFrom_2.value() != "" && dateTo_2.value() != null) {
					filter.push(
						{ field: "datao", operator: "gte", value: dateFrom_2.value() },
						{ field: "datao", operator: "lte", value: dateTo_2.value() }
						);

				}
				ds.filter(filter);
			}
		});
	})
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
    .k-grid-toolbar {
        padding: 0 24px;
    }
</style>